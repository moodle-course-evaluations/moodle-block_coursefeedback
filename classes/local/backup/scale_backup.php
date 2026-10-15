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

use block_coursefeedback\local\array_converter\attributes\array_validate;
use core\param;

/**
 * Simple data model of a scale in a {@see questionnaire_backup}.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scale_backup {

    /**
     * Constructor.
     *
     * @param string $name
     * @param int $optionamount
     * @param string $minoptiontext
     * @param string $maxoptiontext
     * @param bool $hasnoansweroption
     * @param string|null $noansweroptiontext
     * @param string|null $centeroptiontext
     */
    public function __construct(
        /** @var string */
        #[array_validate(PARAM_TEXT)]
        public readonly string $name,
        /** @var int */
        public readonly int $optionamount,
        /** @var string */
        #[array_validate(PARAM_RAW)]
        public readonly string $minoptiontext,
        /** @var string */
        #[array_validate(PARAM_RAW)]
        public readonly string $maxoptiontext,
        /** @var bool */
        public readonly bool $hasnoansweroption = false,
        /** @var string|null */
        #[array_validate(PARAM_RAW, allow_null: true)]
        public readonly ?string $noansweroptiontext = null,
        /** @var string|null */
        #[array_validate(PARAM_RAW, allow_null: true)]
        public readonly ?string $centeroptiontext = null,
    ) {
    }
}
