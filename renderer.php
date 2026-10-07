<?php
// This file is part of Moodle - https://questionpy.org
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\default_survey_creation_method\default_survey_creation_method;
use block_coursefeedback\local\manager\semester_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use block_coursefeedback\local\survey;
use block_coursefeedback\local\urls;
use block_coursefeedback\output\semester_dropdown;
use core\di;
use core\output\notification;
use core\output\plugin_renderer_base;

/**
 * Plugin renderer for block_coursefeedback.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursefeedback_renderer extends plugin_renderer_base {

    /** @var array $alpinejsdependencies */
    private static array $alpinejsdependencies = [];

    /** @var bool $shutdownhookadded */
    private static bool $shutdownhookadded = false;

    /**
     * Add JavaScript code to initialize Alpine.js, if the `register_alpine_js_module` has been called.
     */
    private static function init_alpine_js(): void {
        if ((defined('AJAX_SCRIPT') && AJAX_SCRIPT) || (defined('CLI_SCRIPT') && CLI_SCRIPT) || !self::$alpinejsdependencies) {
            return;
        }

        $deps_json = json_encode(
            ['block_coursefeedback/alpinejs-lazy', ...self::$alpinejsdependencies],
            JSON_THROW_ON_ERROR
        );

        echo html_writer::script("
            require($deps_json, function(Alpine) {
                window.Alpine = Alpine;
                Alpine.start();
                console.debug('Alpine.js initialized');
            })
        ");
    }

    /**
     * Register a JavaScript module to be loaded before Alpine.js is initialized.
     *
     * @param string $module
     * @return void
     */
    private function register_alpine_js_module(string $module): void {
        $this->page->requires->js_call_amd($module);
        self::$alpinejsdependencies[] = $module;

        if ((!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) && (!defined('CLI_SCRIPT') || !CLI_SCRIPT) && !self::$shutdownhookadded) {
            core_shutdown_manager::register_function(self::init_alpine_js(...));
            self::$shutdownhookadded = true;
        }
    }

    #[\Override]
    protected function get_mustache() {
        $mustache = parent::get_mustache();
        $mustache->addHelper('register_alpine_js_module', fn($content) => $this->register_alpine_js_module(trim($content)) || "");
        return $mustache;
    }

    /**
     * Render the given survey to a string. If `$append_to_selector` is set, the survey will be moved there by JS.
     *
     * @param survey $survey
     * @param string|null $append_to_selector
     * @return string
     */
    public function render_survey(survey $survey, ?string $append_to_selector = null): string {
        // For all SPEs with only one slot, initialize the selected slot with it.
        $default_slots = [];
        foreach ($survey->slots_by_spe_id as $spe_id => $slots) {
            if (count($slots) === 1) {
                $default_slots[$spe_id] = $slots[array_key_first($slots)]->get('id');
            }
        }

        $json_data = [
            "pages" => $survey->pages,
            "default_slots" => $default_slots,
            "courseid" => $survey->survey_execution->get('courseid'),
        ];

        $context = [
            'first_page' => $survey->pages[0] ?? null,
            'amount_pages' => count($survey->pages),
            'json_data' => json_encode($json_data),
        ];
        $context['append_to_selector'] = $append_to_selector;

        return $this->render_from_template('block_coursefeedback/survey/root', $context);
    }

    /**
     * Renders the prologue and epilogue of an organization page, including the navigation.
     *
     * @param organization $organization
     * @param evaluation_semester|null $semester
     * @param organization_semester|null $orgsem
     * @param string|null $current_tab
     * @param string|callable $content
     * @return void
     */
    public function render_organization_page(
        organization $organization,
        ?evaluation_semester $semester,
        ?organization_semester $orgsem,
        ?string $current_tab,
        string|callable $content
    ): void {
        $semester_manager = di::get(semester_manager::class);

        if (!$semester && !$orgsem) {
            [$_, $semester, $orgsem] = $semester_manager->get_triplet_by_current_semester($organization->get('id'));
        }

        if ($orgsem && (!$orgsem->get('evaluation_starttime') || !$orgsem->get('evaluation_endtime'))) {
            echo $this->render(new notification(
                get_string('no_default_survey_period_set', 'block_coursefeedback'),
                notification::NOTIFY_WARNING
            ));
        }

        $organizationid = $organization->get('id');

        $valid_tabs = ['settings', 'questionnaires', 'eventtypes', 'courses', 'evaluations', 'semester_settings'];
        if (!in_array($current_tab, [null, ...$valid_tabs])) {
            throw new coding_exception("Invalid tab: $current_tab");
        }

        echo html_writer::start_tag('div', ['class' => 'row']);
        echo html_writer::start_tag('div', ['class' => 'col-lg-4 mb-2']);

        $semester_dropdown = new semester_dropdown(
            $semester_manager->get_all_semesters($organizationid),
            fn($link_semester, $link_orgsem) => $link_orgsem
                ? urls::semester_settings($organizationid, $link_orgsem->get('semesterid'))
                : urls::semester_settings($organizationid, $link_semester->id),
            selected_semester_id: $semester?->id,
            selected_orgsem_id: $orgsem?->get('id'),
        );

        $nav_context = [
            'semester_dropdown_context' => $semester_dropdown->export_for_template($this),
            'organization_settings_url' => urls::organization_settings($organizationid),
        ];

        if ($orgsem) {
            $nav_context = array_merge($nav_context, [
                'semester_settings_url' => urls::semester_settings($organizationid, $semester?->id ?? $orgsem->get('semesterid')),
                'eventtypes_url' => urls::event_types($organizationid, $orgsem->get('id')),
                'courses_url' => urls::courses_without_evaluations($organizationid, $orgsem->get('id')),
                'evaluations_url' => urls::evaluations($organizationid, $orgsem->get('id')),
            ]);
        } else {
            // This is the same URL as for an existing semester, but we use a different label to make it clear why it's the only
            // option.
            $nav_context['setup_new_semester_url'] = urls::semester_settings($organizationid, $semester->id);
        }

        if ($organization->get('has_local_questionnaires')) {
            $nav_context['questionnaires_url'] = urls::questionnaires($organizationid);
        }

        if ($current_tab) {
            $nav_context['current_tab'] = $current_tab;
            $nav_context["current_tab_is_$current_tab"] = true;
        }

        echo $this->render_from_template('block_coursefeedback/organization_nav', $nav_context);

        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', ['class' => 'col-lg-8']);

        if (is_callable($content)) {
            $content();
        } else {
            echo $content;
        }

        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
    }
}
