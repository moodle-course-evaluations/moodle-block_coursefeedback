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

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use core\exception\coding_exception;
use function array_filter;
use function array_map;
use function array_reverse;
use function array_unshift;
use function debugging;
use function usort;

/**
 * block_coursefeedback semester_manager.php description here.
 *
 * @package    block_coursefeedback
 * @copyright  2026  <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class semester_manager {

    /** @var course_organization_mapping */
    private readonly course_organization_mapping $organization_mapping;

    /** @var course_semester_mapping */
    private readonly course_semester_mapping $semester_mapping;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->organization_mapping = course_organization_mapping::get_instance();
        $this->semester_mapping = course_semester_mapping::get_instance();
    }

    /**
     * Return all semesters with an initialized orgsem.
     *
     * @param int $organizationid
     * @return array{0: evaluation_semester|null, 1: organization_semester}[]
     */
    public function get_initialized_semesters(int $organizationid): array {
        return array_filter(
            $this->get_all_semesters($organizationid),
            fn($pair) => $pair[1] !== null
        );
    }

    /**
     * Return all semesters, including uninitialized and orphaned.
     *
     * @param int $organizationid
     * @return array{0: evaluation_semester|null, 1: organization_semester|null}[]
     */
    public function get_all_semesters(int $organizationid): array {
        $organization_semesters = organization_semester::get_records(['organizationid' => $organizationid], sort: 'id');
        $mapping_semesters = array_values($this->semester_mapping->get_semesters());
        usort($mapping_semesters, fn($a, $b) => $a->sort_index <=> $b->sort_index);

        // Start out with just the mapping semesters.
        $pairs = array_map(fn($semester) => [$semester, null], $mapping_semesters);

        // We iterate over org semesters in reverse so that, when we unshift them into $pairs, they have their original order.
        foreach (array_reverse($organization_semesters) as $org_semester) {
            $semesterid = $org_semester->get('semesterid');
            if ($semesterid === null) {
                // No semester ID probably means that the semester mapping implementation was changed.
                array_unshift($pairs, [null, $org_semester]);
                continue;
            }

            $pair =& self::array_find_ref($pairs, fn($pair) => $pair[0]->id === $semesterid);

            if (!$pair) {
                // This shouldn't happen. Let's treat it as though there were no semester ID to begin with.
                debugging(
                    "organization_semester {$org_semester->get('id')} has semesterid $semesterid that is not known by " .
                    "course_semester_mapping implementation"
                );
                array_unshift($pairs, [null, $org_semester]);
                continue;
            }

            // Associate the org semester with the correct mapping semester.
            $pair[1] = $org_semester;
        }

        return $pairs;
    }

    /**
     * Gets the organization, semester, orgsem triplet by a known orgsemid.
     *
     * @param int $organizationid
     * @param int $orgsemid
     * @return array{0: organization, 1: evaluation_semester|null, 2: organization_semester|null}
     */
    public function get_triplet_by_orgsemid(int $organizationid, int $orgsemid): array {
        $org_fields = organization::get_sql_fields('o', 'o_');
        $org_semester_fields = organization_semester::get_sql_fields('os', 'os_');

        global $DB;
        $record = $DB->get_record_sql("
            SELECT $org_fields, $org_semester_fields
            FROM {block_coursefeedback_organization_semester} os
            INNER JOIN {block_coursefeedback_organization} o ON os.organizationid = o.id
            WHERE os.id = :orgsemid
        ", ['orgsemid' => $orgsemid], MUST_EXIST);

        $organization = organization::extract($record, 'o_');
        $orgsem = organization_semester::extract($record, 'os_');

        if ($organization->get('id') !== $organizationid) {
            throw new coding_exception("Orgsem $orgsemid does not belong to organization $organizationid");
        }

        $semester = $orgsem->get_semester();
        return [$organization, $semester, $orgsem];
    }

    /**
     * Gets the organization, semester, orgsem triplet for the current semester.
     *
     * @param int $organizationid
     * @return array{0: organization, 1: evaluation_semester, 2: organization_semester|null}
     */
    public function get_triplet_by_current_semester(int $organizationid): array {
        $semester = $this->semester_mapping->get_current_semester();
        [$organization, $orgsem] = $this->get_tuple_by_semesterid($organizationid, $semester->id);
        return [$organization, $semester, $orgsem];
    }

    /**
     * Gets the organization, semester, orgsem triplet the given semester ID (as known by {@see course_semester_mapping}).
     *
     * @param int $organizationid
     * @param int $semesterid
     * @return array{0: organization, 1: evaluation_semester|null, 2: organization_semester|null}
     */
    public function get_triplet_by_semesterid(int $organizationid, int $semesterid): array {
        $semester = $this->semester_mapping->get_semester_by_id($semesterid);
        [$organization, $orgsem] = $this->get_tuple_by_semesterid($organizationid, $semesterid);
        return [$organization, $semester, $orgsem];
    }

    /**
     * Gets the organization, orgsem tuple by the given semester ID (as known by {@see course_semester_mapping}).
     *
     * @param int $organizationid
     * @param int $semesterid
     * @return array{0: organization, 1: organization_semester|null}
     */
    private function get_tuple_by_semesterid(int $organizationid, int $semesterid): array {
        $org_fields = organization::get_sql_fields('o', 'o_');
        $org_semester_fields = organization_semester::get_sql_fields('os', 'os_');

        global $DB;
        $record = $DB->get_record_sql("
            SELECT $org_fields, $org_semester_fields
            FROM {block_coursefeedback_organization} o
            LEFT JOIN {block_coursefeedback_organization_semester} os ON os.organizationid = o.id AND os.semesterid = :semesterid
            WHERE o.id = :organizationid
        ", ['organizationid' => $organizationid, 'semesterid' => $semesterid], MUST_EXIST);

        $organization = organization::extract($record, 'o_');
        $orgsem = organization_semester::extract($record, 'os_');

        return [$organization, $orgsem];
    }

    /**
     * Gets the organization, semester, orgsem triplet for the given course.
     *
     * If the course has an existing survey execution, the triplet for that SE is returned. Otherwise, the
     *
     * @param object $course
     * @param organization|int|null $organization_or_id
     * @return array{0: organization|null, 1: evaluation_semester|null, 2: organization_semester|null}
     */
    public function get_triplet_by_course(object $course, organization|int|null $organization_or_id = null): array {
        // First, check if the course already has an SE.
        [$organization, $semester, $orgsem] = $this->get_triplet_by_course_via_se($course);
        if ($organization && $orgsem) {
            return [$organization, $semester, $orgsem];
        }

        $semester = $this->semester_mapping->get_course_semester($course->id);

        if (!$organization_or_id) {
            $organization = $this->organization_mapping->get_organization_for_course($course);
        } else if ($organization_or_id instanceof organization) {
            $organization = $organization_or_id;
        } else {
            [$organization, $orgsem] = $this->get_tuple_by_semesterid($organization_or_id, $semester->id);
            return [$organization, $semester, $orgsem];
        }

        if ($organization && $semester) {
            $orgsem = organization_semester::get_record([
                'organizationid' => $organization->get('id'),
                'semesterid' => $semester->id,
            ]);
        }

        return [$organization, $semester, $orgsem];
    }

    /**
     * Gets the organization, semester, orgsem triplet for the given course if that course has an existing survey execution.
     *
     * If the course does not have an existing survey execution, this method returns `[null, null, null]` even though the course may
     * belong to an organization and semester.
     *
     * @param object $course
     * @return array{0: organization|null, 1: evaluation_semester|null, 2: organization_semester|null}
     */
    private function get_triplet_by_course_via_se(object $course): array {
        $org_fields = organization::get_sql_fields('o', 'o_');
        $org_semester_fields = organization_semester::get_sql_fields('os', 'os_');

        global $DB;
        $record = $DB->get_record_sql("
            SELECT $org_fields, $org_semester_fields
            FROM {block_coursefeedback_surveyexecution} se
            JOIN {block_coursefeedback_organization_semester} os ON se.orgsemid = os.id
            JOIN {block_coursefeedback_organization} o ON os.organizationid = o.id
            WHERE se.courseid = :courseid
        ", ['courseid' => $course->id]);

        if ($record) {
            $organization = organization::extract($record, 'o_');
            $orgsem = organization_semester::extract($record, 'os_');

            $semester = $this->semester_mapping->get_semester_by_id($orgsem->get('semesterid'));

            return [$organization, $semester, $orgsem];
        }

        return [null, null];
    }

    /**
     * Checks id the given pairs refer to th same semester.
     *
     * @param evaluation_semester|null $semester_a
     * @param organization_semester|null $orgsem_a
     * @param evaluation_semester|null $semester_b
     * @param organization_semester|null $orgsem_b
     * @return bool
     */
    public static function are_semester_equal(
        ?evaluation_semester $semester_a,
        ?organization_semester $orgsem_a,
        ?evaluation_semester $semester_b,
        ?organization_semester $orgsem_b,
    ): bool {
        if ($semester_a) {
            return $semester_b && $semester_a->id === $semester_b->id;
        }
        if ($orgsem_a) {
            return $orgsem_b && $orgsem_a->get('id') === $orgsem_b->get('id');
        }
        return false;
    }

    /**
     * Returns a _reference to_ the first array value matching the given predicate.
     *
     * @param array $array
     * @param callable $predicate
     * @return mixed
     */
    private static function &array_find_ref(array &$array, callable $predicate): mixed {
        foreach ($array as &$item) {
            if ($predicate($item)) {
                return $item;
            }
        }

        $result = null;
        return $result;
    }
}
