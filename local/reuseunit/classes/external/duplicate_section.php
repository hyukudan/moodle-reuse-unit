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

namespace local_reuseunit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/course/lib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_course;

/**
 * External function to duplicate a section within the same course.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate_section extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID to duplicate'),
            'position' => new external_value(PARAM_TEXT, 'Position: after_source, end', VALUE_DEFAULT, 'after_source'),
            'newname' => new external_value(PARAM_TEXT, 'New section name (optional)', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Duplicate a section within the same course.
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID to duplicate
     * @param string $position Where to place the duplicate
     * @param string $newname New section name
     * @return array Result
     */
    public static function execute(int $courseid, int $sectionid, string $position = 'after_source', string $newname = ''): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'position' => $position,
            'newname' => $newname,
        ]);

        // Check course and capabilities.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);
        require_capability('moodle/course:manageactivities', $context);

        // Get source section.
        $sourcesection = $DB->get_record('course_sections', ['id' => $params['sectionid'], 'course' => $course->id], '*', MUST_EXIST);

        // Use the import_section logic but with same course as source and destination.
        $result = import_section::execute(
            $course->id,
            $params['sectionid'],
            $course->id,
            $params['position'] === 'after_source' ? 'after:' . $sourcesection->section : 'end',
            $params['newname'] ?: get_section_name($course, $sourcesection) . ' (' . get_string('copy', 'local_reuseunit') . ')',
            true,  // Reset dates
            false, // No restrictions
            false  // No gradebook
        );

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether duplication was successful'),
            'sectionid' => new external_value(PARAM_INT, 'New section ID'),
            'sectionnum' => new external_value(PARAM_INT, 'New section number'),
            'activities' => new external_value(PARAM_INT, 'Number of activities duplicated'),
            'resources' => new external_value(PARAM_INT, 'Number of resources duplicated'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'courseurl' => new external_value(PARAM_URL, 'URL to course'),
        ]);
    }
}
