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
 * Statistics dashboard page.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reuseunit/dashboard.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('dashboard', 'local_reuseunit'));
$PAGE->set_heading(get_string('dashboard', 'local_reuseunit'));

// Check capabilities.
require_capability('local/reuseunit:import', $context);

// Add navigation.
$PAGE->navbar->add(get_string('pluginname', 'local_reuseunit'), new moodle_url('/local/reuseunit/index.php'));
$PAGE->navbar->add(get_string('dashboard', 'local_reuseunit'));

// Get initial statistics.
$stats = \local_reuseunit\external\get_statistics::execute($USER->id);

// Prepare data for mustache.
$canviewall = has_capability('local/reuseunit:managetemplates', $context);

$templatedata = [
    'userid' => $USER->id,
    'canviewall' => $canviewall,
    'totalimports' => $stats['totalimports'],
    'successfulimports' => $stats['successfulimports'],
    'activitiesimported' => $stats['activitiesimported'],
    'resourcesimported' => $stats['resourcesimported'],
    'totaltemplates' => $stats['totaltemplates'],
    'templateusage' => $stats['templateusage'],
    'toptemplates' => $stats['toptemplates'],
    'hastoptemplates' => !empty($stats['toptemplates']),
    'recentimports' => $stats['recentimports'],
    'hasrecentimports' => !empty($stats['recentimports']),
    'importsbycourse' => json_encode($stats['importsbycourse']),
    'importsbyactivity' => json_encode($stats['importsbyactivity']),
];

// Initialize AMD module.
$PAGE->requires->js_call_amd('local_reuseunit/dashboard', 'init', [$templatedata]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reuseunit/dashboard', $templatedata);
echo $OUTPUT->footer();
