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
 * Survey item type definition for a single choice question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\surveyitem\singlechoice;

use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\surveyitem\ms_choice\ms_choice;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use core\lang_string;
use core\exception\moodle_exception;

/**
 * Survey item type definition for a single choice question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class singlechoice extends ms_choice {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('singlechoice', 'block_coursefeedback');
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            if (!$answer->value) {
                // We receive "" when the user doesn't select anything in a dropdown.
                continue;
            }
            if (!is_number($answer->value) || !isset($answer->additionaldata[$answer->value])) {
                throw new moodle_exception('invalid_answer', 'block_coursefeedback', a: json_encode($answer->value));
            }
            $to_insert[] = [
                'surveypartexecutionoptionresponseid' => $answer->response_set_id,
                'surveyitemid' => $answer->surveyitem_id,
                'value' => $answer->value,
            ];
        }

        if ($to_insert) {
            $DB->insert_records('block_coursefeedback_surveyitemintresponse', $to_insert);
        }
    }

    #[\Override]
    public function load_and_export_report_data(
        response_slot $response_slot,
        array $surveyitemsoftype,
        array $additional_data
    ): array {
        $responses = surveyitem_manager::get_aggregated_int_responses($response_slot);

        $template_data = self::export_for_template($surveyitemsoftype, $additional_data);
        foreach ($template_data as $surveyitemid => &$surveyitemdata) {
            $n = array_sum($responses[$surveyitemid] ?? []);

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
}
