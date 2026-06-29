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
 * CLI script to restore a survey part / questionnaire from a JSON file.
 *
 * @package     block_coursefeedback
 * @copyright   2026 innoCampus, Technische Universität Berlin
 * @copyright   2026 Moodle.NRW, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursefeedback\local\surveyitem\surveyitem_manager;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$usage = "Restores a survey part / questionnaire from a JSON file.

Usage:
    # php restore_surveypart.php --input=my-surveypart-1.beam.json
    # php restore_surveypart.php -i=- < my-surveypart-2.beam.json
    # php restore_surveypart.php [--help|-h]

Options:
    -h --help               Print this help.
    -i --input=<file>       The input backup file or '-' to read from stdin.
";

[$options, $unrecognized] = cli_get_params([
    'input' => false,
    'help' => false,
], [
    'i' => 'input',
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($usage);
    die();
}
if (empty($options['input'])) {
    cli_error($usage);
}

if ($options['input'] === '-') {
    if (!($backup_content = stream_get_contents(STDIN))) {
        cli_error("Could not read from stdin.");
    }
} else {
    if (!($backup_content = file_get_contents($options['input']))) {
        cli_error("Could not read file '{$options['input']}'.");
    }
}

$surveypart = surveyitem_manager::restore_surveypart($backup_content);

cli_writeln("Survey part restored successfully with ID '{$surveypart->get('id')}' and name '{$surveypart->get('name')}'.");
