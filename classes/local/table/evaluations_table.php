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
 * Table for evaluations.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\table;

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\survey_execution;
use context_system;
use core\output\html_writer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

// PHPCS is confused by constructor parameters being promoted to class properties.
// phpcs:disable moodle.Commenting.VariableComment.Missing
/**
 * Table for evaluations.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluations_table extends no_pagination_table {

    /** @var object Some pre-fetched strings. */
    private object $strings;

    /** @var bool Whether the user can delete surveys. */
    private bool $candelete;

    /**
     * Constructs the table of evaluations.
     *
     * @var evaluation_semester $semester Semester as described in course_semester_mapping to filter by.
     * @var organization $organization Organization to filter by.
     */
    public function __construct(
        public readonly evaluation_semester $semester,
        private readonly organization $organization,
    ) {
        global $OUTPUT, $PAGE;
        parent::__construct('block_coursefeedback-evaluations');
        $this->define_baseurl($PAGE->url);
        $semester_join = course_semester_mapping::get_instance()->get_filter_sql_for_semester($this->semester);
        // We intentionally don't use course_organization_mapping here, since that would make it impossible to delete the survey
        // execution. TODO: Mark courses that don't belong to this organization anymore.
        $this->set_sql(
            "c.id as courseid, c.fullname as name, se.starttime, se.endtime, se.status ",
            "{course} c
            $semester_join->joins
            JOIN {" . survey_execution::TABLE . "} se ON se.courseid = c.id AND se.organizationid = :organizationid",
            $semester_join->wheres,
            ['organizationid' => $this->organization->get('id'), ...$semester_join->params],
        );
        $this->column_nosort = ['checkbox', 'tools'];
        $this->define_columns(['checkbox', 'name', 'starttime', 'status', 'tools']);
        $this->define_headers([
            $OUTPUT->render(new \core\output\checkbox_toggleall('coursefeedback-courses_with_evaluation', true, [
                'name' => 'select-all-courses_with_evaluation',
                'label' => get_string('selectall'),
                'labelclasses' => 'sr-only',
                'classes' => 'm-1',
                'checked' => false,
            ])),
            get_string('name', 'block_coursefeedback'),
            get_string('evaluation_period', 'block_coursefeedback'),
            get_string('status'),
            get_string('tools', 'block_coursefeedback'),
        ]);

        $this->strings = (object) array_merge(
            (array) get_strings([
                'create_default',
                'planned',
                'ongoing',
                'finished',
            ], 'block_coursefeedback'),
            (array) get_strings([
                'strftimedatetimeshort',
            ], 'core_langconfig'),
            (array) get_strings([
                'delete',
            ])
        );

        $this->candelete = has_capability('block/coursefeedback:manageorganizations', context_system::instance());

        $PAGE->requires->js_call_amd('block_coursefeedback/bulkactions_post', 'init');
    }

    /**
     * Renders the checkbox column.
     * @param $row
     * @return string
     */
    public function col_checkbox($row) {
        global $OUTPUT;
        $checkbox = new \core\output\checkbox_toggleall('coursefeedback-courses_with_evaluation', false, [
            'classes' => 'usercheckbox m-1',
            'name' => 'coursefeedback-select',
            'value' => $row->courseid,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', $row->courseid),
            'labelclasses' => 'accesshide',
        ]);
        return $OUTPUT->render($checkbox);
    }

    /**
     * Renders the course name column.
     * @param $row
     * @return string
     */
    public function col_name($row) {
        return html_writer::link(
            course_get_url($row->courseid),
            $row->name,
        );
    }

    /**
     * Renders the evaluation period column.
     * @param $row
     * @return string
     */
    public function col_starttime($row) {
        $starttime = $row->starttime ?? $this->organization->get('evaluation_starttime');
        $endtime = $row->endtime ?? $this->organization->get('evaluation_endtime');
        return html_writer::span(
            userdate($starttime, $this->strings->strftimedatetimeshort)
            . ' - ' . userdate($endtime, $this->strings->strftimedatetimeshort),
            $row->starttime && $row->endtime ? 'fw-bold font-weight-bold' : 'text-muted'
        );
    }

    /**
     * Render the status column.
     * @param $row
     * @return string
     */
    public function col_status($row) {
        if ($row->endtime && time() > $row->endtime) {
            return $this->strings->finished;
        }
        switch ($row->status) {
            case survey_execution::STATUS_PLANNED:
                return $this->strings->planned;
            case survey_execution::STATUS_STARTED:
                return $this->strings->ongoing;
        }
        return '';
    }

    /**
     * Render tools column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_tools($row) {
        global $OUTPUT, $PAGE;
        $output = '';

        $alt = get_string('edit');
        $icon = 't/edit';
        $url = new \moodle_url('/blocks/coursefeedback/course.php', ['id' => $row->courseid]);
        $output .= $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        );

        if ($this->candelete) {
            $alt = get_string('delete');
            $icon = 't/delete';
            $url = new \moodle_url($PAGE->url, [
                'action' => 'delete',
                'sesskey' => sesskey(),
                'selected[]' => $row->courseid,
            ]);
            $output .= $OUTPUT->action_icon(
                $url,
                new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
                null,
                [
                    'title' => $alt,
                    'class' => 'text-danger',
                    'data-confirmation' => 'modal',
                    'data-confirmation-title-str' => json_encode(['delete', 'core']),
                    'data-confirmation-content-str' => json_encode(['areyousure']),
                    'data-confirmation-yes-button-str' => json_encode(['delete', 'core']),
                ],
            );
        }

        return $output;
    }

    /**
     * Prints the action row with actions for selected rows. Is used above and below the table.
     */
    private function print_action_row() {
        global $OUTPUT;

        $actionmenu = new \action_menu();

        $actionmenu->set_menu_trigger(get_string('for_selected', 'block_coursefeedback'));
        echo $OUTPUT->render_action_menu($actionmenu);
    }

    #[\Override]
    public function wrap_html_start() {
        parent::wrap_html_start();
        $this->print_action_row();
        echo '<br>';
    }

    #[\Override]
    public function wrap_html_finish() {
        echo '<br>';
        $this->print_action_row();
        parent::wrap_html_finish();
    }
}
