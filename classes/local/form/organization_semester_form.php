<?php

namespace block_coursefeedback\local\form;

use block_coursefeedback\local\course_semester_mapping\evaluation_semester;
use block_coursefeedback\local\persistent\organization;
use block_coursefeedback\local\persistent\organization_semester;
use moodle_url;
use moodleform;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class organization_semester_form extends moodleform {

    public function __construct(
        moodle_url $action,
        private readonly organization $organization,
        private readonly evaluation_semester $semester
    ) {
        parent::__construct($action);
    }

    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        $options = [];
        foreach (organization_semester::get_records(['organizationid' => $this->organization->get('id')]) as $orgsem) {
            $options[$orgsem->get('id')] = $orgsem->get('semestername');
        }

        $mform->addGroup([
            $mform->createElement('select', 'base_orgsem', null, $options),
            $mform->createElement('submit', 'fill_on_from_base_orgsem', 'Fill', null, false),
        ], groupLabel: "Fill in settings from another semester?", appendName: false);
        $mform->registerNoSubmitButton('fill_on_from_base_orgsem');

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
}