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

namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use core\exception\coding_exception;

/**
 * Contains methods managing test data for now. Will be generalized or binned.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_execution_manager {

    /**
     * Deletes all responses for the given $responseslotids.
     * @param int[] $responseslotids
     * @return void
     */
    public function delete_survey_execution_answers(array $responseslotids): void {
        global $DB;
        if (!$responseslotids) {
            return;
        }
        $transaction = $DB->start_delegated_transaction();
        [$insql, $inparams] = $DB->get_in_or_equal($responseslotids, SQL_PARAMS_NAMED);
        // TODO Refactor out to surveyitems?.
        $DB->delete_records_subquery(
            'block_coursefeedback_surveyitemintresponse',
            'surveypartexecutionoptionresponseid',
            'slotid',
            "SELECT speor.id as slotid FROM {block_coursefeedback_surveypartexecutionoptionresp} speor
                          WHERE speor.surveypartexecutionoptionid $insql",
            $inparams
        );
        $DB->delete_records_subquery(
            'block_coursefeedback_surveyitemtextresponse',
            'surveypartexecutionoptionresponseid',
            'slotid',
            "SELECT speor.id as slotid FROM {block_coursefeedback_surveypartexecutionoptionresp} speor
                          WHERE speor.surveypartexecutionoptionid $insql",
            $inparams
        );
        $DB->delete_records_list(
            'block_coursefeedback_surveypartexecutionoptionresp',
            'surveypartexecutionoptionid',
            $responseslotids
        );
        $transaction->allow_commit();
    }

    /**
     * Deletes a survey execution and all sub-resources.
     *
     * @param survey_execution $survey_execution
     * @return void
     */
    public function delete_survey_execution(survey_execution $survey_execution): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $recordset = $DB->get_recordset_sql("
            SELECT spe.id AS spe_id, slot.id AS slot_id, spe.eventid as spe_eventid
            FROM {" . survey_part_execution::TABLE . "} spe
            LEFT JOIN {" . response_slot::TABLE . "} slot ON spe.id = slot.surveypartexecutionid
            WHERE spe.surveyexecutionid = :surveyexecutionid
        ", ['surveyexecutionid' => $survey_execution->get('id')]);

        $records = iterator_to_array($recordset, preserve_keys: false);

        $this->delete_survey_execution_answers(array_column($records, 'slot_id'));

        $DB->delete_records_list(response_slot_user::TABLE, 'surveypartexecutionoptionid', array_column($records, 'slot_id'));
        $DB->delete_records_list(response_slot::TABLE, 'id', array_column($records, 'slot_id'));
        $DB->delete_records_list(survey_part_execution::TABLE, 'id', array_column($records, 'spe_id'));
        $DB->delete_records_list(teaching_event::TABLE, 'id', array_column($records, 'spe_eventid'));
        $survey_execution->delete();

        $transaction->allow_commit();
    }
}
