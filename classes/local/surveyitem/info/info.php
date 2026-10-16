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
 * Survey item type definition for info elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\surveyitem\info;

use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\lang_string;

/**
 * Survey item type definition for info elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class info extends surveyitemtype_with_settings {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('infotext', 'block_coursefeedback');
    }

    #[\Override]
    public function save_settings_form_data(surveyitem $surveyitem, surveypart $surveypart, object $formdata): void {
        // Nothing to do.
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        // Intentionally left blank.
    }

    /**
     * Exports template data for an auto-created info item.
     *
     * @param string $text May contain HTML.
     * @return array
     */
    public function export_auto_created(string $text): array {
        static $id = PHP_INT_MIN;
        return [
            'type_info' => true,
            'type' => 'info',
            // Client-side code currently expects every item to have an ID. We use successive negative numbers.
            // TODO: Do better.
            'surveyitemid' => $id++,
            'questiontext' => $text,
        ];
    }

    #[\Override]
    public function should_appear_in_reports(): bool {
        return false;
    }
}
