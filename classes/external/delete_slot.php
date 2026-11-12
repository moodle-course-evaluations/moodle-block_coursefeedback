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
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\survey_cache;
use block_coursefeedback\local\survey_execution_data;
use block_coursefeedback\local\survey_freezer;
use block_coursefeedback\output\course_event_slot_table;
use core\di;
use core\exception\coding_exception;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External api to delete a response slot in a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_slot extends external_api {

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'slotid' => new external_value(PARAM_INT, 'id of the slot to delete'),
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
     * @param int $courseid
     * @param int $slotid
     * @return array
     */
    public static function execute(int $courseid, int $slotid): array {
        global $DB, $OUTPUT;

        [ 'courseid' => $courseid, 'slotid' => $slotid ] =
            self::validate_parameters(self::execute_parameters(), [ 'courseid' => $courseid, 'slotid' => $slotid ]);

        self::validate_context(\context_system::instance());
        $course = get_course($courseid);

        [$organization, , ] = di::get(semester_manager::class)->get_triplet_by_course($course);

        permission_manager::require_edit_course_surveysettings($course, null);

        $freezer = di::get(survey_freezer::class);

        $transaction = $DB->start_delegated_transaction();

        $se_fields = survey_execution::get_sql_fields('se', 'se_');
        $recordset = $DB->get_recordset_sql("
            SELECT $se_fields, rs.id AS rs_id, rsu.id as rsu_id
            FROM {" . response_slot::TABLE . "} rs
            JOIN {" . survey_part_execution::TABLE . "} spe ON spe.id = rs.surveypartexecutionid
            JOIN {" . survey_execution::TABLE . "} se ON se.id = spe.surveyexecutionid
            LEFT JOIN {" . response_slot_user::TABLE . "} rsu ON rs.id = rsu.surveypartexecutionoptionid
            WHERE rs.id = :slotid
        ", ['slotid' => $slotid]);

        try {
            $records = iterator_to_array($recordset, preserve_keys: false);
        } finally {
            $recordset->close();
        }

        if (!$records) {
            debugging("Tried to delete nonexistent slot '$slotid'");
        } else {
            $first_record = reset($records);

            $survey_execution = survey_execution::extract($first_record, 'se_');
            if (!$survey_execution) {
                throw new coding_exception("Slot '$slotid' does not belong to any survey execution");
            }

            if ($survey_execution->get('courseid') != $courseid) {
                throw new coding_exception("Slot '$slotid' does not belong to the course '$courseid'");
            }

            $freezer->check_se_action($survey_execution, "delete slot '$slotid'");

            $rsu_ids = array_filter(array_unique(array_column($records, 'rsu_id')));

            if ($rsu_ids) {
                $DB->delete_records_list(response_slot_user::TABLE, 'id', $rsu_ids);
            }
            $DB->delete_records(response_slot::TABLE, ['id' => $slotid]);
        }

        $transaction->allow_commit();

        di::get(survey_cache::class)->evict_by_courseid($course->id);
        $data = survey_execution_data::load_from_course_required($course);
        $table = new course_event_slot_table(
            $data,
            $course,
            is_frozen: $freezer->is_se_frozen($data->survey_execution),
            include_inactive_event_types: permission_manager::can_manage_organization($organization)
        );

        return [
            'new_table_html' => $OUTPUT->render($table),
        ];
    }
}
