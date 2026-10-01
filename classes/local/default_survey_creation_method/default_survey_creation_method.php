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
 * Abstract default survey creation method.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class default_survey_creation_method {

    /** @var string Default method, create empty survey executions. */
    public const METHOD_CREATE_EMPTY = 'empty';

    /** @var string Method specifically for the RUB Campus DB. */
    public const METHOD_RUB = 'rub';

    /**
     * Create new survey execution for the given courseids. Organization and semester can be used, but don't have to be.
     *
     * @param array $courseids
     * @param organization $organization
     * @param organization_semester $orgsem
     * @return survey_execution[] all created survey_executions.
     */
    abstract public static function create_survey_execution(
        array $courseids,
        organization $organization,
        organization_semester $orgsem
    ): array;

    /**
     * Returns the correct default survey creation method based on the setting.
     * @return class-string<default_survey_creation_method>
     */
    public static function get_instance(): string {
        $method = get_config('block_coursefeedback', 'default_survey_creation_method');
        static $instances = [
            self::METHOD_CREATE_EMPTY => empty_survey_creation_method::class,
            self::METHOD_RUB => rub_survey_creation_method::class,
        ];
        return $instances[$method];
    }
}
