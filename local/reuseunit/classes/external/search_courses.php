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
 * External function to search for courses.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_courses extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search query'),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Search for courses the user has access to.
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array List of courses
     */
    public static function execute(string $query, int $limit = 20): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'limit' => $limit,
        ]);

        $query = $params['query'];
        $limit = min($params['limit'], 50); // Max 50 results.

        // Build the search query.
        $searchfields = ['fullname', 'shortname', 'idnumber'];
        $whereclauses = [];
        $sqlparams = [];

        foreach ($searchfields as $index => $field) {
            $paramname = 'search' . $index;
            $whereclauses[] = $DB->sql_like($field, ':' . $paramname, false, false);
            $sqlparams[$paramname] = '%' . $DB->sql_like_escape($query) . '%';
        }

        $where = '(' . implode(' OR ', $whereclauses) . ')';
        $where .= ' AND visible = 1';

        $sql = "SELECT id, fullname, shortname, category, startdate
                FROM {course}
                WHERE $where AND id != :siteid
                ORDER BY fullname ASC";
        $sqlparams['siteid'] = SITEID;

        $courses = $DB->get_records_sql($sql, $sqlparams, 0, $limit * 2);

        // Filter by capability and build result.
        $result = [];
        foreach ($courses as $course) {
            if (count($result) >= $limit) {
                break;
            }

            $context = context_course::instance($course->id, IGNORE_MISSING);
            if (!$context) {
                continue;
            }

            // User must have export capability in source course.
            if (!has_capability('local/reuseunit:export', $context)) {
                continue;
            }

            // Get category name.
            $category = $DB->get_record('course_categories', ['id' => $course->category], 'name');

            $result[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname, true, ['context' => $context]),
                'shortname' => $course->shortname,
                'categoryname' => $category ? $category->name : '',
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
                'categoryname' => new external_value(PARAM_TEXT, 'Category name'),
            ])
        );
    }
}
