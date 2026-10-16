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

/**
 * Survey item type definition for a multiple choice question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\multiplechoice;

use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\surveyitem\ms_choice\ms_choice;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use core\lang_string;
use moodle_exception;

/**
 * Survey item type definition for a multiple choice question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class multiplechoice extends ms_choice {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('multiplechoice', 'block_coursefeedback');
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            if (!is_array($answer->value)) {
                throw new moodle_exception(
                    'invalid_answer',
                    'block_coursefeedback',
                    a: json_encode($answer->value),
                    debuginfo: 'not an array'
                );
            }
            foreach ($answer->value as $one_answer) {
                if (!is_number($one_answer) || !isset($answer->additionaldata[$one_answer])) {
                    throw new moodle_exception(
                        'invalid_answer',
                        'block_coursefeedback',
                        a: json_encode($one_answer),
                        debuginfo: 'not an option'
                    );
                }
                $to_insert[] = [
                    'surveypartexecutionoptionresponseid' => $answer->response_set_id,
                    'surveyitemid' => $answer->surveyitem_id,
                    'value' => $one_answer,
                ];
            }
        }
        $DB->insert_records('block_coursefeedback_surveyitemintresponse', $to_insert);
    }

    /**
     * For the given response_slot and surveyitems, return the number of given answersets
     * (Where a selection of one or more multiple choice options counts as one answerset).
     * @param response_slot $response_slot
     * @param array $surveyitems
     * @return array<int, int> [$surveyitemid => $countofanswers]
     */
    protected function get_number_of_answersets(response_slot $response_slot, array $surveyitems): array {
        global $DB;

        $surveyitemids = array_map(fn ($s) => $s->get('id'), $surveyitems);

        if (!$surveyitemids) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($surveyitemids, SQL_PARAMS_NAMED);

        // Use record set to avoid having a unique first column.
        $recordset = $DB->get_recordset_sql(
            "SELECT siir.surveyitemid, count(DISTINCT speor.id) as count
            FROM {block_coursefeedback_surveyitemintresponse} siir
            JOIN {block_coursefeedback_surveypartexecutionoptionresp} speor
                ON siir.surveypartexecutionoptionresponseid = speor.id
            WHERE speor.surveypartexecutionoptionid = :slotid AND siir.surveyitemid $insql
            GROUP BY siir.surveyitemid",
            ['slotid' => $response_slot->get('id'), ...$inparams]
        );

        $responses = [];

        foreach ($recordset as $record) {
            $responses[$record->surveyitemid] = $record->count;
        }

        return $responses;
    }

    #[\Override]
    public function load_and_export_report_data(
        response_slot $response_slot,
        array $surveyitemsoftype,
        array $additional_data
    ): array {
        $responses = surveyitem_manager::get_aggregated_int_responses($response_slot);
        $number_of_answersets = $this->get_number_of_answersets($response_slot, $surveyitemsoftype);

        $template_data = self::export_for_template($surveyitemsoftype, $additional_data);
        foreach ($template_data as $surveyitemid => &$surveyitemdata) {
            $n = $number_of_answersets[$surveyitemid] ?? 0;
            if ($n < get_config('block_coursefeedback', 'report_min_responses_per_item')) {
                $surveyitemdata['not_enough_responses'] = true;
                continue;
            }

            foreach ($surveyitemdata['options'] as &$optiondata) {
                $optiondata['responses'] = $responses[$surveyitemid][$optiondata['optionid']] ?? 0;
                if ($n > 0) {
                    $optiondata['percent_rounded'] = round($optiondata['responses'] * 100 / $n, 1) . '%';
                }
            }
            $surveyitemdata['n'] = $n;
        }

        return $template_data;
    }

    #[\Override]
    public function post_process_response_values(surveyitem $surveyitem, array $values): int|string|null {
        return implode(',', $values);
    }
}
