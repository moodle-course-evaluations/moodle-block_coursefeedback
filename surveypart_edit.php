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
 * Edit a survey part.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\form\surveypart_edit_form;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\survey_freezer;
use core\di;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();

$params = [];
$organizationid = $surveypart = null;

$id = optional_param('id', null, PARAM_INT);
if ($id) {
    // We are editing an existing SP.
    $params['id'] = $id;
    $surveypart = surveypart::get_record(['id' => $id], MUST_EXIST);

    $organizationid = $surveypart->get('organizationid');

    $org_id_from_query = optional_param('organizationid', null, PARAM_INT);
    if ($org_id_from_query && $org_id_from_query !== $organizationid) {
        // Check that it matches the org ID from the SP.
        throw new coding_exception("Mismatching organizationid in URL ($org_id_from_query) and surveypart ($organizationid)");
    }
} else {
    // We're creating a new SP.
    $organizationid = optional_param('organizationid', null, PARAM_INT);
}

$organization = $organizationid ? organization::get_record(['id' => $organizationid], MUST_EXIST) : null;

if ($surveypart) {
    permission_manager::require_permission_for_editing_surveypart($surveypart);
} else if ($organization) {
    permission_manager::require_manage_organization($organization);
} else {
    require_capability('block/coursefeedback:managesurveysglobally', context_system::instance());
}

breadcrumbs_manager::setup_edit_questionnaire($surveypart, $organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/surveypart_edit.php', $params));
if ($id) {
    $title = get_string('edit_surveypart', 'block_coursefeedback');
} else {
    $title = get_string('new_surveypart', 'block_coursefeedback');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/surveyparts.php', $organizationid ? ['organizationid' => $organizationid] : []);

$freezer = di::get(survey_freezer::class);
if ($surveypart) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $freezer->check_survey_part_action($surveypart, 'edit name and languages');
        $is_frozen = false;
    } else {
        $is_frozen = $freezer->is_survey_part_frozen($surveypart);
    }
} else {
    $is_frozen = false;
}

$mform = new surveypart_edit_form($PAGE->url, organizationid: $organizationid, editable: !$is_frozen);
if ($surveypart) {
    $mform->set_data($surveypart->to_form_data());
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    $transaction = $DB->start_delegated_transaction();

    $surveypart = new surveypart($id ?? 0, $data);

    if ($id) {
        $surveypart->update();
    } else {
        $surveypart->create();
    }

    if (isset($data->languages)) {
        $surveypart->set_languages($data->languages);
    }

    $transaction->allow_commit();
    redirect($returnurl);
}
// Else display form.

echo $OUTPUT->header();

if ($is_frozen) {
    echo $OUTPUT->render_from_template('block_coursefeedback/info_box', [
        'message' => get_string('surveypart_frozen', 'block_coursefeedback'),
    ]);
}

$mform->display();

echo $OUTPUT->footer();
