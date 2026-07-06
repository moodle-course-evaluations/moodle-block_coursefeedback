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

namespace block_coursefeedback\task;

use block_coursefeedback\local\course_feedback_data;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_texts;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\teaching_event;
use block_coursefeedback\local\survey_execution_data;
use context_course;
use core_user;
use moodle_url;

/**
 * Task file for sending the survey created message to teachers.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_survey_created_message_task extends \core\task\adhoc_task {

    /**
     * Creates an instance of the task (but does not schedule it yet).
     * @param array $surveyexecutionids
     * @return self
     */
    public static function create_instance(array $surveyexecutionids) {
        $task = new self();
        $task->set_custom_data([
            'surveyexecutionids' => $surveyexecutionids,
        ]);
        return $task;
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $custom_data = $this->get_custom_data();
        $surveyexecutionids = $custom_data->surveyexecutionids;

        if (!$surveyexecutionids) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($surveyexecutionids, SQL_PARAMS_NAMED);
        $surveyexecutions = survey_execution::get_records_select("id $insql", $inparams);

        $organization_by_id = [];
        $organization_texts_by_id = [];

        foreach ($surveyexecutions as $surveyexecution) {
            $organizationid = $surveyexecution->get('organizationid');
            if (!isset($organization_texts_by_id[$organizationid])) {
                $organization_by_id[$organizationid] = organization::get_record(['id' => $organizationid]);
                $organization_texts_by_id[$organizationid] = organization_texts::get_record(['organizationid' => $organizationid]);
            }

            $organization = $organization_by_id[$organizationid];
            $organization_texts = $organization_texts_by_id[$organizationid];

            if (!$organization || !$organization_texts) {
                mtrace('Skipping mail sending for survey execution ' . $surveyexecution->get('id') .
                    ' because of missing organization or organization_texts for id ' . $organizationid);
                continue;
            }

            if (
                !$organization_texts->get('survey_created_message_subject')
                || !$organization_texts->get('survey_created_message_body')
            ) {
                mtrace('Skipping mail sending for survey execution ' . $surveyexecution->get('id') .
                    ' because texts for organization ' . $organization->get('id') . ' are empty.');
                continue;
            }

            $courseid = $surveyexecution->get('courseid');
            $context = context_course::instance($courseid);
            $course = get_course($courseid);
            $users = get_enrolled_users($context, 'block/coursefeedback:isevaluationteacher');

            foreach ($users as $user) {
                $subject = self::replace_placeholders(
                    $organization_texts->get('survey_created_message_subject'),
                    $user,
                    $surveyexecution,
                    $organization,
                    $course,
                );

                $body = self::replace_placeholders(
                    $organization_texts->get('survey_created_message_body'),
                    $user,
                    $surveyexecution,
                    $organization,
                    $course,
                );

                $message = new \core\message\message();
                $message->component = 'block_coursefeedback';
                $message->name = 'teacher_survey_created';
                $message->userfrom = core_user::get_noreply_user();
                $message->userto = $user;
                $message->subject = $subject;
                $message->fullmessage = $message->fullmessagehtml = $body;
                $message->fullmessageformat = FORMAT_HTML;
                $message->smallmessage = $subject;
                $message->notification = 1;
                $message->contexturl = new moodle_url('/blocks/coursefeedback/course.php', ['id' => $course->id]);
                $message->contexturlname = get_string('course_settings', 'block_coursefeedback');

                message_send($message);
            }
        }
    }

    /**
     * Replaces the placeholders in $text with the given information.
     * @param string $text
     * @param object $user
     * @param survey_execution $survey_execution
     * @param organization $organization
     * @param object $course
     * @return string
     */
    private static function replace_placeholders(
        string $text,
        object $user,
        survey_execution $survey_execution,
        organization $organization,
        object $course
    ): string {
        $dateformatstr = get_string('strftimedatetimeshort', 'core_langconfig');
        $starttime = $survey_execution->get('starttime') ?? $organization->get('default_evaluation_starttime');
        $endtime = $survey_execution->get('endtime') ?? $organization->get('default_evaluation_endtime');
        $placeholders = [
            '##NAME##' => fullname($user),
            '##COURSE_NAME##' => $course->fullname,
            '##PERIOD##' => userdate($starttime, $dateformatstr) . ' - ' . userdate($endtime, $dateformatstr),
            '##SETTINGS_URL##' => (new moodle_url('/blocks/coursefeedback/course.php', ['id' => $course->id]))->out(false),
        ];
        return str_ireplace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
