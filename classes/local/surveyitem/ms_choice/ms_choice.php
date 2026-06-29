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
 * Survey item type definition parent class for multiple and single choice questions.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\ms_choice;

use block_coursefeedback\local\backup\backup_invalid_exception;
use block_coursefeedback\local\multilang_string;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\exception\moodle_exception;
use moodle_url;
use moodleform;

/**
 * Survey item type definition parent class for multiple and single choice questions.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ms_choice extends surveyitemtype_with_settings {

    #[\Override]
    public function get_settings_form(moodle_url $action, surveypart $surveypart): moodleform {
        return new ms_choice_form($action, $surveypart);
    }

    #[\Override]
    public function save_settings_form_data(surveyitem $surveyitem, surveypart $surveypart, object $formdata): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $existingoptions = $DB->get_records(
            'block_coursefeedback_surveyitemansweroption',
            ['surveyitemid' => $surveyitem->get('id')],
            'sortindex ASC',
            'sortindex, id, text'
        );

        /** @var array[] $to_insert */
        $to_insert = [];
        /** @var int[] $to_delete_ids */
        $to_delete_ids = [];
        /** @var array[] $to_update */
        $to_update = [];

        for ($i = 0; $i < $formdata->choices_amount; $i++) {
            /** @var multilang_string|null $submitted */
            $submitted = $formdata->answers[$i] ?? null;
            $existing = $existingoptions[$i] ?? null;

            if ($submitted && !$existing) {
                $to_insert[] = [
                    'surveyitemid' => $surveyitem->get('id'),
                    'text' => $submitted->serialize(),
                    'sortindex' => $i,
                ];
            } else if ($submitted && $existing && multilang_string::deserialize($existing->text) != $submitted) {
                $to_update[] = ['id' => $existing->id, 'text' => $submitted->serialize()];
            } else if (!$submitted && $existing) {
                $to_delete_ids[] = $existing->id;
            }
        }

        $DB->delete_records_list('block_coursefeedback_surveyitemansweroption', 'id', $to_delete_ids);
        $DB->insert_records('block_coursefeedback_surveyitemansweroption', $to_insert);
        foreach ($to_update as $update_record) {
            // No bulk update method :(.
            $DB->update_record('block_coursefeedback_surveyitemansweroption', $update_record, bulk: true);
        }

        $transaction->allow_commit();
    }

    #[\Override]
    public function load_settings_form_data(surveyitem $surveyitem): object {
        global $DB;
        $data = parent::load_settings_form_data($surveyitem);

        $option_records = $DB->get_records(
            'block_coursefeedback_surveyitemansweroption',
            ['surveyitemid' => $surveyitem->get('id')],
            'sortindex ASC',
            'sortindex, id, text'
        );

        $data->answers = array_map(fn($record) => multilang_string::deserialize($record->text), $option_records);
        $data->choices_amount = count($option_records);
        return $data;
    }

    #[\Override]
    public function load_additional_data_for(array $surveyitems): array {
        global $DB;
        $additionaldata = parent::load_additional_data_for($surveyitems);

        $surveyitemids = array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems);
        $records = $DB->get_records_list(
            'block_coursefeedback_surveyitemansweroption',
            "surveyitemid",
            $surveyitemids,
            sort: 'sortindex'
        );

        foreach ($records as $record) {
            $record->text = multilang_string::deserialize($record->text);
            $additionaldata[$record->surveyitemid][$record->id] = $record;
        }

        return $additionaldata;
    }

    #[\Override]
    public function export_for_template(array $surveyitems, array $additional_data): array {
        $template_data = parent::export_for_template($surveyitems, $additional_data);
        foreach ($surveyitems as $surveyitem) {
            $template_data[$surveyitem->get('id')]['options'] = array_values(array_map(
                fn($option) => [
                    'optiontext' => $option->text->translate(),
                    'optionid' => $option->id,
                ],
                $additional_data[$surveyitem->get('id')] ?? []
            ));
        }
        return $template_data;
    }

    #[\Override]
    public function backup_items(array $surveyitems): array {
        global $DB;
        $backup_data = parent::backup_items($surveyitems);

        $surveyitemids = array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems);
        $records = $DB->get_records_list(
            'block_coursefeedback_surveyitemansweroption',
            "surveyitemid",
            $surveyitemids,
            sort: 'sortindex',
            fields: 'id, surveyitemid, text'
        );

        foreach ($records as $record) {
            $backup_data[$record->surveyitemid]['options'][] = [ 'text' => $record->text ];
        }

        return $backup_data;
    }

    #[\Override]
    public function restore_from_backup(array $surveyitems, array $backup_data, array $scales): void {
        parent::restore_from_backup($surveyitems, $backup_data, $scales);

        $options_to_insert = [];
        foreach ($backup_data as $surveyitemid => $data) {
            if (!isset($data->options) || !is_array($data->options)) {
                throw new backup_invalid_exception("'options' is missing or not an array");
            }
            if (empty($data->options)) {
                // Question without answer options. Weird, but valid.
                continue;
            }

            $i = 0;
            foreach ($data->options as $option) {
                if (!is_object($option) || !isset($option->text) || !multilang_string::is_valid($option->text)) {
                    throw new backup_invalid_exception('invalid option in survey item');
                }

                $options_to_insert[] = [
                    'surveyitemid' => $surveyitemid,
                    'text' => $option->text,
                    'sortindex' => $i++,
                ];
            }
        }

        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $DB->insert_records('block_coursefeedback_surveyitemansweroption', $options_to_insert);
        $transaction->allow_commit();
    }
}
