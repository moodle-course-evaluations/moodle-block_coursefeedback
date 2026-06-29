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
 * Surveyitem manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\surveyitem;

use block_coursefeedback\local\backup\backup_invalid_exception;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\scale;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use block_coursefeedback\local\survey_page;
use block_coursefeedback\local\surveyitem\dropdown\dropdown;
use block_coursefeedback\local\surveyitem\emoji\emoji;
use block_coursefeedback\local\surveyitem\info\info;
use block_coursefeedback\local\surveyitem\multiplechoice\multiplechoice;
use block_coursefeedback\local\surveyitem\pagebreak\pagebreak;
use block_coursefeedback\local\surveyitem\scalequestion\scalequestion;
use block_coursefeedback\local\surveyitem\singlechoice\singlechoice;
use block_coursefeedback\local\surveyitem\slot_choice\slot_choice;
use block_coursefeedback\local\surveyitem\text\text;
use core\clock;
use core\di;
use core\exception\coding_exception;
use DateTimeImmutable;
use DateTimeInterface;
use html_writer;
use JsonException;

/**
 * Surveyitem manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class surveyitem_manager {

    /**
     * Returns an associative array of all surveyitemtypes.
     *
     * @return array
     */
    public static function get_all_surveyitemtypes(): array {
        static $surveyitemtypes = [
            'dropdown' => new dropdown(),
            'singlechoice' => new singlechoice(),
            'multiplechoice' => new multiplechoice(),
            'text' => new text(),
            'pagebreak' => new pagebreak(),
            'scalequestion' => new scalequestion(),
            'emoji' => new emoji(),
            'info' => new info(),
            'slot_choice' => new slot_choice(),
        ];
        return $surveyitemtypes;
    }

    /**
     * Returns one surveyitemtype for a identifier.
     *
     * @param string $type
     * @return surveyitemtype
     */
    public static function get_surveyitemtype(string $type): surveyitemtype {
        if (!isset(self::get_all_surveyitemtypes()[$type])) {
            throw new coding_exception('Survey element type ' . $type . ' not found.');
        }
        return self::get_all_surveyitemtypes()[$type];
    }

    /**
     * Fetches the surveyitems for the surveyparts.
     *
     * @param surveypart[] $surveyparts
     * @return array<int, surveyitem[]> [int $surveypartid => [surveyitem $surveyitem]]
     */
    private static function get_surveyitems_for_surveyparts(array $surveyparts): array {
        $all_items = surveyitem::get_records_list(
            'surveypartid',
            array_map(fn($sp) => $sp->get('id'), $surveyparts),
            sort: 'sortindex ASC'
        );

        $items_by_surveypartid = [];
        foreach ($all_items as $item) {
            $items_by_surveypartid[$item->get('surveypartid')][] = $item;
        }

        return $items_by_surveypartid;
    }

    /**
     * Groups the sets of surveyitems by surveyitemtype.
     *
     * @param surveyitem[][] $surveyitemsets
     * @return array<string, surveyitem[]> [string $surveyitemtype => [surveyitem $surveyitem]]
     */
    private static function group_surveyitems_by_type(array $surveyitemsets): array {
        $surveyitems_by_type = [];
        foreach ($surveyitemsets as $surveyitemset) {
            foreach ($surveyitemset as $surveyitem) {
                $surveyitemtype = $surveyitem->get('surveyitemtype');
                if (!isset($surveyitems_by_type[$surveyitemtype])) {
                    $surveyitems_by_type[$surveyitemtype] = [];
                }
                $surveyitems_by_type[$surveyitemtype][] = $surveyitem;
            }
        }
        return $surveyitems_by_type;
    }

    /**
     * Gets the pages for a specific surveypart.
     *
     * @param survey_part_execution $spe
     * @param response_slot[] $slots
     * @param surveyitem[] $surveyitems
     * @param array<int, array> $template_data_by_item_id
     * @param teaching_event|null $event
     * @return survey_page[]
     */
    private static function get_pages_for_surveypart(
        survey_part_execution $spe,
        array $slots,
        array $surveyitems,
        array $template_data_by_item_id,
        ?teaching_event $event = null,
    ): array {
        /** @var survey_page[] $pages */
        $pages = [];
        $current_page_items = [];

        if ($event && $event->get('name') && $event->get('eventtypeid')) {
            // If the SPE belongs to an event that isn't auto-created and has a name, we show it in a small heading.
            $heading = html_writer::tag(
                'h6',
                get_string('event_intro', 'block_coursefeedback', s($event->get('name')))
            );
        } else {
            $heading = html_writer::tag(
                'h6',
                get_string('event_intro_without_name', 'block_coursefeedback')
            );
        }

        $advance_page = function () use (&$pages, &$current_page_items, $spe, $heading): void {
            if ($current_page_items) {
                $pages[] = new survey_page(
                    items: $current_page_items,
                    spe_id: $spe->get('id'),
                );
            }

            $current_page_items = $heading ? [di::get(info::class)->export_auto_created($heading)] : [];
        };

        $advance_page();

        // If the SPE has more than one slot but the questionnaire no slot choice item, auto-create one.
        $slot_choice_items = array_filter($surveyitems, fn($item) => $item->get('surveyitemtype') === 'slot_choice');
        if (!$slot_choice_items && count($slots) > 1) {
            $current_page_items[] = di::get(slot_choice::class)->export_auto_created($spe, $slots);
            $advance_page();
        }

        foreach ($surveyitems as $surveyitem) {
            $surveyitemtype = $surveyitem->get('surveyitemtype');
            if ($surveyitemtype === 'pagebreak') {
                $advance_page();
            } else {
                $current_page_items[] = $template_data_by_item_id[$surveyitem->get('id')];
            }
        }

        $advance_page();
        return $pages;
    }

    /**
     * Constructs the template data for an entire survey consisting of one or more SPEs.
     *
     * TODO: Show a survey in a different language without having to mutate the session.
     *
     * @param surveypart[] $surveyparts
     * @param survey_part_execution[] $spes
     * @param array<int, teaching_event> $events_by_spe_id
     * @param array<int, response_slot[]> $slots_by_spe_id
     * @return survey_page[] template data: array of pages
     */
    public static function export_pages_for_survey(
        array $surveyparts,
        array $spes,
        array $events_by_spe_id,
        array $slots_by_spe_id
    ): array {
        $surveyitems_by_partid = self::get_surveyitems_for_surveyparts($surveyparts);
        $surveyitems_by_type = self::group_surveyitems_by_type($surveyitems_by_partid);

        $template_data_by_item_id = [];
        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitemsoftype) {
            $surveyitemtype = self::get_surveyitemtype($surveyitemtype);

            $additionaldata = $surveyitemtype->load_additional_data_for($surveyitemsoftype);

            $template_data_by_item_id += $surveyitemtype->export_for_template($surveyitemsoftype, $additionaldata);
        }

        $pages = [];
        foreach ($spes as $spe) {
            $surveyitems = $surveyitems_by_partid[$spe->get('surveypartid')] ?? [];
            $slots = $slots_by_spe_id[$spe->get('id')];
            $event = $events_by_spe_id[$spe->get('id')] ?? null;
            $pages = array_merge(
                $pages,
                static::get_pages_for_surveypart($spe, $slots, $surveyitems, $template_data_by_item_id, $event)
            );
        }

        return $pages;
    }

    /**
     * Gets the questiondata ('additionaldata') for all surveyitems in the given surveyparts.
     *
     * @param surveypart[] $surveyparts
     * @return array<string, array<int, mixed>> [string $surveyitemtype => [int $surveyitemid => mixed $data]]
     * @throws coding_exception
     */
    public static function get_questiondata_for_surveyparts(array $surveyparts): array {
        $surveyitems = self::get_surveyitems_for_surveyparts($surveyparts);
        $surveyitems_by_type = self::group_surveyitems_by_type($surveyitems);
        $alladditionaldata = [];

        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitemsoftype) {
            $additionaldata = self::get_surveyitemtype($surveyitemtype)
                ->load_additional_data_for($surveyitemsoftype);
            $alladditionaldata[$surveyitemtype] = $additionaldata;
        }

        return $alladditionaldata;
    }

    /**
     * Get the template data for rendering the report for the given {@see response_slot}.
     * @param response_slot $response_slot
     * @return ?array
     */
    public static function export_report_for_slot(response_slot $response_slot): ?array {
        global $DB;

        $number_of_responses = $DB->count_records(
            'block_coursefeedback_surveypartexecutionoptionresp',
            ['surveypartexecutionoptionid' => $response_slot->get('id')]
        );

        if ($number_of_responses < get_config('block_coursefeedback', 'report_min_responses_overall')) {
            return null;
        }

        $surveypartexecution = survey_part_execution::get_record(
            ['id' => $response_slot->get('surveypartexecutionid')],
            MUST_EXIST
        );
        $surveypart = surveypart::get_record(['id' => $surveypartexecution->get('surveypartid')], MUST_EXIST);
        $surveyitems = $surveypart->get_surveyitems();

        $surveyitems_by_type = self::group_surveyitems_by_type([$surveyitems]);
        $allresponsedata = [];
        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitemsoftype) {
            $additionaldata = self::get_surveyitemtype($surveyitemtype)
                ->load_additional_data_for($surveyitemsoftype);

            $allresponsedata[$surveyitemtype] = self::get_surveyitemtype($surveyitemtype)
                ->load_and_export_report_data($response_slot, $surveyitemsoftype, $additionaldata);
        }

        $return = [];
        foreach ($surveyitems as $surveyitem) {
            if (isset($allresponsedata[$surveyitem->get('surveyitemtype')][$surveyitem->get('id')])) {
                $return[] = $allresponsedata[$surveyitem->get('surveyitemtype')][$surveyitem->get('id')];
            }
        }

        return $return;
    }

    /**
     * Fetch aggregated int responses for the given reponse_slot.
     * @param response_slot $response_slot
     * @return array
     */
    protected static function fetch_aggregated_int_responses(response_slot $response_slot): array {
        global $DB;

        // Use record set to avoid having a unique first column.
        $recordset = $DB->get_recordset_sql(
            "SELECT siir.surveyitemid, siir.value, count(siir.id) as count
            FROM {block_coursefeedback_surveyitemintresponse} siir
            JOIN {block_coursefeedback_surveypartexecutionoptionresp} speor
                ON siir.surveypartexecutionoptionresponseid = speor.id
            WHERE speor.surveypartexecutionoptionid = :slotid
            GROUP BY siir.value, siir.surveyitemid",
            ['slotid' => $response_slot->get('id')]
        );

        $responses = [];

        foreach ($recordset as $record) {
            $responses[$record->surveyitemid] ??= [];
            $responses[$record->surveyitemid][$record->value] = (int) $record->count;
        }

        $recordset->close();

        return $responses;
    }

    /**
     * Get (statically cached) aggregated int responses for the given response_slot.
     * @param response_slot $response_slot
     * @return array
     */
    public static function get_aggregated_int_responses(response_slot $response_slot): array {
        static $cache = [];

        if (!isset($cache[$response_slot->get('id')])) {
            $cache[$response_slot->get('id')] = self::fetch_aggregated_int_responses($response_slot);
        }

        return $cache[$response_slot->get('id')];
    }

    /**
     * Backs up the given survey part, including all scales and items.
     *
     * @param surveypart $surveypart
     * @param bool $pretty Pretty-print the JSON.
     * @return string
     */
    public static function backup_surveypart(surveypart $surveypart, bool $pretty = false): string {
        $backup_time = di::get(clock::class)->now()->format(DateTimeInterface::ATOM);

        $surveyitems_by_partid = self::get_surveyitems_for_surveyparts([$surveypart]);
        $surveyitems_by_type = self::group_surveyitems_by_type($surveyitems_by_partid);

        $json = [
            'name' => $surveypart->get('name'),
            'languages' => $surveypart->get_languages(),
            'backup_time' => $backup_time,
            'scales' => [],
            'items' => [],
        ];

        $scales = scale::get_records(['surveypartid' => $surveypart->get('id')]);
        foreach ($scales as $scale) {
            $scale_record = $scale->to_record();
            unset($scale_record->id);
            unset($scale_record->surveypartid);
            $json['scales'][] = [
                'name' => $scale->get('name'),
                'optionamount' => intval($scale->get('optionamount')),
                'minoptiontext' => $scale->get('minoptiontext')->serialize(),
                'maxoptiontext' => $scale->get('maxoptiontext')->serialize(),
                'hasnoansweroption' => boolval($scale->get('hasnoansweroption')),
                'noansweroptiontext' => $scale->get('noansweroptiontext')?->serialize(),
                'centeroptiontext' => $scale->get('centeroptiontext')?->serialize(),
            ];
        }

        foreach ($surveyitems_by_type as $surveyitemtype => $surveyitems) {
            $type_impl = self::get_surveyitemtype($surveyitemtype);

            $backup_data = $type_impl->backup_items($surveyitems);

            foreach ($surveyitems as $surveyitem) {
                $json['items'][$surveyitem->get('sortindex')] = [
                    'type' => $surveyitemtype,
                    'text' => $surveyitem->get('text')?->serialize(),
                    'textformat' => $surveyitem->get('textformat'),
                    'backup_data' => $backup_data[$surveyitem->get('id')] ?? null,
                ];
            }
        }

        // Make sure the items are a list in case of non-sequential 'sortindex'es.
        $json['items'] = array_values($json['items']);

        return json_encode(
            $json,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0)
        );
    }

    /**
     * Restores the surveypart from the given backup content.
     *
     * @param string $backup_content
     * @return surveypart
     */
    public static function restore_surveypart(string $backup_content): surveypart {
        try {
            $json = json_decode($backup_content, depth: 10, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new backup_invalid_exception($e->getMessage());
        }

        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $backup_datetime = DateTimeImmutable::createFromFormat(
            DateTimeInterface::ATOM,
            $json->backup_time ?: throw new backup_invalid_exception("missing 'backup_time'")
        ) ?: throw new backup_invalid_exception("invalid 'backup_time'");
        $backup_timestamp = $backup_datetime->getTimestamp();
        $restore_timestamp = time();

        $name = $json->name ?: throw new backup_invalid_exception("missing 'name'");
        $name .= " (" . get_string('backup_from_restored_at', 'block_coursefeedback', [
                'backup_time' => userdate($backup_timestamp, format: get_string('strftimedatetimeshortaccurate', 'langconfig')),
                'restore_time' => userdate($restore_timestamp, format: get_string('strftimedatetimeshortaccurate', 'langconfig')),
            ]) . ")";

        $surveypart = new surveypart();
        $surveypart->set('name', $name);
        $surveypart->create();

        if (!is_array($json->languages) || !array_is_list($json->languages)) {
            throw new backup_invalid_exception("'languages' is not a list");
        }
        $installed_langs = get_string_manager()->get_list_of_translations();
        $languages = array_filter($json->languages, function ($lang) use ($installed_langs) {
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

        $scale_backups = $json->scales ?? [];
        if (!is_array($scale_backups) || !array_is_list($scale_backups)) {
            throw new backup_invalid_exception("'scales' is not a list");
        }
        $item_backups = $json->items ?? [];
        if (!is_array($item_backups) || !array_is_list($item_backups)) {
            throw new backup_invalid_exception("'items' is not a list");
        }

        $scales = self::restore_scales($scale_backups, $surveypart);

        self::restore_surveyitems($item_backups, $surveypart, $scales);

        $transaction->allow_commit();

        return $surveypart;
    }

    /**
     * Restores surveyitems from the given backups.
     *
     * @param array $item_backups
     * @param surveypart $surveypart
     * @param array $scales
     * @return void
     */
    private static function restore_surveyitems(array $item_backups, surveypart $surveypart, array $scales): void {
        $items_and_data_by_type = [];

        foreach ($item_backups as $i => $item_backup) {
            if (!is_object($item_backup)) {
                throw new backup_invalid_exception("survey item at index $i is not an object");
            }

            $surveyitemtype = $item_backup->type ?: throw new backup_invalid_exception("survey item at index $i is missing 'type'");

            if (!empty($item_backup->text)) {
                $text = $item_backup->text;
                // If there's text, require a textformat.
                $text_format = $item_backup->textformat
                    ?: throw new backup_invalid_exception("survey item at index $i is missing 'textformat'");
            } else {
                $text = $text_format = null;
            }

            $surveyitem = new surveyitem();
            $surveyitem->set_many([
                'surveypartid' => $surveypart->get('id'),
                'surveyitemtype' => $surveyitemtype,
                'sortindex' => $i,
                'text' => $text,
                'textformat' => $text_format,
            ]);

            $backup_data = $item_backup->backup_data ?? null;

            $surveyitem->create();
            $items_and_data_by_type[$surveyitemtype][] = [$surveyitem, $backup_data];
        }

        foreach ($items_and_data_by_type as $surveyitemtype => $items_and_data) {
            try {
                $type_impl = self::get_surveyitemtype($surveyitemtype);
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
     * Restores scales from the given backups and returns the created objects.
     *
     * @param array $scale_backups
     * @param surveypart $surveypart
     * @return array
     */
    public static function restore_scales(array $scale_backups, surveypart $surveypart): array {
        $scales = [];
        foreach ($scale_backups as $i => $scale_backup) {
            if (!is_object($scale_backup)) {
                throw new backup_invalid_exception("scale at index $i is not an object");
            }

            $scale = new scale();
            $scale->set_many([
                ...((array) $scale_backup),
                'id' => 0,
                'surveypartid' => $surveypart->get('id'),
            ]);

            $scales[] = $scale->create();
        }
        return $scales;
    }
}
