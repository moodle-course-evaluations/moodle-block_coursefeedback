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
 * Scale persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\persistent;

use block_coursefeedback\local\multilang_string;
use core\persistent;

/**
 * Scale persistent class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scale extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_scale';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'surveypartid' => [
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'optionamount' => [
                'type' => PARAM_INT,
            ],
            'minoptiontext' => [
                'type' => PARAM_RAW_TRIMMED,
            ],
            'maxoptiontext' => [
                'type' => PARAM_RAW_TRIMMED,
            ],
            'hasnoansweroption' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
            'noansweroptiontext' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'centeroptiontext' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Loads and deserializes 'noansweroptiontext'.
     *
     * @return multilang_string|null
     */
    protected function get_noansweroptiontext(): ?multilang_string {
        $raw = $this->raw_get('noansweroptiontext');
        return $raw === null ? null : multilang_string::deserialize($raw);
    }

    /**
     * Serializes and sets 'text'.
     *
     * @param multilang_string|string $text
     */
    protected function set_noansweroptiontext(multilang_string|string $text): void {
        if (is_string($text)) {
            // Validate it.
            multilang_string::deserialize($text);
        } else {
            $text = $text->serialize();
        }

        $this->raw_set('noansweroptiontext', $text);
    }

    /**
     * Loads and deserializes 'minoptiontext'.
     *
     * @return multilang_string
     */
    protected function get_minoptiontext(): multilang_string {
        return multilang_string::deserialize($this->raw_get('minoptiontext'));
    }

    /**
     * Serializes and sets 'text'.
     *
     * @param multilang_string|string $text
     */
    protected function set_minoptiontext(multilang_string|string $text): void {
        if (is_string($text)) {
            // Validate it.
            multilang_string::deserialize($text);
        } else {
            $text = $text->serialize();
        }
        $this->raw_set('minoptiontext', $text);
    }

    /**
     * Loads and deserializes 'maxoptiontext'.
     *
     * @return multilang_string
     */
    protected function get_maxoptiontext(): multilang_string {
        return multilang_string::deserialize($this->raw_get('maxoptiontext'));
    }

    /**
     * Serializes and sets 'text'.
     *
     * @param multilang_string|string $text
     */
    protected function set_maxoptiontext(multilang_string|string $text): void {
        if (is_string($text)) {
            // Validate it.
            multilang_string::deserialize($text);
        } else {
            $text = $text->serialize();
        }

        $this->raw_set('maxoptiontext', $text);
    }

    /**
     * Loads and deserializes 'centeroptiontext'.
     *
     * @return multilang_string|null
     */
    protected function get_centeroptiontext(): ?multilang_string {
        $raw = $this->raw_get('centeroptiontext');
        return $raw === null ? null : multilang_string::deserialize($raw);
    }

    /**
     * Serializes and sets 'centeroptiontext'.
     *
     * @param multilang_string|string|null $text
     */
    protected function set_centeroptiontext(multilang_string|string|null $text): void {
        if (is_string($text)) {
            // Validate it.
            multilang_string::deserialize($text);
        } else {
            $text = $text?->serialize();
        }

        $this->raw_set('centeroptiontext', $text);
    }
}
