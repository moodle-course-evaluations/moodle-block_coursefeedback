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
 * Functions for use by the `upgrade.php`.
 *
 * @package    block_coursefeedback
 * @copyright  2026 innoCampus, Technische Universität Berlin
 * @copyright  2026 IT.Services, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a new non-null field, setting it to the given default on all existing records. Xmldb can't do this natively.
 *
 * @param database_manager $dbman
 * @param xmldb_table $table
 * @param xmldb_field $field
 * @param mixed $default
 * @return void
 */
function block_coursefeedback_add_nonnull_field_with_default(
    database_manager $dbman,
    xmldb_table $table,
    xmldb_field $field,
    mixed $default
): void {
    global $DB;

    // We can't add a new non-null field without a default, but TEXT fields can't have defaults (for some reason).
    // So we add as nullable, then set our default and change to non-null.

    $field->setNotNull(false);
    $dbman->add_field($table, $field);
    $DB->execute('UPDATE {' . $table->getName() . '} SET ' . $field->getName() . ' = :default', ['default' => $default]);

    $field->setNotNull(XMLDB_NOTNULL);
    $dbman->change_field_notnull($table, $field);
}

/**
 * Creates a fallback organization with the given name.
 *
 * @param string $name
 * @return int
 */
function block_coursefeedback_create_fallback_org(string $name): int {
    global $DB, $USER;
    return $DB->insert_record('block_coursefeedback_organization', [
        'name' => $name,
        'can_teacher_edit_speriod' => false,
        'can_teacher_edit_ssettings' => false,
        'usermodified' => $USER->id,
        'timecreated' => time(),
        'timemodified' => time(),
    ]);
}

function block_coursefeedback_migrate_organizationid_to_orgsemid(
    string $table_name,
    array $orgsemids_by_organizationids,
    bool $is_unique,
    bool $drop_old
) {
    $table = new xmldb_table($table_name);

    global $DB;
    $dbman = $DB->get_manager();

    $transaction = $DB->start_delegated_transaction();

    // Add the orgsemid column.
    $field = new xmldb_field('orgsemid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'active');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Populate the orgsemid column.
    foreach ($DB->get_records($table_name) as $record) {
        $orgsemid = $orgsemids_by_organizationids[$record->organizationid] ?? null;
        if (!$orgsemid) {
            throw new coding_exception(
                "Organization $record->organizationid has no semester, but does have $table_name, which should not be possible."
            );
        }

        $DB->update_record($table_name, [
            'id' => $record->id,
            'orgsemid' => $orgsemid,
        ]);
    }

    // Make orgsemid not null.
    $field->setNotNull(XMLDB_NOTNULL);
    $dbman->change_field_notnull($table, $field);

    // Add the foreign (or foreign-unique) key.
    $key = new xmldb_key(
        $is_unique ? 'fuk_orgsemid' : 'fk_orgsemid',
        $is_unique ? XMLDB_KEY_FOREIGN_UNIQUE : XMLDB_KEY_FOREIGN,
        ['orgsemid'],
        'block_coursefeedback_organization_semester',
        ['id']
    );
    $dbman->add_key($table, $key);

    if ($drop_old) {
        // Drop the old key.
        $key = new xmldb_key(
            $is_unique ? 'fu_organizationid' : 'fk_organizationid',
            $is_unique ? XMLDB_KEY_FOREIGN_UNIQUE : XMLDB_KEY_FOREIGN,
            ['organizationid'],
            'block_coursefeedback_organization',
            ['id']
        );
        $dbman->drop_key($table, $key);

        // Drop the old organizationid column.
        $field = new xmldb_field('organizationid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
    }

    $transaction->allow_commit();
}
