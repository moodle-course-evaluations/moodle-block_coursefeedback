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
 * Edit the eventtype per campus DB coursetype.
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
use block_coursefeedback\local\persistent\rub_eventtype_mapping;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization_default_surveypart($organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_rub_eventtype_mapping.php', ['id' => $id]));
$PAGE->set_context($context);

$title = get_string('event_types', 'block_coursefeedback') . ': ' . $organization->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$returnurl = new moodle_url('/blocks/coursefeedback/organization_settings.php', ['id' => $id]);

$eventtypes = eventtype::get_eventtypes_for_organization($id);
$eventtypes_by_id = [];
foreach ($eventtypes as $eventtype) {
    $eventtypes_by_id[$eventtype->get('id')] = $eventtype;
}
$mappings = rub_eventtype_mapping::get_saved_and_new_coursetype_mappings($organization);

if (optional_param('submit', null, PARAM_ALPHA)) {
    require_sesskey();

    foreach ($mappings as $mapping) {
        $name = $mapping->get('rub_coursetype');
        $eventtypeid = optional_param('coursetype-' . bin2hex($name), null, PARAM_INT);
        if (!isset($eventtypes_by_id[$eventtypeid])) {
            $eventtypeid = null;
        }
        if ($eventtypeid != $mapping->get('eventtypeid')) {
            $mapping->set('eventtypeid', $eventtypeid);
            $mapping->save();
        }
    }

    redirect($returnurl);
}

$eventtypes_for_template = [];
foreach ($eventtypes as $eventtype) {
    $eventtypes_for_template[$eventtype->get('id')] = [
        'id' => $eventtype->get('id'),
        'name' => $eventtype->get('name'),
    ];
}

$template_mappings = [];

foreach ($mappings as $mapping) {
    $eventtypes_for_coursetype_template = $eventtypes_for_template;
    if (isset($eventtypes_for_coursetype_template[$mapping->get('eventtypeid')])) {
        $eventtypes_for_coursetype_template[$mapping->get('eventtypeid')]['selected'] = true;
    }
    $template_mappings[] = [
        'id' => bin2hex($mapping->get('rub_coursetype')),
        'name' => $mapping->get('rub_coursetype'),
        'eventtypes' => array_values($eventtypes_for_coursetype_template),
    ];
}
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_coursefeedback/organization_rub_eventtype_mapping', [
    'formurl' => $PAGE->url->out(false),
    'sesskey' => sesskey(),
    'returnurl' => $returnurl->out(false),
    'mappings' => $template_mappings,
]);

echo $OUTPUT->footer();
