<?php
// This file is part of Moodle - https://questionpy.org
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

namespace block_coursefeedback\output;

use block_coursefeedback\local\manager\permission_manager;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use coding_exception;
use context_course;
use core\output\inplace_editable;
use core\param;
use core\user;
use html_writer;

/**
 * A {@link inplace_editable} for the users allowed to see slot results.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_users_editable extends inplace_editable {

    /**
     * Constructor.
     *
     * @param response_slot $slot
     * @param array<int, object> $availableusers Associative array of user objects by user ID.
     * @param array<int, object> $assignedusers Associative array of user objects by user ID.
     * @param bool $editable
     * @throws coding_exception
     */
    public function __construct(
        response_slot $slot,
        array $availableusers,
        array $assignedusers,
        bool $editable
    ) {
        // We just ignore any users who are no longer available (i.e., have been unenrolled).
        $assignedusers = array_intersect_key($assignedusers, $availableusers);

        $available_user_names = array_map(user::get_fullname(...), $availableusers);

        if ($assignedusers) {
            $displayvalue = s(implode(', ', array_intersect_key($available_user_names, $assignedusers)));
        } else {
            $displayvalue = html_writer::tag('em', get_string('none'), ['class' => 'text-secondary']);
        }

        $value = json_encode(array_keys($assignedusers));

        parent::__construct(
            component: 'block_coursefeedback',
            itemtype: 'slot_users',
            itemid: $slot->get('id'),
            editable: $editable,
            displayvalue: $displayvalue,
            value: $value
        );

        $this->set_type_autocomplete($available_user_names, ['multiple' => true]);

        $this->edithint = $this->editlabel = get_string('slot_users_of', 'block_coursefeedback', $slot->get('name'));
    }

    /**
     * Updates a slot with a new set of users.
     *
     * @param string $itemid The ID of the slot to update.
     * @param string $newvalue A JSON array of user IDs.
     * @return static|null
     */
    public static function update(string $itemid, string $newvalue): ?static {
        global $PAGE, $DB;
        require_login();

        $slotid = param::INT->clean($itemid);

        $userids = json_decode($newvalue, depth: 2, flags: JSON_THROW_ON_ERROR);

        $slot_fields = response_slot::get_sql_fields('rs', 'rs_');
        $se_fields = survey_execution::get_sql_fields('se', 'se_');
        $record = $DB->get_record_sql("
            SELECT $slot_fields, $se_fields
            FROM {" . response_slot::TABLE . "} rs
            JOIN {" . survey_part_execution::TABLE . "} spe ON spe.id = rs.surveypartexecutionid
            JOIN {" . survey_execution::TABLE . "} se ON se.id = spe.surveyexecutionid
            WHERE rs.id = :slotid
        ", ['slotid' => $slotid], MUST_EXIST);

        $slot = response_slot::extract($record, 'rs_');
        $survey_execution = survey_execution::extract($record, 'se_');

        $course = get_course($survey_execution->get('courseid'));

        /** @var context_course $context */
        $context = context_course::instance($course->id);
        $PAGE->set_context($context);
        permission_manager::require_edit_course_surveysettings($course, $survey_execution->get('organizationid'));

        // Users are returned by enrol_get_course_users index by enrolment ID, we want to index by user ID.
        $enrolledusers = array_column(enrol_get_course_users($course->id), null, 'id');

        /** @var array<int, object> $filtered_users */
        $filtered_users = [];
        foreach ($userids as $userid) {
            $user = $enrolledusers[$userid] ?? null;
            // Prevent the assignment of unenrolled users.
            if ($user) {
                $filtered_users[$userid] = $user;
            } else {
                debugging("User '$userid' is not enrolled in course '$course->id', ignoring", DEBUG_DEVELOPER);
            }
        }

        $slot->set_users(array_keys($filtered_users));

        return new static(slot: $slot, availableusers: $enrolledusers, assignedusers: $filtered_users, editable: true);
    }
}
