<?php
// This file is part of the QuestionPy Moodle plugin - https://questionpy.org
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

namespace block_coursefeedback\local\array_converter\attributes;

use Attribute;
use core\param;

/**
 * Validates the raw value using {@see param::validate_param()} of the given `PARAM_*` constant.
 *
 * When applied to an array property, validates its values.
 *
 * @package    block_coursefeedback
 * @author     Maximilian Haye
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class array_validate {

    /** @var param $values */
    public readonly param $param_type;

    /**
     * Initializes a new attribute instance.
     *
     * @param param|string $param_type
     * @param bool $allow_null
     */
    public function __construct(
        param|string $param_type,
        /** @var bool $allow_null */
        public bool $allow_null = false
    ) {
        $this->param_type = is_string($param_type) ? param::from_type($param_type) : $param_type;
    }

    /**
     * Validates the given raw value against this attribute's parameter type.
     *
     * @param mixed $raw_value
     * @return mixed
     */
    public function validate_raw_value(mixed $raw_value): mixed {
        if (is_array($raw_value)) {
            return array_map(fn($nested_value) => $this->param_type->validate_param($nested_value, $this->allow_null), $raw_value);
        }

        return $this->param_type->validate_param($raw_value, $this->allow_null);
    }
}
