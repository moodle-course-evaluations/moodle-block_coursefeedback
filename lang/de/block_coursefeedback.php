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
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 IT.Services, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['add_block'] = 'Globalen Block hinzufügen, der in jedem Kurs mit einer Umfrage angezeigt wird.';
$string['add_eventtype'] = 'Veranstaltungsart hinzufügen';
$string['add_more_blanks'] = 'Mehr Leerfelder';
$string['add_new_event_to'] = 'Lehrveranstaltung im Kurs <q>{$a}</q> hinzufügen';
$string['add_new_slot_to'] = 'Untergruppe in Lehrveranstaltung <q>{$a}</q> hinzufügen';
$string['add_surveyitem'] = 'Element hinzufügen';
$string['all_courses'] = 'Alle Kurse';
$string['already_answered'] = 'Du hast diese Umfrage bereits beantwortet.';
$string['always_show_default_sp'] = 'Immer den Standardfragebogen anzeigen';
$string['always_show_default_sp_help'] = 'Wenn aktiviert, wird der Standardfragebogen einer Organisation auch dann angezeigt, wenn in den Evaluationseinstellungen des Kurses Lehrveranstaltungen hinterlegt sind. Der Standardfragebogen wird dann vor den LV-Fragebögen abgefragt. Bleibt diese Option deaktiviert, wird der Standardfragebogen nur verwendet, wenn keine Lehrveranstaltungen im Kurs hinterlegt sind.';
$string['amount_options'] = 'Anzahl von Antwortmöglichkeiten';
$string['answer_i'] = 'Antwort #{$a}';
$string['answer_i_in_lang'] = 'Antwort #{$a->i} in {$a->lang}';
$string['answers_section'] = 'Antwortmöglichkeiten';
$string['answers_section_help'] = 'Stellen Sie hier die möglichen Antworten auf diese Frage ein. Die unterstützten Sprachen werden in den Fragebogen-Einstellungen verwaltet. Alle Antworten sollten in alle unterstützten Sprachen übersetzt werden, bevor der Fragebogen verwendet wird.';
$string['array_converter_cannot_convert'] = 'Kann {$a->actualtype} nicht zu \'{$a->typehint}\' konvertieren.';
$string['at_least_one_translation_required'] = 'Geben Sie bitte mindestens eine Sprache an.';
$string['backup_from_restored_at'] = 'Sicherung vom {$a->backup_time}, eingespielt am {$a->restore_time}';
$string['can_teacher_edit_surveyperiod'] = 'Trainer/innen können den Evaluationszeitraum ihrer Kurse anpassen';
$string['can_teacher_edit_surveysettings'] = 'Trainer/innen können Lehrveranstaltungen und Untergruppen ihrer Kurse anpassen.';
$string['cannot_manually_add'] = 'Fragebogen-Elemente des Typs \'{$a}\' können nicht manuell hinzugefügt werden.';
$string['center_option_text'] = 'Beschriftung der mittleren Option';
$string['confirm_event_deletion'] = 'Möchten Sie wirklich die Lehrveranstaltung <q><b>{$a}</b></q> aus der Evaluation löschen?';
$string['confirm_slot_deletion'] = 'Möchten Sie wirklich die Untergruppe <q><b>{$a}</b></q> aus der Evaluation löschen?';
$string['course_event_slot_table_heading'] = 'Lehrveranstaltungen und Untergruppen';
$string['course_not_in_org'] = 'Der Kurs \'{$a->course_name}\' gehört nicht zu Organisationseinheit \'{$a->org_name}\'.';
$string['course_settings'] = 'Evaluationseinstellungen';
$string['course_settings_explanation_events'] =
    'Enthält Ihr Kurs beispielsweise eine Vorlesung und eine Übung, können Sie beide hier hinterlegen. ' .
    'Anhand der von Ihnen angegebenen Veranstaltungsart werden durch die Evaluationsverantwortlichen verschiedene Fragebögen zugewiesen, die dann nacheinander abgefragt werden.';
$string['course_settings_explanation_general'] = 'Hier können Sie optional die Lehrevaluation für ihren Kurs granularer gestalten, mithilfe von Lehrveranstaltungen und Untergruppen.';
$string['course_settings_explanation_slot_users'] =
    'Wenn es nur eine Untergruppe in einer Lehrveranstaltung gibt, sind die Ergebnisse standardmäßig durch alle Trainer/innen einsehbar. ' .
    'Stattdessen können Nutzer/innen hinterlegt werden, die die Ergebnisse sehen sollen, dann können andere Trainer/innen die Ergebnisse nicht mehr sehen. ' .
    'Wenn es es mehr als eine Untergruppe gibt, so müssen Nutzer/innen hinterlegt werden. ' .
    'Die Evaluationsverantwortlichen können die Antworten unabhängig davon immer sehen. ' .
    'Teilnehmer/innen sehen beim Ausfüllen der Umfrage zur Auswahl der Untergrupe nur den spezifierten Namen der Untergruppe, nicht die Namen der Untergruppen-Lehrenden.';
$string['course_settings_explanation_slots'] =
    'Außerdem können Sie innerhalb einer Lehrveranstaltung nach Untergruppen (wie z.&nbsp;B. Tutoriumsterminen oder Arbeitsthemen) aufschlüsseln, um diese getrennt evaluieren zu können. ' .
    'Dann wählen Teilnehmer/innen genau eine Gruppe aus, alle Gruppen teilen aber denselben Fragebogen. Gibt es nur eine Untergruppe (standardmäßig <q>-</q>), ist keine Auswahl durch die Teilnehmer/innen nötig.';
$string['course_settings_explanation_title'] = 'Erläuterungen';
$string['course_settings_of'] = 'Evaluationseinstellungen: {$a->shortname}';
$string['course_survey_status'] = 'Aktueller Status';
$string['coursecategories'] = 'Kursbereiche';
$string['coursefeedback:addinstance'] = 'Eine Instanz von block_coursefeedback hinzufügen';
$string['coursefeedback:filloutsurvey'] = 'Lehrevaluationen ausfüllen';
$string['coursefeedback:isevaluationteacher'] = 'Im Rahmen der Evaluation als Lehrende/r behandelt werden. Kann, je nach Organisations-Einstellungen, Kurs-Evaluationseinstellungen und -Evaluationszeitraum anpassen.';
$string['coursefeedback:manageorganizations'] = 'Lehrevaluations-Organisationseinheiten verwalten';
$string['coursefeedback:managesurveysglobally'] = 'Systemweite Fragebögen verwalten';
$string['coursefeedback:viewcourseresults'] = 'Evaluationsergebnisse sehen, wenn diese nicht in den Einstellungen auf bestimmte Nutzer/innen eingeschränkt sind';
$string['coursefeedback:viewcoursesettings'] = 'Die Kurs-Evaluationseinstellungen einsehen';
$string['coursetype_from_campus'] = 'Lehrveranstaltungsart aus RUB-Campus-Daten';
$string['create_default'] = 'Mit Standardeinstellungen zur Evaluation hinzufügen';
$string['create_new_scale'] = '- Neue Skala -';
$string['datetime_from_to'] = 'Von {$a->from} bis {$a->to}';
$string['default_event_type'] = 'Organisations-Standard';
$string['default_survey_period'] = 'Standard-Evaluationszeitraum';
$string['default_surveypart_for_courses_without_eventtype'] = 'Standard-Fragebogen für Kurse ohne konfigurierte Veranstaltungen';
$string['define_default_surveyparts'] = 'Veranstaltungsarten und Standard-Fragebogen einstellen';
$string['define_evaluation_period_before'] = 'Bitte setzen Sie zuerst den Evaluationszeitraum, bevor Sie Kurse hinzufügen';
$string['define_rub_eventtype_mapping'] = 'Verknüpfung von RUB-Campus-Veranstaltungsarten und Veranstaltungsarten für die Evaluation';
$string['delete_event'] = 'Lehrveranstaltung entfernen';
$string['delete_slot'] = 'Untergruppe entfernen';
$string['delete_survey_responses'] = 'Antworten löschen';
$string['delete_survey_responses_confirm'] = 'Möchten Sie wirklich alle Antworten von <b>{$a->num}</b> Nutzer/innen auf die Evaluation im Kurs <q><b>{$a->coursename}</b></q> löschen? Dies kann nicht rückgängig gemacht werden.';
$string['dropdown'] = 'Dropdown-Liste';
$string['edit_default_survey_period'] = 'Standard-Evaluationszeitraum anpassen';
$string['edit_event'] = 'Lehrveranstaltung bearbeiten';
$string['edit_organization'] = 'Organisation bearbeiten';
$string['edit_scale'] = 'Skala bearbeiten';
$string['edit_slot_name'] = 'Untergruppe-Namen bearbeiten';
$string['edit_surveyitem'] = 'Fragebogen-Element bearbeiten';
$string['edit_surveypart'] = 'Fragebogen bearbeiten';
$string['emoji_choice_1_of_3'] = 'gut';
$string['emoji_choice_1_of_4'] = 'sehr gut';
$string['emoji_choice_1_of_5'] = 'sehr gut';
$string['emoji_choice_1_of_6'] = 'sehr gut';
$string['emoji_choice_2_of_3'] = 'neutral';
$string['emoji_choice_2_of_4'] = 'eher gut';
$string['emoji_choice_2_of_5'] = 'eher gut';
$string['emoji_choice_2_of_6'] = 'gut';
$string['emoji_choice_3_of_3'] = 'schlecht';
$string['emoji_choice_3_of_4'] = 'eher schlecht';
$string['emoji_choice_3_of_5'] = 'neutral';
$string['emoji_choice_3_of_6'] = 'befriedigend';
$string['emoji_choice_4_of_4'] = 'schlecht';
$string['emoji_choice_4_of_5'] = 'eher schlecht';
$string['emoji_choice_4_of_6'] = 'ausreichend';
$string['emoji_choice_5_of_5'] = 'schlecht';
$string['emoji_choice_5_of_6'] = 'mangelhaft';
$string['emoji_choice_6_of_6'] = 'ungenügend';
$string['emoji_surveyitem'] = 'Emojis (😀 bis 😠)';
$string['emoji_unknown_variant'] = 'Die Emoji-Fragen-Variante \'{$a}\' existiert nicht.';
$string['emoji_variant_1_through_3'] = '1 bis 3 (😀😐☹️)';
$string['emoji_variant_1_through_4'] = '1 bis 4 (😀😐😕😠)';
$string['emoji_variant_1_through_5'] = '1 bis 5 (😀🙂😐😕😠)';
$string['emoji_variant_grades_1_through_6'] = 'Schulnoten 1 bis 6 (😀😊🙂😐😕😠)';
$string['emoji_variants_label'] = 'Variante';
$string['end'] = 'Ende';
$string['end_must_be_after_start'] = 'Das Ende muss nach dem Start liegen.';
$string['end_must_be_within_10_years'] = 'Start und Ende dürfen nicht mehr als 10 Jahre von heute entfernt sein.';
$string['end_must_be_within_1_year'] = 'Der Evaluationszeitraum darf nicht länger als 1 Jahr sein.';
$string['evaluation_period'] = 'Evaluationszeitraum';
$string['evaluation_settings'] = 'Evaluationseinstellungen';
$string['evaluation_will_run_in_period'] = 'Die Umfrage wird im Zeitraum des {$a} laufen.';
$string['evaluationadministration'] = 'Evaluationsverwaltung';
$string['event_intro'] = 'Bewerten Sie bitte die Lehrveranstaltung <q><b>{$a}</b></q>.';
$string['event_intro_without_name'] = 'Bitte bewerten Sie diese Lehrveranstaltung.';
$string['event_name'] = 'Veranstaltungsname';
$string['event_name_placeholder'] = 'Meine Lehrveranstaltung';
$string['event_type'] = 'Veranstaltungsart';
$string['eventtype'] = 'Veranstaltungsart';
$string['finish'] = 'Abschließen';
$string['finished'] = 'Beendet';
$string['for_selected'] = 'Für Ausgewählte';
$string['forceshowscale'] = 'Skala anzeigen, auch wenn eine vorhergehende Frage dieselbe Skala benutzt.';
$string['general_settings_and_permissions'] = 'Allgemeine Einstellungen und Berechtigungen';
$string['has_no_answer'] = '<abbr title="nicht zutreffend">n.z.</abbr>-Option anzeigen?';
$string['inconsistent_editor_formats'] = 'Bitte verwenden Sie das gleiche Text-Format für alle Übersetzungen dieses Feldes.';
$string['infotext'] = 'Statischer Info-Text';
$string['invalid_answer'] = 'Der Antwort-Wert \'{$a}\' ist ungültig und wird verworfen.';
$string['invalid_surveypart_backup'] = 'Die Fragebogen-Datei ist fehlerhaft.';
$string['last_slot_deletion_disabled'] = 'Eine Lehrveranstaltung muss min. einen Untergruppe haben, also kann diese nicht gelöscht werden.';
$string['list_of_courses_without_evaluation'] = 'Liste von Kursen ohne Evaluation';
$string['list_of_evaluations'] = 'Liste von Kursen mit Evaluation';
$string['local_moses_no_current_semester'] = 'Die Moses-API liefert kein aktuell laufendes Semester zurück.';
$string['local_moses_not_installed'] = 'Das Plugin local_moses ist nicht installiert.';
$string['local_moses_unsupported'] = 'Die installierte Version {$a->installed} des local_moses-Plugins wird nicht unterstützt. Installieren Sie mindestens die Version {$a->min}.';
$string['max_option_text'] = 'Beschriftung das max. Pols';
$string['mean'] = 'Durchschnitt';
$string['median'] = 'Median';
$string['message_content'] = 'Inhalt';
$string['message_for_teachers_when_survey_created'] = 'Nachricht an Lehrende, wenn Umfragen erstellt werden';
$string['message_subject'] = 'Betreff';
$string['messageprovider:teacher_survey_created'] = 'Nachricht an Lehrende, wenn Umfragen erstellt werden';
$string['min_option_text'] = 'Beschriftung das min. Pols';
$string['multiplechoice'] = 'Multiple Choice';
$string['must_be_between'] = 'Bitte geben Sie eine Zahl zwischen {$a->min} und {$a->max} an.';
$string['name'] = 'Name';
$string['new_organization'] = 'Neue Organisation';
$string['new_scale'] = 'Neue Skala';
$string['new_surveyitem'] = 'Neues Fragebogen-Element';
$string['new_surveypart'] = 'Neuer Fragebogen';
$string['no_answer_option_text'] = 'Text für die <abbr title="nicht zutreffend">n.z.</abbr>-Option';
$string['no_answers'] = 'Keine Antworten';
$string['no_default_survey_period_set'] = 'Der Evaluationszeitraum ist noch nicht gesetzt. Er muss gesetzt werden, bevor Kurse zur Evaluation hinzugefügt werden können.';
$string['no_edit_survey_period'] = 'Sie sind nicht berechtigt, den Evaluationszeitraum zu ändern.';
$string['no_evaluation_permissions'] = 'Sie sind in keiner Organisationseinheit berechtigt, Evaluationen oder Fragebögen zu verwalten. Bitte wenden Sie sich an die Evaluationskoordinator/innen oder den Support.';
$string['no_save_when_switch_role'] = 'Sie dürfen keine Evaluation beantworten, während Sie Ihre Rolle gewechselt haben.';
$string['no_scale_selected'] = 'Bitten wählen Sie eine Skala';
$string['no_survey_execution'] = 'Der Kurs {$a->fullname} ist nicht Teil einer vergangenen, laufenden oder zukünftigen Evaluation.';
$string['not_enough_answers'] = 'Der Bericht kann nicht angezeigt werden, da es nicht genug Antworten gibt.';
$string['not_on_allowed_list'] = 'Sie sind nicht auf der Liste der Nutzer/innen, die berechtigt sind, den Bericht zu sehen.';
$string['not_translated'] = 'nicht übersetzt';
$string['num_responses'] = 'Anzahl von Antworten';
$string['number_of_answers'] = 'Anzahl der Antworten';
$string['ongoing'] = 'Laufend';
$string['option_amount'] = 'Anzahl von Antwortmöglichkeiten';
$string['organization'] = 'Organisation';
$string['organizations'] = 'Organisationen';
$string['pagebreak'] = 'Seitenumbruch';
$string['planned'] = 'Geplant';
$string['please_select'] = 'Bitte wählen...';
$string['pluginname'] = 'Evaluation';
$string['question_in_lang'] = 'Frage ({$a})';
$string['questionnaire'] = 'Fragebogen';
$string['questionnaires'] = 'Fragebögen';
$string['report'] = 'Ergebnisbericht';
$string['report_for'] = 'Ergebnisbericht für <q>{$a}</q>';
$string['reports'] = 'Ergebnisberichte';
$string['scale'] = 'Skala';
$string['scale_delete_in_use'] = 'Kann Skala \'{$a}\' nicht löschen, da sie in mindestens einer Skalenfrage verwendet wird.';
$string['scalequestion'] = 'Skalenfrage';
$string['scales'] = 'Skalen';
$string['settings:course_organization_method'] = 'Wie sollen Kurse einer Organisation zugeordnet werden?';
$string['settings:course_organization_method:coursecat'] = 'Nach Kursbereich';
$string['settings:course_organization_method:customfield'] = 'Nach einem benutzerdefinierten Feld';
$string['settings:course_semester_method'] = 'Wie sollen Kurse einem Semester zugeordnet werden?';
$string['settings:course_semester_method:customfield'] = 'Nach einem benutzerdefinierten Feld';
$string['settings:course_semester_method:match_all'] = 'Kurse werden nicht nach Semester gefiltert';
$string['settings:course_semester_method:moses'] = 'Nach Daten des local_moses-Plugins';
$string['settings:default_survey_creation_method'] = 'Umfrage-Erstellungsmethode';
$string['settings:default_survey_creation_method:create_empty'] = 'Einstellungen in neu hinzugefügten Kursen sind initial leer';
$string['settings:default_survey_creation_method:rub'] = 'RUB-Campus-Daten benutzen';
$string['settings:general_settings'] = 'Allgemeine Einstellungen';
$string['settings:report_min_responses_overall'] = 'Minimale Anzahl von Antworten, um Ergebnisbericht anzuzeigen.';
$string['settings:report_min_responses_per_item'] = 'Minimale Anzahl von Antworten, um Frage in Ergebnisbericht anzuzeigen (außer Freitextfragen).';
$string['show_all_languages'] = 'Alle Sprachen anzeigen';
$string['singlechoice'] = 'Single Choice';
$string['slot_choice_surveyitem'] = 'Untergruppen-Auswahl';
$string['slot_choice_text'] = 'Welche Gruppe haben Sie besucht?';
$string['slot_name'] = 'Untergruppen-Name';
$string['slot_name_placeholder'] = 'Meine Untergruppe';
$string['slot_users'] = 'Untergruppen-Lehrende';
$string['slot_users_help'] = 'Hier können Nutzer/innen (z.B. Tutor/innen) angegeben werden, sodass nur diese die Ergebnisse der Untergruppe sehen. Bei mehr als zwei Untergruppen müssen hier Nutzer/innen angegeben werden. Bei nur einer Untergruppe, ist diese Einstellung optional; werden keine Nutzer/innen hinterlegt, können alle Trainer/innen die Ergebnisse sehen. Teilnehmer/innen sehen beim Ausfüllen der Umfrage nur den Namen der Untergruppe.';
$string['slot_users_of'] = 'Der Untergruppe {$a} zugewiesene Lehrende';
$string['slots'] = 'Untergruppen';
$string['slots_help'] = 'Untergruppen können verwendet werden, um beispielsweise Tutorien verschiedener Gruppenleitenden getrennt zu evaluieren. Wenn es mehrere gibt, muss beim Beantworten des Fragebogens eine ausgewählt werden.';
$string['standard_deviation'] = 'Standardabweichung';
$string['start'] = 'Start';
$string['survey_created_message_help'] = '<p>In den folgenden Texten können diese <i>Platzhalter</i> benutzt werden:</p>
<p>
    ##NAME## für den ganzen Namen des/der Lehrenden<br>
    ##COURSE_NAME## für den Kursnamen<br>
    ##PERIOD## für den Evaluationszeitraum<br>
    ##SETTINGS_URL## für die URL, wo die Lehrenden den Evaluationszeitraum oder die Evaluationseinstellungen anpassen können.
</p>

<p>
Falls Lehrende den Evaluationszeitraum oder die Evaluationseinstellungen bearbeiten dürfen, inkludieren Sie bitte den entsprechenden Link.<br>
Dies können Sie tun, indem Sie im Editor auf den Link-Button klicken, frei einen "Text zum Anzeigen" wählen, und in "URL eingeben" ##SETTINGS_URL## angeben.
</p>
';
$string['survey_execution_endtime'] = 'Endzeitpunkt der Evaluation in diesem Kurs';
$string['survey_execution_frozen_long'] = 'Die Evaluation hat in diesem Kurs bereits begonnen oder steht kurz bevor. ' .
    'Es können keine Veranstaltungen oder Untergruppen mehr angelegt oder entfernt, oder Veranstaltungsarten angepasst werden. ' .
    'Veranstaltungen und Untergruppen können lediglich umbenannt werden, und nur Evaluationsbetreuer/innen können noch den Evaluationszeitraum anpassen.';
$string['survey_execution_frozen_short'] = 'Die Evaluation hat in diesem Kurs bereits begonnen oder steht kurz bevor. Es können keine strukturellen Änderungen mehr gemacht werden.';
$string['survey_execution_period'] = 'Evaluationszeitraum';
$string['survey_execution_period_click_to_edit'] = 'Klicken, um den Evaluationszeitraum anzupassen';
$string['survey_execution_period_default_is'] = 'Der Standard-Evaluationszeitraum ist {$a}.';
$string['survey_execution_period_is_default'] = 'Das ist der Standard-Evaluationszeitraum.';
$string['survey_execution_period_reset_to_default'] = 'Diesen Kurs darauf zurücksetzen';
$string['survey_execution_starttime'] = 'Startzeitpunkt der Evaluation in diesem Kurs';
$string['survey_finished_thanks'] = 'Vielen Dank für Ihr Feedback!';
$string['survey_has_ended'] = 'Der Evaluationszeitraum in diesem Kurs hat bereits geendet.';
$string['survey_languages'] = 'Unterstützte Sprachen';
$string['survey_languages_help'] = 'Wählen Sie die Sprachen aus, in denen der Fragebogen verfügbar sein soll. Beim Hinzufügen und Bearbeiten von Elementen können Übersetzungen in diese Sprachen angegeben werden. Nur Sprachen, die in {$a->sitename} installiert sind, können hier ausgewählt werden.';
$string['survey_no_languages'] = 'Geben Sie mindestens eine Sprache an.';
$string['survey_responses_deleted'] = 'Evaluations-Antworten gelöscht';
$string['surveyitem'] = 'Fragebogen-Element';
$string['surveyitems'] = 'Fragebogen-Elemente';
$string['surveypart_frozen'] = 'Dieser Fragebogen wird in laufenden oder vergangenen Evaluationen verwendet und kann daher nicht bearbeitet werden.';
$string['surveypart_preview'] = 'Vorschau';
$string['surveypart_preview_error'] = 'Der Fragebogen konnte Aufgrund folgenden Fehlers nicht angezeigt werden: {$a}';
$string['task:start_surveys'] = 'Evaluationen starten und Organisations-Standards anwenden';
$string['teaching_event'] = 'Lehrveranstaltung';
$string['teaching_events'] = 'Lehrveranstaltungen';
$string['text'] = 'Freitext';
$string['text_autoresize'] = 'Das Eingabefeld wächst mit dem eingegebenen Text.';
$string['text_in_lang'] = 'Text ({$a})';
$string['text_initialrows'] = 'Initiale Zeilen';
$string['text_initialrows_help'] = 'Für wie viele Zeilen Text soll das Textfeld initial Platz haben? Befragte können das Textfeld beliebig vergrößern und verkleinern.';
$string['text_maxlength'] = 'Maximale Zeichenanzahl';
$string['this_course_belongs_to'] = 'Dieser Kurs wird in der Organisationseinheit <q>{$a}</q> evaluiert.';
$string['tools'] = 'Tools';
$string['uses'] = 'Verwendungen';
$string['view_scales'] = 'Skalen ansehen';
