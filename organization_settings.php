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
 * Edit organization settings, editable by organization users.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\form\organization_settings_form;
use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\user_organization_cache_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_category;
use block_coursefeedback\local\persistent\organization_texts;
use block_coursefeedback\local\persistent\organization_user;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

$id = optional_param('id', null, PARAM_INT);
$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organization_settings.php', $id ? ['id' => $id] : []));
$PAGE->set_context(context_system::instance());

require_login();

$organization = $id ? organization::get_record(['id' => $id], MUST_EXIST) : null;
$organization_texts = $id ? organization_texts::get_record(['organizationid' => $id]) : null;

permission_manager::require_manage_organization($organization);
breadcrumbs_manager::setup_organization_settings($organization);

if ($organization) {
    $PAGE->set_heading($organization->get('name'));
    $PAGE->set_title(get_string('settings') . $PAGE::TITLE_SEPARATOR . $organization->get('name'));
} else {
    $PAGE->set_heading(get_string('new_organization', 'block_coursefeedback'));
    $PAGE->set_title(get_string('new_organization', 'block_coursefeedback'));
}

$is_user_privileged = has_capability('block/coursefeedback:manageorganizations', context_system::instance());

$mform = new organization_settings_form($PAGE->url, $is_user_privileged);

if ($organization) {
    $data = $organization->to_record();

    $data->userids = array_values(organization_user::get_organization_userids($organization->get('id')));
    $data->coursecatids = array_values(organization_category::get_organization_coursecatids($organization->get('id')));

    if ($organization_texts) {
        $data->survey_created_message_body = $organization_texts->get('survey_created_message_body');
        $data->survey_created_message_subject = $organization_texts->get('survey_created_message_subject');
    }

    $mform->set_data($data);
}

if ($mform->is_cancelled()) {
    redirect($organization ? $PAGE->url : new moodle_url('/blocks/coursefeedback/organizations.php'));
} else if ($submitted_data = $mform->get_data()) {
    if (!$organization) {
        $organization = new organization();
    }

    $transaction = $DB->start_delegated_transaction();

    $organization->set_many(organization::properties_filter($submitted_data));
    $organization->save();

    if (!$organization_texts) {
        $organization_texts = new organization_texts(record: (object) [
            'organizationid' => $organization->get('id'),
        ]);
    }

    $organization_texts->set_many(organization_texts::properties_filter($submitted_data));
    $organization_texts->save();

    if ($is_user_privileged) {
        organization_user::set_organization_userids($organization->get('id'), $submitted_data->userids);
        organization_category::set_organization_coursecatids($organization->get('id'), $submitted_data->coursecatids);
        user_organization_cache_manager::get_instance()->purge();
    }

    $transaction->allow_commit();

    redirect(new moodle_url($PAGE->url, ['id' => $organization->get('id')]));
}

echo $OUTPUT->header();

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');

if ($organization) {
    $renderer->render_organization_page($organization, 'settings', $mform->display(...));
} else {
    $mform->display();
}

echo $OUTPUT->footer();
