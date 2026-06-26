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
namespace block_coursefeedback\local\surveyitem\text;

use block_coursefeedback\local\surveyitem\surveyitem_form;

/**
 * Abstract surveyitem class, to be extended by all survey elements.
 *
 * @package     block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_form extends surveyitem_form {

    #[\Override]
    protected function definition(): void {
        parent::definition();
        $mform = $this->_form;

        $mform->addElement('text', 'initialrows', get_string('text_initialrows', 'block_coursefeedback'));
        $mform->setType('initialrows', PARAM_INT);
        $mform->setDefault('initialrows', 3);
        $mform->addRule('initialrows', null, 'required', null, 'client');
        $mform->addHelpButton('initialrows', 'text_initialrows', 'block_coursefeedback');

        $mform->addElement('advcheckbox', 'autoresize', get_string('text_autoresize', 'block_coursefeedback'));
        $mform->setType('autoresize', PARAM_BOOL);
        $mform->setDefault('autoresize', true);

        $mform->addElement('text', 'maxlength', get_string('text_maxlength', 'block_coursefeedback'));
        $mform->setType('maxlength', PARAM_INT);
        $mform->setDefault('maxlength', 500);
        $mform->addRule('maxlength', null, 'required', null, 'client');
    }
}
