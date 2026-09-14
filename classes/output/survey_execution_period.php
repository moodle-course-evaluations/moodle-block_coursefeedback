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

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\survey_execution;
use core\output\bootstrap_renderer;
use core\output\inplace_editable;
use core\output\named_templatable;
use core\output\renderable;
use core\output\renderer_base;
use core_date;
use DateTimeImmutable;

/**
 * Renderable showing the survey execution period and allowing to edit it.
 *
 * Similar to an {@see inplace_editable}, but that is a pain to extend.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_execution_period implements named_templatable, renderable {

    /**
     * Constructor.
     *
     * @param survey_execution $survey_execution
     * @param organization $organization
     * @param bool $editable
     */
    public function __construct(
        /** @var survey_execution $survey_execution */
        private readonly survey_execution $survey_execution,
        /** @var organization $organization */
        private readonly organization $organization,
        /** @var bool $editable */
        private readonly bool $editable
    ) {
    }

    #[\Override]
    public function get_template_name(renderer_base|bootstrap_renderer $renderer): string {
        return 'block_coursefeedback/survey_execution_period';
    }

    /**
     * Turns an integer unix timestamp into an ISO 8601 string for the datetime-local inputs, and a user-friendly string.
     *
     * @param int $timestamp
     * @return array
     */
    private function timestamp_to_iso_and_user(int $timestamp): array {
        return [
            "iso" => (new DateTimeImmutable())
                ->setTimestamp($timestamp)
                ->setTimezone(core_date::get_user_timezone_object())
                ->format('Y-m-d\TH:i'),
            "user" => userdate_htmltime($timestamp, get_string('strftimedatetime', 'langconfig')),
        ];
    }

    #[\Override]
    public function export_for_template(renderer_base|bootstrap_renderer $output): array {
        $context = [
            'editable' => $this->editable,
            'survey_execution_id' => $this->survey_execution->get('id'),
            'starttime' => $this->timestamp_to_iso_and_user($this->survey_execution->get('starttime') ??
                $this->organization->get('default_evaluation_starttime')),
            'endtime' => $this->timestamp_to_iso_and_user($this->survey_execution->get('endtime') ??
                $this->organization->get('default_evaluation_endtime')),
            'default_starttime' => $this->timestamp_to_iso_and_user($this->organization->get('default_evaluation_starttime')),
            'default_endtime' => $this->timestamp_to_iso_and_user($this->organization->get('default_evaluation_endtime')),
        ];
        $context['json_context'] = json_encode($context);
        return $context;
    }
}
