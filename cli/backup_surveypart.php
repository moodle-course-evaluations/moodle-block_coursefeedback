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
 * CLI script to back up a survey part / questionnaire to a JSON file.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\persistent\surveypart;
use block_coursefeedback\local\surveyitem\surveyitem_manager;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$usage = "Backs up a survey part / questionnaire to a JSON file.

Usage:
    # php backup_surveypart.php --surveypartid=1 > my-surveypart-1.beam.json
    # php backup_surveypart.php --surveypartid=2 --output=my-surveypart-2.beam.json
    # php backup_surveypart.php [--help|-h]

Options:
    -h --help               Print this help.
    -q --surveypartid=<id>  The ID of the survey part / questionnaire to backup.
    -o --output=<file>      Write the backup to the given file instead of stdout.
    -p --pretty             Pretty print the JSON output.
";

[$options, $unrecognized] = cli_get_params([
    'output' => false,
    'surveypartid' => false,
    'help' => false,
    'pretty' => false,
], [
    'o' => 'output',
    'q' => 'surveypartid',
    'h' => 'help',
    'p' => 'pretty',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($usage);
    die();
}
if (empty($options['surveypartid'])) {
    cli_error($usage);
}

$surveypart = surveypart::get_record(['id' => $options['surveypartid']]);
if (!$surveypart) {
    cli_error("Survey part with ID '{$options['surveypartid']}' not found");
}

try {
    if (empty($options['output']) || $options['output'] === '-') {
        $stream = STDOUT;
    } else {
        $stream = fopen($options['output'], 'w');
        if (!$stream) {
            cli_error("Could not open file '{$options['output']}' for writing.");
        }
    }

    $backup_content = surveyitem_manager::backup_surveypart($surveypart, pretty: $options['pretty']);

    cli_writeln($backup_content, $stream);
} finally {
    if ($stream !== STDOUT) {
        fclose($stream);
        cli_writeln("Backup was written to '{$options['output']}'.");
    }
}
