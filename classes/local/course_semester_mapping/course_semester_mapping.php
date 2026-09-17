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

use core\di;
use core\dml\sql_join;
use core\exception\coding_exception;
use moodle_exception;

/**
 * Abstract course semester mapping stuff.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class course_semester_mapping {

    /** @var string Map course to semesters by course custom field. */
    public const MAP_BY_CUSTOMFIELD = 'customfield';

    /** @var string Map course to semester by using Moses MTS data provided by the local_moses plugin. */
    public const MAP_MOSES = 'moses';

    /** @var string Map course to semesters matching all courses to all semesters. */
    public const MAP_MATCH_ALL = 'match-all';

    /**
     * Returns all available semesters.
     *
     * @return evaluation_semester[]
     */
    abstract public function get_semesters(): array;

    /**
     * Returns the current semester.
     *
     * @return evaluation_semester
     */
    abstract public function get_current_semester(): evaluation_semester;

    /**
     * @param int $id
     * @return evaluation_semester|null
     */
    abstract public function get_semester_by_id(int $id): ?evaluation_semester;

    /**
     * @param int $timestamp
     * @return evaluation_semester|null
     */
    abstract public function get_semester_active_at(int $timestamp): ?evaluation_semester;

    /**
     * @param int $courseid
     * @return evaluation_semester|null
     */
    abstract public function get_course_semester(int $courseid): ?evaluation_semester;

    /**
     * Return sql to filter courses by this semester.
     * @param evaluation_semester $semester
     * @param string $alias_course_table What the course table is aliased to in the query.
     * @return sql_join
     */
    abstract public function get_filter_sql_for_semester(evaluation_semester $semester, string $alias_course_table = 'c'): sql_join;

    /**
     * Validates that the given method can be used in the current environment.
     *
     * @param string $method
     * @return string Error message if the method is invalid, empty string otherwise.
     */
    final public static function validate_method(string $method): string {
        try {
            // Instantiate the mapper to allow it to do some validation.
            self::get_instance($method);
        } catch (moodle_exception $e) {
            return $e->getMessage();
        }
        return '';
    }

    /**
     * Returns the correct course_semester_mapping function based on the setting.
     *
     * @param string|null $method
     * @return self
     */
    final public static function get_instance(?string $method = null): self {
        if (!$method) {
             $method = get_config('block_coursefeedback', 'course_semester_method');
        }

        return match ($method) {
            self::MAP_BY_CUSTOMFIELD => di::get(course_semester_mapping_by_customfield::class),
            self::MAP_MOSES => di::get(course_semester_mapping_moses::class),
            self::MAP_MATCH_ALL => di::get(course_semester_mapping_match_all::class),
            default => throw new coding_exception("Invalid course semester mapping method: $method")
        };
    }
}
