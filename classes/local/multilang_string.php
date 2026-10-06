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

use coding_exception;
use core\exception\moodle_exception;
use Generator;
use JsonException;
use JsonSerializable;

// PHPCS is confused by constructor property promotion.
// phpcs:disable moodle.Commenting.VariableComment.Missing
/**
 * A class representing one or more translations of a string entered by a user.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class multilang_string implements JsonSerializable {

    /**
     * Private constructor, use {@see from_array()}.
     *
     * @param array $translations
     */
    private function __construct(
        public readonly array $translations = []
    ) {
    }

    /**
     * Create a new multilang string from the given array or return null if it is empty or contains only whitespace.
     *
     * @param array $translations An array mapping language codes to translations. Any empty or whitespace-only translations will be
     *                            silently ignored. If that leaves nothing, null will be returned.
     * @return static|null
     */
    public static function from_array(array $translations): ?static {
        foreach ($translations as $lang => $translation) {
            if ($translation === '' || ctype_space($translation)) {
                unset($translations[$lang]);
            }
        }
        if (!$translations) {
            return null;
        }
        return new static($translations);
    }

    /**
     * Deserializes an instance from the given JSON string.
     *
     * @throws coding_exception If the string is not valid JSON or does not represent a valid multilang string.
     */
    public static function deserialize(string $string): static {
        try {
            $deserialized = json_decode($string, depth: 2, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new coding_exception('Could not deserialize multi-lang string.', $e->getMessage());
        }

        if (!is_object($deserialized)) {
            throw new coding_exception('Multi-lang string is not a JSON object.');
        }

        foreach ($deserialized as $translation) {
            if (!is_string($translation)) {
                throw new coding_exception('Multi-lang string translation is not a string.');
            }
        }

        return new static((array) $deserialized);
    }

    /**
     * Checks if the given string can be deserialized as a multilang string.
     *
     * @param mixed $string
     * @return bool
     */
    public static function is_valid(mixed $string): bool {
        if (!is_string($string)) {
            return false;
        }

        try {
            self::deserialize($string);
            return true;
        } catch (moodle_exception) {
            return false;
        }
    }

    #[\Override]
    public function jsonSerialize(): object {
        return (object) $this->translations;
    }

    /**
     * Serializes this instance to a JSON string.
     *
     * @return string
     * @throws coding_exception
     */
    public function serialize(): string {
        try {
            return json_encode($this, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new coding_exception('Could not serialize multi-lang string.', $e->getMessage());
        }
    }

    /**
     * Translates this multilang string into the current language.
     *
     * If the current language is not available, languages will be tried in this order:
     * 1. The parent languages of the current language.
     * 2. The site-wide default language.
     * 3. English.
     * 4. The first translation we have.
     *
     * @return string
     */
    public function translate(): string {
        foreach ($this->iterate_preferred_languages() as $lang) {
            if (isset($this->translations[$lang])) {
                return $this->translations[$lang];
            }
        }

        // Fall back on the first translation we have.
        return $this->translations[array_key_first($this->translations)];
    }

    /**
     * Returns a generator that yields the preferred languages in order of preference.
     *
     * @return Generator<string>
     */
    private function iterate_preferred_languages(): Generator {
        // First, we try the current language.
        $lang = current_language();
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
}
