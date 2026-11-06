<?php
// This file is part of Moodle - http://moodle.org/
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

namespace block_coursefeedback\local;

use block_coursefeedback;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\user_organization_cache_manager;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback_renderer;
use core\di;
use core\hook\navigation\primary_extend;
use core\hook\output\after_standard_main_region_html_generation;
use core\notification;
use moodle_url;

/**
 * Place for hook callbacks.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Shows the given survey to the current student so long as they haven't responded already.
     *
     * @param after_standard_main_region_html_generation $hook
     * @param survey $ongoing_survey
     * @return void
     */
    private static function maybe_show_ongoing_survey_to_student(
        after_standard_main_region_html_generation $hook,
        survey $ongoing_survey
    ): void {
        global $PAGE, $USER, $DB;

        if (
            $DB->record_exists('block_coursefeedback_surveyexecution_user', [
                'surveyexecutionid' => $ongoing_survey->survey_execution->get('id'),
                'userid' => $USER->id,
            ])
        ) {
            // The user has already filled out this survey.
            return;
        }

        if ($ongoing_survey->is_empty()) {
            debugging("There is an active survey, but it's empty.");
            return;
        }

        /** @var block_coursefeedback_renderer $renderer */
        $renderer = $PAGE->get_renderer('block_coursefeedback');

        // We add the survey HTML to the end of the page, it'll move itself to the notification area.
        $hook->add_html($renderer->render_survey($ongoing_survey, append_to_selector: '#user-notifications'));
    }

    /**
     * Notifies the teacher of the impending, ongoing, or ended survey. Links to the block if possible.
     *
     * @param survey_execution $survey_execution
     * @return void
     */
    private static function notify_teacher_of_survey(survey_execution $survey_execution): void {
        global $PAGE, $SITE;

        if ($survey_execution->is_ongoing()) {
            $html = get_string('notification_ongoing', 'block_coursefeedback', [
                'sitename' => $SITE->shortname,
                'endtime' => userdate_htmltime($survey_execution->get('endtime')),
            ]);
        } else if ($survey_execution->has_ended()) {
            $html = get_string('notification_ended', 'block_coursefeedback', $SITE->shortname);
        } else {
            $html = get_string('notification_upcoming', 'block_coursefeedback', $SITE->shortname);
        }

        // We need the block instance ID in order to effectively link to it.
        foreach ($PAGE->blocks->get_blocks_for_region(BLOCK_POS_LEFT) as $block) {
            if ($block instanceof block_coursefeedback && $block->instance) {
                $instance_id = intval($block->instance->id);
                $html .= " " . get_string('notification_link_to_block', 'block_coursefeedback', "#inst$instance_id");
                break;
            }
        }

        notification::add($html, notification::INFO);
    }

    /**
     * Hook callback after_standard_main_region_html_generation.
     * Calls JS to show survey to user.
     *
     * @param after_standard_main_region_html_generation $hook
     */
    public static function after_standard_main_region_html_generation(after_standard_main_region_html_generation $hook) {
        global $PAGE;
        try {
            if (
                $PAGE->context->contextlevel !== CONTEXT_COURSE
                || !$PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)
            ) {
                return;
            }

            $survey = di::get(survey_cache::class)->get_by_course($PAGE->course);
            if ($survey instanceof survey) {
                $survey_execution = $survey->survey_execution;

                if (has_capability('block/coursefeedback:filloutsurvey', $PAGE->context)) {
                    self::maybe_show_ongoing_survey_to_student($hook, $survey);
                }
            } else if ($survey instanceof survey_execution) {
                $survey_execution = $survey;
            } else {
                return;
            }

            if (permission_manager::can_view_course_settings($PAGE->course, $survey_execution->get('organizationid'))) {
                self::notify_teacher_of_survey($survey_execution);
            }
        } catch (\Exception $e) {
            debugging($e);
        }
    }

    /**
     * Adds the evaluation administration overview page to evaluation admins primary navigation.
     *
     * @param primary_extend $hook
     */
    public static function primary_extend(primary_extend $hook) {
        try {
            if (has_capability('moodle/site:config', \context_system::instance())) {
                return;
            }
            if (permission_manager::can_do_any_evaluation_administration()) {
                $hook->primaryview->add(
                    get_string('evaluationadministration', 'block_coursefeedback'),
                    new moodle_url('/blocks/coursefeedback/overview.php'),
                    key: 'evaluation_administration'
                );
            } else if (user_organization_cache_manager::get_instance()->is_user_evaluation_coordinator_anywhere()) {
                $hook->primaryview->add(
                    get_string('evaluationadministration', 'block_coursefeedback'),
                    new moodle_url('/blocks/coursefeedback/organizations.php'),
                    key: 'evaluation_administration'
                );
            }
        } catch (\Exception $e) {
            debugging($e);
        }
    }

    /**
     * Callback for add_block setting.
     */
    public static function add_block_changed_callback() {
        global $DB;
        $add_block = get_config('block_coursefeedback', 'add_block');
        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        if ($add_block) {
            if (!$page->blocks->is_block_present('coursefeedback')) {
                if (!$page->blocks->is_known_region(BLOCK_POS_LEFT)) {
                    $page->blocks->add_region(BLOCK_POS_LEFT);
                }
                $page->blocks->add_block('coursefeedback', BLOCK_POS_LEFT, 0, true, 'course-view-*');
            }
        } else {
            $DB->delete_records('block_instances', ['blockname' => 'coursefeedback', 'parentcontextid' => 1]);
        }
    }
}
