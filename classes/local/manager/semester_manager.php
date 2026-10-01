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
use block_coursefeedback\local\persistent\organization_semester;
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

    /** @var course_semester_mapping */
    private readonly course_semester_mapping $semester_mapping;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->semester_mapping = course_semester_mapping::get_instance();
    }

    /**
     * @param int $organizationid
     * @return semester_info[]
     */
    public function load_initialized_semesters(int $organizationid): array {
        return array_filter(
            $this->load_all_semesters($organizationid),
            fn($semester) => $semester->orgsem !== null
        );
    }

    /**
     * @param int $organizationid
     * @return semester_info[]
     */
    public function load_all_semesters(int $organizationid): array {
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

        return array_map(fn($pair) => new semester_info(...$pair), $pairs);
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
