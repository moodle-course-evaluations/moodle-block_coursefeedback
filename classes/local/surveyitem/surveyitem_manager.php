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

use block_coursefeedback\local\persistent\response_slot;
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
use core\di;
use core\exception\coding_exception;
use html_writer;

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
    public static function get_surveyitems_for_surveyparts(array $surveyparts): array {
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
    public static function group_surveyitems_by_type(array $surveyitemsets): array {
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
}
