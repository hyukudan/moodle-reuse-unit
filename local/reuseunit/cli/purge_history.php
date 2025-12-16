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
 * CLI script to purge import history older than specified days.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// CLI options.
list($options, $unrecognized) = cli_get_params([
    'days' => 365,
    'userid' => 0,
    'dry-run' => false,
    'help' => false,
], [
    'd' => 'days',
    'u' => 'userid',
    'n' => 'dry-run',
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = <<<EOT
Purge import history records older than specified number of days.

Options:
-d, --days=N        Delete records older than N days (default: 365)
-u, --userid=ID     Only delete records for specific user ID (default: all users)
-n, --dry-run       Show what would be deleted without actually deleting
-h, --help          Print this help

Example:
\$ php purge_history.php --days=90
\$ php purge_history.php --days=30 --userid=5
\$ php purge_history.php --days=180 --dry-run

EOT;
    echo $help;
    exit(0);
}

$days = (int)$options['days'];
$userid = (int)$options['userid'];
$dryrun = $options['dry-run'];

if ($days < 1) {
    cli_error('Days must be at least 1');
}

$cutoff = time() - ($days * 24 * 60 * 60);

cli_heading('Purge Reuse Unit History');
echo "Purging records older than {$days} days (before " . userdate($cutoff) . ")\n";

// Build query conditions.
$conditions = ['timecreated < :cutoff'];
$params = ['cutoff' => $cutoff];

if ($userid > 0) {
    $conditions[] = 'userid = :userid';
    $params['userid'] = $userid;
    echo "Filtering by user ID: {$userid}\n";
}

$where = implode(' AND ', $conditions);

// Count records to delete.
$count = $DB->count_records_select('local_reuseunit_history', $where, $params);
echo "Found {$count} records to delete.\n";

if ($count == 0) {
    echo "No records to delete.\n";
    exit(0);
}

if ($dryrun) {
    echo "\n[DRY RUN] Would delete {$count} records.\n";
    echo "Run without --dry-run to actually delete.\n";
    exit(0);
}

// Confirm deletion.
$input = cli_input('Are you sure you want to delete these records? (yes/no)', 'no');
if (strtolower($input) !== 'yes') {
    echo "Aborted.\n";
    exit(0);
}

// Delete records.
$DB->delete_records_select('local_reuseunit_history', $where, $params);

echo "\nSuccessfully deleted {$count} history records.\n";
