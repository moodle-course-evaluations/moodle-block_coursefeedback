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

namespace block_coursefeedback\local\backup;

use block_coursefeedback\local\array_converter\array_converter;
use block_coursefeedback\local\array_converter\conversion_exception;
use block_coursefeedback\local\persistent\scale;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use core\clock;
use core\exception\coding_exception;
use JsonException;

/**
 * Backs up and restores survey parts a.k.a. questionnaires.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_manager {

    /**
     * Constructor.
     *
     * @param clock $clock
     */
    public function __construct(
        /** @var clock */
        private readonly clock $clock
    ) {
    }

    /**
     * Restores surveyitems from the given backups.
     *
     * @param survey_item_backup[] $item_backups
     * @param surveypart $surveypart
     * @param scale[] $scales
     * @return void
     */
    public function restore_surveyitems(array $item_backups, surveypart $surveypart, array $scales): void {
        $items_and_data_by_type = [];

        foreach (array_values($item_backups) as $i => $item_backup) {
            if (!empty($item_backup->text)) {
                $text = $item_backup->text;
                // If there's text, require a textformat.
                $text_format = $item_backup->textformat
                    ?: throw new backup_invalid_exception("survey item at index $i is missing a 'textformat'");
                if (!in_array($text_format, [FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, FORMAT_MARKDOWN])) {
                    throw new backup_invalid_exception("survey item at index $i has an unknown 'textformat': $text_format");
                }
            } else {
                $text = $text_format = null;
            }

            $surveyitem = new surveyitem();
            $surveyitem->set_many([
                'surveypartid' => $surveypart->get('id'),
                'surveyitemtype' => $item_backup->type,
                'sortindex' => $i,
                'text' => $text,
                'textformat' => $text_format,
            ]);

            $surveyitem->create();
            $items_and_data_by_type[$item_backup->type][] = [$surveyitem, $item_backup->backup_data];
        }

        foreach ($items_and_data_by_type as $surveyitemtype => $items_and_data) {
            try {
                $type_impl = surveyitem_manager::get_surveyitemtype($surveyitemtype);
            } catch (coding_exception) {
                throw new backup_invalid_exception("the survey item type '$surveyitemtype' is not supported");
            }

            $backup_data_by_id = [];
            foreach ($items_and_data as [$surveyitem, $backup_data]) {
                $backup_data_by_id[$surveyitem->get('id')] = $backup_data;
            }

            $type_impl->restore_from_backup(array_column($items_and_data, 0), $backup_data_by_id, scales: $scales);
        }
    }

    /**
     * Restores the surveypart from the given backup content.
     *
     * @param string $backup_content
     * @return surveypart
     */
    public function restore_surveypart(string $backup_content): surveypart {
        try {
            $json = json_decode(trim($backup_content), associative: true, depth: 10, flags: JSON_THROW_ON_ERROR);
            /** @var questionnaire_backup $backup */
            $backup = array_converter::from_array(questionnaire_backup::class, $json);
        } catch (JsonException | conversion_exception $e) {
            throw new backup_invalid_exception($e->getMessage());
        }

        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $backup_timestamp = $backup->backup_time->getTimestamp();
        $restore_timestamp = time();

        $name = $backup->name;
        $name .= " (" . get_string('backup_from_restored_at', 'block_coursefeedback', [
                'backup_time' => userdate($backup_timestamp, format: get_string('strftimedatetimeshortaccurate', 'langconfig')),
                'restore_time' => userdate($restore_timestamp, format: get_string('strftimedatetimeshortaccurate', 'langconfig')),
            ]) . ")";

        $surveypart = new surveypart();
        $surveypart->set('name', $name);
        $surveypart->create();

        $installed_langs = get_string_manager()->get_list_of_translations();
        $languages = array_filter(array_values($backup->languages), function ($lang) use ($installed_langs) {
            if (!isset($installed_langs[$lang])) {
                mtrace(
                    "The backed-up survey part supports '$lang', but that translation is not installed. " .
                    "Its translations will be imported but not shown until the appropriate language pack is installed and enabled."
                );
                return false;
            }
            return true;
        });
        $surveypart->set_languages($languages);

        $scales = self::restore_scales($backup->scales, $surveypart);

        self::restore_surveyitems($backup->items, $surveypart, $scales);

        $transaction->allow_commit();

        return $surveypart;
    }

    /**
     * Restores scales from the given backups and returns the created objects.
     *
     * @param scale_backup[] $scale_backups
     * @param surveypart $surveypart
     * @return array
     */
    public function restore_scales(array $scale_backups, surveypart $surveypart): array {
        $scales = [];
        foreach ($scale_backups as $scale_backup) {
            $scale = new scale();
            $scale->set_many([
                ...get_object_vars($scale_backup),
                'id' => 0,
                'surveypartid' => $surveypart->get('id'),
            ]);

            $scales[] = $scale->create();
        }
        return $scales;
    }

    /**
     * Backs up the given survey part, including all scales and items.
     *
     * @param surveypart $surveypart
     * @param bool $pretty Pretty-print the JSON.
     * @return string
     */
    public function backup_surveypart(surveypart $surveypart, bool $pretty = false): string {
        $backup_time = $this->clock->now();

        $scales = scale::get_records(['surveypartid' => $surveypart->get('id')]);
        $scale_backups = array_map(fn($scale) => new scale_backup(
            name: $scale->get('name'),
            optionamount: intval($scale->get('optionamount')),
            minoptiontext: $scale->get('minoptiontext')->serialize(),
            maxoptiontext: $scale->get('maxoptiontext')->serialize(),
            hasnoansweroption: boolval($scale->get('hasnoansweroption')),
            noansweroptiontext: $scale->get('noansweroptiontext')?->serialize(),
            centeroptiontext: $scale->get('centeroptiontext')?->serialize(),
        ), $scales);

        $surveyitems_by_partid = surveyitem_manager::get_surveyitems_for_surveyparts([$surveypart]);
        $surveyitems_by_type = surveyitem_manager::group_surveyitems_by_type($surveyitems_by_partid);
        $surveyitem_backups = [];
        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitems) {
            $type_impl = surveyitem_manager::get_surveyitemtype($surveyitemtype);

            $backup_data = $type_impl->backup_items($surveyitems);

            foreach ($surveyitems as $surveyitem) {
                $surveyitem_backups[$surveyitem->get('sortindex')] = new survey_item_backup(
                    type: $surveyitemtype,
                    text: $surveyitem->get('text')?->serialize(),
                    textformat: $surveyitem->get('textformat'),
                    backup_data: $backup_data[$surveyitem->get('id')] ?? null,
                );
            }
        }

        // Honor sortindex and make sure the items are a list in case of non-sequential 'sortindex'es.
        ksort($surveyitem_backups);
        $surveyitem_backups = array_values($surveyitem_backups);

        $backup = new questionnaire_backup(
            name: $surveypart->get('name'),
            languages: $surveypart->get_languages(),
            backup_time: $backup_time,
            scales: $scale_backups,
            items: $surveyitem_backups,
        );

        return json_encode(
            array_converter::to_array($backup),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0)
        );
    }
}
