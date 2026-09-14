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
 * Surveypart table class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\table;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_category;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Surveypart table class.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organizations_table extends \table_sql {

    /**
     * Constructor.
     */
    public function __construct() {
        global $PAGE;
        parent::__construct('block_coursefeedback-surveyparts_table');
        $this->define_baseurl($PAGE->url);
        $this->set_sql('id, name', '{' . organization::TABLE . '}', 'true');
        $this->column_nosort = ['users', 'tools'];
        $this->define_columns(['name', 'categories', 'users', 'tools']);
        $this->define_headers([
            get_string('name', 'block_coursefeedback'),
            get_string('coursecategories', 'block_coursefeedback'),
            get_string('users'),
            get_string('tools', 'block_coursefeedback'),
        ]);
    }

    /**
     * Render tools column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_name($row) {
        return \html_writer::link(
            new moodle_url('/blocks/coursefeedback/organization_settings.php', ['id' => $row->id]),
            $row->name
        );
    }
    /**
     * Render categories column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_categories($row) {
        global $DB;
        $categoryids = array_values(organization_category::get_organization_coursecatids($row->id));
        $params['organizationid'] = $row->id;
        if (!empty($categoryids)) {
            [$insql, $params] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
            $categorynames = $DB->get_fieldset_select(
                'course_categories',
                'name',
                "id $insql",
                $params
            );
        } else {
            $categorynames = [];
        }

        $links = [];
        foreach ($categorynames as $index => $name) {
            $id = $categoryids[$index];
            $url = new \moodle_url('/course/index.php', ['categoryid' => $id]);
            $links[] = html_writer::link($url, format_string($name));
        }

        return implode(', ', $links);
    }

    /**
     * Render users column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_users($row) {
        global $DB;
        [$fields, $params] = \core_user\fields::get_sql_fullname();
        $params['organizationid'] = $row->id;
        $usernames = $DB->get_fieldset_sql("SELECT {$fields} FROM {block_coursefeedback_organization} o " .
            "JOIN {block_coursefeedback_organization_user} ou ON o.id = ou.organizationid " .
            "JOIN {user} u ON u.id = ou.userid " .
            "WHERE o.id = :organizationid", $params);

        // TODO Darstellung verbessern User klickbar machen.
        return join(", ", $usernames);
    }

    /**
     * Render tools column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_tools($row) {
        global $OUTPUT;
        $output = '';

        $alt = get_string('edit');
        $icon = 't/edit';
        $url = new \moodle_url('/blocks/coursefeedback/organization_edit.php', ['id' => $row->id]);
        $output .= $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        );

        return $output;
    }
}
