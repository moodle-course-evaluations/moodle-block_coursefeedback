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
global $CFG, $DB, $OUTPUT, $PAGE, $USER, $SITE;

use block_coursefeedback\local\js_util;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\semester_manager;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\utils;
use core\clock;
use core\di;
use core\exception\not_found_exception;

require_login();

$slot_id = required_param('id', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/report.php'), ['id' => $slot_id]);

$slot_fields = response_slot::get_sql_fields('slot', 'slot_');
$spe_fields = survey_part_execution::get_sql_fields('spe', 'spe_');
$se_fields = survey_execution::get_sql_fields('se', 'se_');
$te_fields = teaching_event::get_sql_fields('te', 'te_');
$sp_fields = surveypart::get_sql_fields('sp', 'sp_');

$record = $DB->get_record_sql("
    SELECT $slot_fields, $spe_fields, $sp_fields, $se_fields, $te_fields
    FROM {" . response_slot::TABLE . "} slot
    JOIN {" . survey_part_execution::TABLE . "} spe ON spe.id = slot.surveypartexecutionid
    JOIN {" . surveypart::TABLE . "} sp ON sp.id = spe.surveypartid
    JOIN {" . survey_execution::TABLE . "} se ON se.id = spe.surveyexecutionid
    JOIN {" . teaching_event::TABLE . "} te ON te.id = spe.eventid
    WHERE slot.id = :slot_id
", ['slot_id' => $slot_id]);
if (!$record) {
    throw new not_found_exception('response slot', $slot_id);
}

$slot = response_slot::extract($record, 'slot_');
$spe = survey_part_execution::extract($record, 'spe_');
$questionnaire = surveypart::extract($record, 'sp_');
$surveyexecution = survey_execution::extract($record, 'se_');
$event = teaching_event::extract($record, 'te_');

$context = context_course::instance($surveyexecution->get('courseid'));
$PAGE->set_context($context);

$slot_users = response_slot_user::get_users($slot->get('id'), \core_user\fields::for_name());

$number_of_slots = response_slot::count_records(['surveypartexecutionid' => $spe->get('id')]);
$number_of_valid_spes = survey_part_execution::count_records_select(
    'surveyexecutionid = :se_id AND surveypartid IS NOT NULL',
    ['se_id' => $surveyexecution->get('id')]
);

[$organization, $semester, $orgsem] = di::get(semester_manager::class)
    ->get_triplet_by_orgsemid($surveyexecution->get('organizationid'), $surveyexecution->get('orgsemid'));

if (!permission_manager::can_manage_organization($organization)) {
    if ($slot_users || $number_of_slots >= 2) {
        // Require user to be in $slot_users.
        if (!isset($slot_users[$USER->id])) {
            throw new moodle_exception('not_on_allowed_list', 'block_coursefeedback');
        }
    } else {
        require_capability('block/coursefeedback:viewcourseresults', $context);
    }
}

$course = get_course($surveyexecution->get('courseid'));
// We've checked that the user is allowed to see the report, but they might not be allowed to see the course.
if (can_access_course($course, onlyactive: true)) {
    // If they are allowed to see the course, we also want to show the course navbar and the like.
    $PAGE->set_course($course);
}

$title = get_string('report', 'block_coursefeedback');

$PAGE->set_pagelayout('standard');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$data = surveyitem_manager::export_report_for_slot($slot);

if ($data !== null) {
    js_util::js_call_amd('block_coursefeedback/report', 'init', [$data]);
}

$teachers = utils::get_evaluation_teachers_and_roles_in_course($course->id);

$semester_name = $semester?->name ?? $orgsem->get('semestername');
if ($slot->get('name') && $slot->get('name') !== '-') {
    $slot_name = s($slot->get('name'));
} else {
    $slot_name = html_writer::tag('em', get_string('unnamed_slot', 'block_coursefeedback'));
}

$string_params = [
    'sitename' => s($SITE->shortname),
    'coursename' => s($course->fullname),
    'eventname' => s($event->get('name')),
    'slotname' => $slot_name,
];

$subtitle = $event->get('eventtypeid')
    ? get_string('report_subtitle_event', 'block_coursefeedback', $string_params)
    : get_string('report_subtitle_default', 'block_coursefeedback', $string_params);

$notes = [];
if ($number_of_valid_spes > 1) {
    if ($event->get('eventtypeid')) {
        $notes[] = get_string('report_note_event_questionnaire', 'block_coursefeedback', $string_params);
    } else {
        $notes[] = get_string('report_note_default_questionnaire', 'block_coursefeedback', $string_params);
    }
} else if ($number_of_slots <= 1) {
    $notes[] = get_string('report_note_all_results', 'block_coursefeedback', $string_params);
}

if ($number_of_slots > 1) {
    $notes[] = get_string('report_note_slot', 'block_coursefeedback', $string_params);
}

if (!$surveyexecution->has_ended()) {
    $notes[] = html_writer::tag('span', get_string('report_note_ongoing', 'block_coursefeedback'), ['class' => 'text-warning']);
}

$user_counts = utils::get_course_student_counts([$course->id]);
if (!$user_counts) {
    // TODO: Showing the current participant counts on a report from 2 months ago is less than ideal.
    // We should probably save the participant counts somewhere when the evaluation (starts and?) ends.
    throw new coding_exception("Could not get user counts for course with ID '$course->id'");
}
['enrolled_count' => $enrolled_student_count, 'recently_active_count' => $active_student_count] = (array) reset($user_counts);

[$se_responses, $spe_responses, $slot_responses] = utils::get_response_count_subtotals(
    $surveyexecution->get('id'),
    $spe->get('id'),
    $slot->get('id'),
);

// We're always primarily interested in the number of responses included in this report, i.e. are the slot responses.
$primary_response_count = $slot_responses;
$secondary_response_counts = [];
if ($number_of_slots > 1) {
    // If there are more slots in this SPE, also show the SPE subtotal.
    $secondary_response_counts[] = get_string('of_responses_in_this_event', 'block_coursefeedback', $spe_responses);
}
if ($number_of_slots > 1 || $number_of_valid_spes > 1) {
    // If there are more slots in this course, also show the course total.
    $secondary_response_counts[] = get_string('of_responses_in_this_course', 'block_coursefeedback', $se_responses);
}

$template = [
    'subtitle' => $subtitle,
    'evaluation_starttime' => $surveyexecution->get('starttime'),
    'evaluation_endtime' => $surveyexecution->get('endtime'),
    'event_name' => $event->get('name'),
    'questionnaire_name' => $questionnaire->get('name'),
    'teachers' => $teachers,
    'enrolled_student_count' => $enrolled_student_count,
    'active_student_count' => $active_student_count,
    'active_student_time' => di::get(clock::class)->time(),
    'semester' => $semester_name,
    'show_slot_info' => $number_of_slots > 1 || $slot_users,
    'slot_name' => $slot_name,
    'slot_users' => implode(', ', array_map(fullname(...), array_values($slot_users))),
    'show_notes' => count($notes) > 0,
    'notes' => $notes,
    'primary_response_count' => $primary_response_count,
    'secondary_response_counts' => $secondary_response_counts,
];

if ($data === null) {
    $template['not_enough_responses'] = true;
} else {
    $template['questions'] = $data;
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_coursefeedback/report/report', $template);

echo $OUTPUT->footer();
