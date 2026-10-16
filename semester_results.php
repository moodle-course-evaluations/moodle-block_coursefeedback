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
    $filename = $organization->get('name') . '_' . $orgsem->get('semestername');

    if ($download === 'overview') {
        $rows = $results_service->get_overview($orgsem);

        // TODO: Localize.
        $filename .= '_overview';
    } else if ($download === 'default') {
        $rows = $results_service->get_for_default_questionnaire($orgsem);
        $filename .= '_default';
    } else {
        $eventtype = eventtype::get_record(['id' => $download]);
        $rows = $results_service->get_by_semester_and_eventtype($orgsem, $eventtype);
        $filename .= "_{$eventtype->get('name')}";
    }

    $filename = preg_replace('/[^[:alnum:]äöüß]/', '_', $filename) . '.csv';

    header('Content-Type: text/csv');
    header("Content-Disposition: inline; filename=\"$filename\"");

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
$event_types_table->head = ['Lehrveranstaltungsart', 'Verknüpfter Fragebogen'];

if ($orgsem->get('default_surveypartid')) {
    $questionnaire = surveypart::get_record(['id' => $orgsem->get('default_surveypartid')], MUST_EXIST);

    $event_types_table->data[] = new html_table_row([
        html_writer::tag('em', '(Standard)'),
        html_writer::link(urls::questionnaire($questionnaire->get('id')), $questionnaire->get('name')),
        html_writer::link(urls::semester_results_download($organizationid, $orgsemid, null), get_string('download')),
    ]);
}

$eventtypes = eventtype::get_records(['orgsemid' => $orgsemid]);
foreach ($eventtypes as $eventtype) {
    $row = [$eventtype->get('name')];

    if (!$eventtype->get('surveypartid')) {
        $cell = new html_table_cell('Kein Fragebogen zugewiesen');
        $cell->colspan = 2;
        $row[] = $cell;
    } else {
        // TODO: Optimize this.
        $questionnaire = surveypart::get_record(['id' => $eventtype->get('surveypartid')], MUST_EXIST);
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
    html_writer::div(html_writer::link(urls::semester_results_download_overview($organizationid, $orgsemid), 'Download course overview'), 'mb-3')
    . html_writer::table($event_types_table)
);

echo $OUTPUT->footer();
