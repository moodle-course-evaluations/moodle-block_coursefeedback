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
 * Table for courses without evaluation.
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
use block_coursefeedback\local\manager\semester_tuple;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use block_coursefeedback\local\persistent\survey_execution;
use core\exception\coding_exception;
use core\output\html_writer;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table for courses without evaluation.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses_without_evaluation_table extends no_pagination_table {

    /** @var object Some pre-fetched strings. */
    private object $strings;

    /**
     * Constructor.
     *
     * @param organization $organization
     * @param organization_semester $orgsem
     * @param evaluation_semester $semester
     */
    public function __construct(organization $organization, organization_semester $orgsem, evaluation_semester $semester) {
        global $OUTPUT, $PAGE;
        parent::__construct('block_coursefeedback-courses_without_evaluation');
        $this->define_baseurl($PAGE->url);
        $semester_join = course_semester_mapping::get_instance()->get_filter_sql_for_semester($semester);
        $organization_join = course_organization_mapping::get_instance()->get_filter_sql_for_organization($organization);
        $this->set_sql(
            "c.id, fullname as name, shortname",
            "{course} c
            $semester_join->joins
            $organization_join->joins
            LEFT JOIN {" . survey_execution::TABLE . "} se ON c.id = se.courseid AND se.orgsemid = :orgsemid",
            "se.id IS NULL AND $semester_join->wheres AND $organization_join->wheres",
            ['orgsemid' => $orgsem->get('id'), ...$semester_join->params, ...$organization_join->params],
        );
        $this->column_nosort = ['checkbox', 'tools'];
        $this->define_columns(['checkbox', 'name', 'tools']);
        $this->define_headers([
            $OUTPUT->render(new \core\output\checkbox_toggleall('coursefeedback-courses_without_evaluation', true, [
                'name' => 'select-all-courses_without_evaluation',
                'label' => get_string('selectall'),
                'labelclasses' => 'sr-only',
                'classes' => 'm-1',
                'checked' => false,
            ])),
            get_string('name', 'block_coursefeedback'),
            get_string('tools', 'block_coursefeedback'),
        ]);

        $this->strings = get_strings([
            'create_default',
        ], 'block_coursefeedback');

        $PAGE->requires->js_call_amd('block_coursefeedback/bulkactions_post', 'init');
    }

    /**
     * Renders the checkbox column.
     * @param $row
     * @return string
     */
    public function col_checkbox($row) {
        global $OUTPUT;
        $checkbox = new \core\output\checkbox_toggleall('coursefeedback-courses_without_evaluation', false, [
            'classes' => 'usercheckbox m-1',
            'name' => 'coursefeedback-select',
            'value' => $row->id,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', $row->id),
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
            course_get_url($row->id),
            $row->name,
        );
    }

    /**
     * Render tools column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_tools($row) {
        global $OUTPUT, $PAGE;
        $output = '';

        $alt = get_string('new');
        $icon = 't/add';
        $url = new \moodle_url($PAGE->url, [
            'sesskey' => sesskey(),
            'selected[]' => $row->id,
            'action' => 'create-default',
        ]);
        $output .= $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        );

        return $output;
    }

    /**
     * Prints the action row with actions for selected rows. Is used above and below the table.
     */
    private function print_action_row() {
        global $OUTPUT;

        $actionmenu = new \action_menu();

        $actionmenu->add_secondary_action(
            new \action_menu_link_secondary(
                new \moodle_url(''),
                new \pix_icon('t/add', $this->strings->create_default),
                $this->strings->create_default,
                ['data-coursefeedback-action' => 'create-default']
            )
        );

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
