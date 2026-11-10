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
 * Form for editing surveypart metadata.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_coursefeedback\local\form;

use block_coursefeedback\local\lang_utils;
use block_coursefeedback\local\persistent\surveypart;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing surveypart metadata.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_scale_form extends \moodleform {

    /**
     * Defines forms elements
     * @throws \coding_exception
     */
    public function definition() {
        $mform = $this->_form;
        /** @var surveypart $surveypart */
        $surveypart = $this->_customdata['surveypart'];

        $mform->addElement('hidden', 'surveypartid');
        $mform->setConstant('surveypartid', $surveypart->get('id'));
        $mform->setType('surveypartid', PARAM_INT);

        if ($this->_customdata['id'] ?? null) {
            $mform->addElement('hidden', 'id');
            $mform->setConstant('id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);
        }

        $mform->addElement('text', 'name', get_string('name', 'block_coursefeedback'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'optionamount', get_string('option_amount', 'block_coursefeedback'));
        $mform->setType('optionamount', PARAM_INT);
        $mform->addRule('optionamount', get_string('required'), 'required', null, 'client');

        $langs = lang_utils::get_language_labels($surveypart->get_languages());

        $mform->addElement(new multilang_header_element('text_header', $langs));
        $mform->addElement(new multilang_input_element(
            'minoptiontext',
            get_string('min_option_text', 'block_coursefeedback'),
            $langs,
        ))->require_at_least_one_translation();

        $mform->addElement(new multilang_input_element(
            'centeroptiontext',
            get_string('center_option_text', 'block_coursefeedback'),
            $langs,
        ));
        $even_numbers = [];
        for ($i = 0; $i <= 32; $i += 2) {
            $even_numbers[] = $i;
        }
        $mform->hideIf('centeroptiontext', 'optionamount', 'in', $even_numbers);

        $mform->addElement(new multilang_input_element(
            'maxoptiontext',
            get_string('max_option_text', 'block_coursefeedback'),
            $langs,
        ))->require_at_least_one_translation();

        $mform->addElement('checkbox', 'hasnoansweroption', get_string('has_no_answer', 'block_coursefeedback'));

        // TODO: Require noansweroptiontext if hasnoansweroption.
        $mform->addElement(new multilang_input_element(
            'noansweroptiontext',
            get_string('no_answer_option_text', 'block_coursefeedback'),
            $langs,
        ));

        $mform->disabledIf('noansweroptiontext', 'hasnoansweroption', 'notchecked');

        $this->add_action_buttons();
    }

    #[\Override]
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->hasnoansweroption ??= false;
        }
        return $data;
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (
            isset($data['optionamount']) &&
            (!is_numeric($data['optionamount']) || $data['optionamount'] < 2 || $data['optionamount'] > 32)
        ) {
            $errors['optionamount'] = get_string('must_be_between', 'block_coursefeedback', ['min' => 2, 'max' => 32]);
        }

        return $errors;
    }
}
