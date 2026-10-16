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
 * block_coursefeedback semester_results.php description here.
 *
 * @package    block_coursefeedback
 * @copyright  2026  <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\semester_manager;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\results\results_service;
use block_coursefeedback\local\urls;
use core\di;

require_once(__DIR__ . '/../../config.php');

// Disable moodle specific debug messages and any errors in output.
if (!defined('NO_DEBUG_DISPLAY')) {
    define('NO_DEBUG_DISPLAY', true);
}

global $CFG, $DB, $OUTPUT, $PAGE;

$organizationid = required_param('organizationid', PARAM_INT);
$orgsemid = required_param('orgsemid', PARAM_INT);

$download = optional_param('download', null, PARAM_ALPHANUMEXT);

if (is_numeric($download)) {
    $download = clean_param($download, PARAM_INT);
} else if (!in_array($download, [null, 'overview', 'default'])) {
    throw new invalid_parameter_exception("'download' must be absent, an event type id, 'overview', or 'default'");
}

$params = [
    'organizationid' => $organizationid,
    'orgsemid' => $orgsemid,
];
if ($download) {
    $params['download'] = $download;
}

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/semester_results.php', $params));
$PAGE->set_context(context_system::instance());

require_login();

$semester_manager = di::get(semester_manager::class);
$results_service = di::get(results_service::class);

[$organization, $semester, $orgsem] = $semester_manager->get_triplet_by_orgsemid($organizationid, $orgsemid);

if ($download) {
    $string_params = [
        'orgname' => $organization->get('name'),
        'semestername' => $orgsem->get('semestername'),
    ];
    if ($download === 'overview') {
        $string_id = 'csv_filename_overview';
        $rows = $results_service->get_overview($orgsem);
    } else if ($download === 'default') {
        $string_id = 'csv_filename_default_questionnaire';
        $rows = $results_service->get_for_default_questionnaire($orgsem);
    } else {
        $string_id = 'csv_filename_eventtype';
        $eventtype = eventtype::get_record(['id' => $download]);
        $rows = $results_service->get_by_semester_and_eventtype($orgsem, $eventtype);
        $string_params['eventtypename'] = $eventtype->get('name');
    }

    $filename_base = get_string($string_id, 'block_coursefeedback', $string_params);
    $filename = preg_replace('/[^[:alnum:]äöüÄÖÜß]/', '_', $filename_base) . '.csv';

    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));

    $stream = fopen('php://output', 'w');

    foreach ($rows as $row) {
        fputcsv($stream, $row);
    }

    die;
}

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization($organization);

$PAGE->set_heading($organization->get('name'));
$PAGE->set_title($semester?->name ?? $orgsem->get('semestername') . $PAGE::TITLE_SEPARATOR . $organization->get('name'));

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');

$event_types_table = new html_table();
$event_types_table->head = [
    get_string('eventtype', 'block_coursefeedback'),
    get_string('assigned_questionnaire', 'block_coursefeedback'),
];

$questionnaires = surveypart::get_surveyparts_available_for_organization($organization);

if ($orgsem->get('default_surveypartid')) {
    $questionnaire = $questionnaires[$orgsem->get('default_surveypartid')] ?? null;
    if (!$questionnaire) {
        // Rare edge-case: The questionnaire has become unavailable for some reason after it had been selected.
        $questionnaire = surveypart::get_record(['id' => $orgsem->get('default_surveypartid')], MUST_EXIST);
    }

    $event_types_table->data[] = new html_table_row([
        html_writer::tag('em', get_string('default_questionnaire_parens', 'block_coursefeedback')),
        html_writer::link(urls::questionnaire($questionnaire->get('id')), $questionnaire->get('name')),
        html_writer::link(urls::semester_results_download($organizationid, $orgsemid, null), get_string('download')),
    ]);
}

$eventtypes = eventtype::get_records(['orgsemid' => $orgsemid]);
foreach ($eventtypes as $eventtype) {
    $row = [$eventtype->get('name')];

    if (!$eventtype->get('surveypartid')) {
        $cell = new html_table_cell(html_writer::tag('em', get_string('no_questionnaire_assigned', 'block_coursefeedback')));
        $cell->colspan = 2;
        $row[] = $cell;
    } else {
        $questionnaire = $questionnaires[$eventtype->get('surveypartid')] ?? null;
        if (!$questionnaire) {
            // Rare edge-case: The questionnaire has become unavailable for some reason after it had been selected.
            $questionnaire = surveypart::get_record(['id' => $eventtype->get('surveypartid')], MUST_EXIST);
        }

        $row[] = html_writer::link(urls::questionnaire($questionnaire->get('id')), $questionnaire->get('name'));
        $row[] = html_writer::link(urls::semester_results_download(
            $organizationid,
            $orgsemid,
            $eventtype->get('id')
        ), get_string('download'));
    }

    $event_types_table->data[] = $row;
}

$renderer->render_organization_page(
    $organization,
    $semester,
    $orgsem,
    'semester_results',
    html_writer::div(
        html_writer::link(
            urls::semester_results_download_overview($organizationid, $orgsemid),
            get_string('download_overview', 'block_coursefeedback')
        ),
        'mb-3'
    ) . html_writer::table($event_types_table)
);

echo $OUTPUT->footer();
