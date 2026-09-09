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
 * List organizations.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\persistent\organization_user;
use block_coursefeedback\local\table\my_organizations_table;
use block_coursefeedback\local\table\organizations_table;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE, $USER;

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/organizations.php'));
$context = context_system::instance();
$PAGE->set_context($context);

require_login();

breadcrumbs_manager::setup_organizations();

$PAGE->set_heading(get_string('organizations', 'block_coursefeedback'));

if (!has_capability('block/coursefeedback:manageorganizations', $context)) {
    $records = organization_user::get_records(['userid' => $USER->id], limit: 2);
    if (count($records) == 0) {
        throw new coding_exception('You are not allowed to access this page.');
    } else if (count($records) == 1) {
        redirect(
            new moodle_url(
                '/blocks/coursefeedback/organization_settings.php',
                ['id' => reset($records)->get('organizationid')]
            )
        );
    }
}

echo $OUTPUT->header();

if (has_capability('block/coursefeedback:manageorganizations', $context)) {
    $table = new organizations_table();
    echo html_writer::div($OUTPUT->render(new single_button(
        new moodle_url('/blocks/coursefeedback/organization_settings.php'),
        get_string('new_organization', 'block_coursefeedback'),
        'get',
        single_button::BUTTON_PRIMARY
    )), class: 'mb-2');
} else {
    $table = new my_organizations_table();
}

$table->out(48, false);

echo $OUTPUT->footer();
