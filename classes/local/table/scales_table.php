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
 * A table listing all scales of a surveypart.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\table;

use moodle_url;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * A table listing all scales of a surveypart.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scales_table extends \table_sql {
    /**
     * Constructor for the scales_table.
     *
     * @param int $surveypartid
     * @param bool $editable
     */
    public function __construct(
        /** @var int $surveypartid */
        private readonly int $surveypartid,
        bool $editable
    ) {
        global $PAGE;
        parent::__construct('block_coursefeedback-scales_table');
        $this->define_baseurl($PAGE->url);
        // I need a subquery because it conflicts with the sqltables COUNT(1) query otherwise.
        $this->set_sql(
            '*',
            '(SELECT s.id, s.name, COUNT(sq.id) as uses ' .
            'FROM {block_coursefeedback_scale} s ' .
            'LEFT JOIN {block_coursefeedback_surveyitemscalequestion} sq ON sq.scaleid = s.id ' .
            'WHERE s.surveypartid = :surveypartid ' .
            'GROUP BY s.id, s.name) sub',
            'true',
            ['surveypartid' => $this->surveypartid]
        );
        $this->column_nosort = ['tools'];

        $columns = ['name', 'uses'];
        $headers = [
            get_string('name', 'block_coursefeedback'),
            get_string('uses', 'block_coursefeedback'),
        ];
        // Only add tool column if editable.
        if ($editable) {
            $columns[] = 'tools';
            $headers[] = get_string('tools', 'block_coursefeedback');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
    }

    /**
     * Render name column.
     * @param object $row Row data.
     * @return string action buttons for workflows
     */
    public function col_name($row) {
        return $row->name;
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
        $url = new \moodle_url('/blocks/coursefeedback/scale_edit.php', ['id' => $row->id, 'surveypartid' => $this->surveypartid]);
        $output .= $OUTPUT->action_icon(
            $url,
            new \pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
            null,
            ['title' => $alt]
        );

        if ($row->uses == 0) {
            $alt = get_string('delete');
            $output .= $OUTPUT->action_icon(
                new moodle_url($PAGE->url, ['action' => 'delete', 'id' => $row->id, 'sesskey' => sesskey()]),
                new \pix_icon('t/delete', $alt, 'moodle', ['title' => $alt]),
                null,
                ['title' => $alt]
            );
        }

        return $output;
    }
}
