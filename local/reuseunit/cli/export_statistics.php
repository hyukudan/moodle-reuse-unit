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
 * CLI script to export usage statistics.
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
    'format' => 'text',
    'output' => '',
    'period' => 'all',
    'help' => false,
], [
    'f' => 'format',
    'o' => 'output',
    'p' => 'period',
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = <<<EOT
Export Reuse Unit usage statistics.

Options:
-f, --format=FORMAT Output format: text, csv, json (default: text)
-o, --output=FILE   Output file path (default: stdout)
-p, --period=PERIOD Time period: all, month, week (default: all)
-h, --help          Print this help

Example:
\$ php export_statistics.php --format=csv --output=/tmp/stats.csv
\$ php export_statistics.php --format=json --period=month

EOT;
    echo $help;
    exit(0);
}

$format = $options['format'];
$outputfile = $options['output'];
$period = $options['period'];

// Validate format.
if (!in_array($format, ['text', 'csv', 'json'])) {
    cli_error('Invalid format. Use: text, csv, or json');
}

// Calculate period cutoff.
$cutoff = 0;
switch ($period) {
    case 'week':
        $cutoff = time() - (7 * 24 * 60 * 60);
        break;
    case 'month':
        $cutoff = time() - (30 * 24 * 60 * 60);
        break;
    case 'all':
    default:
        $cutoff = 0;
}

// Gather statistics.
$stats = [];

// Total imports.
$sql = "SELECT COUNT(*) FROM {local_reuseunit_history}";
$params = [];
if ($cutoff > 0) {
    $sql .= " WHERE timecreated >= :cutoff";
    $params['cutoff'] = $cutoff;
}
$stats['total_imports'] = $DB->count_records_sql($sql, $params);

// Successful imports (assuming all records in history are successful).
$stats['successful_imports'] = $stats['total_imports'];

// Total activities imported.
$sql = "SELECT COALESCE(SUM(activities_imported), 0) FROM {local_reuseunit_history}";
if ($cutoff > 0) {
    $sql .= " WHERE timecreated >= :cutoff";
}
$stats['activities_imported'] = (int)$DB->get_field_sql($sql, $params);

// Total resources imported.
$sql = "SELECT COALESCE(SUM(resources_imported), 0) FROM {local_reuseunit_history}";
if ($cutoff > 0) {
    $sql .= " WHERE timecreated >= :cutoff";
}
$stats['resources_imported'] = (int)$DB->get_field_sql($sql, $params);

// Total templates.
$stats['total_templates'] = $DB->count_records('local_reuseunit_templates');

// Templates by share level.
$sql = "SELECT sharelevel, COUNT(*) as count FROM {local_reuseunit_templates} GROUP BY sharelevel";
$sharelevels = $DB->get_records_sql($sql);
$stats['templates_personal'] = 0;
$stats['templates_category'] = 0;
$stats['templates_global'] = 0;
foreach ($sharelevels as $level) {
    switch ($level->sharelevel) {
        case 'personal':
            $stats['templates_personal'] = (int)$level->count;
            break;
        case 'category':
            $stats['templates_category'] = (int)$level->count;
            break;
        case 'global':
            $stats['templates_global'] = (int)$level->count;
            break;
    }
}

// Total favorites.
$stats['total_favorites'] = $DB->count_records('local_reuseunit_favorites');

// Scheduled imports pending.
$stats['scheduled_pending'] = $DB->count_records('local_reuseunit_scheduled', ['status' => 'pending']);

// Linked sections.
$stats['linked_sections'] = $DB->count_records('local_reuseunit_links');

// Unique users who imported.
$sql = "SELECT COUNT(DISTINCT userid) FROM {local_reuseunit_history}";
if ($cutoff > 0) {
    $sql .= " WHERE timecreated >= :cutoff";
}
$stats['unique_users'] = (int)$DB->get_field_sql($sql, $params);

// Top 5 courses by imports.
$sql = "SELECT target_courseid, COUNT(*) as count
        FROM {local_reuseunit_history}";
if ($cutoff > 0) {
    $sql .= " WHERE timecreated >= :cutoff";
}
$sql .= " GROUP BY target_courseid ORDER BY count DESC LIMIT 5";
$topcourses = $DB->get_records_sql($sql, $params);
$stats['top_courses'] = [];
foreach ($topcourses as $course) {
    $courseinfo = $DB->get_record('course', ['id' => $course->target_courseid], 'id, fullname');
    $stats['top_courses'][] = [
        'id' => $course->target_courseid,
        'name' => $courseinfo ? $courseinfo->fullname : 'Unknown',
        'imports' => (int)$course->count,
    ];
}

// Generate output.
$output = '';

switch ($format) {
    case 'json':
        $output = json_encode([
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => $period,
            'statistics' => $stats,
        ], JSON_PRETTY_PRINT);
        break;

    case 'csv':
        $lines = [];
        $lines[] = 'Metric,Value';
        $lines[] = 'Total Imports,' . $stats['total_imports'];
        $lines[] = 'Successful Imports,' . $stats['successful_imports'];
        $lines[] = 'Activities Imported,' . $stats['activities_imported'];
        $lines[] = 'Resources Imported,' . $stats['resources_imported'];
        $lines[] = 'Total Templates,' . $stats['total_templates'];
        $lines[] = 'Personal Templates,' . $stats['templates_personal'];
        $lines[] = 'Category Templates,' . $stats['templates_category'];
        $lines[] = 'Global Templates,' . $stats['templates_global'];
        $lines[] = 'Total Favorites,' . $stats['total_favorites'];
        $lines[] = 'Scheduled Pending,' . $stats['scheduled_pending'];
        $lines[] = 'Linked Sections,' . $stats['linked_sections'];
        $lines[] = 'Unique Users,' . $stats['unique_users'];
        $output = implode("\n", $lines);
        break;

    case 'text':
    default:
        $output = "=== Reuse Unit Statistics ===\n";
        $output .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= "Period: {$period}\n\n";
        $output .= "--- Imports ---\n";
        $output .= "Total imports: {$stats['total_imports']}\n";
        $output .= "Activities imported: {$stats['activities_imported']}\n";
        $output .= "Resources imported: {$stats['resources_imported']}\n";
        $output .= "Unique users: {$stats['unique_users']}\n\n";
        $output .= "--- Templates ---\n";
        $output .= "Total templates: {$stats['total_templates']}\n";
        $output .= "  Personal: {$stats['templates_personal']}\n";
        $output .= "  Category: {$stats['templates_category']}\n";
        $output .= "  Global: {$stats['templates_global']}\n";
        $output .= "Total favorites: {$stats['total_favorites']}\n\n";
        $output .= "--- Synchronization ---\n";
        $output .= "Linked sections: {$stats['linked_sections']}\n";
        $output .= "Scheduled pending: {$stats['scheduled_pending']}\n\n";
        if (!empty($stats['top_courses'])) {
            $output .= "--- Top Courses ---\n";
            foreach ($stats['top_courses'] as $i => $course) {
                $output .= ($i + 1) . ". {$course['name']} ({$course['imports']} imports)\n";
            }
        }
        break;
}

// Output.
if ($outputfile) {
    file_put_contents($outputfile, $output);
    echo "Statistics exported to: {$outputfile}\n";
} else {
    echo $output;
}
