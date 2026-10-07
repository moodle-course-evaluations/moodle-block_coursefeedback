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

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\course_semester_mapping\evaluation_semester;

/**
 * A semester in which evaluations are conducted in an organization.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization_semester extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_organization_semester';

    #[\Override]
    protected static function define_properties(): array {
        return [
            'organizationid' => [
                'type' => PARAM_INT,
            ],
            'semesterid' => [
                'type' => PARAM_INT,
            ],
            'semestername' => [
                'type' => PARAM_TEXT,
            ],
            'default_surveypartid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'evaluation_starttime' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'evaluation_endtime' => [
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
        ];
    }

    private ?evaluation_semester $semester = null;

    public function get_semester(): ?evaluation_semester {
        if (!$this->semester) {
            $this->semester = course_semester_mapping::get_instance()->get_semester_by_id($this->get('semesterid'));
            if (!$this->semester) {
                debugging("No mapping for semesterid {$this->get('semesterid')}");
                return null;
            }
        }
        return $this->semester;
    }

    protected function set_semesterid(int $semesterid): void {
        if ($semesterid !== $this->get('semesterid')) {
            $this->semester = null;
        }
        $this->raw_set('semesterid', $semesterid);
    }

    public static function get_current(int $organizationid): ?self {
        $current_semester = course_semester_mapping::get_instance()->get_current_semester();
        $orgsem = self::get_record(['organizationid' => $organizationid, 'semesterid' => $current_semester->id]);
        if ($orgsem) {
            // So we don't need to retrieve the semester again when we need it.
            $orgsem->semester = $current_semester;
        }
        return $orgsem;
    }

    public static function get_by_semester(int $organizationid, evaluation_semester $semester): ?self {
        $orgsem = self::get_record(['organizationid' => $organizationid, 'semesterid' => $semester->id]);
        if ($orgsem) {
            // So we don't need to retrieve the semester again when we need it.
            $orgsem->semester = $semester;
        }
        return $orgsem;
    }
}
