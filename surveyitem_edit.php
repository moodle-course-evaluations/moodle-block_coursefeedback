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
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\survey_freezer;
use block_coursefeedback\local\surveyitem\surveyitem_form;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\di;
use core\exception\moodle_exception;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();

$id = optional_param('id', null, PARAM_INT);
$surveypartid = required_param('surveypartid', PARAM_INT);
$surveypart = surveypart::get_record(['id' => $surveypartid], MUST_EXIST);

permission_manager::require_permission_for_editing_surveypart($surveypart);

$organization_id = $surveypart->get('organizationid');
$organization = $organization_id ? organization::get_record(['id' => $organization_id], MUST_EXIST) : null;

di::get(survey_freezer::class)
    ->check_survey_part_action($surveypart, $id ? "edit survey item '$id'" : "add survey item");

$type = required_param('type', PARAM_ALPHANUMEXT);
$surveyitemtype = surveyitem_manager::get_surveyitemtype($type);
if (!$surveyitemtype->can_be_added()) {
    throw new moodle_exception('cannot_manually_add', 'block_coursefeedback', a: $surveyitemtype->get_name());
}

$params = ['surveypartid' => $surveypartid, 'type' => $type];

$surveyitem = null;
if ($id) {
    $params['id'] = $id;
    $surveyitem = surveyitem::get_record(['id' => $id], MUST_EXIST);
    if ($surveyitem->get('surveypartid') !== $surveypartid || $surveyitem->get('surveyitemtype') !== $type) {
        // Generic error message.
        throw new coding_exception('Could not load surveyitem');
    }
}

breadcrumbs_manager::setup_edit_surveyitem($surveypart, $surveyitem, $organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/surveyitem_edit.php', $params));
if ($id) {
    $title = get_string('edit_surveyitem', 'block_coursefeedback');
} else {
    $title = get_string('new_surveyitem', 'block_coursefeedback');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $surveypartid]);


if ($surveyitemtype instanceof surveyitemtype_with_settings) {
    $mform = $surveyitemtype->get_settings_form($PAGE->url, $surveypart);
} else {
    if ($id) {
        throw new coding_exception('Cannot edit item of type ' . $type);
    }

    require_sesskey();
    $transaction = $DB->start_delegated_transaction();
    $sortindex = surveyitem::count_records(['surveypartid' => $surveypartid]);
    $surveyitem = new surveyitem();
    $surveyitem->set('surveypartid', $surveypartid);
    $surveyitem->set('surveyitemtype', $type);
    $surveyitem->set('sortindex', $sortindex);
    $surveyitem->save();
    $transaction->allow_commit();
    redirect($returnurl);
}

if ($surveyitem) {
    $data = $surveyitemtype->load_settings_form_data($surveyitem);
    $mform->set_data($data);
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if (($data = $mform->get_data()) && !$mform->no_submit_button_pressed()) {
    $transaction = $DB->start_delegated_transaction();
    if (!$surveyitem) {
        $sortindex = surveyitem::count_records(['surveypartid' => $surveypartid]);
        $surveyitem = new surveyitem();
        $surveyitem->set('surveypartid', $surveypartid);
        $surveyitem->set('surveyitemtype', $type);
        $surveyitem->set('sortindex', $sortindex);
    }

    if (isset($data->text)) {
        [$text, $textformat] = surveyitem_form::editors_to_multilang_string($data->text, $surveypart->get_languages());
        $surveyitem->set('text', $text);
        $surveyitem->set('textformat', $textformat);
    }

    $surveyitem->save();
    $surveyitemtype->save_settings_form_data($surveyitem, $surveypart, $data);
    $transaction->allow_commit();
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
