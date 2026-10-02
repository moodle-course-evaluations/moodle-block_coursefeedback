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

namespace block_coursefeedback\local\default_survey_creation_method;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\teaching_event;

/**
 * RUB default survey creation method.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rub_survey_creation_method extends default_survey_creation_method {

    #[\Override]
    public static function create_survey_execution(
        array $courseids,
        organization $organization,
        organization_semester $orgsem
    ): array {
        global $DB;

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $records = $DB->get_records_sql("
            SELECT c.id, ce.title as campusname, ce.coursenumber as coursenumber, rem.eventtypeid FROM {course} c
            JOIN {local_campusdatapull_rub_campus_event} ce ON ce.semester = :semester AND
                ce.coursenumber = IF(LOCATE(' - ', c.idnumber) != 0,
                    SUBSTRING(c.idnumber, 1, LOCATE(' - ', c.idnumber)), c.idnumber)
            LEFT JOIN {block_coursefeedback_rub_eventtype_mapping} rem
                ON rem.organizationid = :organizationid AND rem.rub_coursetype = ce.coursetype
            WHERE c.id $insql
         ", array_merge(['organizationid' => $organization->get('id'), 'semester' => $orgsem->get('semesterid')], $inparams));

        $ses = [];

        foreach ($courseids as $courseid) {
            $se = new survey_execution(0, (object) [
                'courseid' => $courseid,
                'orgsemid' => $orgsem->get('id'),
                'starttime' => null,
                'endtime' => null,
                'status' => 0,
            ]);
            $se->create();
            if (isset($records[$courseid]) && $records[$courseid]->eventtypeid != null) {
                $teaching_event = new teaching_event(0, (object) [
                    'courseid' => $courseid,
                    'eventtypeid' => $records[$courseid]->eventtypeid,
                    'name' => $records[$courseid]->campusname,
                ]);
                $teaching_event->create();
                $spe = new survey_part_execution(0, (object) [
                    'surveyexecutionid' => $se->get('id'),
                    'eventid' => $teaching_event->get('id'),
                ]);
                $spe->create();
                $speo = new response_slot(0, (object) [
                    'surveypartexecutionid' => $spe->get('id'),
                    'name' => '',
                    'externalid' => $records[$courseid]->coursenumber,
                ]);
                $speo->create();
            }
            $ses[] = $se;
        }
        return $ses;
    }
}
