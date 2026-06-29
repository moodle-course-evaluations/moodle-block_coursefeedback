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
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\surveyitemtype_answerdata;
use core\lang_string;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * Survey item types that have settings must extend {@see surveyitemtype_with_settings}.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class surveyitemtype {

    /**
     * Return the name of the survey element type.
     * @return lang_string
     */
    abstract public function get_name(): lang_string;

    /**
     * Overridden to be false for item types that shouldn't be manually addable by the user.
     * @return bool
     */
    public function can_be_added(): bool {
        return true;
    }

    /**
     * Checks and saves a collection of answers to surveyitems of this type.
     *
     * @param surveyitemtype_answerdata[] $answers An array of surveyitemtype_answerdata objects
     * where respsetid is the id to save the answer under,
     * additionaldata contains data as given by {@see self::load_additional_data_for}
     * and value is the answer as given by the client
     * @return void Return nothing, throw error if necessary.
     */
    abstract public function check_and_save_answers($answers): void;

    /**
     * Load more data for the surveyitems, works in tandem with {@see self::export_for_template}.
     *
     * @param surveyitem[] $surveyitems all surveyitems to load questiondata for, all of this surveyitemtype.
     * @return array<int, mixed> An associative array with surveyitemids as keys, and arbitrary data as value, which will get
     *                           passed into {@see self::export_for_template}.
     */
    public function load_additional_data_for(array $surveyitems): array {
        // Return an empty array for all surveyitems.
        return array_fill_keys(array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems), []);
    }

    /**
     * Create template data from requested $texts and given $additionaldata by {@see self::load_additional_data_for}
     *
     * @param surveyitem[] $surveyitems all surveyitems to load questiondata for, all of this surveyitemtype.
     * @param array $additional_data Array from {@see self::load_additional_data_for}.
     * @return array associative array of surveyitemid => templatedata for surveyitemid.
     */
    public function export_for_template(array $surveyitems, array $additional_data): array {
        $template_data = [];
        foreach ($surveyitems as $surveyitem) {
            $surveyitemid = $surveyitem->get('id');

            $template_data[$surveyitemid] = [
                'type_' . $surveyitem->get('surveyitemtype') => true,
                'type' => $surveyitem->get('surveyitemtype'),
                'surveyitemid' => $surveyitemid,
                'questiontext' => $surveyitem->maybe_format_text(),
            ];
        }
        return $template_data;
    }

    /**
     * Return template data for rendering report for given $response_slot.
     * @param response_slot $response_slot
     * @param surveyitem[] $surveyitemsoftype
     * @param array<int, mixed> $additional_data
     * @return array<int, mixed>
     */
    public function load_and_export_report_data(
        response_slot $response_slot,
        array $surveyitemsoftype,
        array $additional_data
    ): array {
        return [];
    }

    /**
     * Calculate the statistic properties mean, median and stddev for the given aggregated counts.
     * @param array $counts
     * @return array
     */
    protected function calculate_statistic_properties(array $counts) {
        ksort($counts);
        // Mean.
        $n = 0;
        $sum = 0;
        foreach ($counts as $value => $count) {
            $n += $count;
            $sum += $count * $value;
        }

        if ($n == 0) {
            return [
                'n' => $n,
                'mean' => "-",
                'mean_rounded' => "-",
                'stddev' => "-",
                'stddev_rounded' => "-",
                'median' => "-",
                'median_rounded' => "-",
            ];
        }

        $mean = $sum / $n;

        // Stddev.
        if ($n == 1) {
            $stddev = 0;
        } else {
            $variance_sum = 0;
            foreach ($counts as $value => $count) {
                $variance_sum += pow($value - $mean, 2) * $count;
            }
            $stddev = sqrt($variance_sum / ($n - 1));
        }

        // Median.
        $acc = 0;
        $target = intdiv($n + 1, 2);
        $average_with_next_value = null;
        foreach ($counts as $value => $count) {
            if ($average_with_next_value !== null) {
                $median = ($average_with_next_value + $value) / 2;
                break;
            }
            $acc += $count;
            if ($acc > $target) {
                $median = $value;
                break;
            } else if ($acc == $target) {
                if ($n % 2 == 0) {
                    $average_with_next_value = $value;
                } else {
                    $median = $value;
                    break;
                }
            }
        }
        return [
            'n' => $n,
            'mean' => $mean,
            'mean_rounded' => round($mean, 2),
            'stddev' => $stddev,
            'stddev_rounded' => round($stddev, 2),
            'median' => $median,
            'median_rounded' => round($median, 2),
        ];
    }

    /**
     * Called when restoring survey item(s) of this type from a backup, after the {@see surveyitem}s have been created.
     *
     * This method should restore the relevant child table records, if any.
     *
     * @param surveyitem[] $surveyitems
     * @param array<int, array> $backup_data The data returned by {@see self::backup_items}.
     * @param scale[] $scales The scales that were contained in the backup. These have already been restored, so they have IDs.
     * @return void
     * @throws backup_invalid_exception If the backup data is invalid.
     */
    public function restore_from_backup(array $surveyitems, array $backup_data, array $scales): void {
        // Intentionally no-op by default.
    }

    /**
     * Back up the item type specific data for the given survey items.
     *
     * @param surveyitem[] $surveyitems
     * @return array<int, array>
     */
    public function backup_items(array $surveyitems): array {
        // Return an empty array for all surveyitems.
        return array_fill_keys(array_map(fn($surveyitem) => $surveyitem->get('id'), $surveyitems), []);
    }
}
