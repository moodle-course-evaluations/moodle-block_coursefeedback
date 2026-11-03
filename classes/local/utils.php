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

use context_course;
use core\clock;
use core\di;
use DateInterval;

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
     * Returns the interval in which a user must have visited a course in order to be considered an active user of it.
     *
     * @return DateInterval
     */
    public static function get_active_user_interval(): DateInterval {
        // TODO: Probably make this configurable, but at what level?
        return new DateInterval('P2W');
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
     * Gets the number of enrolled and the number of active students for the given course IDs.
     *
     * @param array $courseids
     * @return array<int, object{enrolled_count: int, recently_active_count: int}>
     */
    public static function get_course_student_counts(array $courseids): array {
        if (!$courseids) {
            return [];
        }

        global $DB;
        [$in_courseids_sql, $in_courseids_params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $active_user_cutoff = di::get(clock::class)->now()->sub(self::get_active_user_interval())->getTimestamp();

        return $DB->get_records_sql("
            SELECT ctx.instanceid,
                   COUNT(DISTINCT ra.userid) AS enrolled_count,
                   COUNT(DISTINCT ul.userid) AS recently_active_count
            FROM {context} ctx
                 JOIN {role_assignments} ra ON ra.contextid = ctx.id
                 JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
                 LEFT JOIN {user_lastaccess} ul
                           ON ul.courseid = ctx.instanceid
                               AND ul.userid = ra.userid
                               AND ul.timeaccess >= :active_user_cutoff
            WHERE ctx.instanceid $in_courseids_sql AND ctx.contextlevel = :context_level_course
            GROUP BY ctx.instanceid
        ", [
            ...$in_courseids_params,
            'context_level_course' => CONTEXT_COURSE,
            'active_user_cutoff' => $active_user_cutoff,
        ]);
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
        $records = $DB->get_records_sql_menu("
            SELECT GROUPING(spe.id) + GROUPING(speo.id), COUNT(rs.id)
            FROM {block_coursefeedback_surveyexecution} se
            JOIN {block_coursefeedback_surveypartexecution} spe ON se.id = spe.surveyexecutionid
            JOIN {block_coursefeedback_surveypartexecutionoption} speo ON spe.id = speo.surveypartexecutionid
            JOIN {block_coursefeedback_surveypartexecutionoptionresp} rs ON speo.id = rs.surveypartexecutionoptionid
            WHERE se.id = :se_id
            GROUP BY ROLLUP (spe.id, speo.id)
            HAVING (GROUPING(spe.id) = 1  OR spe.id = :spe_id)
               AND (GROUPING(speo.id) = 1 OR speo.id = :slot_id)
        ", ['se_id' => $se_id, 'spe_id' => $spe_id, 'slot_id' => $slot_id]);

        return array_map(intval(...), [$records[2] ?? 0, $records[1] ?? 0, $records[0] ?? 0]);
    }
}
