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
use block_coursefeedback\local\persistent\teaching_event;
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
use moodle_recordset;

/**
 * External api to delete teaching events in a course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_event extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'eventid' => new external_value(PARAM_INT, 'id of the event to delete'),
        ]);
    }

    /**
     * Returns description of method return.
     *
     * @return external_function_parameters
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            'new_table_html' => new external_value(PARAM_RAW),
        ]);
    }

    /**
     * From the recordset extracts the IDs of the SPEs, slots, and slot users that are part of the event.
     *
     * @param moodle_recordset $recordset
     * @return array
     */
    private static function extract_courseid_and_ids_to_delete(moodle_recordset $recordset): array {
        $spe_ids = [];
        $rs_ids = [];
        $rsu_ids = [];

        foreach ($recordset as $row) {
            if ($row->spe_id) {
                $spe_ids[$row->spe_id] = null;
            }
            if ($row->rs_id) {
                $rs_ids[$row->rs_id] = null;
            }
            if ($row->rsu_id) {
                $rsu_ids[$row->rsu_id] = null;
            }
        }

        return [array_keys($spe_ids), array_keys($rs_ids), array_keys($rsu_ids)];
    }

    /**
     * Does the thing.
     *
     * @param int $courseid
     * @param int $eventid
     * @return array
     */
    public static function execute(int $courseid, int $eventid): array {
        global $DB, $OUTPUT;

        [ 'courseid' => $courseid, 'eventid' => $eventid ] =
            self::validate_parameters(self::execute_parameters(), [ 'courseid' => $courseid, 'eventid' => $eventid ]);

        self::validate_context(\context_system::instance());
        $course = get_course($courseid);

        [$organization, , ] = di::get(semester_manager::class)->get_triplet_by_course($course);

        permission_manager::require_edit_course_surveysettings($course, $organization);

        $freezer = di::get(survey_freezer::class);

        $transaction = $DB->start_delegated_transaction();

        $se_fields = survey_execution::get_sql_fields('se', 'se_');
        $recordset = $DB->get_recordset_sql("
            SELECT te.courseid, $se_fields,
                   spe.id AS spe_id, rs.id AS rs_id, rsu.id as rsu_id
            FROM {" . teaching_event::TABLE . "} te
            LEFT JOIN {" . survey_part_execution::TABLE . "} spe ON spe.eventid = te.id
            LEFT JOIN {" . survey_execution::TABLE . "} se ON se.id = spe.surveyexecutionid
            LEFT JOIN {" . response_slot::TABLE . "} rs ON spe.id = rs.surveypartexecutionid
            LEFT JOIN {" . response_slot_user::TABLE . "} rsu ON rs.id = rsu.surveypartexecutionoptionid
            WHERE te.id = :eventid
        ", ['eventid' => $eventid]);

        try {
            if (!$recordset || !$recordset->valid()) {
                debugging("Tried to delete nonexistent event '$eventid'");
            } else {
                $first_row = $recordset->current();
                if ($first_row->courseid != $courseid) {
                    throw new coding_exception("Event '$eventid' does not belong to the course '$courseid'");
                }

                $survey_execution = survey_execution::extract($first_row, 'se_');
                if ($survey_execution) {
                    // If the event doesn't belong to a survey execution, it definitely isn't locked.
                    $freezer->check_se_action($survey_execution, "delete event '$eventid'");
                }

                [$spe_ids, $rs_ids, $rsu_ids] = self::extract_courseid_and_ids_to_delete($recordset);

                if ($rsu_ids) {
                    $DB->delete_records_list(response_slot_user::TABLE, 'id', $rsu_ids);
                }
                if ($rs_ids) {
                    $DB->delete_records_list(response_slot::TABLE, 'id', $rs_ids);
                }
                if ($spe_ids) {
                    $DB->delete_records_list(survey_part_execution::TABLE, 'id', $spe_ids);
                }

                $DB->delete_records(teaching_event::TABLE, ['id' => $eventid]);
            }
        } finally {
            $recordset?->close();
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
