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
 * Survey item type definition for a text question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\surveyitem\text;

use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\lang_string;
use moodle_exception;

/**
 * Survey item type definition for a text question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text extends surveyitemtype_with_settings {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('text', 'block_coursefeedback');
    }

    #[\Override]
    public function save_settings_form_data(surveyitem $surveyitem, surveypart $surveypart, object $formdata): void {
        global $DB;
        $record = $DB->get_record('block_coursefeedback_surveyitemtext', ['surveyitemid' => $surveyitem->get('id')]);
        if ($record) {
            if ($record->initialrows != $formdata->initialrows) {
                $record->initialrows = $formdata->initialrows;
                $DB->update_record('block_coursefeedback_surveyitemtext', $record);
            }
        } else {
            $DB->insert_record('block_coursefeedback_surveyitemtext', [
                'surveyitemid' => $surveyitem->get('id'),
                'initialrows' => $formdata->initialrows,
            ]);
        }
    }

    #[\Override]
    public function load_settings_form_data(surveyitem $surveyitem): object {
        global $DB;
        $formdata = parent::load_settings_form_data($surveyitem);
        $record = $DB->get_record('block_coursefeedback_surveyitemtext', ['surveyitemid' => $surveyitem->get('id')]);
        if ($record) {
            $formdata->initialrows = $record->initialrows;
        } else {
            debugging("surveyitemtext record not found for survey item with id '{$surveyitem->get('id')}'");
        }
        return $formdata;
    }

    #[\Override]
    public function load_additional_data_for(array $surveyitems): array {
        global $DB;
        $records = $DB->get_records_list(
            'block_coursefeedback_surveyitemtext',
            'surveyitemid',
            array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems),
            fields: 'surveyitemid, initialrows'
        );

        $additionaldata = [];
        foreach ($surveyitems as $surveyitem) {
            if ($record = $records[$surveyitem->get('id')] ?? null) {
                $additionaldata[$record->surveyitemid] = [ 'initialrows' => $record->initialrows ];
            } else {
                debugging("surveyitemtext record not found for survey item with id '{$surveyitem->get('id')}'");
                $additionaldata[$record->surveyitemid] = [ 'initialrows' => 8 ];
            }
        }

        return $additionaldata;
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            if (!is_string($answer->value)) {
                throw new moodle_exception('invalid_answer', 'block_coursefeedback', a: json_encode($answer->value));
            }
            $to_insert[] = [
                'surveypartexecutionoptionresponseid' => $answer->response_set_id,
                'surveyitemid' => $answer->surveyitem_id,
                'value' => $answer->value,
            ];
        }
        $DB->insert_records('block_coursefeedback_surveyitemtextresponse', $to_insert);
    }

    #[\Override]
    public function load_and_export_report_data(
        response_slot $response_slot,
        array $surveyitemsoftype,
        array $additional_data
    ): array {
        global $DB;

        $template_data = self::export_for_template($surveyitemsoftype, $additional_data);

        $recordset = $DB->get_recordset_sql("SELECT sitr.surveyitemid, sitr.value
            FROM {block_coursefeedback_surveyitemtextresponse} sitr
            JOIN {block_coursefeedback_surveypartexecutionoptionresp} speor
                ON sitr.surveypartexecutionoptionresponseid = speor.id
            WHERE speor.surveypartexecutionoptionid = :slotid", ['slotid' => $response_slot->get('id')]);

        foreach ($recordset as $record) {
            $template_data[$record->surveyitemid] ??= ['responses' => []];
            $template_data[$record->surveyitemid]['responses'][] = $record->value;
        }

        foreach ($template_data as &$surveyitemdata) {
            $surveyitemdata['has_responses'] = count($surveyitemdata['responses'] ?? []) > 0;
        }

        $recordset->close();

        return $template_data;
    }

    #[\Override]
    public function export_for_template(array $surveyitems, array $additional_data): array {
        $template_data = parent::export_for_template($surveyitems, $additional_data);
        foreach ($surveyitems as $surveyitem) {
            $template_data[$surveyitem->get('id')]['initialrows'] = $additional_data[$surveyitem->get('id')]['initialrows'];
        }
            return $template_data;
    }
}
