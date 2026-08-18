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
 * Permission manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_coursefeedback\local\manager;

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\surveypart;
use core\exception\coding_exception;
use stdClass;

/**
 * Permission manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission_manager {

    /**
     * Helper function that checks the permission for editing something in the surveypart.
     *
     * @param surveypart $surveypart
     */
    public static function require_permission_for_editing_surveypart(surveypart $surveypart): void {
        if ($surveypart->get('organizationid')) {
            self::can_manage_organization($surveypart->get('organizationid'));
        } else {
            require_capability('block/coursefeedback:managesurveysglobally', \context_system::instance());
        }
    }

    /**
     * Returns whether the current user can do any evaluation administration.
     * @return bool
     */
    public static function can_do_any_evaluation_administration(): bool {
        return has_any_capability(
            ['block/coursefeedback:managesurveysglobally', 'block/coursefeedback:manageorganizations'],
            \context_system::instance()
        );
    }

    /**
     * Whether the current user is an organization manager for the given organization.
     * @param organization|int|null $organization The organization, its ID, or null to check if the user can manage global
     *                                            questionnaires.
     * @return bool
     */
    public static function can_manage_organization(organization|int|null $organization): bool {
        $context = \context_system::instance();
        if (!$organization) {
            return has_capability('block/coursefeedback:managesurveysglobally', $context);
        }
        if (has_capability('block/coursefeedback:manageorganizations', $context)) {
            // The user is allowed to manage all organizations.
            return true;
        }

        $organization_id = is_int($organization) ? $organization : $organization->get('id');
        return user_organization_cache_manager::get_instance()->is_user_evaluation_coordinator_for($organization_id);
    }

    /**
     * Is the current user allowed to delete survey responses for the given organization?
     *
     * @return bool
     */
    public static function can_delete_responses(): bool {
        return is_siteadmin();
    }

    /**
     * Throws an error if the current user is not an organization manager for the given organization.
     * @param ?organization $organization
     * @return void
     */
    public static function require_manage_organization(?organization $organization) {
        if (!self::can_manage_organization($organization)) {
            throw new coding_exception('You do not have permission to do this.');
        }
    }

    /**
     * Checks whether the user can do something, a teacher in the given course can
     * do depending on the $organization->$organization_property.
     * @param stdClass $course
     * @param int|organization|null $organization_or_id
     * @param string $organization_property
     */
    private static function check_teacher_organization_capability(
        stdClass $course,
        int|organization|null $organization_or_id,
        string $organization_property
    ): bool {
        if (!$organization_or_id) {
            $organization = course_organization_mapping::get_instance()->get_organization_for_course($course);
        } else if (is_int($organization_or_id)) {
            $organization = organization::get_record(['id' => $organization_or_id], MUST_EXIST);
        } else {
            $organization = $organization_or_id;
        }
        if (!$organization instanceof organization) {
            return false;
        }

        $context = \context_course::instance($course->id);

        if ($organization->get($organization_property) && has_capability('block/coursefeedback:isevaluationteacher', $context)) {
            return true;
        }

        return self::can_manage_organization($organization);
    }

    /**
     * Whether the user can edit course survey settings.
     * @param stdClass $course
     * @param int|organization|null $organization_or_id
     * @return bool
     */
    public static function can_edit_course_surveysettings(stdClass $course, int|organization|null $organization_or_id): bool {
        return self::check_teacher_organization_capability($course, $organization_or_id, 'can_teacher_edit_ssettings');
    }

    /**
     * Requires that the user can edit the courses surveysettings.
     * @param stdClass $course
     * @param int|organization|null $organization_or_id
     * @return bool
     */
    public static function require_edit_course_surveysettings(stdClass $course, int|organization|null $organization_or_id) {
        if (!self::can_edit_course_surveysettings($course, $organization_or_id)) {
            throw new coding_exception("You cannot edit survey settings for course " . htmlentities($course->shortname));
        }
    }

    /**
     * Whether the user can edit the course survey period.
     *
     * @param stdClass $course
     * @param int|organization|null $organization_or_id
     * @param bool $is_frozen
     * @return bool
     */
    public static function can_edit_course_survey_period(
        stdClass $course,
        int|organization|null $organization_or_id,
        bool $is_frozen
    ): bool {
        if ($is_frozen) {
            // Only evaluation coordinators can edit the period of frozen SEs.
            return self::can_manage_organization($organization_or_id);
        } else {
            return self::check_teacher_organization_capability($course, $organization_or_id, 'can_teacher_edit_speriod');
        }
    }

    /**
     * Whether the user can view the course settings.
     * @param stdClass $course
     * @param organization $organization
     * @return bool
     */
    public static function can_view_course_settings(stdClass $course, organization $organization): bool {
        $context = \context_course::instance($course->id);
        return has_capability('block/coursefeedback:viewcoursesettings', $context)
            || self::can_manage_organization($organization);
    }
}
