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
use block_coursefeedback\local\default_survey_creation_method\default_survey_creation_method;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\semester_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_category;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\semester\semester_state;
use block_coursefeedback\local\table\courses_without_evaluation_table;
use block_coursefeedback\task\send_survey_created_message_task;
use core\di;
use core\task\manager;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

$organizationid = required_param('id', PARAM_INT);
$orgsemid = optional_param('orgsemid', null, PARAM_INT);

$params = ['id' => $organizationid];
if ($orgsemid) {
    $params['orgsemid'] = $orgsemid;
}

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_courses_without_evaluation.php', $params));
$PAGE->set_context(context_system::instance());

require_login();

$semester_manager = di::get(semester_manager::class);
[$organization, $semester, $orgsem] = $orgsemid
    ? $semester_manager->get_triplet_by_orgsemid($organizationid, $orgsemid)
    : $semester_manager->get_triplet_by_current_semester($organizationid);

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization_courses_without_evaluation($organization);


$action = optional_param('action', null, PARAM_ALPHANUMEXT);
if ($action) {
    require_sesskey();
    switch ($action) {
        case 'create-default':
            if (!$orgsem->get('evaluation_starttime') || !$orgsem->get('evaluation_endtime')) {
                throw new moodle_exception('define_evaluation_period_before', 'block_coursefeedback');
            }
            $courseids = required_param_array('selected', PARAM_INT);
            $coursecatids = organization_category::get_all_recursive_coursecatids($organization->get('id'));
            foreach ($courseids as $courseid) {
                $course = get_course($courseid);
                if (!in_array($course->category, $coursecatids)) {
                    throw new moodle_exception('course_not_in_org', 'block_coursefeedback', a: [
                        'course_name' => $course->fullname,
                        'org_name' => $organization->get('name'),
                    ]);
                }
            }
            $surveyexecutions = default_survey_creation_method::get_instance()::create_survey_execution(
                $courseids,
                $organization,
                $orgsem
            );
            $surveyexecutionids = array_map(fn (survey_execution $se) => $se->get('id'), $surveyexecutions);
            manager::queue_adhoc_task(
                send_survey_created_message_task::create_instance($surveyexecutionids)
            );
            redirect($PAGE->url);
    }
}

$PAGE->set_heading($organization->get('name'));
$PAGE->set_title(
    get_string('list_of_courses_without_evaluation', 'block_coursefeedback') . $PAGE::TITLE_SEPARATOR . $organization->get('name')
);

$returnurl = new moodle_url('/blocks/coursefeedback/organization_settings.php', ['id' => $organizationid]);

$table = new courses_without_evaluation_table($organization, $orgsem, $semester);

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');
$renderer->render_organization_page($organization, $semester, $orgsem, 'courses', fn() => $table->out(0, false));

echo $OUTPUT->footer();
