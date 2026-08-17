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

namespace block_coursefeedback\local\surveyitem\emoji;

use block_coursefeedback\local\backup\backup_invalid_exception;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\lang_string;
use moodle_exception;

/**
 * A survey item showing multiple emojis for students to "pick their mood" from.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class emoji extends surveyitemtype_with_settings {

    /**
     * Returns the available emoji scale variants.
     *
     * We may want to make this user-configurable at some point, but hardcoded is fine for now.
     *
     * @return array<string, array{name: string, choices: array{emoji: string, text: string, value: int}[]}>
     */
    public static function get_available_variants(): array {
        return [
            // 1 through 3: Good, neutral, bad.
            '1_through_3' => [
                'name' => get_string('emoji_variant_1_through_3', 'block_coursefeedback'),
                'choices' => [
                    [
                        'emoji' => '😀',
                        'text' => get_string('emoji_choice_1_of_3', 'block_coursefeedback'),
                        'value' => 1,
                    ],
                    [
                        'emoji' => '😐',
                        'text' => get_string('emoji_choice_2_of_3', 'block_coursefeedback'),
                        'value' => 2,
                    ],
                    [
                        'emoji' => '☹️',
                        'text' => get_string('emoji_choice_3_of_3', 'block_coursefeedback'),
                        'value' => 3,
                    ],
                ],
            ],
            // 1 through 4: Very good, somewhat good, somewhat bad, bad.
            '1_through_4' => [
                'name' => get_string('emoji_variant_1_through_4', 'block_coursefeedback'),
                'choices' => [
                    [
                        'emoji' => '😀',
                        'text' => get_string('emoji_choice_1_of_4', 'block_coursefeedback'),
                        'value' => 1,
                    ],
                    [
                        'emoji' => '🙂',
                        'text' => get_string('emoji_choice_2_of_4', 'block_coursefeedback'),
                        'value' => 2,
                    ],
                    [
                        'emoji' => '😕',
                        'text' => get_string('emoji_choice_3_of_4', 'block_coursefeedback'),
                        'value' => 3,
                    ],
                    [
                        'emoji' => '😠',
                        'text' => get_string('emoji_choice_4_of_4', 'block_coursefeedback'),
                        'value' => 4,
                    ],
                ],
            ],
            // 1 through 5: Very good, somewhat good, neutral, somewhat bad, bad.
            '1_through_5' => [
                'name' => get_string('emoji_variant_1_through_5', 'block_coursefeedback'),
                'choices' => [
                    [
                        'emoji' => '😀',
                        'text' => get_string('emoji_choice_1_of_5', 'block_coursefeedback'),
                        'value' => 1,
                    ],
                    [
                        'emoji' => '🙂',
                        'text' => get_string('emoji_choice_2_of_5', 'block_coursefeedback'),
                        'value' => 2,
                    ],
                    [
                        'emoji' => '😐',
                        'text' => get_string('emoji_choice_3_of_5', 'block_coursefeedback'),
                        'value' => 3,
                    ],
                    [
                        'emoji' => '😕',
                        'text' => get_string('emoji_choice_4_of_5', 'block_coursefeedback'),
                        'value' => 4,
                    ],
                    [
                        'emoji' => '😠',
                        'text' => get_string('emoji_choice_5_of_5', 'block_coursefeedback'),
                        'value' => 5,
                    ],
                ],
            ],
            // 1 through six scale with labels from German school grades.
            'grades_1_through_6' => [
                'name' => get_string('emoji_variant_grades_1_through_6', 'block_coursefeedback'),
                'choices' => [
                    [
                        'emoji' => '😀',
                        'text' => get_string('emoji_choice_1_of_6', 'block_coursefeedback'),
                        'value' => 1,
                    ],
                    [
                        'emoji' => '😊',
                        'text' => get_string('emoji_choice_2_of_6', 'block_coursefeedback'),
                        'value' => 2,
                    ],
                    [
                        'emoji' => '🙂',
                        'text' => get_string('emoji_choice_3_of_6', 'block_coursefeedback'),
                        'value' => 3,
                    ],
                    [
                        'emoji' => '😐',
                        'text' => get_string('emoji_choice_4_of_6', 'block_coursefeedback'),
                        'value' => 4,
                    ],
                    [
                        'emoji' => '😕',
                        'text' => get_string('emoji_choice_5_of_6', 'block_coursefeedback'),
                        'value' => 5,
                    ],
                    [
                        'emoji' => '😠',
                        'text' => get_string('emoji_choice_6_of_6', 'block_coursefeedback'),
                        'value' => 6,
                    ],
                ],
            ],
        ];
    }

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('emoji_surveyitem', 'block_coursefeedback');
    }

    #[\Override]
    public function save_settings_form_data(surveyitem $surveyitem, surveypart $surveypart, object $formdata): void {
        global $DB;
        $existing_record = $DB->get_record('block_coursefeedback_surveyitememojis', [
            'surveyitemid' => $surveyitem->get('id'),
        ]);

        if (empty($formdata->variant) || !array_key_exists($formdata->variant, self::get_available_variants())) {
            throw new moodle_exception("emoji_unknown_variant", 'block_coursefeedback', a: $formdata->variant ?? '(missing)');
        }

        $record = [
            ...((array) $existing_record ?: []),
            'surveyitemid' => $surveyitem->get('id'),
            'variant' => $formdata->variant,
        ];

        if ($existing_record) {
            $DB->update_record('block_coursefeedback_surveyitememojis', $record);
        } else {
            $DB->insert_record('block_coursefeedback_surveyitememojis', $record);
        }
    }

    #[\Override]
    public function load_settings_form_data(surveyitem $surveyitem): object {
        global $DB;
        $formdata = parent::load_settings_form_data($surveyitem);
        $record = $DB->get_record(
            'block_coursefeedback_surveyitememojis',
            ['surveyitemid' => $surveyitem->get('id')],
            strictness: MUST_EXIST
        );
        $formdata->variant = $record->variant;
        return $formdata;
    }

    #[\Override]
    public function load_additional_data_for(array $surveyitems): array {
        global $DB;
        $surveyitemids = array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems);
        $records = $DB->get_records_list(
            'block_coursefeedback_surveyitememojis',
            "surveyitemid",
            $surveyitemids,
        );

        $additionaldata = [];
        foreach ($records as $record) {
            $additionaldata[$record->surveyitemid] = $record;
        }

        return $additionaldata;
    }

    #[\Override]
    public function export_for_template(array $surveyitems, array $additional_data): array {
        $available_variants = self::get_available_variants();
        $template_data = parent::export_for_template($surveyitems, $additional_data);
        foreach ($surveyitems as $surveyitem) {
            $data = $additional_data[$surveyitem->get('id')];
            $template_data[$surveyitem->get('id')]['choices'] = $available_variants[$data->variant]['choices'];
        }
        return $template_data;
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        global $DB;
        $to_insert = [];
        foreach ($answers as $answer) {
            $metadata = $answer->additionaldata;
            $variant = self::get_available_variants()[$metadata->variant] ?? null;
            if (!$variant) {
                throw new moodle_exception("emoji_unknown_variant", 'block_coursefeedback', a: $metadata->variant);
            }
            $possiblevalues = array_column($variant['choices'], 'value');

            if (!is_number($answer->value) || !in_array(intval($answer->value), $possiblevalues, true)) {
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
            $response_stats = $this->calculate_statistic_properties($responses[$surveyitemid] ?? []);
            if ($response_stats['n'] < get_config('block_coursefeedback', 'report_min_responses_per_item')) {
                $surveyitemdata['not_enough_responses'] = true;
                continue;
            }

            foreach ($surveyitemdata['choices'] as &$optiondata) {
                $optiondata['responses'] = $responses[$surveyitemid][$optiondata['value']] ?? 0;
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
        $records = $DB->get_records_list(
            'block_coursefeedback_surveyitememojis',
            'surveyitemid',
            array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems),
        );

        foreach ($records as $record) {
            $backup_data[$record->surveyitemid] += [
                'variant' => $record->variant,
            ];
        }

        return $backup_data;
    }

    #[\Override]
    public function restore_from_backup(array $surveyitems, array $backup_data, array $scales): void {
        parent::restore_from_backup($surveyitems, $backup_data, $scales);

        $records_to_insert = [];
        foreach ($backup_data as $surveyitemid => $data) {
            if (!($variant = $data['variant'] ?? null)) {
                throw new backup_invalid_exception("emoji question variant is missing from backup data");
            }
            if (empty(self::get_available_variants()[$variant])) {
                throw new backup_invalid_exception("emoji question variant '$variant' is not supported");
            }

            $records_to_insert[] = [
                'surveyitemid' => $surveyitemid,
                'variant' => $variant,
            ];
        }

        global $DB;
        $DB->insert_records('block_coursefeedback_surveyitememojis', $records_to_insert);
    }
}
