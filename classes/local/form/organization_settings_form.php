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

namespace block_coursefeedback\local\form;

use core\output\notification;
use core_course_category;
use moodle_url;
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
class organization_settings_form extends moodleform {

    /**
     * Constructor.
     *
     * @param moodle_url $action
     * @param bool $privileged
     */
    public function __construct(
        moodle_url $action,
        /** @var bool */
        private readonly bool $privileged
    ) {
        parent::__construct($action);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'admin_settings_header', get_string('general'));
        $mform->setExpanded('admin_settings_header');

        $mform->addElement('text', 'name', get_string('name', 'block_coursefeedback'));
        $mform->setType('name', PARAM_TEXT);
        if ($this->privileged) {
            // If the name is frozen, it mustn't be required, since frozen elements don't send anything.
            $mform->addRule('name', get_string('required'), 'required', null, 'client');
        }

        $mform->addElement('autocomplete', 'userids', get_string('selectusers', 'tool_cohortroles'), [], [
            'ajax' => 'core_user/form_user_selector',
            'multiple' => true,
            'valuehtmlcallback' => function ($value) {
                global $DB, $OUTPUT;
                $user = $DB->get_record('user', ['id' => (int) $value]);
                if (!$user || !user_can_view_profile($user)) {
                    return false;
                }
                $details = user_get_user_details($user);
                $details['extrafields'] = [
                    [
                        'name' => 'email',
                        'value' => $user->email,
                    ],
                ];
                return $OUTPUT->render_from_template(
                    'core_user/form_user_selector_suggestion',
                    $details
                );
            },
        ]);

        // TODO durch ajax ersetzen damit nicht die ganze Liste ans frontend geschickt werden muss.
        $allcats = core_course_category::make_categories_list();
        $mform->addElement(
            'autocomplete',
            'coursecatids',
            get_string('coursecategories', 'block_coursefeedback'),
            $allcats,
            [
                'multiple' => true,
                'minchars' => 0, // Direkt filtern, auch ohne tippen.
            ]
        );
        $mform->setType('coursecatids', PARAM_SEQUENCE);

        $mform->addElement(
            'advcheckbox',
            'has_local_questionnaires',
            get_string('questionnaire_scope', 'block_coursefeedback'),
            get_string('has_local_questionnaires', 'block_coursefeedback')
        );
        $mform->setType('has_local_questionnaires', PARAM_BOOL);
        $mform->setDefault('has_local_questionnaires', 0);
        $mform->addHelpButton('has_local_questionnaires', 'questionnaire_scope', 'block_coursefeedback');

        $mform->addElement(
            'advcheckbox',
            'no_global_questionnaires',
            get_string('no_global_questionnaires', 'block_coursefeedback')
        );
        $mform->setType('no_global_questionnaires', PARAM_BOOL);
        $mform->hideIf('no_global_questionnaires', 'has_local_questionnaires');
        $mform->setDefault('no_global_questionnaires', 0);

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

        $mform->addElement('date_time_selector', 'default_evaluation_starttime', get_string('start', 'block_coursefeedback'));
        $mform->setType('default_evaluation_starttime', PARAM_INT);
        $mform->addRule('default_evaluation_starttime', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_time_selector', 'default_evaluation_endtime', get_string('end', 'block_coursefeedback'));
        $mform->setType('default_evaluation_endtime', PARAM_INT);
        $mform->addRule('default_evaluation_endtime', get_string('required'), 'required', null, 'client');

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

        if (!$this->privileged) {
            $mform->freeze(['name', 'userids', 'coursecatids', 'has_local_questionnaires', 'no_global_questionnaires']);
        }
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ($data['default_evaluation_starttime'] > $data['default_evaluation_endtime']) {
            $errors['default_evaluation_endtime'] = get_string('end_must_be_after_start', 'block_coursefeedback');
        }
        return $errors;
    }

    #[\Override]
    public function set_data($default_values): void {
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

            if (!($data->has_local_questionnaires ??= false)) {
                // Force global questionnaires to be available when local questionnaires are turned off.
                $data->no_global_questionnaires = false;
            }

            if (!$this->privileged) {
                // Prevent changes to these fields.
                unset(
                    $data->name,
                    $data->userids,
                    $data->coursecatids,
                    $data->has_local_questionnaires,
                    $data->no_global_questionnaires
                );
            }
        }

        return $data;
    }

    #[\Override]
    public function display(): void {
        global $OUTPUT;
        if (!$this->privileged) {
            echo $OUTPUT->render(new notification(
                get_string('only_editable_by_admins', 'block_coursefeedback'),
                notification::NOTIFY_INFO,
                closebutton: false
            ));
        }
        parent::display();
    }
}
