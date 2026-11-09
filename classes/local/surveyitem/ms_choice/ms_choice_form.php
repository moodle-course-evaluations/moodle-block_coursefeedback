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

/**
 * Surveyitem manager.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\surveyitem\ms_choice;

use block_coursefeedback\local\form\multilang_header_element;
use block_coursefeedback\local\form\multilang_input_element;
use block_coursefeedback\local\lang_utils;
use block_coursefeedback\local\surveyitem\surveyitem_form;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ms_choice_form extends surveyitem_form {

    /**
     * Definition for the form.
     * @throws \coding_exception
     */
    #[\Override]
    protected function definition(): void {
        parent::definition();

        $mform =& $this->_form;

        $mform->addElement('hidden', 'choices_amount');
        $mform->setType('choices_amount', PARAM_INT);
        $mform->setDefault('choices_amount', 3);

        $mform->addElement('header', 'answers_section', get_string('answers_section', 'block_coursefeedback'));
        $mform->setExpanded('answers_section', ignoreuserstate: true);
        $mform->addHelpButton('answers_section', 'answers_section', 'block_coursefeedback');

        // Ideally, we'd hide the columns according to the current_language radio, but that doesn't seem possible for the headers
        // since they are raw html elements.
        $mform->addElement(new multilang_header_element('answers_column_headers', $this->surveypart->get_languages()));

        $mform->addElement('submit', 'add_blanks', get_string('add_more_blanks', 'block_coursefeedback'));
        $mform->registerNoSubmitButton('add_blanks');
    }

    /**
     * Definition after data for the form.
     * @throws \coding_exception
     */
    #[\Override]
    public function definition_after_data(): void {
        $mform =& $this->_form;

        $choicesamountel = $mform->getElement('choices_amount');

        $data = $this->get_submitted_data();
        $choicesamount = intval($choicesamountel->getValue());

        // Always keep at least 3 answers / blanks.
        $choicesamount = max($choicesamount, 3);
        if (isset($data->add_blanks)) {
            // If the "add blanks" button was pressed, add 3 more blanks.
            $choicesamount += 3;
        }

        $mform->setConstant('choices_amount', $choicesamount);

        for ($i = 0; $i < $choicesamount; $i++) {
            $answer_name = "answers[$i]";

            $labels_by_langs = [];
            foreach ($this->surveypart->get_languages() as $language) {
                $labels_by_langs[$language] = get_string('answer_i_in_lang', 'block_coursefeedback', [
                    'i' => $i + 1,
                    'lang' => lang_utils::get_language_label($language),
                ]);
            }

            $multilang_input = new multilang_input_element(
                $answer_name,
                get_string('answer_i', 'block_coursefeedback', $i + 1),
                $labels_by_langs
            );
            $mform->insertElementBefore($multilang_input, 'add_blanks');

            // If we don't unset, future iterations will overwrite.
            unset($multilang_input);
        }
    }
}
