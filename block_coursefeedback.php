<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\survey_execution_data;

/**
 * Block coursefeedback is defined here.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 IT.Services, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursefeedback extends block_base {

    /**
     * Initializes the block title.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_coursefeedback');
    }

    #[\Override]
    public function get_content_for_output($output) {
        if ($this->instance->parentcontextid == 1 && $this->get_content()->text == '') {
            // If this is a global block and we have no text, do not show the block even in edit mode.
            return null;
        }
        return parent::get_content_for_output($output);
    }

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    #[\Override]
    public function _self_test() {
        return true;
    }
    // phpcs:enable

    /**
     * Get template context for the report section.
     *
     * @param survey_execution_data $survey_execution_data
     * @param bool $can_view_course_settings
     * @return array
     */
    private function get_report_template_context(
        survey_execution_data $survey_execution_data,
        bool $can_view_course_settings
    ): array {
        global $USER;

        $template_events = [];
        $can_manage_organization = permission_manager::can_manage_organization($survey_execution_data->organization);
        foreach ($survey_execution_data->events_by_id as $event) {
            $spe = $survey_execution_data->spes_by_event_id[$event->get('id')];
            $slots = $survey_execution_data->slots_by_spe_id[$spe->get('id')];
            $multiple_slots = count($slots) >= 2;

            $template_slots = [];
            foreach ($slots as $slot) {
                $users = $survey_execution_data->users_by_slot_id[$slot->get('id')] ?? [];
                if ($can_manage_organization) {
                    $canseeslot = true;
                } else if ($multiple_slots || $users) {
                    $canseeslot = false;
                    foreach ($users as $user) {
                        if ($user->id == $USER->id) {
                            $canseeslot = true;
                            break;
                        }
                    }
                } else {
                    $canseeslot = $can_view_course_settings;
                }

                if ($canseeslot) {
                    $name = $slot->get('name');
                    if (!$name || $name === '-') {
                        $name = get_string('report', 'block_coursefeedback');
                    }
                    $template_slots[] = [
                        'url' => new moodle_url('/blocks/coursefeedback/report.php', ['id' => $slot->get('id')]),
                        'name' => $name,
                    ];
                }
            }

            if ($template_slots) {
                $template_events[] = [
                    'name' => $event->get('name') ?: $this->page->course->fullname,
                    'slots' => $template_slots,
                ];
            }
        }

        return $template_events;
    }

    /**
     * Generates the actual content of the block.
     * @return string
     */
    private function generate_content(): string {
        global $OUTPUT;
        try {
            $mapper = course_organization_mapping::get_instance();
            $organization = $mapper->get_organization_for_course($this->page->course);
            if (!$organization) {
                return '';
            }

            $can_view_course_settings = permission_manager::can_view_course_settings($this->page->course, $organization);

            $survey_execution = survey_execution::get_record([
                'organizationid' => $organization->get('id'),
                'courseid' => $this->page->course->id,
            ]);
            if (!$survey_execution) {
                return '';
            }

            $survey_execution_data = survey_execution_data::load_from_survey_execution_id_required(
                $survey_execution->get('id')
            );

            if ($survey_execution->get('status') == survey_execution::STATUS_STARTED) {
                $template_events = $this->get_report_template_context($survey_execution_data, $can_view_course_settings);
            } else {
                $template_events = [];
            }

            if (!$template_events && !$can_view_course_settings) {
                return '';
            }

            $context = [
                'organization_name' => $organization->get('name'),
                'starttime' => $survey_execution->get('starttime') ?? $organization->get('evaluation_starttime'),
                'endtime' => $survey_execution->get('endtime') ?? $organization->get('evaluation_endtime'),
            ];

            if ($can_view_course_settings) {
                $context['evaluation_settings_url'] = new moodle_url(
                    '/blocks/coursefeedback/course.php',
                    ['id' => $this->page->course->id]
                );
            }

            if ($template_events) {
                $context['reports'] = $template_events;
                $context['has_reports'] = (bool) $template_events;
            }

            return $OUTPUT->render_from_template('block_coursefeedback/block_content', $context);
        } catch (Exception $e) {
            debugging($e);
        }
        return '';
    }

    /**
     * Returns the block content.
     *
     * @return \stdClass
     */
    public function get_content(): \stdClass {

        if ($this->content === null) {
            $this->content = new \stdClass();
            $this->content->text = $this->generate_content();
        }

        return $this->content;
    }


    /**
     * Loads any instance-specific configuration.
     */
    public function specialization(): void {
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_coursefeedback');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enable global settings for this block.
     *
     * @return bool
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Defines where this block can be added.
     *
     * @return string[]
     */
    public function applicable_formats(): array {
        return [
            'all'                => false,
            'course-view'        => false,
            'course-view-social' => false,
        ];
    }
}
