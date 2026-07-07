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

/**
 * Renders a report over the results of the evaluation in this course.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE, $USER;

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\js_util;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\surveyitem\surveyitem_manager;

require_login();

$surveypartexecutionoptionid = required_param('id', PARAM_INT);
$slot = response_slot::get_record(['id' => $surveypartexecutionoptionid], MUST_EXIST);
$surveypartexecution = survey_part_execution::get_record(['id' => $slot->get('surveypartexecutionid')], MUST_EXIST);
$surveyexecution = survey_execution::get_record(['id' => $surveypartexecution->get('surveyexecutionid')], MUST_EXIST);

$slot_users = response_slot_user::get_records(['surveypartexecutionoptionid' => $slot->get('id')]);
$slot_user_ids = array_map(fn ($slot_user) => $slot_user->get('userid'), $slot_users);
$number_of_slots = response_slot::count_records(['surveypartexecutionid' => $surveypartexecution->get('id')]);

$context = context_course::instance($surveyexecution->get('courseid'));
$organization = organization::get_record(['id' => $surveyexecution->get('organizationid')], MUST_EXIST);

if (!permission_manager::can_manage_organization($organization)) {
    if ($slot_users || $number_of_slots >= 2) {
        // Require user to be in $slot_users.
        if (
            !in_array($USER->id, $slot_user_ids)
        ) {
            throw new \core\exception\moodle_exception('not_on_allowed_list', 'block_coursefeedback');
        }
    } else {
        require_capability('block/coursefeedback:viewcourseresults', $context);
    }
}

$PAGE->set_context($context);

$course = get_course($surveyexecution->get('courseid'));
// We've checked that the user is allowed to see the report, but they might not be allowed to see the course.
if (can_access_course($course, onlyactive: true)) {
    // If they are allowed to see the course, we also want to show the course navbar and the like.
    $PAGE->set_course($course);
}

$title = get_string('report', 'block_coursefeedback');

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/report.php'), ['id' => $surveypartexecutionoptionid]);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$data = surveyitem_manager::export_report_for_slot($slot);

if ($data !== null) {
    js_util::js_call_amd('block_coursefeedback/report', 'init', [$data]);
}

if ($slot_users) {
    $users = $DB->get_records_list('user', 'id', $slot_user_ids);
    $usernames = array_map(fn ($user) => fullname($user), $users);
} else if (has_capability('block/coursefeedback:viewcourseresults', $context)) {
    $usernames = [fullname($USER)];
} else {
    $usernames = [];
}

$template = [
    'coursename' => get_course($surveyexecution->get('courseid'))->fullname,
    'usernames' => join(", ", $usernames),
    'semester' => course_semester_mapping::get_instance()->get_current_semester()->name,
];

if ($data === null) {
    $template['not_enough_responses'] = true;
} else {
    $template['questions'] = $data;
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_coursefeedback/report/report', $template);

echo $OUTPUT->footer();
