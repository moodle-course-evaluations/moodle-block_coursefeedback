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

namespace block_coursefeedback\local;

use core\exception\moodle_exception;
use moodle_url;

/**
 * Not-yet-single source-of-truth for plugin URLs.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class urls {

    /**
     * Build a URL to the (global or org-scoped) questionnaire list.
     *
     * @param int|null $organizationid
     * @return moodle_url
     */
    public static function questionnaires(?int $organizationid): moodle_url {
        return new moodle_url(
            '/blocks/coursefeedback/surveyparts.php',
            $organizationid ? ['organizationid' => $organizationid] : []
        );
    }

    /**
     * Build a URL to the given questionnaire / survey part.
     *
     * @param int $surveypartid
     * @return moodle_url
     */
    public static function questionnaire(int $surveypartid): moodle_url {
        return new moodle_url('/blocks/coursefeedback/surveypart.php', ['id' => $surveypartid]);
    }

    /**
     * Build a URL to the event type to questionnaire mapping table.
     *
     * @param int $organizationid
     * @param int|null $orgsemid An orgsem or empty to use the current semester.
     * @return moodle_url
     */
    public static function event_types(int $organizationid, ?int $orgsemid): moodle_url {
        $params = ['id' => $organizationid];
        if ($orgsemid) {
            $params['orgsemid'] = $orgsemid;
        }
        return new moodle_url('/blocks/coursefeedback/organization_default_surveypart.php', $params);
    }

    /**
     * Build a URL to the table of courses for which no survey exists yet in this semester.
     *
     * @param int $organizationid
     * @param int|null $orgsemid An orgsem or empty to use the current semester.
     * @return moodle_url
     */
    public static function courses_without_evaluations(int $organizationid, ?int $orgsemid): moodle_url {
        $params = ['id' => $organizationid];
        if ($orgsemid) {
            $params['orgsemid'] = $orgsemid;
        }
        return new moodle_url('/blocks/coursefeedback/organization_courses_without_evaluation.php', $params);
    }

    /**
     * Build a URL to the table of courses for which a survey exists in this semester.
     *
     * @param int $organizationid
     * @param int|null $orgsemid An orgsem or empty to use the current semester.
     * @return moodle_url
     */
    public static function evaluations(int $organizationid, ?int $orgsemid): moodle_url {
        $params = ['id' => $organizationid];
        if ($orgsemid) {
            $params['orgsemid'] = $orgsemid;
        }
        return new moodle_url('/blocks/coursefeedback/organization_evaluations.php', $params);
    }

    /**
     * Build a URL to the organization settings that change between semesters.
     *
     * @param int $organizationid
     * @param int $semesterid
     * @return moodle_url
     */
    public static function semester_settings(int $organizationid, int $semesterid): moodle_url {
        return new moodle_url('/blocks/coursefeedback/semester_settings.php', [
            'organizationid' => $organizationid,
            'semesterid' => $semesterid,
        ]);
    }

    /**
     * Build a URL to the results page for the given orgsem.
     *
     * @param int $organizationid
     * @param int $orgsemid
     * @return moodle_url
     */
    public static function semester_results(int $organizationid, int $orgsemid): moodle_url {
        return new moodle_url('/blocks/coursefeedback/semester_results.php', [
            'organizationid' => $organizationid,
            'orgsemid' => $orgsemid,
        ]);
    }

    /**
     * Build a URL to the direct download of the results for the given event type or the default questionnaire.
     *
     * @param int $organizationid
     * @param int $orgsemid
     * @param int|null $eventtypeid
     * @return moodle_url
     */
    public static function semester_results_download(int $organizationid, int $orgsemid, ?int $eventtypeid): moodle_url {
        return new moodle_url('/blocks/coursefeedback/semester_results.php', [
            'organizationid' => $organizationid,
            'orgsemid' => $orgsemid,
            'download' => $eventtypeid ?? 'default',
        ]);
    }

    /**
     * Build a URL to the direct download of the results for the given event type.
     *
     * @param int $organizationid
     * @param int $orgsemid
     * @return moodle_url
     */
    public static function semester_results_download_overview(int $organizationid, int $orgsemid): moodle_url {
        return new moodle_url('/blocks/coursefeedback/semester_results.php', [
            'organizationid' => $organizationid,
            'orgsemid' => $orgsemid,
            'download' => 'overview',
        ]);
    }

    /**
     * Build a URL to the semester-independent organization settings.
     *
     * @param int $organizationid
     * @return moodle_url
     */
    public static function organization_settings(int $organizationid): moodle_url {
        return new moodle_url('/blocks/coursefeedback/organization_settings.php', ['id' => $organizationid]);
    }
}
