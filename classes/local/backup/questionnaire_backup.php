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

namespace block_coursefeedback\local\backup;

use block_coursefeedback\local\array_converter\attributes\array_element_class;
use block_coursefeedback\local\array_converter\attributes\array_validate;
use core\param;
use DateTimeImmutable;

/**
 * Simple data model of a survey part / questionnaire backup.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionnaire_backup {

    /**
     * Constructor.
     *
     * @param string $name
     * @param array $languages
     * @param DateTimeImmutable $backup_time
     * @param array $scales
     * @param array $items
     */
    public function __construct(
        /** @var string */
        #[array_validate(PARAM_TEXT)]
        public readonly string $name,
        /** @var string[] */
        #[array_validate(PARAM_ALPHANUMEXT)]
        public readonly array $languages,
        /** @var DateTimeImmutable */
        public readonly DateTimeImmutable $backup_time,
        /** @var scale_backup[] */
        #[array_element_class(scale_backup::class)]
        public readonly array $scales = [],
        /** @var survey_item_backup[] */
        #[array_element_class(survey_item_backup::class)]
        public readonly array $items = [],
    ) {
    }
}
