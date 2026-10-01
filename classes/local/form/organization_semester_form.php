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

namespace block_coursefeedback\local\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form organization settings, editable by organization users.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization_semester_form extends moodleform {

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'semester_settings_header', 'Semester Settings');
        $mform->setExpanded('semester_settings_header');

        $mform->addElement('advcheckbox', 'always_show_default_sp', get_string('always_show_default_sp', 'block_coursefeedback'));
        $mform->addHelpButton('always_show_default_sp', 'always_show_default_sp', 'block_coursefeedback');
        $mform->setType('always_show_default_sp', PARAM_BOOL);

        $mform->addElement(
            'checkbox',
            'can_teacher_edit_speriod',
            get_string('teacher_permissions', 'block_coursefeedback'),
            get_string('can_teacher_edit_surveyperiod', 'block_coursefeedback'),
        );
        $mform->setType('can_teacher_edit_speriod', PARAM_BOOL);

        $mform->addElement(
            'checkbox',
            'can_teacher_edit_ssettings',
            get_string('can_teacher_edit_surveysettings', 'block_coursefeedback'),
        );
        $mform->setType('can_teacher_edit_ssettings', PARAM_BOOL);

        $mform->addElement('header', 'default_survey_period_header', get_string('default_survey_period', 'block_coursefeedback'));
        $mform->setExpanded('default_survey_period_header');

        $mform->addElement('date_time_selector', 'evaluation_starttime', get_string('start', 'block_coursefeedback'));
        $mform->setType('evaluation_starttime', PARAM_INT);
        $mform->addRule('evaluation_starttime', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_time_selector', 'evaluation_endtime', get_string('end', 'block_coursefeedback'));
        $mform->setType('evaluation_endtime', PARAM_INT);
        $mform->addRule('evaluation_endtime', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'header',
            'survey_created_message_header',
            get_string('message_for_teachers_when_survey_created', 'block_coursefeedback')
        );

        $mform->addElement(
            'static',
            'survey_created_message_help',
            '',
            get_string('survey_created_message_help', 'block_coursefeedback')
        );

        $mform->addElement(
            'text',
            'survey_created_message_subject',
            get_string('message_subject', 'block_coursefeedback'),
            ['size' => 100]
        );
        $mform->setType('survey_created_message_subject', PARAM_TEXT);

        $mform->addElement(
            'editor',
            'survey_created_message_body',
            get_string('message_content', 'block_coursefeedback'),
            '',
            ['changeformat' => 0],
        );
        $mform->setType('survey_created_message_body', PARAM_RAW);

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ($data['evaluation_starttime'] > $data['evaluation_endtime']) {
            $errors['evaluation_endtime'] = get_string('end_must_be_after_start', 'block_coursefeedback');
        }
        return $errors;
    }

    #[\Override]
    public function set_data($default_values): void {
        // TODO: WTF?
        $default_values = (array) $default_values;
        if ($default_values && ($default_values['survey_created_message_body'] ?? null)) {
            $default_values['survey_created_message_body'] = [
                'text' => $default_values['survey_created_message_body'],
                'format' => FORMAT_HTML,
            ];
        }
        parent::set_data($default_values);
    }

    #[\Override]
    public function get_data(): ?object {
        $data = parent::get_data();
        if ($data) {
            $data->can_teacher_edit_speriod ??= false;
            $data->can_teacher_edit_ssettings ??= false;
            $data->survey_created_message_body = $data->survey_created_message_body['text'];
        }

        return $data;
    }
}
