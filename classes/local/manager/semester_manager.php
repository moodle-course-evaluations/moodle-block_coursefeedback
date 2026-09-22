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

namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization_semester;
use Generator;

/**
 * block_coursefeedback semester_manager.php description here.
 *
 * @package    block_coursefeedback
 * @copyright  2026  <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class semester_manager {

    private readonly course_semester_mapping $semester_mapping;

    public function __construct() {
        $this->semester_mapping = course_semester_mapping::get_instance();
    }

    /**
     * @param array<int, evaluation_semester> $mapping_semesters
     * @param array $org_semesters
     * @return semester_info[]
     */
    private function combine(array $mapping_semesters, array $org_semesters): array {
        $semester_ids = array_unique(array_merge(
            array_map(fn($semester) => $semester->id, $mapping_semesters),
            array_filter(
                array_map(fn($orgsem) => $orgsem->get('semesterid'), $org_semesters),
                fn($semester_id) => $semesterid !== null
            )
        ));

        $pairs = array_map(fn($semester) => [$semester, null], $mapping_semesters);

        $orphaned_semesters = [];
        foreach (array_reverse($org_semesters) as $org_semester) {
            $semesterid = $org_semester->get('semesterid');
            if ($semesterid === null) {
                array_unshift($pairs, [null, $org_semester]);
                continue;
            }

            $pair = current(array_filter($pairs, fn($pair) => $pair[0]->id === $org_semester->get()));

            if (!$pair) {
                debugging(
                    "organization_semester {$org_semester->get('id')} has semesterid $semesterid that is not known by " .
                    "course_semester_mapping implementation"
                );
                continue;
            }

            $pair[1] = $org_semester;
        }

        return array_map(fn($pair) => new semester_info(...$pair), $pairs);
    }

    /**
     * @param int $organizationid
     * @return semester_info[]
     */
    public function load_initialized_semesters(int $organizationid): array {
        $mapping_semesters = $this->semester_mapping->get_semesters();

        return array_values();
    }

    /**
     * @param int $organizationid
     * @return array{0: ?evaluation_semester, 1: ?organization_semester}[]
     */
    public function load_all_semesters(int $organizationid): array {
        $mapping_semesters = $this->semester_mapping->get_semesters();

        $org_semesters = $this->load_initialized_semesters($organizationid);

        $semester_ids = array_unique(array_merge(
            array_map(fn($semester) => $semester->id, $mapping_semesters),
            array_filter(
                array_map(fn($orgsem) => $orgsem->get('semesterid'), $org_semesters),
                fn($semester_id) => $semester_id !== null
            )
        ));

        $results = [];
        foreach ($semester_ids as $semester_id) {
            $matching_mapping_semesters = array_filter($mapping_semesters, fn($semester) => $semester->id === $semester_id);
            $mapping_semester = reset($matching_mapping_semesters);

            $matching_orgsems = array_filter($org_semesters, fn($orgsem) => $orgsem->get('semesterid') === $semester_id);
            $orgsem = reset($matching_orgsems);

            if (!$mapping_semester && !$orgsem) {
                continue;
            }
            $results[] = [$mapping_semester, $orgsem];
        }

        return $results;
    }
}
