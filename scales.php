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
 * List scales.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\survey_freezer;
use block_coursefeedback\local\table\scales_table;
use core\di;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

$surveypartid = required_param('surveypartid', PARAM_INT);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/scales.php', ['surveypartid' => $surveypartid]));
$PAGE->set_context(context_system::instance());

require_login();

$surveypart = surveypart::get_record(['id' => $surveypartid]);

permission_manager::require_permission_for_editing_surveypart($surveypart);
breadcrumbs_manager::setup_survey_scales($surveypart);

$freezer = di::get(survey_freezer::class);

$title = get_string('view_scales', 'block_coursefeedback');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$action = optional_param('action', null, PARAM_ALPHANUMEXT);
if ($action) {
    require_sesskey();
    if ($action === 'delete') {
        $scaleid = required_param('id', PARAM_INT);
        $freezer->check_survey_part_action($surveypart, "delete scale '$scaleid'");

        $uses = $DB->count_records('block_coursefeedback_surveyitemscalequestion', ['scaleid' => $scaleid]);
        if ($uses > 0) {
            throw new moodle_exception('scale_delete_in_use', 'block_coursefeedback');
        }
        $DB->delete_records('block_coursefeedback_scale', ['id' => $scaleid]);
    }
    redirect($PAGE->url);
}

$is_frozen = $freezer->is_survey_part_frozen($surveypart);
$table = new scales_table($surveypartid, editable: !$is_frozen);

echo $OUTPUT->header();

if ($is_frozen) {
    echo $OUTPUT->render_from_template('block_coursefeedback/info_box', [
        'message' => get_string('surveypart_frozen', 'block_coursefeedback'),
    ]);
}

$table->out(48, false);

echo $OUTPUT->footer();
