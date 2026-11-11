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

/**
 * Contains the {@see inplace_editable} callback for the `block_coursefeedback` plugin.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\output\slot_users_editable;
use core\output\inplace_editable;

/**
 * Callback for inplace editable API.
 *
 * @param string $itemtype
 * @param string $itemid
 * @param string $newvalue
 * @return inplace_editable|null
 */
function block_coursefeedback_inplace_editable(string $itemtype, string $itemid, string $newvalue): ?inplace_editable {
    if ($itemtype === 'slot_users') {
        return slot_users_editable::update($itemid, $newvalue);
    }

    debugging("Unrecognised itemtype for block_coursefeedback_inplace_editable: '$itemtype'");
    return null;
}

/**
 * Called when building a course's navbar. We add a link to the course's survey settings if the course has a survey execution.
 *
 * @param navigation_node $parentnode
 * @param stdClass $course
 * @param context $context
 * @return void
 */
function block_coursefeedback_extend_navigation_course(
    navigation_node $parentnode,
    stdClass $course,
    context $context,
): void {
    global $DB;
    try {
        if (
            has_capability('block/coursefeedback:viewcoursesettings', $context)
            && ($organization = course_organization_mapping::get_instance()->get_organization_for_course($course))
            && $DB->record_exists(
                survey_execution::TABLE,
                ['courseid' => $course->id, 'organizationid' => $organization->get('id')]
            )
        ) {
            $url = new moodle_url('/blocks/coursefeedback/course.php', ['id' => $course->id]);
            $parentnode->add(get_string("course_settings", "block_coursefeedback"), $url, key: "coursefeedback");
        }
    } catch (Exception | Error $e) {
        debugging($e);
    }
}
