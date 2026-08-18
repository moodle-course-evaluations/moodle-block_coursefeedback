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
 * Edit a organization.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\form\edit_organization_form;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\user_organization_cache_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_category;
use block_coursefeedback\local\persistent\organization_user;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
require_capability('block/coursefeedback:manageorganizations', $context);

$id = optional_param('id', null, PARAM_INT);

$params = [];
$organization = null;
if ($id) {
    $params['id'] = $id;
    $organization = organization::get_record(['id' => $id], MUST_EXIST);
}

breadcrumbs_manager::setup_edit_organization($organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_edit.php', $params));
if ($id) {
    $title = get_string('edit_organization', 'block_coursefeedback');
} else {
    $title = get_string('new_organization', 'block_coursefeedback');
}

$PAGE->set_context($context);
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organizations.php');

$mform = new edit_organization_form($PAGE->url);

if ($organization) {
    $data = $organization->to_record();
    $data->userids = array_values(organization_user::get_organization_userids($organization->get('id')));
    $data->coursecatids = array_values(organization_category::get_organization_coursecatids($organization->get('id')));
    $mform->set_data($data);
}

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    if ($id) {
        $organization->set_many(organization::properties_filter($data));
        $organization->update();
    } else {
        $organization = new organization(0, $data);
        $organization->create();
    }
    organization_user::set_organization_userids($organization->get('id'), $data->userids);
    organization_category::set_organization_coursecatids($organization->get('id'), $data->coursecatids);
    user_organization_cache_manager::get_instance()->purge();
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
