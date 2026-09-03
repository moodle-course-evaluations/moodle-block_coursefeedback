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

use block_coursefeedback\local\form\organization_settings_form;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_texts;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$id = required_param('id', PARAM_INT);
$organization = organization::get_record(['id' => $id], MUST_EXIST);
$PAGE->set_context($context);
permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization($organization);

$tab = optional_param('tab', null, PARAM_ALPHA);

$params = ['id' => $id];
if ($tab) {
    $params['tab'] = $tab;
}
$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization.php', $params));

$title = $organization->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');

$renderer->render_organization_page($id, $tab, function () use ($organization, $tab, $PAGE) {
    if ($tab === 'settings') {
        $mform = new organization_settings_form($PAGE->url, $organization);

        $mform->set_data($organization->to_record());
        $organization_texts = organization_texts::get_record(['organizationid' => $organization->get('id')]);
        if ($organization_texts) {
            $mform->set_data($organization_texts->to_record());
        }

        if ($mform->is_cancelled()) {
            redirect($PAGE->url);
        } else if ($data = $mform->get_data()) {
            $organization->set_many(organization::properties_filter($data));
            $organization->update();

            if (!$organization_texts) {
                $organization_texts = new organization_texts(record: (object)[
                    'organizationid' => $organization->get('id'),
                ]);
            }
            $organization_texts->set_many(organization_texts::properties_filter($data));
            $organization_texts->save();

            redirect($PAGE->url);
        }

        $mform->display();
    }
});

$context = [
    'name' => $organization->get('name'),
];

echo $OUTPUT->render_from_template('block_coursefeedback/organization', $context);

echo $OUTPUT->footer();
