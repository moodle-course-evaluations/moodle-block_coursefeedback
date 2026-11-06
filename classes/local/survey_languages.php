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

use block_coursefeedback\local\persistent\survey_execution;

/**
 * A class combining all the course feedback data for a single course.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_languages {

    /**
     * Constructor
     *
     * @param survey_execution $survey_execution
     * @param string[] $languages
     */
    private function __construct(
        /** @var survey_execution */
        public readonly survey_execution $survey_execution,
        /** @var string[] $languages */
        public readonly array $languages,
    ) {
    }

    /**
     * Get survey_languages for survey execution.
     * @param survey_execution $survey_execution
     * @return self
     */
    public static function from_survey_execution(survey_execution $survey_execution): self {
        global $DB;
        $languages = $DB->get_fieldset_sql(
            'SELECT DISTINCT spl.language FROM {block_coursefeedback_surveypartexecution} spe ' .
            'JOIN {block_coursefeedback_surveypart} sp ON sp.id = spe.surveypartid ' .
            'JOIN {block_coursefeedback_surveypart_language} spl ON spl.surveypartid = sp.id ' .
            'WHERE spe.surveyexecutionid = :surveyexecutionid',
            [
                'surveyexecutionid' => $survey_execution->get('id'),
            ]
        );
        return new self(
            $survey_execution,
            $languages,
        );
    }
}
