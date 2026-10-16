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

namespace block_coursefeedback\local\results;

use block_coursefeedback\local\multilang_string;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\organization_semester;
use block_coursefeedback\local\persistent\surveyitem;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;
use block_coursefeedback\local\surveyitem\surveyitemtype;
use core\clock;
use core\exception\coding_exception;
use DateInterval;

/**
 * block_coursefeedback results_service.php description here.
 *
 * @package    block_coursefeedback
 * @copyright  2026  <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class results_service {

    /**
     * Constructor.
     * @param clock $clock
     */
    public function __construct(
        /** @var clock $clock */
        private readonly clock $clock
    ) {
    }

    /**
     * Gets CSV rows for the organization overview.
     *
     * @param organization_semester $orgsem
     * @return array[]
     */
    public function get_overview(organization_semester $orgsem): array {
        global $DB;

        $has_default_questionnaire = boolval($orgsem->get('default_surveypartid'));

        $eventtypes = eventtype::get_records(['orgsemid' => $orgsem->get('id')]);

        $header = ['course_id', 'course_fullname', 'enrolled_users', 'active_users'];
        if ($has_default_questionnaire) {
            $header[] = 'default_questionnaire_responses';
        }
        foreach ($eventtypes as $eventtype) {
            $header[] = $eventtype->get('name');
        }

        if ($eventtypes) {
            [$in_eventtypeids_sql, $in_eventtypeids_params] = $DB->get_in_or_equal(
                array_map(fn($eventtype) => $eventtype->get('id'), $eventtypes),
                SQL_PARAMS_NAMED
            );
        } else {
            // Get_in_or_equal doesn't support empty arrays. We just duplicate the 'IS NULL' so it won't have any effect.
            $in_eventtypeids_sql = "IS NULL";
            $in_eventtypeids_params = [];
        }

        $courses_recordset = $DB->get_recordset_sql("
            SELECT c.id, c.fullname, te.eventtypeid, COUNT(rs.id) AS response_count
            FROM {course} c
            INNER JOIN {block_coursefeedback_surveyexecution} se ON c.id = se.courseid AND se.orgsemid = :orgsemid
            LEFT JOIN {block_coursefeedback_surveypartexecution} spe ON spe.surveyexecutionid = se.id
            LEFT JOIN {block_coursefeedback_course_eventtype} te
                ON te.id = spe.eventid AND (te.eventtypeid IS NULL OR te.eventtypeid $in_eventtypeids_sql)
            LEFT JOIN {block_coursefeedback_surveypartexecutionoption} speo ON speo.surveypartexecutionid = spe.id
            LEFT JOIN {block_coursefeedback_surveypartexecutionoptionresp} rs ON rs.surveypartexecutionoptionid = speo.id
            GROUP BY c.id, c.fullname, te.eventtypeid
        ", [...$in_eventtypeids_params, 'orgsemid' => $orgsem->get('id')]);

        if (!$courses_recordset || !$courses_recordset->valid()) {
            // This orgsem has no surveys planned, in progress, or finished.
            return [$header];
        }

        $courses = [];
        foreach ($courses_recordset as $record) {
            $courses[$record->id] ??= [
                'fullname' => $record->fullname,
                'default_questionnaire_responses' => null,
                'responses_by_eventtypeid' => [],
            ];

            if ($record->eventtypeid) {
                $courses[$record->id]['responses_by_eventtypeid'][$record->eventtypeid] = $record->response_count;
            } else if ($has_default_questionnaire) {
                $courses[$record->id]['default_questionnaire_responses'] = $record->response_count;
            }
        }

        [$in_courseids_sql, $in_courseids_params] = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED);

        $course_user_records = $DB->get_records_sql("
            SELECT c.id,
                   COUNT(DISTINCT ra.userid) AS enrolled_count,
                   COUNT(DISTINCT ul.userid) AS recently_active_count
            FROM {course} c
                 JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                 JOIN {role_assignments} ra ON ra.contextid = ctx.id
                 JOIN {role} r ON r.id = ra.roleid AND r.archetype = 'student'
                 LEFT JOIN {user_lastaccess} ul
                           ON ul.courseid = c.id
                               AND ul.userid = ra.userid
                               AND ul.timeaccess >= :active_user_cutoff
            WHERE c.id $in_courseids_sql
            GROUP BY c.id
        ", [
            ...$in_courseids_params,
            'active_user_cutoff' => $this->clock->now()->sub(new DateInterval('P2W'))->getTimestamp(),
        ]);

        $rows = [$header];
        foreach ($courses as $courseid => $course) {
            $row = [
                $courseid, $course['fullname'],
                $course_user_records[$courseid]?->enrolled_count ?? 0,
                $course_user_records[$courseid]?->recently_active_count ?? 0,
            ];

            if ($has_default_questionnaire) {
                $row[] = $course['default_questionnaire_responses'];
            }

            foreach ($eventtypes as $event_type_id => $_) {
                $row[] = $course['responses_by_eventtypeid'][$event_type_id] ?? null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Gets response sets CSV rows for the default questionnaire across the orgsem.
     *
     * @param organization_semester $orgsem
     * @return array[]
     */
    public function get_for_default_questionnaire(organization_semester $orgsem): array {
        if (!$orgsem->get('default_surveypartid')) {
            throw new coding_exception("Orgsem {$orgsem->get('id')} does not have a default questionnaire");
        }

        $questionnaire = surveypart::get_record(['id' => $orgsem->get('default_surveypartid')]);

        return $this->get_response_sets_by_filter($orgsem, $questionnaire, 'te.eventtypeid IS NULL', []);
    }

    /**
     * Gets response set CSV rows for the given event type across the orgsem.
     *
     * @param organization_semester $orgsem
     * @param eventtype $eventtype
     * @return array[]
     */
    public function get_by_semester_and_eventtype(organization_semester $orgsem, eventtype $eventtype): array {
        // TODO: Gracefully handle or forbid changed eventtype-sp mapping.

        $surveypart_fields = surveypart::get_sql_fields('sp', 'sp_');
        global $DB;
        $surveypart_record = $DB->get_record_sql("
            SELECT $surveypart_fields
            FROM {block_coursefeedback_eventtype} tet
            JOIN {block_coursefeedback_surveypart} sp ON sp.id = tet.surveypartid
            WHERE tet.id = :eventtypeid AND tet.orgsemid = :orgsemid
        ", ['eventtypeid' => $eventtype->get('id'), 'orgsemid' => $orgsem->get('id')], MUST_EXIST);
        $questionnaire = surveypart::extract($surveypart_record, 'sp_');

        return $this->get_response_sets_by_filter(
            $orgsem,
            $questionnaire,
            'te.eventtypeid = :eventtypeid',
            ['eventtypeid' => $eventtype->get('id')]
        );
    }

    /**
     * Gets response set CSV rows by the given filter SQL.
     *
     * @param organization_semester $orgsem
     * @param surveypart $questionnaire
     * @param string $eventtype_filter
     * @param array $params
     * @return array[]
     */
    private function get_response_sets_by_filter(
        organization_semester $orgsem,
        surveypart $questionnaire,
        string $eventtype_filter,
        array $params
    ): array {
        $surveyitems = array_filter(
            $questionnaire->get_surveyitems(),
            fn($surveyitem) => surveyitem_manager::get_surveyitemtype($surveyitem)->should_appear_in_reports()
        );

        global $DB;
        $response_recordset = $DB->get_recordset_sql("
            SELECT rs.id AS response_set_id, c.id AS course_id, c.fullname AS course_fullname,
                   te.id AS event_id, te.name AS event_name,
                   speo.id AS slot_id, speo.name AS slot_name,
                   COALESCE(textresp.surveyitemid, intresp.surveyitemid) AS surveyitemid,
                   textresp.value AS text_value, intresp.value AS int_value
            FROM {course} c
            INNER JOIN {block_coursefeedback_surveyexecution} se ON c.id = se.courseid AND se.orgsemid = :orgsemid
            INNER JOIN {block_coursefeedback_surveypartexecution} spe ON spe.surveyexecutionid = se.id
            INNER JOIN {block_coursefeedback_course_eventtype} te ON te.id = spe.eventid AND $eventtype_filter
            INNER JOIN {block_coursefeedback_surveypartexecutionoption} speo ON speo.surveypartexecutionid = spe.id
            INNER JOIN {block_coursefeedback_surveypartexecutionoptionresp} rs ON rs.surveypartexecutionoptionid = speo.id
            LEFT JOIN {block_coursefeedback_surveyitemtextresponse} textresp ON textresp.surveypartexecutionoptionresponseid = rs.id
            LEFT JOIN {block_coursefeedback_surveyitemintresponse} intresp ON intresp.surveypartexecutionoptionresponseid = rs.id
            ORDER BY te.courseid, rs.id
        ", [...$params, 'orgsemid' => $orgsem->get('id')]);

        $response_sets = [];
        foreach ($response_recordset as $record) {
            $response_sets[$record->response_set_id] ??= [
                'course_id' => $record->course_id,
                'course_fullname' => $record->course_fullname,
                'event_id' => $record->event_id,
                'event_name' => $record->event_name,
                'slot_id' => $record->slot_id,
                'slot_name' => $record->slot_name,
                'values_by_surveyitemid' => [],
            ];

            if ($record->text_value !== null) {
                $response_sets[$record->response_set_id]['values_by_surveyitemid'][$record->surveyitemid][] =
                    $record->text_value;
            }
            if ($record->int_value !== null) {
                $response_sets[$record->response_set_id]['values_by_surveyitemid'][$record->surveyitemid][] =
                    intval($record->int_value);
            }
        }

        $header = ['response_set_id', 'course_id', 'course_fullname', 'event_id', 'event_name', 'slot_id', 'slot_name'];

        foreach ($surveyitems as $surveyitem) {
            /** @var multilang_string|null $text */
            $text = $surveyitem->get('text');
            if ($text) {
                $header[] = format_string($text->translate());
            } else {
                $header[] = get_string('unnamed_surveyitem', 'block_coursefeedback', $surveyitem->get('id'));
            }
        }

        $rows = [$header];

        foreach ($response_sets as $response_set_id => $response_set) {
            $row = [
                $response_set_id,
                $response_set['course_id'], $response_set['course_fullname'],
                $response_set['event_id'], $response_set['event_name'],
                $response_set['slot_id'], $response_set['slot_name'],
            ];

            foreach ($surveyitems as $surveyitem) {
                $values = $response_set['values_by_surveyitemid'][$surveyitem->get('id')] ?? [];
                $surveyitemtype = surveyitem_manager::get_surveyitemtype($surveyitem);
                $row[] = $surveyitemtype->post_process_response_values($surveyitem, $values);
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
