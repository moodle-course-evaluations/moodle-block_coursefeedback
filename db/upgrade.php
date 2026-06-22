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
 * This file keeps track of upgrades to block_coursefeedback.
 *
 * @package    block_coursefeedback
 * @copyright   2025 innoCampus, Technische Universität Berlin
 * @copyright   2025 IT.Services, Ruhr-Universität Bochum
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\course_organization_mapping\course_organization_mapping;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Upgrade script for the Course Feedback block.
 *
 * @param int $oldversion The version number currently installed.
 * @return bool True on success.
 * @throws moodle_exception If the installed version is too old or confirmation is missing.
 */
function xmldb_block_coursefeedback_upgrade(int $oldversion): bool {
    global $DB, $USER;
    $dbman = $DB->get_manager();

    // 1) Enforce minimum starting version.
    if ($oldversion < 2025_05_09_00) {
        throw new coding_exception(
            'This upgrade requires plugin version 2025050900 or higher. '
            . 'Please upgrade to version 2025050900 (git ref f29819d04929e78ff92e836b31784a9d553be82a) before proceeding.'
        );
    }

    // 2) Verify that the admin has accepted the major overhaul and possible data loss.
    if ($oldversion < 2025050901) {
        $confirmed = get_config('block_coursefeedback', 'confirmoverhaul');
        if (empty($confirmed)) {
            throw new coding_exception(
                'You must confirm the major overhaul and possible data loss '
                . 'in the Course Feedback block settings before continuing.'
            );
        }

        // Define table block_coursefeedback to be renamed to block_coursefeedback_old.
        $table = new xmldb_table('block_coursefeedback');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback.
            $dbman->rename_table($table, 'block_coursefeedback_old');
        }

        // Define table block_coursefeedback_questns to be renamed to block_coursefeedback_old_questns.
        $table = new xmldb_table('block_coursefeedback_questns');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_questns.
            $dbman->rename_table($table, 'block_coursefeedback_old_questns');
        }

        // Define table block_coursefeedback_answers to be renamed to block_coursefeedback_old_answers.
        $table = new xmldb_table('block_coursefeedback_answers');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_answers.
            $dbman->rename_table($table, 'block_coursefeedback_old_answers');
        }

        // Define table block_coursefeedback_textans to be renamed to block_coursefeedback_old_textans.
        $table = new xmldb_table('block_coursefeedback_textans');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_textans.
            $dbman->rename_table($table, 'block_coursefeedback_old_textans');
        }

        // Define table block_coursefeedback_uidansw to be renamed to block_coursefeedback_old_uidansw.
        $table = new xmldb_table('block_coursefeedback_uidansw');
        if ($dbman->table_exists($table)) {
            // Launch rename table for block_coursefeedback_uidansw.
            $dbman->rename_table($table, 'block_coursefeedback_old_uidansw');
        }

        upgrade_block_savepoint(true, 2025050901, 'coursefeedback');
    }
    if ($oldversion < 2025112400) {
        // Define table block_coursefeedback_course_eventtype to be created.
        $table = new xmldb_table('block_coursefeedback_course_eventtype');

        // Adding fields to table block_coursefeedback_course_eventtype.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('eventtypeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_course_eventtype.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('fk_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('fk_eventtypeid', XMLDB_KEY_FOREIGN, ['eventtypeid'], 'block_coursefeedback_eventtype', ['id']);
        $table->add_key('fk_teacherid', XMLDB_KEY_FOREIGN, ['teacherid'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_course_eventtype.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_eventtype to be created.
        $table = new xmldb_table('block_coursefeedback_eventtype');

        // Adding fields to table block_coursefeedback_eventtype.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_eventtype.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_eventtype.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_organization to be created.
        $table = new xmldb_table('block_coursefeedback_organization');

        // Adding fields to table block_coursefeedback_organization.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_organization.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_organization.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_organization_coursecat to be created.
        $table = new xmldb_table('block_coursefeedback_organization_coursecat');

        // Adding fields to table block_coursefeedback_organization_coursecat.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursecatid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_organization_coursecat.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fuk_coursecatid', XMLDB_KEY_FOREIGN_UNIQUE, ['coursecatid'], 'course_category', ['id']);
        $table->add_key('fk_organizationid', XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_organization_coursecat.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_organization_survey to be created.
        $table = new xmldb_table('block_coursefeedback_organization_survey');

        // Adding fields to table block_coursefeedback_organization_survey.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_organization_survey.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('surveyid_fk', XMLDB_KEY_FOREIGN, ['surveyid'], 'block_coursefeedback_survey', ['id']);
        $table->add_key('organizationid_fk', XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);
        $table->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_organization_survey.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_organization_user to be created.
        $table = new xmldb_table('block_coursefeedback_organization_user');

        // Adding fields to table block_coursefeedback_organization_user.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_organization_user.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('fk_organizationid', XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);

        // Conditionally launch create table for block_coursefeedback_organization_user.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_scale to be created.
        $table = new xmldb_table('block_coursefeedback_scale');

        // Adding fields to table block_coursefeedback_scale.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('optionamount', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('minoptiontextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('maxoptiontextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hasnoansweroption', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('noansweroptiontextid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_scale.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('fk_surveypartid', XMLDB_KEY_FOREIGN, ['surveypartid'], 'block_coursefeedback_surveypart', ['id']);

        // Conditionally launch create table for block_coursefeedback_scale.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_survey to be created.
        $table = new xmldb_table('block_coursefeedback_survey');

        // Adding fields to table block_coursefeedback_survey.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_survey.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_organizationid', XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_survey.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveyitem to be created.
        $table = new xmldb_table('block_coursefeedback_surveyitem');

        // Adding fields to table block_coursefeedback_surveyitem.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('surveyitemtype', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortindex', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('textid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveyitem.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('fk_textid', XMLDB_KEY_FOREIGN, ['textid'], 'block_coursefeedback_text', ['id']);
        $table->add_key('fk_surveypartid', XMLDB_KEY_FOREIGN, ['surveypartid'], 'block_coursefeedback_surveypart', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveyitem.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveyitemscalequestion to be created.
        $table = new xmldb_table('block_coursefeedback_surveyitemscalequestion');

        // Adding fields to table block_coursefeedback_surveyitemscalequestion.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scaleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('forceshowscale', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveyitemscalequestion.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('fk_scaleid', XMLDB_KEY_FOREIGN, ['scaleid'], 'block_coursefeedback_scale', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveyitemscalequestion.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveyitemansweroption to be created.
        $table = new xmldb_table('block_coursefeedback_surveyitemansweroption');

        // Adding fields to table block_coursefeedback_surveyitemansweroption.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('textid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortindex', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveyitemansweroption.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('fk_surveyitemid', XMLDB_KEY_FOREIGN, ['surveyitemid'], 'block_coursefeedback_surveyitem', ['id']);
        $table->add_key('fk_textid', XMLDB_KEY_FOREIGN, ['textid'], 'block_coursefeedback_text', ['id']);

        // Adding indexes to table block_coursefeedback_surveyitemansweroption.
        $table->add_index('ui_surveyitemid_sortindex', XMLDB_INDEX_NOTUNIQUE, ['surveyitemid', 'sortindex']);

        // Conditionally launch create table for block_coursefeedback_surveyitemansweroption.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveypart to be created.
        $table = new xmldb_table('block_coursefeedback_surveypart');

        // Adding fields to table block_coursefeedback_surveypart.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveypart.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveypart.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveypart_language to be created.
        $table = new xmldb_table('block_coursefeedback_surveypart_language');

        // Adding fields to table block_coursefeedback_surveypart_language.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('language', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, null);
        $table->add_field('isprimary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveypart_language.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveypart_language.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_text to be created.
        $table = new xmldb_table('block_coursefeedback_text');

        // Adding fields to table block_coursefeedback_text.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);

        // Adding keys to table block_coursefeedback_text.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_coursefeedback_text.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_texttranslation to be created.
        $table = new xmldb_table('block_coursefeedback_texttranslation');

        // Adding fields to table block_coursefeedback_texttranslation.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('textid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lang', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL, null, null);
        $table->add_field('text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('format', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_texttranslation.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('fk_textid', XMLDB_KEY_FOREIGN, ['textid'], 'block_coursefeedback_text', ['id']);

        // Conditionally launch create table for block_coursefeedback_texttranslation.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveyexecution to be created.
        $table = new xmldb_table('block_coursefeedback_surveyexecution');

        // Adding fields to table block_coursefeedback_surveyexecution.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveyexecution.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('frk_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveyexecution.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveypartexecution to be created.
        $table = new xmldb_table('block_coursefeedback_surveypartexecution');

        // Adding fields to table block_coursefeedback_surveypartexecution.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyexecutionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('surveypartid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveypartexecution.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key(
            'frk_surveyexecutionid',
            XMLDB_KEY_FOREIGN,
            ['surveyexecutionid'],
            'block_coursefeedback_surveyexecution',
            ['id']
        );
        $table->add_key(
            'frk_surveypartid',
            XMLDB_KEY_FOREIGN,
            ['surveypartid'],
            'block_coursefeedback_surveypart',
            ['id']
        );

        // Conditionally launch create table for block_coursefeedback_surveypartexecution.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveyexecution_user to be created.
        $table = new xmldb_table('block_coursefeedback_surveyexecution_user');

        // Adding fields to table block_coursefeedback_surveyexecution_user.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyexecutionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveyexecution_user.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveyexecution_user.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveypartexecutionoption to be created.
        $table = new xmldb_table('block_coursefeedback_surveypartexecutionoption');

        // Adding fields to table block_coursefeedback_surveypartexecutionoption.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartexecutionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('externalid', XMLDB_TYPE_CHAR, '32', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveypartexecutionoption.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key(
            'frk_surveypartexecutionid',
            XMLDB_KEY_FOREIGN,
            ['surveypartexecutionid'],
            'block_coursefeedback_surveypartexecution',
            ['id']
        );

        // Conditionally launch create table for block_coursefeedback_surveypartexecutionoption.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveypartexecutionoption_user to be created.
        $table = new xmldb_table('block_coursefeedback_surveypartexecutionoption_user');

        // Adding fields to table block_coursefeedback_surveypartexecutionoption_user.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartexecutionoptionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_surveypartexecutionoption_user.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key(
            'frk_surveypartexecutionoptionid',
            XMLDB_KEY_FOREIGN,
            ['surveypartexecutionoptionid'],
            'block_coursefeedback_surveypartexecutionoption',
            ['id']
        );
        $table->add_key('frk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveypartexecutionoption_user.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveypartexecutionoptionresp to be created.
        $table = new xmldb_table('block_coursefeedback_surveypartexecutionoptionresp');

        // Adding fields to table block_coursefeedback_surveypartexecutionoptionresp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartexecutionoptionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_coursefeedback_surveypartexecutionoptionresp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key(
            'frk_surveypartexecutionoptionid',
            XMLDB_KEY_FOREIGN,
            ['surveypartexecutionoptionid'],
            'block_coursefeedback_surveypartexecutionoption',
            ['id']
        );

        // Conditionally launch create table for block_coursefeedback_surveypartexecutionoptionresp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveyitemtextresponse to be created.
        $table = new xmldb_table('block_coursefeedback_surveyitemtextresponse');

        // Adding fields to table block_coursefeedback_surveyitemtextresponse.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartexecutionoptionresponseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('surveyitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_coursefeedback_surveyitemtextresponse.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key(
            'frk_surveypartexecutionoptionresponseid',
            XMLDB_KEY_FOREIGN,
            ['surveypartexecutionoptionresponseid'],
            'block_coursefeedback_surveypartexecutionoptionresp',
            ['id']
        );
        $table->add_key(
            'frk_surveyitemid',
            XMLDB_KEY_FOREIGN,
            ['surveyitemid'],
            'block_coursefeedback_surveyitem',
            ['id']
        );

        // Conditionally launch create table for block_coursefeedback_surveyitemtextresponse.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_coursefeedback_surveyitemintresponse to be created.
        $table = new xmldb_table('block_coursefeedback_surveyitemintresponse');

        // Adding fields to table block_coursefeedback_surveyitemintresponse.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveypartexecutionoptionresponseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('surveyitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_coursefeedback_surveyitemintresponse.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key(
            'frk_surveypartexecutionoptionresponseid',
            XMLDB_KEY_FOREIGN,
            ['surveypartexecutionoptionresponseid'],
            'block_coursefeedback_surveypartexecutionoptionresp',
            ['id']
        );
        $table->add_key(
            'frk_surveyitemid',
            XMLDB_KEY_FOREIGN,
            ['surveyitemid'],
            'block_coursefeedback_surveyitem',
            ['id']
        );

        // Conditionally launch create table for block_coursefeedback_surveyitemintresponse.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2025112400, 'coursefeedback');
    }

    if ($oldversion < 2026020900) {
        // Define field isprimary to be dropped from block_coursefeedback_surveypart_language.
        $table = new xmldb_table('block_coursefeedback_surveypart_language');
        $field = new xmldb_field('isprimary');

        // Conditionally launch drop field isprimary.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define key fk_textid (foreign) to be dropped form block_coursefeedback_surveyitemansweroption.
        $table = new xmldb_table('block_coursefeedback_surveyitemansweroption');
        $key = new xmldb_key('fk_textid', XMLDB_KEY_FOREIGN, ['textid'], 'block_coursefeedback_text', ['id']);

        // Launch drop key fk_textid.
        $dbman->drop_key($table, $key);

        // Define field textid to be dropped from block_coursefeedback_surveyitemansweroption.
        $field = new xmldb_field('textid');

        // Conditionally launch drop field textid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field text to be added to block_coursefeedback_surveyitemansweroption.
        $field = new xmldb_field('text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'timemodified');

        // Conditionally launch add field text.
        if (!$dbman->field_exists($table, $field)) {
            block_coursefeedback_add_nonnull_field_with_default($dbman, $table, $field, '{"en": "Migration placeholder"}');
        }

        // Define key fk_textid (foreign) to be dropped form block_coursefeedback_surveyitem.
        $table = new xmldb_table('block_coursefeedback_surveyitem');
        $key = new xmldb_key('fk_textid', XMLDB_KEY_FOREIGN, ['textid'], 'block_coursefeedback_text', ['id']);

        // Launch drop key fk_textid.
        $dbman->drop_key($table, $key);

        // Define field textid to be dropped from block_coursefeedback_surveyitem.
        $field = new xmldb_field('textid');

        // Conditionally launch drop field textid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field text to be added to block_coursefeedback_surveyitem.
        $field = new xmldb_field('text', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');

        // Conditionally launch add field text.
        if (!$dbman->field_exists($table, $field)) {
            block_coursefeedback_add_nonnull_field_with_default($dbman, $table, $field, '{"en": "Migration placeholder"}');
        }

        // Define field textformat to be added to block_coursefeedback_surveyitem.
        $field = new xmldb_field('textformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1, 'text');

        // Conditionally launch add field textformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table block_coursefeedback_texttranslation to be dropped.
        $table = new xmldb_table('block_coursefeedback_texttranslation');

        // Conditionally launch drop table for block_coursefeedback_texttranslation.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table block_coursefeedback_text to be dropped.
        $table = new xmldb_table('block_coursefeedback_text');

        // Conditionally launch drop table for block_coursefeedback_text.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Changing nullability of field text on table block_coursefeedback_surveyitem to nullable.
        $table = new xmldb_table('block_coursefeedback_surveyitem');
        $field = new xmldb_field('text', XMLDB_TYPE_TEXT, null, null, false, null, null, 'timemodified');

        // Launch change of nullability for field text.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field textformat on table block_coursefeedback_surveyitem to not null.
        $field = new xmldb_field('textformat', XMLDB_TYPE_INTEGER, '2', null, false, null, null, 'text');

        // Launch change of nullability for field textformat.
        $dbman->change_field_notnull($table, $field);

        // Define field minoptiontextid to be dropped from block_coursefeedback_scale.
        $table = new xmldb_table('block_coursefeedback_scale');
        $field = new xmldb_field('minoptiontextid');

        // Conditionally launch drop field minoptiontextid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field maxoptiontextid to be dropped from block_coursefeedback_scale.
        $field = new xmldb_field('maxoptiontextid');

        // Conditionally launch drop field maxoptiontextid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field noansweroptiontextid to be dropped from block_coursefeedback_scale.
        $field = new xmldb_field('noansweroptiontextid');

        // Conditionally launch drop field noansweroptiontextid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field minoptiontext to be added to block_coursefeedback_scale.
        $field = new xmldb_field('minoptiontext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');

        // Conditionally launch add field minoptiontext.
        if (!$dbman->field_exists($table, $field)) {
            block_coursefeedback_add_nonnull_field_with_default($dbman, $table, $field, '{"en": "Migration placeholder"}');
        }

        // Define field maxoptiontext to be added to block_coursefeedback_scale.
        $field = new xmldb_field('maxoptiontext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'minoptiontext');

        // Conditionally launch add field maxoptiontext.
        if (!$dbman->field_exists($table, $field)) {
            block_coursefeedback_add_nonnull_field_with_default($dbman, $table, $field, '{"en": "Migration placeholder"}');
        }

        // Define field noansweroptiontext to be added to block_coursefeedback_scale.
        $field = new xmldb_field('noansweroptiontext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'maxoptiontext');

        // Conditionally launch add field noansweroptiontext.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Set it on all answers that should have it.
            $DB->set_field(
                $table->getName(),
                'noansweroptiontext',
                '{"en": "Migration placeholder"}',
                ['hasnoansweroption' => true]
            );
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026020900, 'coursefeedback');
    }

    if ($oldversion < 2026022700) {
        // Define table block_coursefeedback_surveyitememojis to be created.
        $table = new xmldb_table('block_coursefeedback_surveyitememojis');

        // Adding fields to table block_coursefeedback_surveyitememojis.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('variant', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_coursefeedback_surveyitememojis.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_surveyitemid', XMLDB_KEY_FOREIGN, ['surveyitemid'], 'block_coursefeedback_surveyitem', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveyitememojis.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026022700, 'coursefeedback');
    }

    if ($oldversion < 2026032200) {
        // Define field organizationid to be added to block_coursefeedback_eventtype.
        $table = new xmldb_table('block_coursefeedback_eventtype');
        $field = new xmldb_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'active');

        // Conditionally launch add field organizationid.
        if (!$dbman->field_exists($table, $field)) {
            if ($DB->record_exists('block_coursefeedback_eventtype', [])) {
                $fallback_org_id = block_coursefeedback_create_fallback_org('Orphaned event types');
                block_coursefeedback_add_nonnull_field_with_default($dbman, $table, $field, $fallback_org_id);
            } else {
                $dbman->add_field($table, $field);
            }
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026032200, 'coursefeedback');
    }

    if ($oldversion < 2026033101) {
        // Define field organizationid to be added to block_coursefeedback_surveypart.
        $table = new xmldb_table('block_coursefeedback_surveypart');
        $field = new xmldb_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'name');

        // Conditionally launch add field organizationid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key fk_organizationid (foreign) to be added to block_coursefeedback_surveypart.
        $table = new xmldb_table('block_coursefeedback_surveypart');
        $key = new xmldb_key(
            'fk_organizationid',
            XMLDB_KEY_FOREIGN,
            ['organizationid'],
            'block_coursefeedback_organization',
            ['id']
        );

        // Launch add key fk_organizationid.
        $dbman->add_key($table, $key);

        // Define field surveypartid to be added to block_coursefeedback_eventtype.
        $table = new xmldb_table('block_coursefeedback_eventtype');
        $field = new xmldb_field('surveypartid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'organizationid');

        // Conditionally launch add field surveypartid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key fk_surveypartid (foreign) to be added to block_coursefeedback_eventtype.
        $table = new xmldb_table('block_coursefeedback_eventtype');
        $key = new xmldb_key('fk_surveypartid', XMLDB_KEY_FOREIGN, ['surveypartid'], 'block_coursefeedback_surveypart', ['id']);

        // Launch add key fk_surveypartid.
        $dbman->add_key($table, $key);

        // Define key fk_organizationid (foreign) to be added to block_coursefeedback_eventtype.
        $table = new xmldb_table('block_coursefeedback_eventtype');
        $key = new xmldb_key(
            'fk_organizationid',
            XMLDB_KEY_FOREIGN,
            ['organizationid'],
            'block_coursefeedback_organization',
            ['id']
        );

        // Launch add key fk_organizationid.
        $dbman->add_key($table, $key);

        // Define field default_surveypartid to be added to block_coursefeedback_organization.
        $table = new xmldb_table('block_coursefeedback_organization');
        $field = new xmldb_field('default_surveypartid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'name');

        // Conditionally launch add field default_surveypartid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key fk_default_surveypartid (foreign) to be added to block_coursefeedback_organization.
        $table = new xmldb_table('block_coursefeedback_organization');
        $key = new xmldb_key(
            'fk_default_surveypartid',
            XMLDB_KEY_FOREIGN,
            ['default_surveypartid'],
            'block_coursefeedback_surveypart',
            ['id']
        );

        // Launch add key fk_default_surveypartid.
        $dbman->add_key($table, $key);

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026033101, 'coursefeedback');
    }

    if ($oldversion < 2026041600) {
        // Define field eventid to be added to block_coursefeedback_surveypartexecution.
        $table = new xmldb_table('block_coursefeedback_surveypartexecution');
        $field = new xmldb_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timemodified');

        // Conditionally launch add field eventid.
        if (!$dbman->field_exists($table, $field)) {
            $field->setNotNull(false);
            $dbman->add_field($table, $field);

            // Associate existing events with SPEs and create new events where those aren't enough.
            $eventids_by_courses = [];
            foreach ($DB->get_records('block_coursefeedback_course_eventtype', fields: 'id, courseid') as $record) {
                $eventids_by_courses[$record->courseid][] = $record->id;
            }

            $spes = $DB->get_records_sql('
                SELECT spe.id, se.courseid
                FROM {block_coursefeedback_surveypartexecution} spe
                INNER JOIN {block_coursefeedback_surveyexecution} se ON se.id = spe.surveyexecutionid
            ');
            foreach ($spes as $spe) {
                $event_id = array_shift($eventids_by_courses[$spe->courseid]);
                if (!$event_id) {
                    $event_id = $DB->insert_record('block_coursefeedback_course_eventtype', [
                        'courseid' => $spe->courseid,
                        'eventtypeid' => null,
                        'teacherid' => 0,
                        'name' => '',
                        'usermodified' => $USER->id,
                        'timecreated' => time(),
                        'timemodified' => time(),
                    ]);
                }

                $DB->update_record('block_coursefeedback_surveypartexecution', [
                    'id' => $spe->id,
                    'eventid' => $event_id,
                ]);
            }

            // Now that every SPE should have an eventid, make it non-null.
            $field->setNotNull(XMLDB_NOTNULL);
            $dbman->change_field_notnull($table, $field);
        }

        // Define key fk_eventid (foreign) to be added to block_coursefeedback_surveypartexecution.
        $key = new xmldb_key('fk_eventid', XMLDB_KEY_FOREIGN, ['eventid'], 'block_coursefeedback_course_eventtype', ['id']);

        // Launch add key fk_eventid.
        $dbman->add_key($table, $key);

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026041600, 'coursefeedback');
    }

    if ($oldversion < 2026042600) {
        // Define field default_evaluation_starttime to be added to block_coursefeedback_organization.
        $table = new xmldb_table('block_coursefeedback_organization');
        $field = new xmldb_field(
            'default_evaluation_starttime',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'default_surveypartid'
        );

        // Conditionally launch add field default_evaluation_starttime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field default_evaluation_endtime to be added to block_coursefeedback_organization.
        $table = new xmldb_table('block_coursefeedback_organization');
        $field = new xmldb_field(
            'default_evaluation_endtime',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'default_evaluation_starttime'
        );

        // Conditionally launch add field default_evaluation_endtime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026042600, 'coursefeedback');
    }

    if ($oldversion < 2026042800) {
        // Define table block_coursefeedback_rub_eventtype_mapping to be created.
        $table = new xmldb_table('block_coursefeedback_rub_eventtype_mapping');

        // Adding fields to table block_coursefeedback_rub_eventtype_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rub_coursetype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('eventtypeid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_rub_eventtype_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_organizationid', XMLDB_KEY_FOREIGN, ['organizationid'], 'block_coursefeedback_organization', ['id']);
        $table->add_key('fk_eventtypeid', XMLDB_KEY_FOREIGN, ['eventtypeid'], 'block_coursefeedback_eventtype', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('u_organizationid_coursetype', XMLDB_KEY_UNIQUE, ['organizationid', 'rub_coursetype']);

        // Conditionally launch create table for block_coursefeedback_rub_eventtype_mapping.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026042800, 'coursefeedback');
    }

    if ($oldversion < 2026042802) {
        // Changing nullability of field starttime on table block_coursefeedback_surveyexecution to null.
        $table = new xmldb_table('block_coursefeedback_surveyexecution');
        $field = new xmldb_field('starttime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');

        // Launch change of nullability for field starttime.
        $dbman->change_field_notnull($table, $field);

        // Changing nullability of field endtime on table block_coursefeedback_surveyexecution to null.
        $table = new xmldb_table('block_coursefeedback_surveyexecution');
        $field = new xmldb_field('endtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'starttime');

        // Launch change of nullability for field endtime.
        $dbman->change_field_notnull($table, $field);

        // Define key frk_surveypartid (foreign) to be dropped form block_coursefeedback_surveypartexecution.
        $table = new xmldb_table('block_coursefeedback_surveypartexecution');
        $key = new xmldb_key('frk_surveypartid', XMLDB_KEY_FOREIGN, ['surveypartid'], 'block_coursefeedback_surveypart', ['id']);

        // Launch drop key frk_surveypartid.
        $dbman->drop_key($table, $key);

        // Changing nullability of field surveypartid on table block_coursefeedback_surveypartexecution to null.
        $table = new xmldb_table('block_coursefeedback_surveypartexecution');
        $field = new xmldb_field('surveypartid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'surveyexecutionid');

        // Launch change of nullability for field surveypartid.
        $dbman->change_field_notnull($table, $field);

        // Define key frk_surveypartid (foreign) to be added to block_coursefeedback_surveypartexecution.
        $table = new xmldb_table('block_coursefeedback_surveypartexecution');
        $key = new xmldb_key('frk_surveypartid', XMLDB_KEY_FOREIGN, ['surveypartid'], 'block_coursefeedback_surveypart', ['id']);

        // Launch add key frk_surveypartid.
        $dbman->add_key($table, $key);

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026042802, 'coursefeedback');
    }

    if ($oldversion < 2026050200) {
        // Define field organizationid to be added to block_coursefeedback_surveyexecution.
        $table = new xmldb_table('block_coursefeedback_surveyexecution');
        $field = new xmldb_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');

        // Conditionally launch add field organizationid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            try {
                $mapping = course_organization_mapping::get_instance();
            } catch (moodle_exception) {
                // The setting might not have been set yet.
                $mapping = null;
            }

            $fallback_org_id = null;

            // Set organization for survey_executions.
            foreach ($DB->get_records($table->getName()) as $record) {
                $org_id = $mapping?->get_organization_for_course($record->courseid)?->get('id');
                if (!$org_id) {
                    // The course isn't part of an organization (anymore).
                    // Assign the SE to an automatically created one.
                    $org_id = $fallback_org_id ??= block_coursefeedback_create_fallback_org('Orphaned survey executions');
                    mtrace("Assigning orphaned survey execution '$record->id' to organization '$org_id'.");
                }
                $record->organizationid = $org_id;
                $DB->update_record($table->getName(), $record);
            }

            $field->setNotNull();

            $dbman->change_field_notnull($table, $field);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026050200, 'coursefeedback');
    }

    if ($oldversion < 2026050300) {
        // Define key fk_eventtypeid (foreign) to be dropped form block_coursefeedback_course_eventtype.
        $table = new xmldb_table('block_coursefeedback_course_eventtype');
        $key = new xmldb_key('fk_eventtypeid', XMLDB_KEY_FOREIGN, ['eventtypeid'], 'block_coursefeedback_eventtype', ['id']);

        // Launch drop key fk_eventtypeid.
        $dbman->drop_key($table, $key);

        // Changing nullability of field eventtypeid on table block_coursefeedback_course_eventtype to null.
        $field = new xmldb_field('eventtypeid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');
        // Launch change of nullability for field eventtypeid.
        $dbman->change_field_notnull($table, $field);

        // Launch add key fk_eventtypeid.
        $dbman->add_key($table, $key);

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026050300, 'coursefeedback');
    }

    if ($oldversion < 2026050401) {
        // Define field can_teacher_edit_speriod to be added to block_coursefeedback_organization.
        $table = new xmldb_table('block_coursefeedback_organization');
        $field = new xmldb_field(
            'can_teacher_edit_speriod',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '1',
            'default_evaluation_endtime'
        );

        // Conditionally launch add field can_teacher_edit_speriod.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field can_teacher_edit_ssettings to be added to block_coursefeedback_organization.
        $table = new xmldb_table('block_coursefeedback_organization');
        $field = new xmldb_field(
            'can_teacher_edit_ssettings',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '1',
            'can_teacher_edit_speriod'
        );

        // Conditionally launch add field can_teacher_edit_ssetting.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026050401, 'coursefeedback');
    }

    if ($oldversion < 2026050600) {
        // Define table block_coursefeedback_organization_texts to be created.
        $table = new xmldb_table('block_coursefeedback_organization_texts');

        // Adding fields to table block_coursefeedback_organization_texts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('organizationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('survey_created_message_subject', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('survey_created_message_body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_coursefeedback_organization_texts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key(
            'fu_organizationid',
            XMLDB_KEY_FOREIGN_UNIQUE,
            ['organizationid'],
            'block_coursefeedback_organization',
            ['id']
        );

        // Conditionally launch create table for block_coursefeedback_organization_texts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026050600, 'coursefeedback');
    }

    if ($oldversion < 2026060101) {
        // Define field sortindex to be added to block_coursefeedback_course_eventtype.
        $table = new xmldb_table('block_coursefeedback_course_eventtype');
        $field = new xmldb_field('sortindex', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'name');

        // Conditionally launch add field sortindex.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field always_show_default_sp to be added to block_coursefeedback_organization.
        $table = new xmldb_table('block_coursefeedback_organization');
        $field = new xmldb_field(
            'always_show_default_sp',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'can_teacher_edit_ssettings'
        );

        // Conditionally launch add field always_show_default_sp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026060101, 'coursefeedback');
    }

    if ($oldversion < 2026061700) {
        // Define field centeroptiontext to be added to block_coursefeedback_scale.
        $table = new xmldb_table('block_coursefeedback_scale');
        $field = new xmldb_field('centeroptiontext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'noansweroptiontext');

        // Conditionally launch add field centeroptiontext.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026061700, 'coursefeedback');
    }

    if ($oldversion < 2026061800) {
        // Define key usermodified (foreign) to be dropped from block_coursefeedback_surveyexecution_user.
        $table = new xmldb_table('block_coursefeedback_surveyexecution_user');
        $key = new xmldb_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Launch drop key usermodified.
        $dbman->drop_key($table, $key);

        // Define field usermodified to be dropped from block_coursefeedback_surveyexecution_user.
        $table = new xmldb_table('block_coursefeedback_surveyexecution_user');
        $field = new xmldb_field('usermodified');

        // Conditionally launch drop field usermodified.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field timecreated to be dropped from block_coursefeedback_surveyexecution_user.
        $table = new xmldb_table('block_coursefeedback_surveyexecution_user');
        $field = new xmldb_field('timecreated');

        // Conditionally launch drop field timecreated.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field timemodified to be dropped from block_coursefeedback_surveyexecution_user.
        $table = new xmldb_table('block_coursefeedback_surveyexecution_user');
        $field = new xmldb_field('timemodified');

        // Conditionally launch drop field timemodified.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define key userid (foreign) to be added to block_coursefeedback_surveyexecution_user.
        $table = new xmldb_table('block_coursefeedback_surveyexecution_user');
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Launch add key userid.
        $dbman->add_key($table, $key);

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026061800, 'coursefeedback');
    }

    if ($oldversion < 2026062200) {
        // Define table block_coursefeedback_surveyitemtext to be created.
        $table = new xmldb_table('block_coursefeedback_surveyitemtext');

        // Adding fields to table block_coursefeedback_surveyitemtext.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('surveyitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('initialrows', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '8');

        // Adding keys to table block_coursefeedback_surveyitemtext.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fu_surveyitemid', XMLDB_KEY_FOREIGN_UNIQUE, ['surveyitemid'], 'block_coursefeedback_surveyitem', ['id']);

        // Conditionally launch create table for block_coursefeedback_surveyitemtext.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Populate the new table with defaults for all text survey items.
        $text_item_ids = $DB->get_fieldset('block_coursefeedback_surveyitem', 'id', ['surveyitemtype' => 'text']);
        $new_records = array_map(fn($id) => ['surveyitemid' => $id, 'initialrows' => 8], $text_item_ids);
        $DB->insert_records('block_coursefeedback_surveyitemtext', $new_records);

        // Coursefeedback savepoint reached.
        upgrade_block_savepoint(true, 2026062200, 'coursefeedback');
    }

    return true;
}
