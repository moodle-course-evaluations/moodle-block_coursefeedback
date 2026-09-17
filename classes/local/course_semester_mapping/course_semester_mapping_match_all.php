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
use core\lang_string;

/**
 * Semester mapping that just matches all courses.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_semester_mapping_match_all extends course_semester_mapping {

    /** @var evaluation_semester */
    private readonly evaluation_semester $semester;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->semester = new evaluation_semester(
            id: 0,
            name: new lang_string('all_courses', 'block_coursefeedback'),
            sort_index: 0
        );
    }

    #[\Override]
    public function get_semesters(): array {
        return [ $this->semester ];
    }

    #[\Override]
    public function get_current_semester(): evaluation_semester {
        return $this->semester;
    }

    #[\Override]
    public function get_semester_by_id(int $id): ?evaluation_semester {
        if ($id === $this->semester->id) {
            return $this->semester;
        }
        return null;
    }

    #[\Override]
    public function get_semester_active_at(int $timestamp): ?evaluation_semester {
        return $this->semester;
    }

    #[\Override]
    public function get_course_semester(int $courseid): ?evaluation_semester {
        return $this->semester;
    }

    #[\Override]
    public function get_filter_sql_for_semester(evaluation_semester $semester, string $alias_course_table = 'c'): sql_join {
        return new sql_join(
            wheres: '1=1',
        );
    }
}
