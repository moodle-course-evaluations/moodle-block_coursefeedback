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

namespace block_coursefeedback\local\persistent;

use block_coursefeedback\local\survey_cache;
use core\clock;
use core\di;
use core\exception\coding_exception;
use core\lang_string;

/**
 * Execution settings and status for a single course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_execution extends persistent_with_bulk_actions {

    /** Table name for the persistent. */
    public const TABLE = 'block_coursefeedback_surveyexecution';

    /** @var int Survey is planned. */
    public const STATUS_PLANNED = 0;
    /** @var int Survey is active (and locked). The default event and surveypart was added, if there were no associated events. */
    public const STATUS_STARTED = 1;

    /**
     * Return the definition of the properties of this model.
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'organizationid' => [
                'type' => PARAM_INT,
            ],
            'starttime' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'endtime' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'status' => [
                'type' => PARAM_INT,
                'default' => 0,
                'choices' => [
                    self::STATUS_PLANNED,
                    self::STATUS_STARTED,
                ],
            ],
        ];
    }

    /**
     * Returns the appropriate string for this survey execution's state.
     *
     * @return lang_string
     */
    public function get_localized_status(): lang_string {
        $id = $this->get('id');
        $endtime = $this->get('endtime');
        $ended = $endtime && time() > $endtime;
        $status = $this->get('status');

        switch ($this->get('status')) {
            case self::STATUS_PLANNED:
                if ($ended) {
                    debugging("Survey execution '$id' endtime has passed but state is still planned.");
                }
                return new lang_string('planned', 'block_coursefeedback');
            case self::STATUS_STARTED:
                if ($ended) {
                    return new lang_string('finished', 'block_coursefeedback');
                }
                return new lang_string('ongoing', 'block_coursefeedback');
            default:
                throw new coding_exception("Survey execution '$id' has invalid status: '$status'");
        }
    }

    /**
     * Checks if the SE is currently ongoing.
     *
     * @return bool
     */
    public function is_ongoing(): bool {
        if ($this->get('status') != self::STATUS_STARTED) {
            return false;
        }

        $starttime = $this->get('starttime');
        $endtime = $this->get('endtime');
        if (!$starttime || !$endtime) {
            debugging("Survey execution {$this->get('id')} with status STARTED is missing a start and/or end time.");
            return false;
        }

        $now = di::get(clock::class)->time();
        return $now >= $starttime && $now < $endtime;
    }

    /**
     * Checks if the survey did run and has ended.
     *
     * @return bool
     */
    public function has_ended(): bool {
        if ($this->get('status') != self::STATUS_STARTED) {
            return false;
        }

        $endtime = $this->get('endtime');
        if (!$endtime) {
            debugging("Survey execution {$this->get('id')} with status STARTED is missing an end time.");
            return false;
        }

        return di::get(clock::class)->time() >= $endtime;
    }

    #[\Override]
    protected function after_create(): void {
        di::get(survey_cache::class)->evict_by_courseid($this->get('courseid'));
    }

    #[\Override]
    protected function after_delete($result): void {
        di::get(survey_cache::class)->evict_by_courseid($this->get('courseid'));
    }

    #[\Override]
    protected function after_update($result): void {
        di::get(survey_cache::class)->evict_by_courseid($this->get('courseid'));
    }
}
