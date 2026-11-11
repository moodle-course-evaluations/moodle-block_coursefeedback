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

namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\task\save_student_counts_task;
use core\clock;
use core\exception\coding_exception;

/**
 * Manages the calculating, saving and retrieving of course student counts.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_count_manager {

    /**
     * Constructor.
     *
     * @param clock $clock
     */
    public function __construct(
        /** @var clock */
        private readonly clock $clock
    ) {
    }

    /**
     * Gets the start time of the window within which user accesses should make the user be considered 'active'.
     *
     * @param survey_execution $survey_execution
     * @return int
     */
    public static function get_active_user_window_starttime(survey_execution $survey_execution): int {
        // TODO: Probably make this configurable, but at what level?
        static $interval = 14 * 24 * 60 * 60; // 2 weeks.
        return $survey_execution->get('starttime') - $interval;
    }

    /**
     * Gets the number of enrolled and the number of active students for the given course IDs.
     *
     * @param int $courseid
     * @param int $active_window_starttime
     * @return student_counts|null
     */
    public function calculate_current(int $courseid, int $active_window_starttime): ?student_counts {
        $now = $this->clock->time();

        global $DB;
        $record = $DB->get_record_sql("
            SELECT ctx.instanceid,
                   COUNT(DISTINCT rar.userid) AS enrolled,
                   COUNT(DISTINCT ul.userid) AS active
            FROM {context} ctx
                LEFT JOIN (
                    SELECT ra.userid, ra.contextid
                    FROM {role_assignments} ra
                    JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
                ) rar ON rar.contextid = ctx.id
                LEFT JOIN {user_lastaccess} ul
                       ON ul.courseid = ctx.instanceid
                           AND ul.userid = rar.userid
                           AND ul.timeaccess >= :active_window_starttime
            WHERE ctx.instanceid = $courseid AND ctx.contextlevel = :context_level_course
            GROUP BY ctx.instanceid
        ", [
            'context_level_course' => CONTEXT_COURSE,
            'courseid' => $courseid,
            'active_window_starttime' => $active_window_starttime,
        ]);

        if (!$record) {
            debugging("Could not get student counts for course $courseid, has it been deleted?");
            return null;
        }

        return new student_counts($record->enrolled, $record->active, $now);
    }

    /**
     * Queue a task so that the student counts are saved at the end of the given SE for the relevant course.
     *
     * @param survey_execution $survey_execution
     * @return void
     */
    public function queue_saving_for_se(survey_execution $survey_execution): void {
        if (!$survey_execution->get('starttime') || !$survey_execution->get('endtime')) {
            throw new coding_exception(
                "Given SE is missing starttime or endtime! It has status {$survey_execution->get('status')}."
            );
        }

        $new_task = save_student_counts_task::create(
            courseid: $survey_execution->get('courseid'),
            active_window_starttime: self::get_active_user_window_starttime($survey_execution),
            // If the endtime has already elapsed, we want to run ASAP.
            run_at: $this->clock->now() > $survey_execution->get('endtime') ? null : $survey_execution->get('endtime'),
        );

        if (\core\task\manager::get_queued_adhoc_task_record($new_task)) {
            // The same task is already queued.
            return;
        }

        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // Delete existing tasks for this course.
        $this->dequeue_by_courseid($survey_execution->get('courseid'));

        // Queue the new one.
        \core\task\manager::queue_adhoc_task($new_task);

        $transaction->allow_commit();
    }

    /**
     * Gets the saved student counts for the given SE or calculates them now if there are none saved yet.
     *
     * @param survey_execution $survey_execution
     * @return student_counts|null
     */
    public function get_saved_or_current_for_se(survey_execution $survey_execution): ?student_counts {
        global $DB;
        $saved_record = $DB->get_record('block_coursefeedback_student_counts', [
            'courseid' => $survey_execution->get('courseid'),
        ]);
        if ($saved_record) {
            return self::from_record($saved_record);
        }

        // No student counts saved. The evaluation is probably ongoing?
        // Try to get the current student counts.
        return $this->calculate_current(
            $survey_execution->get('courseid'),
            self::get_active_user_window_starttime($survey_execution)
        );
    }

    /**
     * Gets only previously saved student counts, but for multiple courses at once.
     *
     * @param int[] $courseids
     * @return array<int, student_counts> Student counts indexed by course id.
     */
    public function get_only_saved_for_many_courses(array $courseids): array {
        if (!$courseids) {
            return [];
        }

        global $DB;
        $records = $DB->get_records_list('block_coursefeedback_student_counts', 'courseid', $courseids);

        $results = [];
        foreach ($records as $record) {
            $results[$record->courseid] = self::from_record($record);
        }
        return $results;
    }

    /**
     * Deletes all queued save tasks for the given course.
     *
     * @param int $courseid
     * @return void
     */
    public function dequeue_by_courseid(int $courseid): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        /** @var save_student_counts_task[] $existing_tasks */
        $existing_tasks = \core\task\manager::get_adhoc_tasks(save_student_counts_task::class, skiprunning: true);

        foreach ($existing_tasks as $existing_task) {
            if ($existing_task->get_custom_data()->courseid === $courseid) {
                \core\task\manager::delete_adhoc_task($existing_task->get_id());
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Turn a raw DB record into a {@see student_counts} instance.
     *
     * @param object $record
     * @return student_counts
     */
    private static function from_record(object $record): student_counts {
        return new student_counts($record->enrolled, $record->active, $record->as_of_timestamp);
    }
}
