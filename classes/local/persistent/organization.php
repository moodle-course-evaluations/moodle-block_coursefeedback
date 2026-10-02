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

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\manager\semester_pair;
use core\dml\sql_join;
use core\exception\coding_exception;

/**
 * An organizational unit, such as a faculty, in which evaluations are conducted.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_organization';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'has_local_questionnaires' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
            'no_global_questionnaires' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
        ];
    }

    /**
     * @param int $organizationid
     * @return array{0: self, 1: semester_pair}
     */
    public static function get_for_current_semester(int $organizationid): array {
        $semester = course_semester_mapping::get_instance()->get_current_semester();
        return self::get_with_orgsem_by_semester($organizationid, $semester);
    }

    /**
     * @param int $organizationid
     * @param string $on_sql
     * @param array $params
     * @return array{0: self, 1: organization_semester|null}
     */
    private static function get_with_orgsem(int $organizationid, string $on_sql, array $params): array {
        $org_fields = self::get_sql_fields('o', 'o_');
        $org_semester_fields = organization_semester::get_sql_fields('os', 'os_');

        global $DB;
        $record = $DB->get_record_sql("
            SELECT $org_fields, $org_semester_fields
            FROM {block_coursefeedback_organization} o
            LEFT JOIN {block_coursefeedback_organization_semester} os ON o.id = os.organizationid AND $on_sql
            WHERE o.id = :organizationid
        ", [...$params, 'organizationid' => $organizationid], MUST_EXIST);

        $organization = self::extract($record, 'o_');
        $organization_semester = organization_semester::extract($record, 'os_');
        return [$organization, $organization_semester];
    }

    /**
     * @param int $organizationid
     * @param evaluation_semester $semester
     * @return array{0: self, 1: semester_pair}
     */
    public static function get_with_orgsem_by_semester(int $organizationid, evaluation_semester $semester): array {
        [$organization, $orgsem] =  self::get_with_orgsem($organizationid, 'os.semesterid = :semesterid', ['semesterid' => $semester->id]);
        return [$organization, new semester_pair($semester, $orgsem)];
    }

    /**
     * @param int $organizationid
     * @param int $orgsemid
     * @return array{0: self, 1: semester_pair}
     */
    public static function get_with_orgsem_by_id(int $organizationid, int $orgsemid): array {
        [$organization, $orgsem] = self::get_with_orgsem($organizationid, 'os.id = :orgsemid', ['orgsemid' => $orgsemid]);
        if (!$orgsem) {
            throw new coding_exception("orgsem not found by orgsemid $orgsemid in organization $organizationid");
        }

        $semesterid = $orgsem->get('semesterid');
        $semester = $semesterid === null
            ? null
            : course_semester_mapping::get_instance()->require_semester_by_id($semesterid);

        return [$organization, new semester_pair($semester, $orgsem)];
    }
}
