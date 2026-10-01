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

namespace block_coursefeedback\external;

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\semester_manager;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\teaching_event;
use block_coursefeedback\local\survey_cache;
use block_coursefeedback\local\survey_execution_data;
use block_coursefeedback\local\survey_freezer;
use block_coursefeedback\output\course_event_slot_table;
use coding_exception;
use core\di;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External api to create and update teaching events in a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upsert_event extends external_api {

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'eventid' => new external_value(PARAM_INT, 'if updating, the id of the event to update, otherwise 0', VALUE_DEFAULT, 0),
            'surveyexecutionid' => new external_value(PARAM_INT, 'id of the survey execution to which the course belongs'),
            'name' => new external_value(PARAM_TEXT, 'name of the (new) event'),
            'eventtypeid' => new external_value(PARAM_INT, 'id of the event type'),
        ]);
    }

    /**
     * Returns description of method return.
     * @return external_function_parameters
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            'new_table_html' => new external_value(PARAM_RAW),
        ]);
    }

    /**
     * Does the thing.
     *
     * @param int $eventid
     * @param int $surveyexecutionid
     * @param string $name
     * @param int $eventtypeid
     * @return array
     */
    public static function execute(int $eventid, int $surveyexecutionid, string $name, int $eventtypeid): array {
        [
            'eventid' => $eventid, 'surveyexecutionid' => $surveyexecutionid, 'name' => $name, 'eventtypeid' => $eventtypeid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'eventid' => $eventid, 'surveyexecutionid' => $surveyexecutionid, 'name' => $name, 'eventtypeid' => $eventtypeid,
        ]);

        $survey_execution = survey_execution::get_record(['id' => $surveyexecutionid], MUST_EXIST);
        $courseid = $survey_execution->get('courseid');

        self::validate_context(\context_system::instance());
        $course = get_course($courseid);

        [$organization, $semester, $orgsem] = di::get(semester_manager::class)->get_triplet_by_course($course);

        permission_manager::require_edit_course_surveysettings($course, $organization);

        $freezer = di::get(survey_freezer::class);

        global $DB, $OUTPUT;
        $transaction = $DB->start_delegated_transaction();

        $eventtype = eventtype::get_record([
            'id' => $eventtypeid,
            'orgsemid' => $orgsem->get('id'),
        ], MUST_EXIST);

        if ($eventid) {
            self::update_event($courseid, $survey_execution, $eventtype, $name, $eventid);
        } else {
            self::create_event($courseid, $survey_execution, $eventtype, $name);
        }

        $transaction->allow_commit();

        di::get(survey_cache::class)->evict_by_courseid($course->id);
        $data = survey_execution_data::load_from_course_required($course);
        $table = new course_event_slot_table($data, $course, is_frozen: $freezer->is_se_frozen($data->survey_execution));

        return [
            'new_table_html' => $OUTPUT->render($table),
        ];
    }

    /**
     * Creates a new event.
     *
     * @param int $courseid
     * @param survey_execution $survey_execution
     * @param eventtype $eventtype
     * @param string $name
     * @return void
     */
    private static function create_event(
        int $courseid,
        survey_execution $survey_execution,
        eventtype $eventtype,
        string $name
    ): void {
        di::get(survey_freezer::class)
            ->check_se_action($survey_execution, "create event in course '$courseid'");

        $event = new teaching_event();
        $event->set_many([
            'courseid' => $courseid,
            'name' => $name,
            'eventtypeid' => $eventtype->get('id'),
        ]);
        $event->save();

        $spe = new survey_part_execution();
        $spe->set_many([
            'surveyexecutionid' => $survey_execution->get('id'),
            'eventid' => $event->get('id'),
        ]);
        $spe->save();

        $response_slot = new response_slot();
        $response_slot->set_many([
            'surveypartexecutionid' => $spe->get('id'),
            'name' => '-',
        ]);
        $response_slot->save();
    }

    /**
     * Updates an existing event.
     *
     * @param int $courseid
     * @param survey_execution $survey_execution
     * @param eventtype $eventtype
     * @param string $name
     * @param int $eventid
     * @return void
     */
    private static function update_event(
        int $courseid,
        survey_execution $survey_execution,
        eventtype $eventtype,
        string $name,
        int $eventid
    ): void {
        $event = teaching_event::get_record(['id' => $eventid], MUST_EXIST);
        if ($event->get('courseid') !== $courseid) {
            throw new coding_exception("Teaching event '$eventid' does not belong to course '$courseid'");
        }

        $event->set('name', $name);

        if ($event->get('eventtypeid') !== $eventtype->get('id')) {
            di::get(survey_freezer::class)
                ->check_se_action($survey_execution, "update type of event '$eventid'");

            $event->set('eventtypeid', $eventtype->get('id'));
        }

        $event->save();

        $spe = survey_part_execution::get_record(['eventid' => $eventid]);

        if ($spe && $spe->get('surveypartid') && $spe->get('surveypartid') !== $eventtype->get('surveypartid')) {
            // This _should_ only happen if the event type is changed, but we check to be sure.
            di::get(survey_freezer::class)
                ->check_se_action($survey_execution, "update survey part of SPE '{$spe->get('id')}'");

            $spe->set('surveypartid', $eventtype->get('surveypartid'));
            $spe->save();
        }
    }
}
