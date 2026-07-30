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

use block_coursefeedback\local\survey_execution_data;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\surveyitemtype_answerdata;
use core\context\course;
use core\exception\coding_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core\exception\moodle_exception;

/**
 * External api to save survey responses.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_survey_answers extends external_api {

    /**
     * Parameter description.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'course id'),
            'surveyparts' => new external_multiple_structure(
                new external_single_structure([
                    'surveypartexecutionoptionid' => new external_value(PARAM_INT, 'id of the surveypartexecutionoption chosen'),
                    'answers' => new external_multiple_structure(
                        new external_single_structure([
                            'surveyitemid' => new external_value(PARAM_INT),
                            'value' => new external_value(PARAM_RAW, 'the actual value as json, depending on the surveyitemtype'),
                        ]),
                    ),
                ]),
            ),
        ]);
    }

    /**
     * Function to save survey response.
     * @param int $courseid
     * @param array $submittedsurveyparts
     * @return array[]
     */
    public static function execute(int $courseid, array $submittedsurveyparts): array {
        [
            'courseid' => $courseid,
            'surveyparts' => $submittedsurveyparts,
        ] = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'surveyparts' => $submittedsurveyparts,
        ]);

        global $DB, $USER;

        $context = course::instance($courseid);
        self::validate_context($context);
        require_capability('block/coursefeedback:filloutsurvey', $context, doanything: false);

        if (is_role_switched($courseid)) {
            throw new moodle_exception('no_save_when_switch_role', 'block_coursefeedback');
        }

        $course = get_course($courseid);

        $transaction = $DB->start_delegated_transaction();

        // TODO: Load the survey data from survey_cache.
        $course_data = survey_execution_data::load_from_course_required($course);

        if (!$course_data->survey_execution->is_ongoing()) {
            throw new moodle_exception('survey_has_ended', 'block_coursefeedback');
        }

        if (
            $DB->record_exists('block_coursefeedback_surveyexecution_user', [
                'surveyexecutionid' => $course_data->survey_execution->get('id'),
                'userid' => $USER->id,
            ])
        ) {
            throw new moodle_exception("already_answered", 'block_coursefeedback');
        }

        $all_question_data = surveyitem_manager::get_questiondata_for_surveyparts(
            array_values($course_data->survey_parts_by_spe_id)
        );

        /** @var array<string, surveyitemtype_answerdata[]> $answerdata Answers indexed by surveyitemtype. */
        $answerdata = [];

        /** @var int[] $processed_spe_ids To prevent a malicious user (or buggy client code) from saving to multiple slots. */
        $processed_spe_ids = [];

        foreach ($submittedsurveyparts as ['surveypartexecutionoptionid' => $slotid, 'answers' => $answers]) {
            if (!$answers) {
                // We don't save the empty response set. The fact that the user did the survey is saved separately after all SPEs.
                continue;
            }

            $slot = $course_data->slots_by_id[$slotid] ?? null;
            if (!$slot) {
                throw new coding_exception("Got answer in slot '$slotid' that is not in the survey.");
            }

            $spe_id = $slot->get('surveypartexecutionid');
            if (in_array($spe_id, $processed_spe_ids)) {
                throw new coding_exception("Got answer for multiple slots in the same SPE ($spe_id).");
            }
            $processed_spe_ids[] = $spe_id;

            $response_set_id = $DB->insert_record('block_coursefeedback_surveypartexecutionoptionresp', [
                'surveypartexecutionoptionid' => $slotid,
            ]);

            foreach ($answers as ['surveyitemid' => $surveyitemid, 'value' => $value]) {
                $surveyitemtype = null;
                $question_data = null;
                foreach ($all_question_data as $type => $data_by_itemid) {
                    if (array_key_exists($surveyitemid, $data_by_itemid)) {
                        $surveyitemtype = $type;
                        $question_data = $data_by_itemid[$surveyitemid];
                    }
                }

                if (!$surveyitemtype || $question_data === null) {
                    throw new coding_exception("Got answer to survey item '$surveyitemid' that is not in the survey.");
                }

                $parsed_value = json_decode($value, depth: 10, flags: JSON_THROW_ON_ERROR);

                $answerdata[$surveyitemtype][] = new surveyitemtype_answerdata(
                    response_set_id: $response_set_id,
                    surveyitem_id: $surveyitemid,
                    value: $parsed_value,
                    additionaldata: $question_data,
                );
            }
        }

        foreach ($answerdata as $surveyitemtype => $data) {
            surveyitem_manager::get_surveyitemtype($surveyitemtype)->check_and_save_answers($data);
        }

        // Save the fact that the user completed this survey execution.
        $maxtries = 10;
        $successful = false;
        for ($i = 0; $i < $maxtries; $i++) {
            try {
                $DB->insert_record_raw('block_coursefeedback_surveyexecution_user', [
                    'id' => random_int(0x1, 0xffffffff),
                    'surveyexecutionid' => $course_data->survey_execution->get('id'),
                    'userid' => $USER->id,
                ], customsequence: true);
                $successful = true;
                break;
            } catch (\dml_exception $exception) {
                continue;
            }
        }
        if (!$successful) {
            throw new moodle_exception('could_not_save_participation', 'block_coursefeedback');
        }

        $transaction->allow_commit();

        $userids = $DB->get_fieldset(
            'block_coursefeedback_surveyexecution_user',
            'userid',
            ['surveyexecutionid' => $course_data->survey_execution->get('id')]
        );
        if (count($userids) > 1) {
            $selected = $userids[random_int(0, count($userids) - 1)];
            $DB->execute('UPDATE {block_coursefeedback_surveyexecution_user} ' .
                'SET userid=:userid1 WHERE userid=:userid2 AND surveyexecutionid=:seid', [
                'userid1' => $selected,
                'userid2' => $selected,
                'seid' => $course_data->survey_execution->get('id'),
            ]);
        }

        return [];
    }

    /**
     * Currently does not return anything.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
