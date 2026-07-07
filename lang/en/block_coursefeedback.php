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

/**
 * Plugin strings are defined here.
 *
 * @package     block_coursefeedback
 * @category    string
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 IT.Services, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['add_block'] = 'Add global block, which is shown in every course with a survey';
$string['add_eventtype'] = 'Add new event type';
$string['add_more_blanks'] = 'Add more blanks';
$string['add_new_event_to'] = 'Add a new event to course <q>{$a}</q>';
$string['add_new_slot_to'] = 'Add a new slot to event <q>{$a}</q>';
$string['add_surveyitem'] = 'Add questionnaire element';
$string['all_courses'] = 'All courses';
$string['already_answered'] = 'You already responded to this survey.';
$string['always_show_default_sp'] = 'Always show the default questionnaire';
$string['always_show_default_sp_help'] = 'If enabled, the organization\'s default questionnaire will be shown even if the course has explicitly configured teaching events. It will be the first questionnaire, at the beginning of the survey. If left unchecked, the default questionnaire will only be used when no teaching events are configured.';
$string['amount_options'] = 'Amount of options';
$string['answer_i'] = 'Answer #{$a}';
$string['answer_i_in_lang'] = 'Answer #{$a->i} in {$a->lang}';
$string['answers_section'] = 'Choices';
$string['answers_section_help'] = 'Configure the possible answers to this question here. The languages are configured in the questionnaire settings. All answers should be translated to all languages before the questionnaire is activated.';
$string['at_least_one_translation_required'] = 'At least one language is required.';
$string['can_teacher_edit_surveyperiod'] = 'Teachers can edit the survey period';
$string['can_teacher_edit_surveysettings'] = 'Teachers can edit the survey settings';
$string['cannot_manually_add'] = 'Questionnaire elements of type \'{$a}\' cannot be manually added.';
$string['center_option_text'] = 'Text for the center option';
$string['confirm_event_deletion'] = 'Are you sure you want to delete the teaching event <q><b>{$a}</b></q>?';
$string['confirm_slot_deletion'] = 'Are you sure you want to delete the slot <q><b>{$a}</b></q>?';
$string['course_event_slot_table_heading'] = 'Teaching Events and Response Slots';
$string['course_not_in_org'] = 'The course \'{$a->course_name}\' does not belong to organization \'{$a->org_name}\'.';
$string['course_settings'] = 'Survey settings';
$string['course_settings_explanation_events'] =
    'If, for instance, your course contains both a lecture and an exercise, you can add both here. ' .
    'The evaluation coordinators configure the questionnaires that are used for each event type, which are then shown to the user one after the other.';
$string['course_settings_explanation_general'] = 'Here, you may make the survey for your course more fine-grained by configuring teaching events and response slots.';
$string['course_settings_explanation_slot_users'] =
    'If there is only one slot in a teaching event, by default, the responses to a slot are visible to all teachers of the course. ' .
    'Instead, you can select particular users who may view them. If you do, other teachers will no longer be able to. ' .
    'If there is more than one slot, you have to specify users here. ' .
    'Evaluation coordinators can always see responses, regardless of this setting. ' .
    'Participants do not see the slot users when filling out the survey, only its name.';
$string['course_settings_explanation_slots'] =
    'Furthermore, you can define slots for each teaching event, in order to distinguish between exercise groups, assignment topics, or similar. ' .
    'Students then select a slot, but all slots use the same questionnaire. If there is only one slot (<q>-</q>, by default), there is no selection by the student.';
$string['course_settings_explanation_title'] = 'Concepts';
$string['course_settings_of'] = 'Survey settings: {$a->shortname}';
$string['course_survey_status'] = 'Current survey status';
$string['coursecategories'] = 'Course categories';
$string['coursefeedback:addinstance'] = 'Add instance of block_coursefeedback';
$string['coursefeedback:filloutsurvey'] = 'Allows user to answer course evaluations';
$string['coursefeedback:isevaluationteacher'] = 'Is a teacher for the course. Can, depending on the organization settings, edit survey settings and period.';
$string['coursefeedback:manageorganizations'] = 'Manage course feedback organizations';
$string['coursefeedback:managesurveysglobally'] = 'Manage global questionnaires';
$string['coursefeedback:viewcourseresults'] = 'Can see results if there is only one slot and no users set for that slot' .
$string['coursefeedback:viewcoursesettings'] = 'View the survey settings for a course.';
$string['coursetype_from_campus'] = 'Course type from campus';
$string['create_default'] = 'Create default survey';
$string['create_new_scale'] = '- Create new scale -';
$string['datetime_from_to'] = '{$a->from} to {$a->to}';
$string['default_event_type'] = 'Default for organization';
$string['default_survey_period'] = 'Default survey period';
$string['default_surveypart_for_courses_without_eventtype'] = 'Default questionnaire for courses with no event type';
$string['define_default_surveyparts'] = 'Define event types and default questionnaires';
$string['define_evaluation_period_before'] = 'Please define the evaluation period before defining any surveys';
$string['define_rub_eventtype_mapping'] = 'Map Campus course types to event types';
$string['delete_event'] = 'Delete this event';
$string['delete_slot'] = 'Delete this slot';
$string['delete_survey_responses'] = 'Delete responses';
$string['delete_survey_responses_confirm'] = 'Are you sure you want to delete responses from <b>{$a->num}</b> users to the survey in the course <q><b>{$a->coursename}</b></q>? This cannot be undone.';
$string['dropdown'] = 'Drop-down list';
$string['edit_default_survey_period'] = 'Edit default survey period';
$string['edit_event'] = 'Edit this event';
$string['edit_organization'] = 'Edit organization';
$string['edit_scale'] = 'Edit scale';
$string['edit_slot_name'] = 'Edit this response slot name';
$string['edit_surveyitem'] = 'Edit questionnaire element';
$string['edit_surveypart'] = 'Edit questionnaire';
$string['emoji_choice_1_of_3'] = 'good';
$string['emoji_choice_1_of_4'] = 'very good';
$string['emoji_choice_1_of_5'] = 'very good';
$string['emoji_choice_1_of_6'] = 'excellent';
$string['emoji_choice_2_of_3'] = 'neutral';
$string['emoji_choice_2_of_4'] = 'somewhat good';
$string['emoji_choice_2_of_5'] = 'somewhat good';
$string['emoji_choice_2_of_6'] = 'good';
$string['emoji_choice_3_of_3'] = 'bad';
$string['emoji_choice_3_of_4'] = 'somewhat bad';
$string['emoji_choice_3_of_5'] = 'neutral';
$string['emoji_choice_3_of_6'] = 'satisfactory';
$string['emoji_choice_4_of_4'] = 'very bad';
$string['emoji_choice_4_of_5'] = 'somewhat bad';
$string['emoji_choice_4_of_6'] = 'sufficient';
$string['emoji_choice_5_of_5'] = 'very bad';
$string['emoji_choice_5_of_6'] = 'poor';
$string['emoji_choice_6_of_6'] = 'insufficient';
$string['emoji_surveyitem'] = 'Emojis (😀 to 😠)';
$string['emoji_unknown_variant'] = 'The emoji question variant \'{$a}\' does not exist.';
$string['emoji_variant_1_through_3'] = '1 through 3 (😀😐☹️)';
$string['emoji_variant_1_through_4'] = '1 through 4 (😀😐😕😠)';
$string['emoji_variant_1_through_5'] = '1 through 5 (😀🙂😐😕😠)';
$string['emoji_variant_grades_1_through_6'] = 'Grades 1 through 6 (😀😊🙂😐😕😠)';
$string['emoji_variants_label'] = 'Variant';
$string['end'] = 'End';
$string['end_must_be_after_start'] = 'The end time must be after the start time.';
$string['end_must_be_within_10_years'] = 'The start and the end must be no longer than 10 years from now.';
$string['end_must_be_within_1_year'] = 'The survey duration cannot be longer than 1 year.';
$string['evaluation_period'] = 'Evaluation period';
$string['evaluation_settings'] = 'Evaluation settings';
$string['evaluation_will_run_in_period'] = 'The survey will run in the period of {$a}.';
$string['evaluationadministration'] = 'Evaluation administration';
$string['event_intro'] = 'Please rate the event <q><b>{$a}</b></q>.';
$string['event_intro_without_name'] = 'Please rate this course.';
$string['event_name'] = 'Event name';
$string['event_name_placeholder'] = 'My teaching event';
$string['event_type'] = 'Event type';
$string['eventtype'] = 'Event type';
$string['finish'] = 'Finish';
$string['finished'] = 'Finished';
$string['for_selected'] = 'For selected';
$string['forceshowscale'] = 'Show scale even if it is immediately preceded by a question with the same scale.';
$string['general_settings_and_permissions'] = 'General settings and permissions';
$string['has_no_answer'] = 'Should the item have an <abbr title="not applicable">n/a</abbr> option?';
$string['inconsistent_editor_formats'] = 'Please use the same format for all translations of this field.';
$string['infotext'] = 'Infotext';
$string['invalid_answer'] = 'The answer value \'{$a}\' is invalid and will be discarded.';
$string['last_slot_deletion_disabled'] = 'An event must have at least one slot, so the last one can\'t be deleted.';
$string['list_of_courses_without_evaluation'] = 'List of courses without evaluation';
$string['list_of_evaluations'] = 'List of evaluations';
$string['local_moses_no_current_semester'] = 'The Moses API did not return a currently active semester.';
$string['local_moses_not_installed'] = 'The local_moses plugin is not installed.';
$string['local_moses_unsupported'] = 'Version {$a->installed} of the local_moses plugin is not supported. Please upgrade at least to version {$a->min}.';
$string['max_option_text'] = 'Text for max. pole';
$string['mean'] = 'Mean';
$string['median'] = 'Median';
$string['message_content'] = 'Content';
$string['message_for_teachers_when_survey_created'] = 'Message for teachers when surveys are created';
$string['message_subject'] = 'Subject';
$string['messageprovider:teacher_survey_created'] = 'Teacher notifications when surveys are created';
$string['min_option_text'] = 'Text for min. pole';
$string['multiplechoice'] = 'Multiple choice';
$string['must_be_between'] = 'Please enter a number between {$a->min} and {$a->max}.';
$string['name'] = 'Name';
$string['new_organization'] = 'New organization';
$string['new_scale'] = 'New scale';
$string['new_surveyitem'] = 'New questionnaire element';
$string['new_surveypart'] = 'New questionnaire';
$string['no_answer_option_text'] = 'Text for the <abbr title="not applicable">n/a</abbr> option';
$string['no_answers'] = 'No answers';
$string['no_default_survey_period_set'] = 'No default evaluation period set. Please define an evaluation period before creating surveys.';
$string['no_edit_survey_period'] = 'You are not allowed to edit the survey period.';
$string['no_evaluation_permissions'] = 'You do not have permission to manage survey settings or questionnaires in any organisation. Please contact your evaluation coordinators or the support.';
$string['no_save_when_switch_role'] = 'You may not respond to a survey while switching roles.';
$string['no_scale_selected'] = 'Please select a scale';
$string['no_survey_execution'] = 'The course {$a->fullname} is not part of any past, current or future survey.';
$string['not_enough_answers'] = 'The report cannot be shown, because there are not enough answers.';
$string['not_on_allowed_list'] = 'You are not on the allowed list of users to see this report.';
$string['not_translated'] = 'Not translated';
$string['num_responses'] = 'Number of responses';
$string['number_of_answers'] = 'Number of answers';
$string['ongoing'] = 'Ongoing';
$string['option_amount'] = 'Amount of options';
$string['organization'] = 'Organization';
$string['organizations'] = 'Organizations';
$string['pagebreak'] = 'Page break';
$string['planned'] = 'Planned';
$string['please_select'] = 'Please select...';
$string['pluginname'] = 'Course feedback';
$string['question_in_lang'] = 'Question ({$a})';
$string['questionnaire'] = 'Questionnaire';
$string['questionnaires'] = 'Questionnaires';
$string['report'] = 'Report';
$string['report_for'] = 'Report for <q>{$a}</q>';
$string['reports'] = 'Reports';
$string['scale'] = 'Scale';
$string['scale_delete_in_use'] = 'Cannot delete scale \'{$a}\' that is in use by at least one scale question.';
$string['scalequestion'] = 'Scale question';
$string['scales'] = 'Scales';
$string['settings:course_organization_method'] = 'How should organization association be derived for courses?';
$string['settings:course_organization_method:coursecat'] = 'By course category';
$string['settings:course_organization_method:customfield'] = 'By course custom field';
$string['settings:course_semester_method'] = 'How should semester association be derived for courses?';
$string['settings:course_semester_method:customfield'] = 'By semester course custom field';
$string['settings:course_semester_method:match_all'] = 'Do not filter by semester';
$string['settings:course_semester_method:moses'] = 'By semester data provided by the local_moses plugin';
$string['settings:default_survey_creation_method'] = 'Default survey creation method';
$string['settings:default_survey_creation_method:create_empty'] = 'Create empty surveys';
$string['settings:default_survey_creation_method:rub'] = 'Use RUB Campus data';
$string['settings:general_settings'] = 'General settings';
$string['settings:report_min_responses_overall'] = 'Minimum number of responses to show report';
$string['settings:report_min_responses_per_item'] = 'Minimum number of responses to show item in report (except text question).';
$string['show_all_languages'] = 'Show all languages';
$string['singlechoice'] = 'Single choice';
$string['slot_choice_surveyitem'] = 'Slot Choice';
$string['slot_choice_text'] = 'Which slot did you visit?';
$string['slot_name'] = 'Slot name';
$string['slot_name_placeholder'] = 'My response slot';
$string['slot_users'] = 'Slot users';
$string['slot_users_help'] = 'You can add the users responsible for this slot here, so only they will be able to see its responses. If there are two or more slots, users have to be specified here. If there is only one slot, this setting is optional; if this is empty, all teachers may see the results. Students filling out the survey will only ever see the slot name.';
$string['slot_users_of'] = 'Users assigned to slot {$a}';
$string['slots'] = 'Response slots';
$string['slots_help'] = 'Slots can be used to group user responses, such as for different tutorials. If there is more than one slot, users must select one before the questionnaire is shown.';
$string['standard_deviation'] = 'Standard deviation';
$string['start'] = 'Start';
$string['survey_created_message_help'] = '<p>In the following texts, these <i>placeholders</i> can be used:</p>
<p>
    ##NAME## for the full name of the teacher<br>
    ##COURSE_NAME## for the course name<br>
    ##PERIOD## for the evaluation period<br>
    ##SETTINGS_URL## for the url where the teacher can edit the survey period and settings
</p>
<p>
If teachers are allowed to edit the survey period or settings, please include the link for them to do so.<br>
You can do that by using the link/anchor button in the editor, freely choosing a "Text to display" and entering ##SETTINGS_URL## in the "Enter a URL" field.
</p>
';
$string['survey_execution_endtime'] = 'End time of survey in this course';
$string['survey_execution_frozen_long'] = 'The survey in this course has already started or is about to start. ' .
    'You can no longer add and remove events or slots, or change event types. You may still rename events and slots and evaluation coordinators may still change the survey period.';
$string['survey_execution_frozen_short'] = 'The survey in this course has already started or is about to start. Structural changes can no longer be made.';
$string['survey_execution_period'] = 'Survey Execution Period';
$string['survey_execution_period_click_to_edit'] = 'Click to edit the survey execution period';
$string['survey_execution_period_default_is'] = 'The default survey period for the evaluation is {$a}.';
$string['survey_execution_period_is_default'] = 'This is the default survey period for the evaluation.';
$string['survey_execution_period_reset_to_default'] = 'Reset this course to match the default';
$string['survey_execution_starttime'] = 'Start time of survey in this course';
$string['survey_finished_thanks'] = 'Thank you for giving feedback!';
$string['survey_has_ended'] = 'The survey period for this course has already ended.';
$string['survey_languages'] = 'Languages';
$string['survey_languages_help'] = 'Select the languages in which the questionnaire should be available. When adding and editing questions, there will be inputs for these languages. Only languages installed in {$a->sitename} will be available here.';
$string['survey_no_languages'] = 'Please select at least one language.';
$string['survey_responses_deleted'] = 'Survey responses deleted';
$string['surveyitem'] = 'Questionnaire element';
$string['surveyitems'] = 'Questionnaire elements';
$string['surveypart_frozen'] = 'This questionnaire is used in ongoing and/or past surveys and cannot be modified.';
$string['surveypart_preview'] = 'Preview';
$string['surveypart_preview_error'] = 'The questionnaire could not be displayed due to the following error: {$a}';
$string['task:start_surveys'] = 'Start surveys and apply defaults from organization';
$string['teaching_event'] = 'Teaching event';
$string['teaching_events'] = 'Teaching events';
$string['text'] = 'Text';
$string['text_autoresize'] = 'Expand the text area to fit the entered text.';
$string['text_in_lang'] = 'Text ({$a})';
$string['text_initialrows'] = 'Initial rows';
$string['text_initialrows_help'] = 'How many lines of text should the text area initially fit? Respondents can resize the text field arbitrarily.';
$string['text_maxlength'] = 'Maximum number of characters';
$string['this_course_belongs_to'] = 'This course will be evaluated within the <q>{$a}</q>.';
$string['tools'] = 'Tools';
$string['uses'] = 'Uses';
$string['view_scales'] = 'View scales';
