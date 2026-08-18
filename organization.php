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
 * Show details for an organization.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\default_survey_creation_method\default_survey_creation_method;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);
$PAGE->set_context($context);
permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization($organization);
$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $id]));

$title = $organization->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

echo $OUTPUT->header();

$context = [
    'name' => $organization->get('name'),
    'organization_settings_url' =>
        new moodle_url('/blocks/coursefeedback/organization_settings.php', ['id' => $id]),
    'courses_without_evaluation_url' =>
        new moodle_url('/blocks/coursefeedback/organization_courses_without_evaluation.php', ['id' => $id]),
    'evaluations_url' =>
        new moodle_url('/blocks/coursefeedback/organization_evaluations.php', ['id' => $id]),
    'default_surveypart_url' =>
        new moodle_url('/blocks/coursefeedback/organization_default_surveypart.php', ['id' => $id]),
    'edit_default_survey_period_url' =>
        new moodle_url('/blocks/coursefeedback/organization_edit_default_survey_period.php', ['id' => $id]),
    'surveyparts_url' =>
        new moodle_url('/blocks/coursefeedback/surveyparts.php', ['organizationid' => $id]),
    'has_default_survey_period' =>
        $organization->get('default_evaluation_starttime') && $organization->get('default_evaluation_endtime'),
    'default_evaluation_starttime' =>
        $organization->get('default_evaluation_starttime'),
    'default_evaluation_endtime' =>
        $organization->get('default_evaluation_endtime'),
];

if (get_config('block_coursefeedback', 'default_survey_creation_method') === default_survey_creation_method::METHOD_RUB) {
    $context['rub_eventtype_mapping_url'] =
        new moodle_url('/blocks/coursefeedback/organization_rub_eventtype_mapping.php', ['id' => $id]);
}

echo $OUTPUT->render_from_template('block_coursefeedback/organization', $context);

echo $OUTPUT->footer();
