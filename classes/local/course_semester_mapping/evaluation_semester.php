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

namespace block_coursefeedback\local\course_semester_mapping;

use core\lang_string;

/**
 * Semester object for the purposes of this plugin.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation_semester {

    /**
     * Constructor.
     *
     * @param int $id
     * @param string|lang_string $name
     * @param int $sort_index
     * @param bool $is_current
     */
    public function __construct(
        /** @var int $id Unique identifier of the semester, as defined by the implementation. */
        public readonly int $id,
        /** @var string|lang_string $name Human readable name of the semester. */
        public readonly string|lang_string $name,
        /** @var int $sort_index Any integer that is higher for later semesters. */
        public readonly int $sort_index,
        /** @var bool $is_current Whether this is the semester we are currently in. */
        public readonly bool $is_current,
    ) {
    }
}
