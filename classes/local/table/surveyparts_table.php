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

use block_coursefeedback\local\persistent\surveypart;
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
class surveyparts_table extends \table_sql {

    /**
     * Constructor.
     *
     * @param int|null $organizationid
     */
    public function __construct(?int $organizationid) {
        global $PAGE;
        parent::__construct('block_coursefeedback-surveyparts_table');
        $this->define_baseurl($PAGE->url);

        $this->set_sql(
            fields: 'id, name',
            from: '{' . surveypart::TABLE . '}',
            where: $organizationid ? 'organizationid = :organizationid' : 'organizationid IS NULL',
            params: $organizationid ? ['organizationid' => $organizationid] : []
        );
        $this->column_nosort = ['tools'];
        $this->define_columns(['name', 'tools']);
        $this->define_headers([
            get_string('name', 'block_coursefeedback'),
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
            new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $row->id]),
            $row->name
        );
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
        $url = new \moodle_url('/blocks/coursefeedback/surveypart_edit.php', ['id' => $row->id]);
        $output .= $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        );

        return $output;
    }
}
