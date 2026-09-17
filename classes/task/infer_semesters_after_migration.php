<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_coursefeedback\task;

use block_coursefeedback\local\course_semester_mapping\course_semester_mapping;
use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use core\task\adhoc_task;

/**
 * A plugin's upgrade.php should not call plugin code itself, so we defer setting semester id and name into this task.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class infer_semesters_after_migration extends adhoc_task {

    #[\Override]
    public function execute(): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $org_fields = organization::get_sql_fields('o', 'o');
        $org_sem_fields = organization_semester::get_sql_fields('os', 's');

        $records = $DB->get_records_sql("
            SELECT $org_fields, $org_sem_fields
            FROM {block_coursefeedback_organization} o
            INNER JOIN {block_coursefeedback_organization_semester} os ON o.id = os.organizationid
        ");

        foreach ($records as $record) {
            $organization = organization::extract($record, 'o');
            $organization_semester = organization_semester::extract($record, 's');

            if ($organization_semester->get('semesterid') !== -100) {
                // The org semester already has a non-placeholder semester.
                continue;
            }

            $semester = $this->guess_semester_for_org($organization, $organization_semester);
            $organization_semester->set('semesterid', $semester->id);
            $organization_semester->set('semestername', strval($semester->name));
            $organization_semester->update();

            mtrace("Replaced placeholder semester for '{$organization->get('name')}' with '$semester->name'.");
        }

        $transaction->allow_commit();
    }

    /**
     * Infer the semester that the organization was used for before the migration.
     *
     * @param organization $organization
     * @param organization_semester $organization_semester
     * @return evaluation_semester
     */
    private function guess_semester_for_org(
        organization $organization,
        organization_semester $organization_semester
    ): evaluation_semester {
        $semester_mapping = course_semester_mapping::get_instance();

        global $DB;
        $courses = $DB->get_records_sql('
            SELECT c.*
            FROM {block_coursefeedback_surveyexecution} se
            INNER JOIN {course} c ON se.courseid = c.id
            WHERE se.organizationid = :organizationid
        ', ['organizationid' => $organization->get('id')]);

        // Try to get the semester of any course evaluated in the organization.
        foreach ($courses as $course) {
            $semester = $semester_mapping->get_course_semester($course->id);
            if ($semester) {
                return $semester;
            }
        }

        // Try to get the semester that was active at the evaluation start time.
        if ($organization_semester->get('evaluation_starttime')) {
            $semester = $semester_mapping->get_semester_active_at($organization_semester->get('evaluation_starttime'));
            if ($semester) {
                return $semester;
            }
        }

        // As a last resort, get the current semester.
        return $semester_mapping->get_current_semester();
    }
}
