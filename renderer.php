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

use block_coursefeedback\local\survey;
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
    protected function get_mustache(): Mustache_Engine {
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
}
