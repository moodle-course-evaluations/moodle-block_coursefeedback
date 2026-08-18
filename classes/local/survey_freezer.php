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
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use core\exception\moodle_exception;

/**
 * Checks if a resource is frozen, i.e., can't be modified significantly due to active surveys.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_freezer {

    /**
     * Returns true if the survey execution is frozen, i.e., can't be modified in a structurally significant manner.
     *
     * @param survey_execution $se
     * @return bool
     */
    public function is_se_frozen(survey_execution $se): bool {
        return $se->get('status') == survey_execution::STATUS_STARTED;
    }

    /**
     * Throws if the survey execution is frozen.
     *
     * @param survey_execution $se
     * @param string $action_debug_info To include in the debuginfo of the {@see moodle_exception}.
     * @return void
     */
    public function check_se_action(survey_execution $se, string $action_debug_info): void {
        if ($this->is_se_frozen($se)) {
            throw new moodle_exception(
                'survey_execution_frozen_short',
                'block_coursefeedback',
                debuginfo: "cannot $action_debug_info due to survey execution '{$se->get('id')}' in status '{$se->get('status')}'"
            );
        }
    }

    /**
     * Gets the started (and finished) survey executions using the given surveypart.
     *
     * @param surveypart|int $surveypart_or_id
     * @return survey_execution[]
     */
    private function get_started_ses_using_sp(surveypart|int $surveypart_or_id): array {
        $surveypartid = $surveypart_or_id instanceof surveypart ? $surveypart_or_id->get('id') : $surveypart_or_id;

        global $DB;
        $se_fields = survey_execution::get_sql_fields('se', 'se_');
        $records = $DB->get_records_sql("
            SELECT DISTINCT $se_fields
            FROM {" . survey_execution::TABLE . "} se
            JOIN {" . survey_part_execution::TABLE . "} spe ON se.id = spe.surveyexecutionid
            WHERE spe.surveypartid = :surveypartid AND se.status = :status
        ", ['surveypartid' => $surveypartid, 'status' => survey_execution::STATUS_STARTED]);

        return array_filter(array_map(fn($record) => survey_execution::extract($record, 'se_'), $records));
    }

    /**
     * Returns true if the survey part is frozen, i.e., can't be modified.
     *
     * @param surveypart|int $surveypart_or_id
     * @return bool
     */
    public function is_survey_part_frozen(surveypart|int $surveypart_or_id): bool {
        return count($this->get_started_ses_using_sp($surveypart_or_id)) > 0;
    }

    /**
     * Throws if the given survey part contained is frozen due to an ongoing or past survey execution.
     *
     * @param surveypart|int $surveypart_or_id
     * @param string $action_debug_info To include in the debuginfo of the {@see moodle_exception}.
     * @return void
     */
    public function check_survey_part_action(surveypart|int $surveypart_or_id, string $action_debug_info): void {
        $started_ses = $this->get_started_ses_using_sp($surveypart_or_id);
        if ($started_ses) {
            $se_ids = implode(', ', array_map(fn($se) => "'{$se->get('id')}'", $started_ses));
            throw new moodle_exception(
                'surveypart_frozen',
                'block_coursefeedback',
                debuginfo: "cannot $action_debug_info in survey part '{$surveypart_or_id}' due to started survey executions: "
                . $se_ids
            );
        }
    }
}
