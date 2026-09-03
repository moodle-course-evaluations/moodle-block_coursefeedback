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
 * Edit a survey item.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\scale;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\survey_freezer;
use core\di;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();

$surveypartid = required_param('surveypartid', PARAM_INT);
$surveypart = surveypart::get_record(['id' => $surveypartid], MUST_EXIST);

permission_manager::require_permission_for_editing_surveypart($surveypart);

$organization_id = $surveypart->get('organizationid');
$organization = $organization_id ? organization::get_record(['id' => $organization_id], MUST_EXIST) : null;

$id = optional_param('id', null, PARAM_INT);
di::get(survey_freezer::class)
    ->check_survey_part_action($surveypart, $id ? "edit scale '$id'" : "add scale");

$params = ['surveypartid' => $surveypartid];
$scale = null;
if ($id) {
    $scale = scale::get_record(['id' => $id], MUST_EXIST);
    if ($scale->get('surveypartid') != $surveypart->get('id')) {
        throw new coding_exception('Scale does not belong to surveypart');
    }
    $params['id'] = $id;
}

breadcrumbs_manager::setup_edit_survey_scale($surveypart, $id, $organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/scale_edit.php', $params));
$PAGE->set_context(context_system::instance());
if ($scale) {
    $title = get_string('edit_scale', 'block_coursefeedback');
} else {
    $title = get_string('new_scale', 'block_coursefeedback');
}
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url(
    '/blocks/coursefeedback/surveyitem_edit.php',
    ['surveypartid' => $surveypartid, 'type' => 'scalequestion']
);

$mform = new \block_coursefeedback\local\form\edit_scale_form(null, ['surveypart' => $surveypart, 'id' => $id]);
if ($scale) {
    $mform->set_data($scale->to_record());
}
if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $mform->get_data()) {
    if (!$scale) {
        $scale = new scale();
        $scale->set('surveypartid', $surveypartid);
    }

    $scale->set_many(scale::properties_filter($data));
    $scale->save();

    $returnurl->param('scaleid', $scale->get('id'));
    redirect($returnurl);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
