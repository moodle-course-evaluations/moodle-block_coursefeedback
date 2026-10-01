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
 * block_coursefeedback semester_settings.php description here.
 *
 * @package    block_coursefeedback
 * @copyright  2026  <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\form\organization_semester_form;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\semester_info;
use block_coursefeedback\local\manager\semester_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use block_coursefeedback\output\semester_dropdown;
use core\di;

require_once(__DIR__ . '/../../config.php');

global $CFG, $DB, $OUTPUT, $PAGE;

$organizationid = required_param('organizationid', PARAM_INT);
$semesterid = required_param('semesterid', PARAM_INT);

$baseid = optional_param('baseid', null, PARAM_INT);

$params = [
    'organizationid' => $organizationid,
    'semesterid' => $semesterid,
];
if ($baseid) {
    $params['baseid'] = $baseid;
}

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/semester_settings.php', $params));
$PAGE->set_context(context_system::instance());

require_login();

$semester = course_semester_mapping::get_instance()->require_semester_by_id($semesterid);
[$organization, $semester_info] = organization::get_with_orgsem_by_semester($organizationid, $semester);
$orgsem = $semester_info->orgsem;

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization($organization);

$PAGE->set_heading($organization->get('name'));
$PAGE->set_title($semester_info->get_name() . $PAGE::TITLE_SEPARATOR . $organization->get('name'));

$mform = new organization_semester_form($PAGE->url);

if ($orgsem) {
    $mform->set_data($orgsem->to_record());
}

$base_orgsem = null;
if ($baseid) {
    $base_orgsem = organization_semester::get_record(['id' => $baseid]);

    $mform->set_data($base_orgsem->to_record());
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/blocks/coursefeedback/organization_settings.php', [
        'id' => $organizationid,
    ]));
} else if ($submitted_data = $mform->get_data()) {
    $transaction = $DB->start_delegated_transaction();

    $orgsem ??= new organization_semester();

    $orgsem->set_many(organization_semester::properties_filter($submitted_data));

    $orgsem->set('organizationid', $organizationid);
    $orgsem->set('semesterid', $semester->id);
    $orgsem->set('semestername', $semester->name);

    $orgsem->save();

    $transaction->allow_commit();

    redirect(new moodle_url('/blocks/coursefeedback/semester_settings.php', array_diff_key($params, array_flip(['baseid']))));
}

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');

$renderer->render_organization_page($organization, $semester_info, 'semester_settings', function () use (
    $mform,
    $renderer,
    $organizationid,
    $base_orgsem
) {
    global $PAGE;

    if ($base_orgsem) {
        echo get_string('filled_in_from', 'block_coursefeedback', $base_orgsem->get('semestername'));
    }
    $fill_in_dropdown = new semester_dropdown(
        di::get(semester_manager::class)->load_initialized_semesters($organizationid),
        fn($semester_info) => new moodle_url($PAGE->url, ['baseid' => $semester_info->orgsem->get('id')]),
        button_text: get_string('fill_in_from', 'block_coursefeedback'),
    );
    echo $renderer->render($fill_in_dropdown);

    $mform->display();
});

echo $OUTPUT->footer();
