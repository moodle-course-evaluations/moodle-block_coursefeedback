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

use core\clock;
use core\dml\sql_join;
use core\exception\coding_exception;
use customfield_semester\data_controller;
use DateTime;

/**
 * Course to semester mapping by customfield.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_semester_mapping_by_customfield extends course_semester_mapping {

    /**
     * Constructor.
     * @param clock $clock
     */
    public function __construct(
        /** @var clock */
        private readonly clock $clock
    ) {
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
        $semester = $this->get_current_semester();
        return [
            // TODO
            $semester->id => $semester,
        ];
    }

    #[\Override]
    public function get_current_semester(): evaluation_semester {
        $id = data_controller::get_semester_for_datetime(DateTime::createFromImmutable($this->clock->now()));
        $name = data_controller::get_name_for_semester($id);

        return new evaluation_semester($id, $name ?? $id, $id, is_current: true);
    }

    #[\Override]
    public function get_semester_by_id(int $id): ?evaluation_semester {
        $name = data_controller::get_name_for_semester($id);
        $current_id = data_controller::get_semester_for_datetime(DateTime::createFromImmutable($this->clock->now()));

        return new evaluation_semester($id, $name ?? $id, $id, is_current: $id === $current_id);
    }

    #[\Override]
    public function get_semester_active_at(int $timestamp): ?evaluation_semester {
        $dt = (new DateTime())->setTimestamp($timestamp);
        $id = data_controller::get_semester_for_datetime($dt);
        return $id ? $this->get_semester_by_id($id) : null;
    }

    #[\Override]
    public function get_course_semester(int $courseid): ?evaluation_semester {
        $customfield_id = $this->get_customfield_id();

        global $DB;
        $record = $DB->get_record('customfield_data', ['fieldid' => $customfield_id, 'instanceid' => $courseid]);

        return $record ? $this->get_semester_by_id($record->value) : null;
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
