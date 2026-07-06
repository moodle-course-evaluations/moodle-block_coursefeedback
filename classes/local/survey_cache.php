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

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\survey_execution;
use cache;
use core\clock;

/**
 * Transparent cache manager for surveys.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_cache {

    /** @var cache */
    private readonly cache $cache;

    /**
     * Constructor.
     *
     * @param clock $clock
     */
    public function __construct(
        /** @var clock */
        private readonly clock $clock
    ) {
        $this->cache = cache::make('block_coursefeedback', 'survey');
    }

    /**
     * Evict the cache entry for the given course.
     *
     * @param int $courseid
     * @return void
     */
    public function evict_by_courseid(int $courseid): void {
        $this->cache->delete($courseid);
    }

    /**
     * Warms the cache using the given survey execution data.
     *
     * @param survey_execution_data $survey_execution_data
     * @return void
     */
    public function warm(survey_execution_data $survey_execution_data): void {
        $courseid = $survey_execution_data->survey_execution->get('courseid');
        if (
            $survey_execution_data->survey_execution->get('status') == survey_execution::STATUS_STARTED
            && !$survey_execution_data->survey_execution->has_ended()
        ) {
            $survey = survey::for_course($survey_execution_data);
            $this->cache->set($courseid, $survey);
        } else {
            $this->cache->set($courseid, $survey_execution_data->survey_execution);
        }
    }

    /**
     * Get the ongoing survey for the given course, if any, from the cache or fetch it and cache it transparently.
     *
     * @param object $course
     * @param bool $force_refresh
     * @return survey|null
     */
    public function get_by_course(object $course, bool $force_refresh = false): ?survey {
        $data = $force_refresh ? false : $this->cache->get($course->id);

        if ($data === false) {
            return $this->handle_cache_miss($course);
        } else if ($data === null || $data instanceof survey_execution || $data instanceof survey) {
            return $this->handle_cache_hit($course, $data);
        } else {
            debugging('Unexpected survey cache content of type ' . get_debug_type($data));
            return $this->handle_cache_miss($course);
        }
    }

    /**
     * Fetch and cache the ongoing survey, if any. Otherwise, cache the fact that there is no ongoing survey.
     *
     * @param object $course
     * @return survey|null
     */
    private function handle_cache_miss(object $course): ?survey {
        $data = survey_execution_data::load_from_course($course);
        if (!$data) {
            // Cache the fact that there isn't an SE.
            $this->cache->set($course->id, null);
            return null;
        }

        if (
            $data->survey_execution->get('status') == survey_execution::STATUS_PLANNED
            || $data->survey_execution->has_ended()
        ) {
            // Cache the fact that there is an SE that isn't ongoing.
            $this->cache->set($course->id, $data->survey_execution);
            return null;
        }

        // The SE is ongoing or about to be. Fetch the entire survey.
        $survey = survey::for_course($data);
        $this->cache->set($course->id, $survey);

        return $data->survey_execution->is_ongoing() ? $survey : null;
    }

    /**
     * Check if the cached value is still relevant and return it if so.
     *
     * If an ongoing survey is cached that has since ended, replace the cache entry with the SE.
     * If a started SE is cached that has since reached its start time, fetch the entire survey and cache it.
     *
     * @param object $course
     * @param survey_execution|survey|null $cached_data
     * @return survey|null
     */
    private function handle_cache_hit(object $course, null|survey_execution|survey $cached_data): ?survey {
        if ($cached_data === null) {
            // Cache hit, but no survey execution.
            return null;
        }

        if ($cached_data instanceof survey) {
            // The entire survey is cached, which means it was STARTED and not finished last we checked.

            if ($cached_data->survey_execution->has_ended()) {
                // If the survey has ended, we only cache the SE to limit cache size.
                // We don't evict because that would have us making DB calls again next time.
                $this->cache->set($course->id, $cached_data->survey_execution);
                return null;
            }

            return $cached_data->survey_execution->is_ongoing() ? $cached_data : null;
        }

        if ($cached_data instanceof survey_execution) {
            // The survey was planned or finished last we checked.

            if ($cached_data->get('status') == survey_execution::STATUS_PLANNED || $cached_data->has_ended()) {
                // Still planned or finished.
                return null;
            }

            // The survey has begun or is about to. Fetch the entire thing and cache it.
            $survey_execution_data = survey_execution_data::load_from_survey_execution_id_required($cached_data->get('id'));
            $survey = survey::for_course($survey_execution_data);
            $this->cache->set($course->id, $survey);

            return $cached_data->is_ongoing($this->clock) ? $survey : null;
        }
    }
}
