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

namespace block_coursefeedback\local\course_semester_mapping;

use core\dml\sql_join;
use core\exception\coding_exception;

/**
 * Course to semester mapping by customfield.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_semester_mapping_by_customfield extends course_semester_mapping {

    /** @var int The currently selected semester. Which is of course always SoSe 2026. */
    public const SELECTED_SEMESTER = 20260;

    /**
     * Constructor.
     */
    public function __construct() {
        // Check that the custom field exists. Prevents enabling this implementation when it doesn't.
        $this->get_customfield_id();
    }

    /**
     * Gets the ids of the semester customfield.
     * @return int
     */
    private function get_customfield_id(): int {
        global $DB;
        $record = $DB->get_record('customfield_field', [
            'name' => 'semester',
            'type' => 'semester',
        ]);
        if (!$record) {
            throw new coding_exception('No semester customfield found!');
        }
        return $record->id;
    }

    #[\Override]
    public function get_semesters(): array {
        return [
            $this->get_current_semester(),
        ];
    }

    #[\Override]
    public function get_current_semester(): evaluation_semester {
        return new evaluation_semester(
            self::SELECTED_SEMESTER,
            'SoSe 2026',
            self::SELECTED_SEMESTER
        );
    }

    #[\Override]
    public function get_filter_sql_for_semester(evaluation_semester $semester, string $alias_course_table = 'c'): sql_join {
        $customfield_id = $this->get_customfield_id();
        return new sql_join(
            "JOIN {customfield_data} cfd_semester ON cfd_semester.fieldid = :fieldid
                AND cfd_semester.value = :semester
                AND cfd_semester.instanceid = $alias_course_table.id",
            'TRUE',
            [
                'fieldid' => $customfield_id,
                'semester' => $semester->id,
            ]
        );
    }
}
