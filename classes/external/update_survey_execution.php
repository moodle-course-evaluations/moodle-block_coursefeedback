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

namespace block_coursefeedback\external;

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\semester_manager;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\survey_freezer;
use block_coursefeedback\output\survey_execution_period;
use coding_exception;
use context_system;
use core\di;
use core\exception\moodle_exception;
use core_date;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * External api to update a survey execution.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_survey_execution extends external_api {

    /**
     * Returns description of method parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'surveyexecutionid' => new external_value(PARAM_INT),
            'starttime' => new external_value(PARAM_RAW_TRIMMED),
            'endtime' => new external_value(PARAM_RAW_TRIMMED),
        ]);
    }

    /**
     * Returns description of method return.
     * @return external_function_parameters
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            "html" => new external_value(PARAM_RAW),
        ]);
    }

    /**
     * Accepts either a unix timestamp or an ISO8601 string, and returns a unix timestamp.
     *
     * @param string $string
     * @return int
     */
    private static function to_timestamp(string $string): int {
        if (is_numeric($string)) {
            return intval($string);
        }

        foreach ([DateTimeInterface::ATOM, 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'] as $format) {
            $datetime = DateTimeImmutable::createFromFormat($format, $string, core_date::get_user_timezone_object());
            if ($datetime) {
                return $datetime->getTimestamp();
            }
        }

        throw new coding_exception("Invalid timestamp format: $string");
    }

    /**
     * Does the thing.
     *
     * @param int $surveyexecutionid
     * @param string $starttime
     * @param string $endtime
     * @return array
     * @throws coding_exception
     */
    public static function execute(int $surveyexecutionid, string $starttime, string $endtime): array {
        [
            'surveyexecutionid' => $surveyexecutionid, 'starttime' => $starttime, 'endtime' => $endtime,
        ] = self::validate_parameters(self::execute_parameters(), [
            'surveyexecutionid' => $surveyexecutionid, 'starttime' => $starttime, 'endtime' => $endtime,
        ]);

        $starttime = self::to_timestamp($starttime);
        $endtime = self::to_timestamp($endtime);

        // Some sanity checks.
        if ($endtime <= $starttime) {
            throw new moodle_exception(
                "end_must_be_after_start",
                "block_coursefeedback",
                debuginfo: "$endtime (end) <= $starttime (start)"
            );
        }
        $duration = $endtime - $starttime;
        $one_year = 365 * 24 * 60 * 60;
        if ($duration > $one_year) {
            throw new moodle_exception(
                "end_must_be_within_1_year",
                "block_coursefeedback",
                debuginfo: "$duration (duration) > $one_year (1y)"
            );
        }

        $min = time() - 10 * $one_year;
        $max = time() + 10 * $one_year;
        if ($starttime < $min || $endtime >= $max) {
            throw new moodle_exception(
                "end_must_be_within_10_years",
                "block_coursefeedback",
                debuginfo: $starttime < $min ? "$starttime (start) < $min" : "$endtime (end) >= $max"
            );
        }

        global $DB;
        $transaction = $DB->start_delegated_transaction();

        $survey_execution = survey_execution::get_record(['id' => $surveyexecutionid], MUST_EXIST);

        self::validate_context(context_system::instance());
        $course = get_course($survey_execution->get('courseid'));

        [$organization, , $orgsem] = di::get(semester_manager::class)->get_triplet_by_course($course);

        if (
            !permission_manager::can_edit_course_survey_period(
                $course,
                $organization,
                is_frozen: di::get(survey_freezer::class)->is_se_frozen($survey_execution)
            )
        ) {
            throw new coding_exception('no_edit_survey_period', 'block_coursefeedback');
        }

        $survey_execution->set('starttime', $starttime);
        $survey_execution->set('endtime', $endtime);
        $survey_execution->update();

        $transaction->allow_commit();

        global $PAGE;
        $renderer = $PAGE->get_renderer('block_coursefeedback');
        $survey_execution_period = new survey_execution_period($survey_execution, $orgsem, editable: true);
        return [
            'html' => $renderer->render($survey_execution_period),
        ];
    }
}
