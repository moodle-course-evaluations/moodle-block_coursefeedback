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
 * Organizations table for user.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\table;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_user;
use moodle_url;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

/**
 * Organizations table for user.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_organizations_table extends \table_sql {

    /**
     * Constructor.
     */
    public function __construct() {
        global $PAGE, $USER;
        parent::__construct('block_coursefeedback-my_organizations_table');
        $this->define_baseurl($PAGE->url);
        $this->set_sql(
            'o.id, o.name',
            '{' . organization::TABLE . '} o
            JOIN {' . organization_user::TABLE . '} ou ON o.id = ou.organizationid',
            'ou.userid = :userid',
            ['userid' => $USER->id],
        );
        $this->column_nosort = ['users', 'tools'];
        $this->define_columns(['name']);
        $this->define_headers([
            get_string('name', 'block_coursefeedback'),
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
}
