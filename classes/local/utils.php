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

/**
 * A utility class with methods that don't have a better place yet.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Flattens an iterable of iterables.
     *
     * @param iterable<iterable> $iterable
     * @param bool $preserve_keys
     * @return array
     */
    public static function array_flatten(iterable $iterable, bool $preserve_keys = false): array {
        $result = [];
        foreach ($iterable as $outer) {
            foreach ($outer as $key => $inner) {
                if ($preserve_keys) {
                    $result[$key] = $inner;
                } else {
                    $result[] = $inner;
                }
            }
        }
        return $result;
    }
}
