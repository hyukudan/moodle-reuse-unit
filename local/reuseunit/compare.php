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
 * Diff comparison page for sections and template versions.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reuseunit/compare.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('comparison', 'local_reuseunit'));
$PAGE->set_heading(get_string('comparison', 'local_reuseunit'));

// Check capabilities.
require_capability('local/reuseunit:import', $context);

// Add navigation.
$PAGE->navbar->add(get_string('pluginname', 'local_reuseunit'), new moodle_url('/local/reuseunit/index.php'));
$PAGE->navbar->add(get_string('comparison', 'local_reuseunit'));

// Get optional parameters for pre-selected comparison.
$mode = optional_param('mode', 'sections', PARAM_ALPHA);
$templateid = optional_param('templateid', 0, PARAM_INT);
$version1 = optional_param('v1', 0, PARAM_INT);
$version2 = optional_param('v2', 0, PARAM_INT);
$sourcecourse = optional_param('sourcecourse', 0, PARAM_INT);
$sourcesection = optional_param('sourcesection', 0, PARAM_INT);
$targetcourse = optional_param('targetcourse', 0, PARAM_INT);
$targetsection = optional_param('targetsection', 0, PARAM_INT);

// Get available courses for the user.
$courses = enrol_get_my_courses('id, fullname, shortname', 'fullname ASC');
$courselist = [];
foreach ($courses as $course) {
    $courselist[] = [
        'id' => $course->id,
        'fullname' => $course->fullname,
        'shortname' => $course->shortname,
    ];
}

// Get templates for version comparison.
$templates = $DB->get_records('local_reuseunit_templates', ['userid' => $USER->id], 'name ASC', 'id, name');
$templatelist = [];
foreach ($templates as $template) {
    $templatelist[] = [
        'id' => $template->id,
        'name' => $template->name,
    ];
}

// Prepare data for mustache.
$templatedata = [
    'mode' => $mode,
    'modesections' => ($mode === 'sections'),
    'modeversions' => ($mode === 'versions'),
    'courses' => $courselist,
    'hascourses' => !empty($courselist),
    'templates' => $templatelist,
    'hastemplates' => !empty($templatelist),
    'preselectedtemplate' => $templateid,
    'preselectedv1' => $version1,
    'preselectedv2' => $version2,
    'preselectedsourcecourse' => $sourcecourse,
    'preselectedsourcesection' => $sourcesection,
    'preselectedtargetcourse' => $targetcourse,
    'preselectedtargetsection' => $targetsection,
];

// Initialize AMD module.
$PAGE->requires->js_call_amd('local_reuseunit/compare', 'init', [$templatedata]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reuseunit/compare', $templatedata);
echo $OUTPUT->footer();
