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

namespace block_coursefeedback\local\default_survey_creation_method;

use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use block_coursefeedback\local\persistent\survey_execution;

/**
 * Empty default survey creation method.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class empty_survey_creation_method extends default_survey_creation_method {

    #[\Override]
    public static function create_survey_execution(
        array $courseids,
        organization $organization,
        organization_semester $orgsem
    ): array {
        $ses = [];
        foreach ($courseids as $courseid) {
            $se = new survey_execution(0, (object) [
                'starttime' => null,
                'endtime' => null,
                'courseid' => $courseid,
                'orgsemid' => $orgsem->get('id'),
                'status' => 0,
            ]);
            $se->save();
            $ses[] = $se;
        }
        return $ses;
    }
}
