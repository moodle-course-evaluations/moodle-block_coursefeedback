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

namespace block_coursefeedback\local\persistent;

use core\exception\moodle_exception;
use dml_write_exception;

/**
 * Maps organizations to course categories.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization_category extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_organization_coursecat';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'coursecatid' => [
                'type' => PARAM_INT,
            ],
            'organizationid' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Returns all coursecatids for the organization.
     *
     * @param int $organizationid
     * @return array
     */
    public static function get_organization_coursecatids(int $organizationid): array {
        global $DB;

        return $DB->get_fieldset(self::TABLE, 'coursecatid', ['organizationid' => $organizationid]);
    }

    /**
     * Sets the coursecatids for the organization.
     *
     * @param int $organizationid
     * @param array $coursecatids
     */
    public static function set_organization_coursecatids(int $organizationid, array $coursecatids): void {
        try {
            self::diff_create_delete([
                'organizationid' => $organizationid,
            ], 'coursecatid', $coursecatids);
        } catch (dml_write_exception $e) {
            // We optimistically assume there is no conflict and only check for one when the write fails.
            global $DB;

            if ($coursecatids) {
                [$insql, $params] = $DB->get_in_or_equal($coursecatids, SQL_PARAMS_NAMED);

                $conflicting_record = $DB->get_record_sql("
                    SELECT coursecat.name AS cat_name, org.name AS org_name
                    FROM {block_coursefeedback_organization_coursecat} occ
                    JOIN {course_categories} coursecat ON occ.coursecatid = coursecat.id
                    JOIN {block_coursefeedback_organization} org ON occ.organizationid = org.id
                    WHERE coursecat.id $insql
                ", $params);
                if ($conflicting_record) {
                    throw new moodle_exception('coursecat_assignment_conflict', 'block_coursefeedback', a: $conflicting_record);
                }
            }

            throw $e;
        }
    }

    /**
     * Returns all coursecatids which belong to this organization.
     *
     * @param int $organizationid
     * @return array
     */
    public static function get_all_recursive_coursecatids(int $organizationid): array {
        $allids = [];
        $coursecatids = self::get_organization_coursecatids($organizationid);
        foreach ($coursecatids as $coursecatid) {
            $allids[] = $coursecatid;
            $allids = array_merge($allids, \core_course_category::get($coursecatid)->get_all_children_ids());
        }
        return $allids;
    }

    /**
     * Fetch the organization for the given category. This returns the organization associated with
     * the nearest parent category, or null, if no parent category is associated with an organization.
     *
     * @param \core_course_category $category
     * @return null|int The organization id.
     */
    public static function get_organizationid_for_category(\core_course_category $category): ?int {
        global $DB;
        $coursecatids = $category->get_parents();
        $coursecatids[] = $category->id;
        [$sql, $params] = $DB->get_in_or_equal($coursecatids, SQL_PARAMS_NAMED);

        $organization_categories = $DB->get_records_select(
            self::TABLE,
            "coursecatid $sql",
            $params,
            fields: 'coursecatid, organizationid',
        );
        foreach (array_reverse($coursecatids) as $coursecatid) {
            if (isset($organization_categories[$coursecatid])) {
                return $organization_categories[$coursecatid]->organizationid;
            }
        }

        return null;
    }
}
