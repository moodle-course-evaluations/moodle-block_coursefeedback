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

namespace block_coursefeedback\local\surveyitem\slot_choice;

use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\surveyitem\surveyitemtype;
use coding_exception;
use core\lang_string;

/**
 * A special survey item for choosing the slot.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_choice extends surveyitemtype {

    #[\Override]
    public function get_name(): lang_string {
        return new lang_string('slot_choice_surveyitem', 'block_coursefeedback');
    }

    #[\Override]
    public function can_be_added(): bool {
        return false;
    }

    #[\Override]
    public function check_and_save_answers($answers): void {
        // Intentionally left blank.
    }

    /**
     * Exports template data for an auto-created slot choice item.
     *
     * When displaying an SPE that has multiple slots but no explicit slot choice item, we render a default slot choice item.
     *
     * @param survey_part_execution $spe
     * @param response_slot[] $slots
     * @return array
     */
    public function export_auto_created(survey_part_execution $spe, array $slots): array {
        return [
            'type_slot_choice' => true,
            'type' => 'slot_choice',
            // Client-side code currently expects every item to have an ID. We use the negative SPE ID.
            'surveyitemid' => -$spe->get('id'),
            'questiontext' => get_string('slot_choice_text', 'block_coursefeedback'),

            // We reuse the single choice template, so this has the same structure.
            'required' => true,
            'options' => array_map(
                fn($slot) => [
                    'optiontext' => $slot->get('name'),
                    'optionid' => $slot->get('id'),
                ],
                array_values($slots)
            ),
        ];
    }

    #[\Override]
    public function export_for_template(array $surveyitems, array $additional_data): array {
        // TODO: Refactor this to not break the Liskov substitution principle.
        throw new coding_exception('Slot choice items cannot be rendered normally.');
    }

    #[\Override]
    public function should_appear_in_reports(): bool {
        return false;
    }
}
