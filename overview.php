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
 * Show links to the organization and/or questionnaire lists, depending on what the user has access to.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\manager\user_organization_cache_manager;
use core\exception\moodle_exception;

require_once(__DIR__ . '/../../config.php');
global $CFG, $OUTPUT, $PAGE;

require_login();
if (!permission_manager::can_do_any_evaluation_administration()) {
    throw new moodle_exception('no_evaluation_permissions', 'block_coursefeedback');
}

$context = context_system::instance();

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/overview.php'));
$PAGE->set_context($context);
$title = get_string('evaluationadministration', 'block_coursefeedback');
$PAGE->set_heading($title);
$PAGE->set_title($title);

echo $OUTPUT->header();

if (
    has_capability('block/coursefeedback:manageorganizations', $context)
    || user_organization_cache_manager::get_instance()->is_user_evaluation_coordinator_anywhere()
) {
    echo \core\output\html_writer::link(
        new moodle_url('/blocks/coursefeedback/organizations.php'),
        get_string('organizations', 'block_coursefeedback'),
        ['class' => 'd-block my-1'],
    );
}

if (has_capability('block/coursefeedback:managesurveysglobally', $context)) {
    echo \core\output\html_writer::link(
        new moodle_url('/blocks/coursefeedback/surveyparts.php'),
        get_string('questionnaires', 'block_coursefeedback'),
        ['class' => 'd-block my-1'],
    );
}

echo $OUTPUT->footer();
