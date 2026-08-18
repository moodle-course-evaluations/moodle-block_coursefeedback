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

namespace block_coursefeedback\local\persistent;

use core\persistent;

/**
 * An organizational unit, such as a faculty, in which evaluations are conducted.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization extends persistent {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_organization';

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'default_surveypartid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'default_evaluation_starttime' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'default_evaluation_endtime' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'can_teacher_edit_speriod' => [
                'type' => PARAM_BOOL,
                'default' => true,
            ],
            'can_teacher_edit_ssettings' => [
                'type' => PARAM_BOOL,
                'default' => true,
            ],
            'always_show_default_sp' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
            'can_have_local_questionnaires' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
            'disallow_global_questionnaires' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
        ];
    }
}
