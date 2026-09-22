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

use core\clock;
use core\dml\sql_join;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use core\plugin_manager;
use local_moses\api\semester_resource;
use local_moses\course_data;
use local_moses\moses_api;

/**
 * Implementation of {@see course_semester_mapping} using the local_moses plugin.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_semester_mapping_moses extends course_semester_mapping {

    /** @var int */
    private const MIN_VERSION = 2025052200;

    /** @var semester_resource */
    private readonly semester_resource $semester_res;

    /**
     * Checks that the local_moses plugin is installed and of a compatible version.
     *
     * @param clock $clock
     */
    public function __construct(
        /** @var clock $clock */
        private readonly clock $clock
    ) {
        $local_moses_info = plugin_manager::instance()->get_plugin_info('local_moses');
        if (!$local_moses_info) {
            throw new moodle_exception('local_moses_not_installed', 'block_coursefeedback');
        }
        if ($local_moses_info->versiondb < self::MIN_VERSION) {
            throw new moodle_exception('local_moses_unsupported', 'block_coursefeedback', a: [
                'installed' => $local_moses_info->versiondb,
                'min' => self::MIN_VERSION,
            ]);
        }

        $this->semester_res = moses_api::get_semester_res();
    }

    /**
     * Converts a {@see semester_resource} record to an {@see evaluation_semester}.
     *
     * @param object $record
     * @return evaluation_semester
     */
    private function from_moses_record(object $record): evaluation_semester {
        $time = $this->clock->time();
        return new evaluation_semester(
            id: $record->mosesid,
            name: $record->name ?: $record->kurzname,
            sort_index: $record->startdate,
            is_current: $record->startdate <= $time && $record->enddate >= $time,
        );
    }

    #[\Override]
    public function get_semesters(): array {
        // Only consider semesters since May 2026 just to declutter.
        $min_considered_time = make_timestamp(2026, 05, 01);

        $resources = $this->semester_res->get_all_since($min_considered_time);
        if (!$resources) {
            debugging('local_moses returned no semesters');
        }

        $results = [];
        foreach ($resources as $resource) {
            $results[$resource->mosesid] = $this->from_moses_record($resource);
        }
        return $results;
    }

    #[\Override]
    public function get_current_semester(): evaluation_semester {
        $current_semester = $this->semester_res->get_current();
        if (!$current_semester) {
            throw new moodle_exception("local_moses_no_current_semester", 'block_coursefeedback');
        }
        return $this->from_moses_record($current_semester);
    }

    #[\Override]
    public function get_semester_by_id(int $id): ?evaluation_semester {
        $semester = $this->semester_res->get_by_id($id);
        return $semester ? $this->from_moses_record($semester) : null;
    }

    #[\Override]
    public function get_semester_active_at(int $timestamp): ?evaluation_semester {
        $semester = $this->semester_res->get_current($timestamp);
        return $semester ? $this->from_moses_record($semester) : null;
    }

    #[\Override]
    public function get_course_semester(int $courseid): ?evaluation_semester {
        $moses_data = course_data::get_by_course_id($courseid);
        if (!$moses_data || $moses_data->semesterid <= 0) {
            return null;
        }

        $semester = $this->semester_res->get_by_id($moses_data->semesterid);
        if (!$semester) {
            throw new coding_exception("Course '$courseid' has Moses semesterid '$moses_data->semesterid' which doesn't exist");
        }

        return $this->from_moses_record($semester);
    }

    #[\Override]
    public function get_filter_sql_for_semester(evaluation_semester $semester, string $alias_course_table = 'c'): sql_join {
        return new sql_join(
            joins: "JOIN {moses_course_data} moses ON $alias_course_table.id = moses.courseid",
            wheres: "moses.semesterid = :semesterid",
            params: ['semesterid' => $semester->id]
        );
    }
}
