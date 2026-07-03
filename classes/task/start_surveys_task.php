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

namespace block_coursefeedback\task;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\response_slot;
use block_coursefeedback\local\persistent\survey_execution;
use block_coursefeedback\local\persistent\survey_part_execution;
use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\persistent\teaching_event;
use block_coursefeedback\local\survey_cache;
use block_coursefeedback\local\survey_execution_data;
use core\di;
use core\task\scheduled_task;

/**
 * Task file for locking survey_executions.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class start_surveys_task extends scheduled_task {

    /** @var int Amount of seconds surveys will be created in advance. */
    private const CREATE_SURVEYS_IN_ADVANCE_SECONDS = 2 * 60;

    /** @var survey_cache */
    private readonly survey_cache $survey_cache;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->survey_cache = di::get(survey_cache::class);
    }

    /**
     * Return the task's name as shown in admin screens.
     * @return string
     */
    public function get_name() {
        return get_string('task:start_surveys', 'block_coursefeedback');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $survey_execution_ids = $DB->get_fieldset_sql(
            "SELECT se.id
            FROM {" . survey_execution::TABLE . "} se
            JOIN {" . organization::TABLE . "} o ON se.organizationid = o.id
            WHERE se.status = " . survey_execution::STATUS_PLANNED . "
                AND :time >= COALESCE(se.starttime, o.default_evaluation_starttime)",
            ['time' => time() + self::CREATE_SURVEYS_IN_ADVANCE_SECONDS],
        );

        $survey_execution_datas = survey_execution_data::load_from_survey_execution_ids($survey_execution_ids);

        foreach ($survey_execution_datas as $survey_execution_data) {
            $se = $survey_execution_data->survey_execution;

            $has_surveypart = false;
            /** @var array<int, int> $additional_survey_part_ids Holds the SPs that we add to the survey by using defaults. */
            $additional_survey_part_ids = [];

            $transaction = $DB->start_delegated_transaction();

            mtrace("Activate survey for course " . $se->get('courseid'));

            foreach ($survey_execution_data->events_by_id as $event) {
                $spe = $survey_execution_data->spes_by_event_id[$event->get('id')];
                $surveypart = $survey_execution_data->survey_parts_by_spe_id[$spe->get('id')] ?? null;
                if ($surveypart) {
                    $has_surveypart = true;
                } else {
                    $eventtype = $survey_execution_data->types_by_event_id[$event->get('id')] ?? null;
                    $event_type_surveypartid = $eventtype?->get('surveypartid');
                    if ($event_type_surveypartid) {
                        $spe->set('surveypartid', $event_type_surveypartid);
                        $spe->save();
                        $has_surveypart = true;
                        $additional_survey_part_ids[$spe->get('id')] = $event_type_surveypartid;
                        mtrace("... and inherit surveypartid for " . $event->get('id'));
                    } else {
                        mtrace("... and skip event " . $event->get('id') . " with no eventtype or default surveypartid");
                    }
                    // TODO Otherwise, should we delete SPE and Event?
                }
            }

            $always_show_default_sp = $survey_execution_data->organization->get('always_show_default_sp');
            $org_default_surveypartid = $survey_execution_data->organization->get('default_surveypartid');
            if ($org_default_surveypartid && (!$has_surveypart || $always_show_default_sp)) {
                $teaching_event = new teaching_event(record: (object) [
                    'courseid' => $se->get('courseid'),
                    'eventtypeid' => null,
                    'name' => '',
                    // Insert before all normal events, if any.
                    'sortindex' => -1,
                ]);
                $teaching_event->create();
                $spe = new survey_part_execution(record: (object) [
                    'surveyexecutionid' => $se->get('id'),
                    'surveypartid' => $survey_execution_data->organization->get('default_surveypartid'),
                    'eventid' => $teaching_event->get('id'),
                ]);
                $spe->create();
                $slot = new response_slot(record: (object) [
                    'surveypartexecutionid' => $spe->get('id'),
                    'name' => '',
                    'externalid' => null,
                ]);
                $slot->create();

                // Mutate the survey execution data so we can use it to immediately warm the survey cache.
                $survey_execution_data->events_by_id =
                    [$teaching_event->get('id') => $teaching_event] + $survey_execution_data->events_by_id;
                $survey_execution_data->spes_by_event_id =
                    [$teaching_event->get('id') => $spe] + $survey_execution_data->spes_by_event_id;
                $survey_execution_data->slots_by_spe_id =
                    [$spe->get('id') => [$slot]] + $survey_execution_data->slots_by_spe_id;
                $survey_execution_data->slots_by_id =
                    [$slot->get('id') => $slot] + $survey_execution_data->slots_by_id;
                $additional_survey_part_ids[$spe->get('id')] = $org_default_surveypartid;

                mtrace(" ... and create default event");
            }

            if (!$se->get('starttime')) {
                $se->set('starttime', $survey_execution_data->organization->get('default_evaluation_starttime'));
            }
            if (!$se->get('endtime')) {
                $se->set('endtime', $survey_execution_data->organization->get('default_evaluation_endtime'));
            }
            $se->set('status', survey_execution::STATUS_STARTED);
            $se->save();

            $transaction->allow_commit();

            // Add the added survey parts to the survey execution data so we can use it to warm the survey cache.
            $additional_survey_parts = surveypart::get_records_list('id', $additional_survey_part_ids);
            foreach ($additional_survey_part_ids as $spe_id => $surveypart_id) {
                $survey_execution_data->survey_parts_by_spe_id[$spe_id] = $additional_survey_parts[$surveypart_id];
            }
            $this->survey_cache->warm($survey_execution_data);
        }
    }
}
