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
 * List survey parts.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\table\surveyparts_table;
use core\output\single_button;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();

$organizationid = optional_param('organizationid', null, PARAM_INT);
$organization = $organizationid ? organization::get_record(['id' => $organizationid], MUST_EXIST) : null;

if ($organization) {
    permission_manager::require_manage_organization($organization);
} else {
    require_capability('block/coursefeedback:managesurveysglobally', context_system::instance());
}

breadcrumbs_manager::setup_questionnaires($organization);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(
    '/blocks/coursefeedback/surveyparts.php',
    $organizationid ? ['organizationid' => $organizationid] : []
));

$title = $organization
    ? get_string('questionnaires_in', 'block_coursefeedback', $organization->get('name'))
    : get_string('questionnaires', 'block_coursefeedback');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$table = new surveyparts_table($organizationid);

echo $OUTPUT->header();

echo $OUTPUT->render(new single_button(
    url: new moodle_url('/blocks/coursefeedback/surveypart_edit.php', $organizationid ? ['organizationid' => $organizationid] : []),
    label: get_string('new_surveypart', 'block_coursefeedback'),
    method: 'get',
    type: single_button::BUTTON_PRIMARY
)) . '<br><br>';

$table->out(48, false);

echo $OUTPUT->footer();
