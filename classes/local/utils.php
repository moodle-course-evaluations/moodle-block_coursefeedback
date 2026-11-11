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

namespace block_coursefeedback\local;

use block_coursefeedback\local\persistent\survey_execution;
use context_course;
use core\clock;
use core\di;
use core\exception\coding_exception;
use DateInterval;
use mysqli_native_moodle_database;
use DateInvalidOperationException;
use DateTime;
use DateTimeImmutable;

/**
 * A utility class with methods that don't have a better place yet.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Flattens an iterable of iterables.
     *
     * @param iterable<iterable> $iterable
     * @param bool $preserve_keys
     * @return array
     */
    public static function array_flatten(iterable $iterable, bool $preserve_keys = false): array {
        $result = [];
        foreach ($iterable as $outer) {
            foreach ($outer as $key => $inner) {
                if ($preserve_keys) {
                    $result[$key] = $inner;
                } else {
                    $result[] = $inner;
                }
            }
        }
        return $result;
    }

    /**
     * Gets the names and role names of all participants in the given course who we consider teachers.
     *
     * @param int $courseid
     * @return array{name: string, roles: string}
     */
    public static function get_evaluation_teachers_and_roles_in_course(int $courseid): array {
        global $DB;

        $context = context_course::instance($courseid);

        [$needed_roles, $forbidden_roles] = get_roles_with_cap_in_context($context, 'block/coursefeedback:isevaluationteacher');
        if (!$needed_roles) {
            return [];
        }
        [$in_role_sql, $in_role_params] = $DB->get_in_or_equal(array_keys($needed_roles), SQL_PARAMS_NAMED);

        $user_fields_sql = \core_user\fields::for_name()
            ->including('id')
            ->get_sql('u', namedparams: true, prefix: 'u_', leadingcomma: false);

        $recordset = $DB->get_recordset_sql("
            SELECT DISTINCT $user_fields_sql->selects, ra.roleid
            FROM {context} ctx
                JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.roleid $in_role_sql
                JOIN {user} u ON u.id = ra.userid
                $user_fields_sql->joins
            WHERE ctx.instanceid = :courseid AND ctx.contextlevel = :context_level
        ", [
            'courseid' => $courseid,
            'context_level' => CONTEXT_COURSE,
            ...$in_role_params,
            ...$user_fields_sql->params,
        ]);

        $role_names = role_get_names($context);

        $users = [];
        foreach ($recordset as $record) {
            $users[$record->u_id] ??= [
                'name' => fullname(record_extractor::maybe_extract($record, 'u_')),
                'roles' => [],
            ];

            $users[$record->u_id]['roles'][$record->roleid] = role_get_name($role_names[$record->roleid], $context);
        }

        foreach ($users as $userid => &$user) {
            // Remove the users who have a role forbidding the block/coursefeedback:isevaluationteacher capability.
            if (array_intersect_key($user['roles'], $forbidden_roles)) {
                unset($users[$userid]);
            }

            $user['roles'] = implode(', ', $user['roles']);
        }

        return array_values($users);
    }

    /**
     * Generates ROLLUP SQL compatible with the current DBMS.
     *
     * @param string ...$columns
     * @return string
     */
    private static function sql_rollup(string ...$columns): string {
        global $DB;
        if ($DB instanceof mysqli_native_moodle_database) {
            // MySQL and MariaDB (which subclasses mysqli_...) use non-standard ROLLUP syntax :(.
            return implode(', ', $columns) . ' WITH ROLLUP';
        } else {
            // SQL:1999 standard syntax for all other DBMS.
            return 'ROLLUP(' . implode(', ', $columns) . ')';
        }
    }

    /**
     * Gets hierarchical subtotal response counts for an SE, SPE and a slot.
     *
     * The given slot must be part of the SPE, which must be part of the SE, for the results to make sense.
     *
     * @param int $se_id
     * @param int $spe_id
     * @param int $slot_id
     * @return array{0: int, 1: int, 2: int}
     */
    public static function get_response_count_subtotals(int $se_id, int $spe_id, int $slot_id): array {
        global $DB;

        // TODO: block_coursefeedback_surveyexecution_user entries will eventually be deleted, but we'd still like response rates.
        // We can't simply count response sets because each user produces up to one response set per event.
        // We may need to track response statistics separately.
        $seu_count = $DB->count_records_sql("
            SELECT COUNT(DISTINCT seu.userid)
            FROM {block_coursefeedback_surveyexecution_user} seu
            WHERE seu.surveyexecutionid = :se_id
        ", ['se_id' => $se_id]);

        $rollup = self::sql_rollup('spe.id', 'speo.id');
        $records = $DB->get_records_sql_menu("
            -- Poor person's GROUPING(), which isn't supported by MariaDB.
            SELECT (CASE WHEN spe.id IS NULL THEN 1 ELSE 0 END) + (CASE WHEN speo.id IS NULL THEN 1 ELSE 0 END), COUNT(rs.id)
            FROM {block_coursefeedback_surveyexecution} se
            JOIN {block_coursefeedback_surveypartexecution} spe ON se.id = spe.surveyexecutionid
            JOIN {block_coursefeedback_surveypartexecutionoption} speo ON spe.id = speo.surveypartexecutionid
            JOIN {block_coursefeedback_surveypartexecutionoptionresp} rs ON speo.id = rs.surveypartexecutionoptionid
            WHERE se.id = :se_id
            GROUP BY $rollup
            HAVING (spe.id IS NULL OR spe.id = :spe_id)
               AND (speo.id IS NULL OR speo.id = :slot_id)
        ", ['se_id' => $se_id, 'spe_id' => $spe_id, 'slot_id' => $slot_id]);

        [$se_response_sets, $spe_response_sets, $slot_response_sets] =
            array_map(intval(...), [$records[2] ?? 0, $records[1] ?? 0, $records[0] ?? 0]);

        // When students "click through" the survey without answering anything, they participation gets tracked, but no response
        // set created. To avoid situations where the SPE response counts don't add up to the course response count, we cap the
        // course response count at the total number of response sets.
        return [min($seu_count, $se_response_sets), $spe_response_sets, $slot_response_sets];
    }
}
