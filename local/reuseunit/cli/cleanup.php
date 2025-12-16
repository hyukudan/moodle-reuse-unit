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
 * CLI script to clean up old data.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// CLI options.
list($options, $unrecognized) = cli_get_params([
    'scheduled' => false,
    'history' => false,
    'orphaned' => false,
    'all' => false,
    'days' => 30,
    'dry-run' => false,
    'help' => false,
], [
    's' => 'scheduled',
    'i' => 'history',
    'r' => 'orphaned',
    'a' => 'all',
    'd' => 'days',
    'n' => 'dry-run',
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = <<<EOT
Clean up old Reuse Unit data.

Options:
-s, --scheduled     Clean up completed scheduled imports
-i, --history       Clean up old import history
-r, --orphaned      Clean up orphaned data (favorites for deleted templates, etc.)
-a, --all           Clean up all types of data
-d, --days=N        Age threshold in days (default: 30)
-n, --dry-run       Show what would be deleted without actually deleting
-h, --help          Print this help

Example:
\$ php cleanup.php --all --days=60
\$ php cleanup.php --scheduled --dry-run
\$ php cleanup.php --orphaned

EOT;
    echo $help;
    exit(0);
}

$days = (int)$options['days'];
$dryrun = $options['dry-run'];

if ($options['all']) {
    $options['scheduled'] = true;
    $options['history'] = true;
    $options['orphaned'] = true;
}

if (!$options['scheduled'] && !$options['history'] && !$options['orphaned']) {
    cli_error('Please specify what to clean up. Use --help for options.');
}

if ($days < 1) {
    cli_error('Days must be at least 1');
}

$cutoff = time() - ($days * 24 * 60 * 60);
$totaldeleted = 0;

cli_heading('Reuse Unit Cleanup');

if ($dryrun) {
    echo "[DRY RUN MODE - No data will be deleted]\n\n";
}

// Clean up completed scheduled imports.
if ($options['scheduled']) {
    echo "=== Cleaning up scheduled imports ===\n";

    $where = "status IN ('completed', 'cancelled', 'failed') AND timecreated < :cutoff";
    $params = ['cutoff' => $cutoff];

    $count = $DB->count_records_select('local_reuseunit_scheduled', $where, $params);
    echo "Found {$count} completed/cancelled scheduled imports older than {$days} days.\n";

    if ($count > 0 && !$dryrun) {
        $DB->delete_records_select('local_reuseunit_scheduled', $where, $params);
        echo "Deleted {$count} records.\n";
        $totaldeleted += $count;
    }
    echo "\n";
}

// Clean up old history.
if ($options['history']) {
    echo "=== Cleaning up import history ===\n";

    $historydays = max($days, 90); // History should be kept at least 90 days.
    $historycutoff = time() - ($historydays * 24 * 60 * 60);

    $where = "timecreated < :cutoff";
    $params = ['cutoff' => $historycutoff];

    $count = $DB->count_records_select('local_reuseunit_history', $where, $params);
    echo "Found {$count} history records older than {$historydays} days.\n";

    if ($count > 0 && !$dryrun) {
        $DB->delete_records_select('local_reuseunit_history', $where, $params);
        echo "Deleted {$count} records.\n";
        $totaldeleted += $count;
    }
    echo "\n";
}

// Clean up orphaned data.
if ($options['orphaned']) {
    echo "=== Cleaning up orphaned data ===\n";

    // Orphaned favorites (template deleted).
    $sql = "SELECT f.id FROM {local_reuseunit_favorites} f
            LEFT JOIN {local_reuseunit_templates} t ON f.templateid = t.id
            WHERE t.id IS NULL";
    $orphanedfavorites = $DB->get_records_sql($sql);
    $count = count($orphanedfavorites);
    echo "Found {$count} orphaned favorites (template deleted).\n";

    if ($count > 0 && !$dryrun) {
        $ids = array_keys($orphanedfavorites);
        $DB->delete_records_list('local_reuseunit_favorites', 'id', $ids);
        echo "Deleted {$count} orphaned favorites.\n";
        $totaldeleted += $count;
    }

    // Orphaned links (template deleted).
    $sql = "SELECT l.id FROM {local_reuseunit_links} l
            LEFT JOIN {local_reuseunit_templates} t ON l.templateid = t.id
            WHERE t.id IS NULL";
    $orphanedlinks = $DB->get_records_sql($sql);
    $count = count($orphanedlinks);
    echo "Found {$count} orphaned links (template deleted).\n";

    if ($count > 0 && !$dryrun) {
        $ids = array_keys($orphanedlinks);
        $DB->delete_records_list('local_reuseunit_links', 'id', $ids);
        echo "Deleted {$count} orphaned links.\n";
        $totaldeleted += $count;
    }

    // Orphaned links (course deleted).
    $sql = "SELECT l.id FROM {local_reuseunit_links} l
            LEFT JOIN {course} c ON l.courseid = c.id
            WHERE c.id IS NULL";
    $orphanedcourselinks = $DB->get_records_sql($sql);
    $count = count($orphanedcourselinks);
    echo "Found {$count} orphaned links (course deleted).\n";

    if ($count > 0 && !$dryrun) {
        $ids = array_keys($orphanedcourselinks);
        $DB->delete_records_list('local_reuseunit_links', 'id', $ids);
        echo "Deleted {$count} orphaned course links.\n";
        $totaldeleted += $count;
    }

    // Orphaned template versions (template deleted).
    $sql = "SELECT v.id FROM {local_reuseunit_versions} v
            LEFT JOIN {local_reuseunit_templates} t ON v.templateid = t.id
            WHERE t.id IS NULL";
    $orphanedversions = $DB->get_records_sql($sql);
    $count = count($orphanedversions);
    echo "Found {$count} orphaned versions (template deleted).\n";

    if ($count > 0 && !$dryrun) {
        $ids = array_keys($orphanedversions);
        $DB->delete_records_list('local_reuseunit_versions', 'id', $ids);
        echo "Deleted {$count} orphaned versions.\n";
        $totaldeleted += $count;
    }

    echo "\n";
}

echo "=== Summary ===\n";
if ($dryrun) {
    echo "Dry run completed. No data was deleted.\n";
    echo "Run without --dry-run to actually delete data.\n";
} else {
    echo "Total records deleted: {$totaldeleted}\n";
}
