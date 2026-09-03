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
 * Edit courses without evaluation.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\survey_execution_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_category;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\table\evaluations_table;
use core\di;

require_once(__DIR__ . '/../../config.php');
global $DB, $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization_evaluations($organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_evaluations.php', ['id' => $id]));
$PAGE->set_context($context);

$action = optional_param('action', null, PARAM_ALPHANUMEXT);
if ($action) {
    require_sesskey();
    switch ($action) {
        case 'delete':
            require_capability('block/coursefeedback:manageorganizations', $context);
            $courseids = required_param_array('selected', PARAM_INT);

            [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $surveyexecutions = survey_execution::get_records_select("courseid $insql", $inparams);

            foreach ($surveyexecutions as $surveyexecution) {
                if ($surveyexecution->get('organizationid') !== $organization->get('id')) {
                    $course = get_course($surveyexecution->get('courseid'));
                    throw new moodle_exception('course_not_in_org', 'block_coursefeedback', a: [
                        'course_name' => $course->fullname,
                        'org_name' => $organization->get('name'),
                    ]);
                }
            }

            $se_manager = di::get(survey_execution_manager::class);
            foreach ($surveyexecutions as $surveyexecution) {
                $se_manager->delete_survey_execution($surveyexecution);
            }

            redirect($PAGE->url);
    }
}

$title = get_string('list_of_evaluations', 'block_coursefeedback') . ': ' . $organization->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $id]);

$table = new evaluations_table(course_semester_mapping::get_instance()->get_current_semester(), $organization);

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');
$renderer->render_organization_page($id, 'evaluations', function () use ($table) {
    $table->out(0, false);
});

echo $OUTPUT->footer();
