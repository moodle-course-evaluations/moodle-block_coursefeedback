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

namespace block_coursefeedback\local;

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\persistent\eventtype;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\response_slot_user;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use core_user\fields;

/**
 * A class combining all the course feedback data for a single course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_execution_data {

    /**
     * Private constructor, use {@see load_from_course()} or {@see load_from_course_required()}.
     *
     * @param survey_execution $survey_execution
     * @param organization $organization
     * @param organization_semester $orgsem
     * @param array<int, teaching_event> $events_by_id
     * @param array<int, eventtype> $types_by_event_id
     * @param array<int, survey_part_execution> $spes_by_event_id
     * @param array<int, surveypart> $survey_parts_by_spe_id
     * @param array<int, response_slot[]> $slots_by_spe_id
     * @param array<int, response_slot> $slots_by_id
     * @param array<int, array<int, object>> $users_by_slot_id
     */
    private function __construct(
        /** @var survey_execution $survey_execution */
        public readonly survey_execution $survey_execution,
        /** @var organization $organization */
        public readonly organization $organization,
        /** @var organization_semester $orgsem */
        public readonly organization_semester $orgsem,
        /** @var array<int, teaching_event> $events_by_id */
        public array $events_by_id,
        /** @var array<int, eventtype> $types_by_event_id */
        public readonly array $types_by_event_id,
        /** @var array<int, survey_part_execution> $spes_by_event_id */
        public array $spes_by_event_id,
        /** @var array<int, surveypart> $survey_parts_by_spe_id */
        public array $survey_parts_by_spe_id,
        /** @var array<int, response_slot[]> $slots_by_spe_id */
        public array $slots_by_spe_id,
        /** @var array<int, response_slot> $slots_by_id */
        public array $slots_by_id,
        /** @var array<int, array<int, object>> $users_by_slot_id */
        public readonly array $users_by_slot_id,
    ) {
    }

    /**
     * Loads all survey execution data which match the given where snippet.
     * @param string $where SQL Where part
     * @param array $params
     * @return self[]
     */
    private static function load_by_filters(string $where, array $params): array {
        global $DB;

        $se_fields = survey_execution::get_sql_fields('se', 'se_');
        $organization_fields = organization::get_sql_fields('o', 'o_');
        $orgsem_fields = organization_semester::get_sql_fields('os', 'os_');
        $event_fields = teaching_event::get_sql_fields('te', 'te_');
        $event_type_fields = eventtype::get_sql_fields('et', 'et_');
        $spe_fields = survey_part_execution::get_sql_fields('spe', 'spe_');
        $sp_fields = surveypart::get_sql_fields('sp', 'sp_');
        $slot_fields = response_slot::get_sql_fields('rs', 'rs_');
        $user_fields_sql = fields::for_name()
            ->including('id')
            ->get_sql(alias: 'u', namedparams: true, prefix: 'u_');

        $recordset = $DB->get_recordset_sql("
            SELECT $se_fields, $organization_fields, $orgsem_fields, $event_fields, $event_type_fields,
                   $spe_fields, $sp_fields, $slot_fields $user_fields_sql->selects
            FROM {" . survey_execution::TABLE . "} se
            LEFT JOIN {" . organization_semester::TABLE . "} os ON se.orgsemid = os.id
            LEFT JOIN {" . organization::TABLE . "} o ON se.organizationid = o.id AND os.organizationid = o.id
            LEFT JOIN {" . teaching_event::TABLE . "} te ON se.courseid = te.courseid
            LEFT JOIN {" . eventtype::TABLE . "} et ON te.eventtypeid = et.id
            LEFT JOIN {" . survey_part_execution::TABLE . "} spe ON se.id = spe.surveyexecutionid AND te.id = spe.eventid
            LEFT JOIN {" . surveypart::TABLE . "} sp ON spe.surveypartid = sp.id
            LEFT JOIN {" . response_slot::TABLE . "} rs ON spe.id = rs.surveypartexecutionid
            LEFT JOIN {" . response_slot_user::TABLE . "} ru ON rs.id = ru.surveypartexecutionoptionid
            LEFT JOIN {user} u ON ru.userid = u.id
            $user_fields_sql->joins
            WHERE $where
            ORDER BY se.id, te.sortindex, te.id, spe.id, rs.id, ru.id
        ", [...$params, ...$user_fields_sql->params]);

        $datas = [];

        try {
            if (!$recordset || !$recordset->valid()) {
                return [];
            }

            $record_extractor = new record_extractor($recordset);

            foreach ($record_extractor->yield_records('se_') as $se_record) {
                $survey_execution = new survey_execution(record: $se_record);
                $organization = new organization(record: $record_extractor->get_related('o_'));
                $orgsem = new organization_semester(record: $record_extractor->get_related('os_'));

                $events_by_id = [];
                $event_types_by_id = [];
                $types_by_event_id = [];
                $spes_by_event_id = [];
                $survey_parts_by_spe_id = [];
                $slots_by_spe_id = [];
                $slots_by_id = [];
                $users_by_slot_id = [];

                foreach (
                    $record_extractor->yield_records(
                        'te_',
                        fn($row) => $row->se_id === $se_record->id
                    ) as $event_record
                ) {
                    $events_by_id[$event_record->id] = new teaching_event(record: $event_record);

                    if ($event_record->eventtypeid) {
                        // Avoid creating multiple instances of the same event type.
                        $type = $event_types_by_id[$event_record->eventtypeid] ?? null;
                        if (!$type) {
                            $et_record = $record_extractor->get_related('et_');
                            if (!$et_record) {
                                throw new coding_exception(
                                    "Event '$event_record->id' has eventtypeid '$event_record->eventtypeid' that doesn't exist"
                                );
                            }
                            $type = $event_types_by_id[$event_record->eventtypeid] = new eventtype(record: $et_record);
                        }
                        $types_by_event_id[$event_record->id] = $type;
                    }

                    $spe_record = $record_extractor->get_related('spe_');
                    if (!$spe_record) {
                        // This event has no survey part execution.
                        continue;
                    }

                    $spes_by_event_id[$event_record->id] = new survey_part_execution(record: $spe_record);
                    $sp_record = $record_extractor->get_related('sp_');
                    if ($spe_record->surveypartid) {
                        if (!$sp_record) {
                            throw new coding_exception("Survey part execution '$spe_record->id' has surveypartid "
                                . "'$spe_record->surveypartid' that doesn't exist.");
                        }
                        $survey_parts_by_spe_id[$spe_record->id] = new surveypart(record: $sp_record);
                    }

                    foreach (
                        $record_extractor->yield_records(
                            'rs_',
                            fn($row) => $row->spe_id === $spe_record->id
                        ) as $slot_record
                    ) {
                        $slots_by_id[$slot_record->id] = $slots_by_spe_id[$spe_record->id][]
                            = new response_slot(record: $slot_record);

                        foreach (
                            $record_extractor->yield_records(
                                'u_',
                                fn($row) => $row->rs_id === $slot_record->id
                            ) as $user
                        ) {
                            $users_by_slot_id[$slot_record->id][$user->id] = $user;
                        }
                    }
                }

                $datas[] = new self(
                    survey_execution: $survey_execution,
                    organization: $organization,
                    orgsem: $orgsem,
                    events_by_id: $events_by_id,
                    types_by_event_id: $types_by_event_id,
                    spes_by_event_id: $spes_by_event_id,
                    survey_parts_by_spe_id: $survey_parts_by_spe_id,
                    slots_by_spe_id: $slots_by_spe_id,
                    slots_by_id: $slots_by_id,
                    users_by_slot_id: $users_by_slot_id,
                );
            }
        } finally {
            $recordset?->close();
        }
        return $datas;
    }

    /**
     * Load data for a single survey execution. Throw error if it doesn't exist.
     * @param int $survey_execution_id
     * @return self
     */
    public static function load_from_survey_execution_id_required(int $survey_execution_id): self {
        $data = self::load_by_filters('se.id = :surveyexecutionid', ['surveyexecutionid' => $survey_execution_id]);
        if (count($data) != 1) {
            throw new coding_exception('There is no survey execution with id ' . $survey_execution_id);
        }
        return $data[0];
    }


    /**
     * Load multiple survey execution data.
     * @param array $survey_execution_ids
     * @return self[]
     */
    public static function load_from_survey_execution_ids(array $survey_execution_ids): array {
        global $DB;
        if (!$survey_execution_ids) {
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal($survey_execution_ids, SQL_PARAMS_NAMED);
        return self::load_by_filters(
            "se.id $insql",
            $inparams
        );
    }

    /**
     * Loads the course feedback data for the given course if there is an active survey.
     *
     * @param object|int $course_or_id
     * @param ?int $organizationid Organization for the survey.
     *      If null, the current organization for the course will be fetched.
     * @return self|null
     */
    public static function load_from_course(object|int $course_or_id, ?int $organizationid = null): ?self {
        $course = is_int($course_or_id) ? get_course($course_or_id) : $course_or_id;

        if (!$organizationid) {
            $organization = course_organization_mapping::get_instance()->get_organization_for_course($course);
            if (!$organization) {
                return null;
            }
            $organizationid = $organization->get('id');
        }

        $data = self::load_by_filters("se.courseid = :courseid AND se.organizationid = :organizationid", [
            'courseid' => $course->id,
            'organizationid' => $organizationid,
        ]);
        return count($data) === 1 ? $data[0] : null;
    }

    /**
     * Loads the course feedback data for the given course, throwing if the course has no active survey.
     *
     * @param object|int $course_or_id
     * @param ?int $organizationid Organization for the survey.
     *       If null, the current organization for the course will be fetched.
     * @return self
     */
    public static function load_from_course_required(object|int $course_or_id, ?int $organizationid = null): self {
        $course = is_int($course_or_id) ? get_course($course_or_id) : $course_or_id;

        $result = self::load_from_course($course, $organizationid);
        if (!$result) {
            throw new moodle_exception(
                'no_survey_execution',
                'block_coursefeedback',
                a: $course,
                debuginfo: "courseid: $course->id"
            );
        }
        return $result;
    }
}
