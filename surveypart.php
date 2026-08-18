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
 * List survey part.
 *
 * @package    block_coursefeedback
 * @copyright  2025 innoCampus, Technische Universität Berlin
 * @copyright  2025 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\manager\breadcrumbs_manager;
use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\survey;
use block_coursefeedback\local\survey_freezer;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\surveyitem\surveyitemtype;
use block_coursefeedback\local\surveyitem\surveyitemtype_with_settings;
use core\di;
use core\output\notification;

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;

require_login();
$id = required_param('id', PARAM_INT);
$surveypart = surveypart::get_record(['id' => $id], MUST_EXIST);

$organizationid = $surveypart->get('organizationid');
$organization = $organizationid ? organization::get_record(['id' => $organizationid], MUST_EXIST) : null;

permission_manager::require_permission_for_editing_surveypart($surveypart);
breadcrumbs_manager::setup_questionnaire($surveypart, $organization);

$PAGE->set_url(new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$title = $surveypart->get('name');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$freezer = di::get(survey_freezer::class);

if ($action = optional_param('action', null, PARAM_ALPHANUMEXT)) {
    require_sesskey();
    if ($action === 'delete') {
        $deleteid = required_param('deleteid', PARAM_INT);
        $freezer->check_survey_part_action($surveypart, "delete survey item '$deleteid'");

        $surveyitem = surveyitem::get_record(['id' => $deleteid], MUST_EXIST);
        $surveyitem->delete_and_fix_sortorder();
        redirect($PAGE->url);
    }
    if ($action === 'reorder') {
        $freezer->check_survey_part_action($surveypart, "reorder survey items");

        $orderingjson = required_param('ordering', PARAM_RAW);
        $ordering = json_decode($orderingjson);
        $surveypart->reorder_surveyitems($ordering);
        redirect($PAGE->url);
    }
}

$is_frozen = $freezer->is_survey_part_frozen($surveypart);

if (!$is_frozen) {
    $PAGE->requires->js_call_amd('block_coursefeedback/drag_and_drop_reorder', 'init');
}

echo $OUTPUT->header();

$actionmenu = null;
if (!$is_frozen) {
    $actionmenu = new \core\output\action_menu();
    $actionmenu->set_menu_trigger(
        get_string('add_surveyitem', 'block_coursefeedback'),
        'btn btn-primary'
    );
    $actionmenu->set_menu_left();

    /**
     * @var string $type
     * @var surveyitemtype $class
     */
    foreach (surveyitem_manager::get_all_surveyitemtypes() as $type => $class) {
        if (!$class->can_be_added()) {
            continue;
        }
        $actionmenu->add_secondary_action(
            new \core\output\action_link(
                new \moodle_url(
                    '/blocks/coursefeedback/surveyitem_edit.php',
                    ['type' => $type, 'surveypartid' => $id, 'sesskey' => sesskey()]
                ),
                $class->get_name(),
            )
        );
    }
}

$context = [];
$scale_url = new moodle_url('/blocks/coursefeedback/scales.php', ['surveypartid' => $surveypart->get('id')]);
$context['id'] = $surveypart->get('id');
$context['action_url'] = $PAGE->url->out_omit_querystring();
$context['add_element_menu'] = $actionmenu?->export_for_template($OUTPUT);
$context['has_scales'] = (bool) $DB->count_records('block_coursefeedback_scale', ['surveypartid' => $surveypart->get('id')]);
$context['scale_url'] = $scale_url->out(false);
$context['sesskey'] = sesskey();
$context['show_save_button'] = !$is_frozen;

if ($is_frozen) {
    $context['info_message'] = get_string('surveypart_frozen', 'block_coursefeedback');
}

$surveyitems = surveyitem::get_records(['surveypartid' => $surveypart->get('id')], 'sortindex');
foreach ($surveyitems as $surveyitem) {
    $surveyitemtype = $surveyitem->get('surveyitemtype');

    $actionmenu = null;
    if (!$is_frozen) {
        $actionmenu = new \core\output\action_menu();

        // If item is editable.
        if (surveyitem_manager::get_surveyitemtype($surveyitemtype) instanceof surveyitemtype_with_settings) {
            $editstr = get_string('edit');
            $actionmenu->add_secondary_action(new \core\output\action_link(
                new \moodle_url(
                    '/blocks/coursefeedback/surveyitem_edit.php',
                    ['type' => $surveyitemtype, 'surveypartid' => $id, 'id' => $surveyitem->get('id')]
                ),
                $editstr,
                null,
                null,
                new \core\output\pix_icon('t/edit', $editstr),
            ));
        }

        $deletestr = get_string('delete');
        $actionmenu->add_secondary_action(new \core\output\action_link(
            new \moodle_url(
                $PAGE->url,
                ['action' => 'delete', 'deleteid' => $surveyitem->get('id'), 'sesskey' => sesskey()],
            ),
            $deletestr,
            null,
            ['class' => 'text-danger'],
            new pix_icon('t/delete', $deletestr),
        ));

        $actionmenu->set_kebab_trigger();
    }

    $item_context = (object) [
        'show_handle' => !$is_frozen,
        'actionmenu' => $actionmenu?->export_for_template($OUTPUT),
        'type' => $surveyitem->get('surveyitemtype'),
        'itemid' => $surveyitem->get('id'),
        'text' => $surveyitem->maybe_format_text(),
    ];

    $context['surveyitems'][] = $item_context;
}

echo $OUTPUT->render_from_template('block_coursefeedback/edit_survey', $context);

/** @var block_coursefeedback_renderer $renderer */
$renderer = $PAGE->get_renderer('block_coursefeedback');
try {
    $survey = survey::for_testing_surveypart($surveypart);
    $preview_content = $renderer->render_survey($survey);
} catch (moodle_exception $e) {
    // Show message and stacktrace instead.
    $preview_content = $renderer->render(new notification(
        message: html_writer::tag("pre", strval($e), ['class' => 'mb-0']),
        messagetype: notification::NOTIFY_ERROR,
        closebutton: false,
        title: get_string('surveypart_preview_error', 'block_coursefeedback', $e->getMessage()),
    ));
}

echo html_writer::tag(
    "section",
    html_writer::empty_tag("hr")
    . html_writer::tag("h3", get_string('surveypart_preview', 'block_coursefeedback'), ['class' => 'mb-3'])
    . $preview_content
);

echo $OUTPUT->footer();
