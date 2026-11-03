<?php
// This file is part of Moodle - https://questionpy.org
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

use core_user\fields;

/**
 * Maps response slots to users who are allowed to see the results.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_slot_user extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveypartexecutionoption_user';

    #[\Override]
    protected static function define_properties(): array {
        return [
            'surveypartexecutionoptionid' => [
                'type' => PARAM_INT,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Uses this table just for joining and returns the user objects.
     *
     * @param int $slot_id
     * @param fields $user_fields
     * @return array<int, object>
     */
    public static function get_users(int $slot_id, \core_user\fields $user_fields): array {
        $user_fields_sql = $user_fields
            ->get_sql('u', namedparams: true, leadingcomma: false);

        global $DB;
        return $DB->get_records_sql("
               SELECT u.id, $user_fields_sql->selects
               FROM {" . self::TABLE . "} ru
               JOIN {user} u ON ru.userid = u.id
               $user_fields_sql->joins
               WHERE ru.surveypartexecutionoptionid = :slot_id
        ", ['slot_id' => $slot_id, ...$user_fields_sql->params]);
    }
}
