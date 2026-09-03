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
 * Edit the default surveypart per eventtype.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\output\surveypart_chooser;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization_default_surveypart($organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_default_surveypart.php', ['id' => $id]));
$PAGE->set_context($context);

$title = get_string('define_default_surveyparts', 'block_coursefeedback') . ': ' . $organization->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organization.php', ['id' => $id]);

$surveyparts = surveypart::get_surveyparts_available_for_organization($organization);
$eventtypes = eventtype::get_eventtypes_for_organization($id);

if (optional_param('submit', null, PARAM_ALPHA)) {
    require_sesskey();
    $addedids = required_param('added', PARAM_RAW);
    $addedids = \core\param::INT->clean_param_array(json_decode($addedids));
    foreach ($addedids as $addedid) {
        $eventtypename = required_param("name-new-$addedid", PARAM_TEXT);
        $surveypartid = required_param("surveypart-new-$addedid", PARAM_INT);
        if (!isset($surveyparts[$surveypartid])) {
            $surveypartid = null;
        }
        $neweventtype = new eventtype(0, (object) [
            'name' => $eventtypename,
            'active' => true,
            'surveypartid' => $surveypartid,
            'organizationid' => $organization->get('id'),
        ]);
        $neweventtype->save();
    }
    foreach ($eventtypes as $eventtype) {
        $eventtypename = required_param("name-" . $eventtype->get('id'), PARAM_TEXT);
        $surveypartid = required_param("surveypart-" . $eventtype->get('id'), PARAM_INT);
        if (!isset($surveyparts[$surveypartid])) {
            $surveypartid = null;
        }

        if ($eventtype->get('name') === $eventtypename && $eventtype->get('surveypartid') === $surveypartid) {
            continue;
        }

        $eventtype->set('name', $eventtypename);
        $eventtype->set('surveypartid', $surveypartid);
        $eventtype->update();
    }

    $default_surveypartid = required_param('surveypart-default', PARAM_INT);
    if (!isset($surveyparts[$default_surveypartid])) {
        $default_surveypartid = null;
    }
    if ($default_surveypartid !== $organization->get('default_surveypartid')) {
        $organization->set('default_surveypartid', $default_surveypartid);
        $organization->update();
    }

    redirect($returnurl);
}

$PAGE->requires->js_call_amd(
    'block_coursefeedback/organization_default_surveypart',
    'init',
    [(new surveypart_chooser($surveyparts, null, $organization))->export_for_template($OUTPUT)]
);

$template_eventtypes = array_map(fn ($eventtype) => [
    'id' => $eventtype->get('id'),
    'name' => $eventtype->get('name'),
    'surveypart_chooser_context' => (new surveypart_chooser(
        $surveyparts,
        $eventtype->get('surveypartid'),
        $organization
    ))->export_for_template($OUTPUT),
], array_values($eventtypes));

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');

$renderer->render_organization_page($id, 'eventtypes', function () use ($template_eventtypes, $returnurl, $surveyparts, $organization, $PAGE, $OUTPUT) {
    echo $OUTPUT->render_from_template('block_coursefeedback/organization_default_surveypart', [
        'formurl' => $PAGE->url->out(false),
        'sesskey' => sesskey(),
        'returnurl' => $returnurl->out(false),
        'default_surveypart_chooser_context' => (new surveypart_chooser(
            $surveyparts,
            $organization->get('default_surveypartid'),
            $organization
        ))->export_for_template($OUTPUT),
        'eventtypes' => $template_eventtypes,
    ]);
});

echo $OUTPUT->footer();
