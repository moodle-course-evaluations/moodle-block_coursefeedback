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
 * Survey item type definition for a scale question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\surveyitem\scalequestion;

use block_coursefeedback\local\backup\backup_invalid_exception;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\scale;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\lang_string;
use moodle_exception;

/**
 * Survey item type definition for a scale question.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scalequestion extends surveyitemtype_with_settings {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('scalequestion', 'block_coursefeedback');
    }

    #[\Override]
    public function save_settings_form_data(surveyitem $surveyitem, surveypart $surveypart, object $formdata): void {
        global $DB;
        $formdata->forceshowscale ??= false;
        $formdata->surveyitemid = $surveyitem->get('id');
        $record = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $surveyitem->get('id')]);
        if ($record) {
            foreach ($formdata as $key => $value) {
                $record->$key = $value;
            }
            $DB->update_record('block_coursefeedback_surveyitemscalequestion', $record);
        } else {
            $DB->insert_record('block_coursefeedback_surveyitemscalequestion', $formdata);
        }
    }

    #[\Override]
    public function load_settings_form_data(surveyitem $surveyitem): object {
        global $DB;
        $data = parent::load_settings_form_data($surveyitem);
        $record = $DB->get_record('block_coursefeedback_surveyitemscalequestion', ['surveyitemid' => $surveyitem->get('id')]);
        if ($record) {
            $data->scaleid = $record->scaleid;
            $data->forceshowscale = $record->forceshowscale;
        }
        return $data;
    }

    #[\Override]
    public function load_additional_data_for(array $surveyitems): array {
        global $DB;
        $additionaldata = parent::load_additional_data_for($surveyitems);

        $surveyitemids = array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems);
        $scale_fields = scale::get_sql_fields('s', 's_');
        [$insql, $params] = $DB->get_in_or_equal($surveyitemids);
        $records = $DB->get_records_sql(
            "SELECT sq.surveyitemid, sq.*, $scale_fields FROM {block_coursefeedback_surveyitemscalequestion} sq " .
            'JOIN {block_coursefeedback_scale} s ON sq.scaleid = s.id ' .
            "WHERE sq.surveyitemid $insql",
            $params
        );
        foreach ($records as $record) {
            $additionaldata[$record->surveyitemid] = (object) [
                ...array_filter((array) $record, fn($key) => !str_starts_with($key, 's_'), ARRAY_FILTER_USE_KEY),
                'scale' => new scale(record: scale::extract_record($record, 's_')),
            ];
        }
        return $additionaldata;
    }

    #[\Override]
    public function export_for_template(array $surveyitems, array $additional_data): array {
        $structure = parent::export_for_template($surveyitems, $additional_data);

        $lastsurveyitem = null;

        foreach ($surveyitems as $surveyitem) {
            $record = $additional_data[$surveyitem->get('id')];
            $hasnaoption = (bool) $record->scale->get('hasnoansweroption');
            $optionamount = $record->scale->get('optionamount');
            $show_scale = $record->forceshowscale ||
                !$lastsurveyitem ||
                $additional_data[$lastsurveyitem->get('id')]->scaleid != $record->scaleid ||
                $lastsurveyitem->get('sortindex') !== $surveyitem->get('sortindex') - 1;

            $structure[$surveyitem->get('id')] += [
                'max_pole' => $record->scale->get('maxoptiontext')->translate(),
                'min_pole' => $record->scale->get('minoptiontext')->translate(),
                'has_na_option' => $hasnaoption,
                'na_option' => $hasnaoption ? $record->scale->get('noansweroptiontext')->translate() : null,
                'optionamount' => $optionamount,
                'options' => [],
                'show_scale' => $show_scale,
            ];

            for ($i = 1; $i <= $optionamount; $i++) {
                $text = null;
                if ($i == 1 || $i == $optionamount) {
                    $text = $record->scale->get($i == 1 ? 'minoptiontext' : 'maxoptiontext')->translate();
                }

                $structure[$surveyitem->get('id')]['options'][] = [
                    'id' => $i,
                    'text' => $text,
                ];
            }

            $centeroptiontext = $record->scale->get('centeroptiontext');
            if ($centeroptiontext && $optionamount > 2 && $optionamount % 2 === 1) {
                // The center option is ignored when the number of options is even.
                $structure[$surveyitem->get('id')]['options'][($optionamount - 1) / 2]['text'] = $centeroptiontext->translate();
            }

            $lastsurveyitem = $surveyitem;
        }

        return $structure;
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            $metadata = $answer->additionaldata;
            if (
                !is_number($answer->value) ||
                $answer->value == 0 && !$metadata->scale->get('hasnoansweroption') ||
                $answer->value < 0 ||
                $answer->value > $metadata->scale->get('optionamount')
            ) {
                throw new moodle_exception('invalid_answer', 'block_coursefeedback', a: json_encode($answer->value));
            }
            $to_insert[] = [
                'surveypartexecutionoptionresponseid' => $answer->response_set_id,
                'surveyitemid' => $answer->surveyitem_id,
                'value' => $answer->value,
            ];
        }
        $DB->insert_records('block_coursefeedback_surveyitemintresponse', $to_insert);
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
            // Do not use n/a-answers for statistics calculations.
            $only_the_important_responses = $responses[$surveyitemid] ?? [];
            unset($only_the_important_responses[0]);
            $response_stats = $this->calculate_statistic_properties($only_the_important_responses);
            if ($response_stats['n'] < get_config('block_coursefeedback', 'report_min_responses_per_item')) {
                $surveyitemdata['not_enough_responses'] = true;
                continue;
            }

            foreach ($surveyitemdata['options'] as &$optiondata) {
                $optiondata['responses'] = $responses[$surveyitemid][$optiondata['id']] ?? 0;
            }

            $surveyitemdata['response_stats'] = $response_stats;
            $surveyitemdata['chartdata'] = json_encode($surveyitemdata, JSON_HEX_APOS | JSON_HEX_QUOT);
        }

        return $template_data;
    }

    #[\Override]
    public function backup_items(array $surveyitems): array {
        $backup_data = parent::backup_items($surveyitems);

        global $DB;
        $surveyitemids = array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems);
        [$insql, $params] = $DB->get_in_or_equal($surveyitemids);
        $records = $DB->get_records_sql("
            SELECT sq.surveyitemid, sq.forceshowscale, s.name AS scale_name
            FROM {block_coursefeedback_surveyitemscalequestion} sq
            JOIN {block_coursefeedback_scale} s ON sq.scaleid = s.id
            WHERE sq.surveyitemid $insql
        ", $params);

        foreach ($records as $record) {
            // TODO: Ensure no duplicate scale names.
            $backup_data[$record->surveyitemid] += [
                'scale_name' => $record->scale_name,
                'forceshowscale' => boolval($record->forceshowscale),
            ];
        }

        return $backup_data;
    }

    #[\Override]
    public function restore_from_backup(array $surveyitems, array $backup_data, array $scales): void {
        parent::restore_from_backup($surveyitems, $backup_data, $scales);

        $records_to_insert = [];
        foreach ($backup_data as $surveyitemid => $data) {
            $scale_name = $data->scale_name ?? null;
            if (!is_string($scale_name)) {
                throw new backup_invalid_exception("missing or invalid 'scale_name'");
            }

            $scales_with_name = array_filter($scales, fn($scale) => $scale->get('name') == $scale_name);
            if (!$scales_with_name) {
                throw new backup_invalid_exception("scale question references scale '$scale_name', which is missing");
            }
            if (count($scales_with_name) > 1) {
                throw new backup_invalid_exception("scale question references scale '$scale_name', of which there are multiple");
            }

            $scale = reset($scales_with_name);

            $records_to_insert[] = [
                'surveyitemid' => $surveyitemid,
                'scaleid' => $scale->get('id'),
                'forceshowscale' => $data->forceshowscale ?? false,
            ];
        }

        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $DB->insert_records('block_coursefeedback_surveyitemscalequestion', $records_to_insert);
        $transaction->allow_commit();
    }
}
