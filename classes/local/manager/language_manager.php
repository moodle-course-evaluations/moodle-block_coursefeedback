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

namespace block_coursefeedback\local\manager;

use Generator;

/**
 * Contains methods managing languages.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class language_manager {


    /**
     * Returns a generator that yields the preferred languages in order of preference.
     *
     * @return Generator<string>
     */
    public static function iterate_preferred_languages(?string $lang = null): Generator {
        if (!$lang) {
            $lang = current_language();
        }
        // First, we try the current language.
        yield $lang;

        // Then, the parent languages of the current language.
        $parent_languages = get_string_manager()->get_language_dependencies($lang);
        foreach (array_reverse($parent_languages) as $parent_language) {
            if ($parent_language !== $lang) {
                yield $parent_language;
            }
        }

        // English.
        yield 'en';

        // Site-wide default language.
        global $CFG;
        yield $CFG->lang;
    }

    /**
     * Return the best matching language out of the $available_languages.
     *
     * @param string[] $available_languages
     * @param string|null $preferred_language
     * @return string
     */
    public static function find_preferred_language(array $available_languages, ?string $preferred_language = null): string {
        $preferred_language ??= current_language();

        $available_languages = array_flip($available_languages);

        foreach (self::iterate_preferred_languages($preferred_language) as $try_lang) {
            if (isset($available_languages[$try_lang])) {
                return $try_lang;
            }
        }

        // Fall back on the first language we have.
        return array_key_first($available_languages);
    }
}
