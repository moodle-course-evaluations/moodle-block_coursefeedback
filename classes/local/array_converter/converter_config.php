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

namespace block_coursefeedback\local\array_converter;

use block_coursefeedback\local\array_converter\attributes\array_validate;
use core\param;

/**
 * Holds customization of {@see array_converter}.
 *
 * @package    block_coursefeedback
 * @author     Maximilian Haye
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter_config {
    /** @var string[] mapping from property names to array keys */
    public array $renames = [];
    /** @var string[] mapping from property names to arrays of their aliases */
    public array $aliases = [];

    /** @var ?string discriminator array key, if any */
    public ?string $discriminator = null;
    /** @var string[] mapping from discriminator values to concrete classes */
    public array $variants = [];
    /** @var string|null if an unknown discriminator is given, warn and use this class */
    public ?string $fallbackvariant;

    /** @var string[] mapping from property names to the classes of their array elements */
    public array $elementclasses = [];

    /** @var array<string, array_validate> mapping from property names to {@see array_validate} instances  */
    public array $validators = [];

    /**
     * Merges the given config into this one.
     *
     * @param converter_config $other
     * @return void
     */
    public function update_from(converter_config $other): void {
        $this->renames = array_merge($this->renames, $other->renames);
        $this->aliases = array_merge($this->aliases, $other->aliases);
        $this->discriminator = $this->discriminator ?? $other->discriminator;
        $this->variants = array_merge($this->variants, $other->variants);
        $this->fallbackvariant = $this->fallbackvariant ?? $other->fallbackvariant;
        $this->elementclasses = array_merge($this->elementclasses, $other->elementclasses);
        $this->validators = array_merge($this->validators, $other->validators);
    }
}
