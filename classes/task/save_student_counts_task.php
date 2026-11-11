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

namespace block_coursefeedback\task;

use block_coursefeedback\local\manager\student_count_manager;
use core\clock;
use core\di;
use core\exception\coding_exception;
use core\task\adhoc_task;

/**
 * Saves the enrolled and active student counts of a course at a specified time (evaluation end time).
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_student_counts_task extends adhoc_task {

    /**
     * Create a new task instance wih the given arguments.
     *
     * @param int $courseid
     * @param int $active_window_starttime
     * @param int|null $run_at Null means 'run ASAP'.
     * @return self
     */
    public static function create(int $courseid, int $active_window_starttime, ?int $run_at): self {
        $task = new self();
        $task->set_custom_data([
            'courseid' => $courseid,
            'active_window_starttime' => $active_window_starttime,
        ]);
        if ($run_at) {
            $task->set_next_run_time($run_at);
        }
        $task->set_attempts_available(3);
        return $task;
    }

    #[\Override]
    public function execute(): void {
        [
            'courseid' => $courseid,
            'active_window_starttime' => $active_window_starttime,
        ] = (array) $this->get_custom_data();

        static $max_start_delay = 24 * 60 * 60; // 1 day.
        $intended_run_time = $this->get_next_run_time() ?: null;
        if ($intended_run_time && di::get(clock::class)->time() > $intended_run_time + $max_start_delay) {
            // It makes no sense to save student counts much later than the evaluation end time.
            throw new coding_exception("Task should have been run at $intended_run_time or at most 1 day later.");
        }

        $student_count = di::get(student_count_manager::class)->calculate_current($courseid, $active_window_starttime);
        if (!$student_count) {
            return;
        }

        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $existing_record = $DB->get_record('block_coursefeedback_student_counts', ['courseid' => $courseid]);

        $data = [
            'courseid' => $courseid,
            'enrolled' => $student_count->enrolled,
            'active' => $student_count->active,
            'active_window_starttime' => $active_window_starttime,
            'as_of_timestamp' => $student_count->as_of_timestamp,
        ];

        mtrace('Data to save: ' . implode(', ', array_map(fn($key, $value) => "$key = $value", array_keys($data), $data)));

        if (!$existing_record) {
            $id = $DB->insert_record('block_coursefeedback_student_counts', $data);

            mtrace("Inserted new record with ID $id.");
        } else {
            $DB->update_record('block_coursefeedback_student_counts', [
                'id' => $existing_record->id,
                ...$data,
            ]);

            mtrace(
                "Updated existing record with ID $existing_record->id that had been saved at $existing_record->as_of_timestamp."
            );
        }

        $transaction->allow_commit();
    }
}
