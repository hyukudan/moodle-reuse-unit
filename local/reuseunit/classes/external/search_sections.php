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
 * External function to search sections by activity type.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_sections extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search query (section name)', VALUE_DEFAULT, ''),
            'activitytypes' => new external_value(PARAM_TEXT, 'Comma-separated activity types (e.g., quiz,assign)', VALUE_DEFAULT, ''),
            'courseid' => new external_value(PARAM_INT, 'Limit to specific course (0 for all)', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum results', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Search for sections by name and/or activity types.
     *
     * @param string $query Search query
     * @param string $activitytypes Comma-separated activity types
     * @param int $courseid Course ID filter
     * @param int $limit Max results
     * @return array List of matching sections
     */
    public static function execute(string $query = '', string $activitytypes = '', int $courseid = 0, int $limit = 20): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'activitytypes' => $activitytypes,
            'courseid' => $courseid,
            'limit' => $limit,
        ]);

        $limit = min($params['limit'], 50);
        $results = [];

        // Parse activity types.
        $types = [];
        if (!empty($params['activitytypes'])) {
            $types = array_map('trim', explode(',', $params['activitytypes']));
            $types = array_filter($types);
        }

        // Get courses the user has access to.
        if ($params['courseid'] > 0) {
            $courses = [$DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST)];
        } else {
            // Get all courses user can export from.
            $courses = enrol_get_my_courses('id, fullname, shortname', 'fullname ASC', 0, [], false);
        }

        foreach ($courses as $course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);
            if (!$context || !has_capability('local/reuseunit:export', $context)) {
                continue;
            }

            $modinfo = get_fast_modinfo($course);

            foreach ($modinfo->get_section_info_all() as $section) {
                if ($section->section == 0) {
                    continue; // Skip general section.
                }

                $sectionname = get_section_name($course, $section);

                // Filter by query.
                if (!empty($params['query'])) {
                    if (stripos($sectionname, $params['query']) === false) {
                        continue;
                    }
                }

                // Count activities by type.
                $activitycounts = [];
                $totalactivities = 0;
                $totalresources = 0;
                $matchestype = empty($types);

                if (!empty($modinfo->sections[$section->section])) {
                    foreach ($modinfo->sections[$section->section] as $cmid) {
                        $cm = $modinfo->cms[$cmid];
                        if (!$cm->uservisible) {
                            continue;
                        }

                        $modname = $cm->modname;
                        if (!isset($activitycounts[$modname])) {
                            $activitycounts[$modname] = 0;
                        }
                        $activitycounts[$modname]++;

                        // Check if matches requested types.
                        if (!empty($types) && in_array($modname, $types)) {
                            $matchestype = true;
                        }

                        // Count totals.
                        $archetype = plugin_supports('mod', $modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                        if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                            $totalresources++;
                        } else {
                            $totalactivities++;
                        }
                    }
                }

                // Skip if doesn't match activity type filter.
                if (!$matchestype) {
                    continue;
                }

                // Skip empty sections unless explicitly searching.
                if (empty($activitycounts) && empty($params['query'])) {
                    continue;
                }

                $results[] = [
                    'sectionid' => $section->id,
                    'sectionnum' => $section->section,
                    'sectionname' => $sectionname,
                    'courseid' => $course->id,
                    'coursename' => format_string($course->fullname),
                    'courseshortname' => $course->shortname,
                    'activities' => $totalactivities,
                    'resources' => $totalresources,
                    'activitytypes' => array_keys($activitycounts),
                    'activitycounts' => json_encode($activitycounts),
                    'visible' => $section->visible,
                ];

                if (count($results) >= $limit) {
                    break 2;
                }
            }
        }

        return $results;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'sectionid' => new external_value(PARAM_INT, 'Section ID'),
                'sectionnum' => new external_value(PARAM_INT, 'Section number'),
                'sectionname' => new external_value(PARAM_TEXT, 'Section name'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'coursename' => new external_value(PARAM_TEXT, 'Course full name'),
                'courseshortname' => new external_value(PARAM_TEXT, 'Course short name'),
                'activities' => new external_value(PARAM_INT, 'Number of activities'),
                'resources' => new external_value(PARAM_INT, 'Number of resources'),
                'activitytypes' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Activity type'),
                    'Activity types in section'
                ),
                'activitycounts' => new external_value(PARAM_RAW, 'JSON encoded activity counts by type'),
                'visible' => new external_value(PARAM_BOOL, 'Section visibility'),
            ])
        );
    }
}
