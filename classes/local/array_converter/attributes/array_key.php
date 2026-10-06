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

/**
 * Changes the name under which the value of a property appears in arrays.
 *
 * Renames differ from aliases in that they apply to both serialization and deserialization, and replace the original
 * property name.
 *
 * @package    block_coursefeedback
 * @author     Maximilian Haye
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class array_key {
    /**
     * Initializes a new attribute instance.
     *
     * @param string $key
     */
    public function __construct(
        /** @var string $key */
        public readonly string $key
    ) {
    }
}
