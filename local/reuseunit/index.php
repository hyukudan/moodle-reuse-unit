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
 * Main page for the Reuse Unit plugin.
 *
 * @package    local_reuseunit
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Optional course context for quick import.
$courseid = optional_param('courseid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

require_login();

$context = $courseid ? context_course::instance($courseid) : context_system::instance();
require_capability('local/reuseunit:import', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reuseunit/index.php', ['courseid' => $courseid, 'sectionid' => $sectionid]));
$PAGE->set_title(get_string('pluginname', 'local_reuseunit'));
$PAGE->set_heading(get_string('pluginname', 'local_reuseunit'));

// Add required JS and CSS.
$PAGE->requires->js_call_amd('local_reuseunit/import_wizard', 'init', [
    'courseid' => $courseid,
    'sectionid' => $sectionid,
]);
$PAGE->requires->css('/local/reuseunit/styles.css');

echo $OUTPUT->header();

// Render the import wizard template.
$templatecontext = [
    'courseid' => $courseid,
    'sectionid' => $sectionid,
    'haspreselectedcourse' => !empty($courseid),
    'searchcoursesurl' => (new moodle_url('/local/reuseunit/ajax/search_courses.php'))->out(false),
];

echo $OUTPUT->render_from_template('local_reuseunit/import_wizard', $templatecontext);

echo $OUTPUT->footer();
