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
class edit_organization_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name', 'block_coursefeedback'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('autocomplete', 'userids', get_string('selectusers', 'tool_cohortroles'), [], [
            'ajax' => 'core_user/form_user_selector',
            'multiple' => true,
            'valuehtmlcallback' => function ($value) {
                global $DB, $OUTPUT;
                $user = $DB->get_record('user', ['id' => (int)$value]);
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
        $allcats = \core_course_category::make_categories_list();

        $options = [
            'multiple'          => true,
            'noselectionstring' => false, // Kein “Alle”-Eintrag.
            'minchars'          => 0, // Direkt filtern, auch ohne tippen.
        ];

        $mform->addElement(
            'autocomplete',
            'coursecatids',
            get_string('coursecategories', 'block_coursefeedback'),
            $allcats,
            $options
        );
        $mform->setType('coursecatids', PARAM_SEQUENCE);

        $mform->addElement('advcheckbox', 'always_show_default_sp', get_string('always_show_default_sp', 'block_coursefeedback'));
        $mform->addHelpButton('always_show_default_sp', 'always_show_default_sp', 'block_coursefeedback');
        $mform->setType('always_show_default_sp', PARAM_BOOL);

        $mform->addElement(
            'static',
            'questionnaire_restriction_note',
            null,
            get_string('questionnaire_restriction_note', 'block_coursefeedback')
        );

        $mform->addElement(
            'advcheckbox',
            'can_have_local_questionnaires',
            get_string('can_have_local_questionnaires', 'block_coursefeedback')
        );
        $mform->setType('can_have_local_questionnaires', PARAM_BOOL);
        $mform->setDefault('can_have_local_questionnaires', 0);

        $mform->addElement(
            'advcheckbox',
            'disallow_global_questionnaires',
            get_string('disallow_global_questionnaires', 'block_coursefeedback')
        );
        $mform->setType('disallow_global_questionnaires', PARAM_BOOL);
        $mform->hideIf('disallow_global_questionnaires', 'can_have_local_questionnaires');
        $mform->setDefault('disallow_global_questionnaires', 0);

        $this->add_action_buttons();
    }

    #[\Override]
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            // Ensure that disallow_global_questionnaires requires can_have_local_questionnaires.
            $data->disallow_global_questionnaires &= $data->can_have_local_questionnaires ?? false;
        }
        return $data;
    }
}
