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

use moodle_exception;
use moodle_url;
use moodleform;

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
class surveypart_edit_form extends moodleform {

    /**
     * Constructor.
     * @param moodle_url $action
     * @param int|null $organizationid
     * @param bool $editable
     */
    public function __construct(
        moodle_url $action,
        /** @var int|null */
        private readonly ?int $organizationid,
        bool $editable
    ) {
        parent::__construct($action, editable: $editable);
    }

    /**
     * Defines forms elements
     * @throws moodle_exception
     */
    public function definition(): void {
        global $CFG;
        $mform = $this->_form;

        if ($this->organizationid) {
            $mform->addElement('hidden', 'organizationid');
            $mform->setType('organizationid', PARAM_INT);
            $mform->setConstant('organizationid', $this->organizationid);
        }

        $mform->addElement('text', 'name', get_string('name', 'block_coursefeedback'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', 'client');

        $stringman = get_string_manager();
        $installed_langs = $stringman->get_list_of_translations();

        $mform->addElement(
            'autocomplete',
            'languages',
            get_string('survey_languages', 'block_coursefeedback'),
            $installed_langs,
            [ 'multiple' => true ]
        );
        $mform->addRule('languages', get_string('survey_no_languages', 'block_coursefeedback'), 'required', 'client');
        // Default to the user's language and the site language.
        $mform->setDefault('languages', array_unique([current_language(), $CFG->lang]));

        $site = get_site();
        $sitename = format_string($site->shortname);
        $mform->addHelpButton('languages', 'survey_languages', 'block_coursefeedback', a: ['sitename' => $sitename]);

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        if (empty($data['languages']) || !is_array($data['languages'])) {
            // The validation of 'required' rules doesn't seem to work properly on autocomplete elements, so we check it manually.
            return ['languages' => get_string('survey_no_languages', 'block_coursefeedback')];
        }

        return [];
    }
}
