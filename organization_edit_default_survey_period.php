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
 * Edit organizations default survey period.
 *
 * @package     block_coursefeedback
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\form\edit_default_survey_period_form;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization_default_survey_period($organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_edit_default_survey_period.php', ['id' => $id]));
$title = get_string('edit_default_survey_period', 'block_coursefeedback');

$PAGE->set_context($context);
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $id]);

$mform = new edit_default_survey_period_form($PAGE->url);
$mform->set_data($organization->to_record());

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    $organization->set_many(organization::properties_filter($data));
    $organization->update();
    redirect($returnurl);
} // Else display form.

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
