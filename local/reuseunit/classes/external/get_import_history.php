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
use context_system;

/**
 * External function to get import history.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_import_history extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'limit' => new external_value(PARAM_INT, 'Maximum number of results', VALUE_DEFAULT, 10),
        ]);
    }

    /**
     * Get import history for the current user.
     *
     * @param int $limit Maximum number of results
     * @return array List of import history records
     */
    public static function execute(int $limit = 10): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'limit' => $limit,
        ]);

        $limit = min($params['limit'], 50);

        $context = context_system::instance();
        self::validate_context($context);

        // Get history records.
        $records = $DB->get_records('local_reuseunit_history', [
            'userid' => $USER->id,
        ], 'timecreated DESC', '*', 0, $limit);

        $result = [];
        foreach ($records as $record) {
            // Get course names.
            $sourcecourse = $DB->get_record('course', ['id' => $record->source_courseid], 'fullname');
            $destcourse = $DB->get_record('course', ['id' => $record->dest_courseid], 'fullname');

            $result[] = [
                'id' => $record->id,
                'source_courseid' => $record->source_courseid,
                'source_coursename' => $sourcecourse ? $sourcecourse->fullname : get_string('error_coursenotfound', 'local_reuseunit'),
                'source_sectionname' => $record->source_sectionname ?? '',
                'dest_courseid' => $record->dest_courseid,
                'dest_coursename' => $destcourse ? $destcourse->fullname : get_string('error_coursenotfound', 'local_reuseunit'),
                'status' => $record->status,
                'activities_count' => $record->activities_count ?? 0,
                'resources_count' => $record->resources_count ?? 0,
                'timecreated' => $record->timecreated,
                'timecreatedformatted' => userdate($record->timecreated),
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
                'id' => new external_value(PARAM_INT, 'History record ID'),
                'source_courseid' => new external_value(PARAM_INT, 'Source course ID'),
                'source_coursename' => new external_value(PARAM_TEXT, 'Source course name'),
                'source_sectionname' => new external_value(PARAM_TEXT, 'Source section name'),
                'dest_courseid' => new external_value(PARAM_INT, 'Destination course ID'),
                'dest_coursename' => new external_value(PARAM_TEXT, 'Destination course name'),
                'status' => new external_value(PARAM_TEXT, 'Import status'),
                'activities_count' => new external_value(PARAM_INT, 'Number of activities imported'),
                'resources_count' => new external_value(PARAM_INT, 'Number of resources imported'),
                'timecreated' => new external_value(PARAM_INT, 'Unix timestamp'),
                'timecreatedformatted' => new external_value(PARAM_TEXT, 'Formatted date'),
            ])
        );
    }
}
