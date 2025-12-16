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

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use context_course;

/**
 * External function to get sections from a course.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_sections extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get sections from a course with content summary.
     *
     * @param int $courseid Course ID
     * @return array List of sections
     */
    public static function execute(int $courseid): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        $courseid = $params['courseid'];

        // Check course exists.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = context_course::instance($courseid);

        // Check capability.
        self::validate_context($context);
        require_capability('local/reuseunit:export', $context);

        // Get course module info.
        $modinfo = get_fast_modinfo($course);
        $sections = [];

        foreach ($modinfo->get_section_info_all() as $sectioninfo) {
            // Skip section 0 (general section) if empty.
            if ($sectioninfo->section == 0) {
                continue;
            }

            // Count activities and resources.
            $activities = 0;
            $resources = 0;

            if (!empty($modinfo->sections[$sectioninfo->section])) {
                foreach ($modinfo->sections[$sectioninfo->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if (!$cm->uservisible) {
                        continue;
                    }

                    // Determine if it's an activity or resource.
                    $archetype = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                    if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                        $resources++;
                    } else {
                        $activities++;
                    }
                }
            }

            // Get section name.
            $sectionname = get_section_name($course, $sectioninfo);

            $sections[] = [
                'id' => $sectioninfo->id,
                'section' => $sectioninfo->section,
                'name' => $sectionname,
                'summary' => format_text($sectioninfo->summary, $sectioninfo->summaryformat, [
                    'context' => $context,
                    'noclean' => true,
                ]),
                'visible' => $sectioninfo->visible,
                'activities' => $activities,
                'resources' => $resources,
                'hascontents' => ($activities + $resources) > 0,
            ];
        }

        return $sections;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Section ID'),
                'section' => new external_value(PARAM_INT, 'Section number'),
                'name' => new external_value(PARAM_TEXT, 'Section name'),
                'summary' => new external_value(PARAM_RAW, 'Section summary HTML'),
                'visible' => new external_value(PARAM_BOOL, 'Section visibility'),
                'activities' => new external_value(PARAM_INT, 'Number of activities'),
                'resources' => new external_value(PARAM_INT, 'Number of resources'),
                'hascontents' => new external_value(PARAM_BOOL, 'Whether section has contents'),
            ])
        );
    }
}
