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
 * block_coursefeedback new_semester.php description here.
 *
 * @package    block_coursefeedback
 * @copyright  2026  <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\form\organization_semester_form;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;

require_once(__DIR__ . '/../../config.php');

global $CFG, $DB, $OUTPUT, $PAGE;

$organizationid = required_param('organizationid', PARAM_INT);
$semesterid = required_param('semesterid', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/new_semester.php', [
    'organizationid' => $organizationid,
    'semesterid' => $semesterid,
]));
$PAGE->set_context(context_system::instance());

require_login();

$semester = course_semester_mapping::get_instance()->get_semester_by_id($semesterid);

[$organization, $orgsem] = organization::get_for_semester($organizationid, $semester);

if ($orgsem) {
    debugging("The semester '$semester->name' is already initialized.");
    redirect(new moodle_url('/blocks/coursefeedback/organization_settings.php', [
        'id' => $organizationid,
        'semesterid' => $semesterid,
    ]));
}

$mform = new organization_semester_form($PAGE->url, $organization, $semester);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/blocks/coursefeedback/organization_settings.php', [
        'id' => $organizationid,
        'semesterid' => $semesterid,
    ]));
} else if ($submitted_data = $mform->get_data()) {
    $orgsem = (new organization_semester())->set_many((array) $submitted_data);

    $orgsem->save();
} else if ($mform->no_submit_button_pressed()) {
    $base_org_sem_id = required_param('base_orgsem', PARAM_INT);
    $base_orgsem = organization_semester::get_record(['id' => $base_org_sem_id]);
    $mform->set_data($base_orgsem->to_record());
}

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');

$renderer->render_organization_page($organization, null, 'semester_settings', $mform->display(...));

echo $OUTPUT->footer();
