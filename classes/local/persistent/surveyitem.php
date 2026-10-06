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

namespace block_coursefeedback\local\persistent;

use block_coursefeedback\local\multilang_string;
use core\persistent;

/**
 * A single item of a questionnaire.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class surveyitem extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveyitem';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'surveypartid' => [
                'type' => PARAM_INT,
            ],
            'surveyitemtype' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'sortindex' => [
                'type' => PARAM_INT,
            ],
            'text' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'textformat' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Loads and deserializes 'text'.
     *
     * @return multilang_string|null
     */
    protected function get_text(): ?multilang_string {
        $raw = $this->raw_get('text');
        return $raw === null ? null : multilang_string::deserialize($raw);
    }

    /**
     * Serializes and sets 'text'.
     *
     * @param multilang_string|string|null $text
     */
    protected function set_text(multilang_string|string|null $text): void {
        if (is_string($text)) {
            // Validate it.
            multilang_string::deserialize($text);
        } else {
            $text = $text?->serialize();
        }

        $this->raw_set('text', $text);
    }

    /**
     * If this item has text, translate and format it using the saved `textformat`, or return null otherwise.
     *
     * @return string|null
     */
    public function maybe_format_text(): ?string {
        if ($text = $this->get('text')) {
            return format_text(
                $text->translate(),
                $this->get('textformat') ?? FORMAT_PLAIN
            );
        }
        return null;
    }

    /**
     * Get surveyitem records for the surveypart.
     * @param int $surveypartid
     * @return array
     */
    public static function get_surveyitem_records_for_surveypart(int $surveypartid) {
        global $DB;
        return $DB->get_records_sql(
            'SELECT si.id, si.surveyitemtype, si.sortindex FROM {block_coursefeedback_surveyitem} si ' .
            'WHERE si.surveypartid = :surveypartid ' .
            'ORDER BY si.sortindex ',
            ['surveypartid' => $surveypartid]
        );
    }

    /**
     * Delete this surveyitem and fix the sortorder.
     * @return void
     */
    public function delete_and_fix_sortorder() {
        global $DB;
        $sortindex = $this->get('sortindex');
        $surveypartid = $this->get('surveypartid');
        $this->delete();
        $DB->execute(
            "UPDATE {" . self::TABLE . "} SET sortindex = sortindex - 1 " .
            "WHERE sortindex > :thissortindex AND surveypartid = :surveypartid",
            [
                'thissortindex' => $sortindex,
                'surveypartid' => $surveypartid,
            ]
        );
    }
}
