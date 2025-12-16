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
 * Library functions for local_reuseunit.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends course navigation with import option.
 *
 * @param navigation_node $parentnode The navigation node to extend
 * @param stdClass $course The course object
 * @param context_course $context The course context
 */
function local_reuseunit_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    if (has_capability('local/reuseunit:import', $context)) {
        $url = new moodle_url('/local/reuseunit/index.php', ['courseid' => $course->id]);
        $parentnode->add(
            get_string('importunit', 'local_reuseunit'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'reuseunit',
            new pix_icon('i/import', '')
        );
    }
}

/**
 * Inject JavaScript and CSS when viewing a course.
 */
function local_reuseunit_before_standard_html_head() {
    global $PAGE, $COURSE;

    // Only load on course pages.
    if ($PAGE->context->contextlevel !== CONTEXT_COURSE) {
        return '';
    }

    if (!has_capability('local/reuseunit:import', $PAGE->context)) {
        return '';
    }

    // Load CSS.
    $PAGE->requires->css('/local/reuseunit/styles.css');

    // Load JS for edit mode integration.
    if ($PAGE->user_is_editing()) {
        $PAGE->requires->js_call_amd('local_reuseunit/course_integration', 'init', [
            'courseid' => $COURSE->id,
            'contextid' => $PAGE->context->id,
        ]);
    }

    return '';
}

/**
 * Fragment callback for import modal.
 *
 * @param array $args Arguments
 * @return string HTML content
 */
function local_reuseunit_output_fragment_import_modal($args) {
    global $OUTPUT, $DB;

    $courseid = clean_param($args['courseid'], PARAM_INT);
    $sectionid = clean_param($args['sectionid'] ?? 0, PARAM_INT);

    $context = context_course::instance($courseid);
    require_capability('local/reuseunit:import', $context);

    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

    return $OUTPUT->render_from_template('local_reuseunit/import_wizard', [
        'courseid' => $courseid,
        'sectionid' => $sectionid,
        'coursename' => format_string($course->fullname),
        'haspreselectedcourse' => true,
    ]);
}

/**
 * Fragment callback for save template modal.
 *
 * @param array $args Arguments
 * @return string HTML content
 */
function local_reuseunit_output_fragment_save_template_modal($args) {
    global $OUTPUT, $DB;

    $courseid = clean_param($args['courseid'], PARAM_INT);
    $sectionid = clean_param($args['sectionid'], PARAM_INT);
    $sectionname = clean_param($args['sectionname'] ?? '', PARAM_TEXT);

    $context = context_course::instance($courseid);
    require_capability('local/reuseunit:export', $context);

    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $category = $DB->get_record('course_categories', ['id' => $course->category]);

    return $OUTPUT->render_from_template('local_reuseunit/save_template_modal', [
        'courseid' => $courseid,
        'sectionid' => $sectionid,
        'sectionname' => $sectionname,
        'categoryid' => $course->category,
        'categoryname' => $category ? format_string($category->name) : '',
    ]);
}
