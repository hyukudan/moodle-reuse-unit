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
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_system;

/**
 * External function to get scheduled imports for the current user.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_scheduled_imports extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'status' => new external_value(PARAM_ALPHA, 'Filter by status: all, pending, running, completed', VALUE_DEFAULT, 'all'),
        ]);
    }

    /**
     * Get scheduled imports.
     *
     * @param string $status Status filter
     * @return array Scheduled imports
     */
    public static function execute(string $status = 'all'): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'status' => $status,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        // Build query.
        $sql = "SELECT s.*, c.fullname as coursename
                FROM {local_reuseunit_scheduled} s
                LEFT JOIN {course} c ON c.id = s.dest_courseid
                WHERE s.userid = :userid";
        $sqlparams = ['userid' => $USER->id];

        if ($params['status'] !== 'all') {
            if ($params['status'] === 'completed') {
                $sql .= " AND (s.status = 'completed' OR s.status = 'completed_with_errors')";
            } else {
                $sql .= " AND s.status = :status";
                $sqlparams['status'] = $params['status'];
            }
        }

        $sql .= " ORDER BY s.scheduled_time DESC";

        $schedules = $DB->get_records_sql($sql, $sqlparams);

        $result = [];
        foreach ($schedules as $schedule) {
            $imports = json_decode($schedule->import_data, true) ?? [];
            $resultdata = json_decode($schedule->result_data, true) ?? [];

            $result[] = [
                'id' => (int) $schedule->id,
                'destcourseid' => (int) $schedule->dest_courseid,
                'destcoursename' => $schedule->coursename ?? 'Unknown',
                'importcount' => count($imports),
                'scheduledtime' => (int) $schedule->scheduled_time,
                'scheduledtimeformatted' => userdate($schedule->scheduled_time),
                'status' => $schedule->status,
                'statusformatted' => self::format_status($schedule->status),
                'startedat' => (int) ($schedule->started_at ?? 0),
                'completedat' => (int) ($schedule->completed_at ?? 0),
                'successcount' => (int) ($resultdata['success'] ?? 0),
                'failcount' => (int) ($resultdata['failed'] ?? 0),
                'cancancel' => $schedule->status === 'pending',
            ];
        }

        return ['schedules' => $result, 'count' => count($result)];
    }

    /**
     * Format status for display.
     *
     * @param string $status Raw status
     * @return string Formatted status
     */
    protected static function format_status(string $status): string {
        switch ($status) {
            case 'pending':
                return get_string('status_pending', 'local_reuseunit');
            case 'running':
                return get_string('status_running', 'local_reuseunit');
            case 'completed':
                return get_string('status_completed', 'local_reuseunit');
            case 'completed_with_errors':
                return get_string('status_completedwitherrors', 'local_reuseunit');
            case 'cancelled':
                return get_string('status_cancelled', 'local_reuseunit');
            default:
                return $status;
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'schedules' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Schedule ID'),
                    'destcourseid' => new external_value(PARAM_INT, 'Destination course ID'),
                    'destcoursename' => new external_value(PARAM_TEXT, 'Destination course name'),
                    'importcount' => new external_value(PARAM_INT, 'Number of imports'),
                    'scheduledtime' => new external_value(PARAM_INT, 'Scheduled time'),
                    'scheduledtimeformatted' => new external_value(PARAM_TEXT, 'Formatted scheduled time'),
                    'status' => new external_value(PARAM_ALPHA, 'Status'),
                    'statusformatted' => new external_value(PARAM_TEXT, 'Formatted status'),
                    'startedat' => new external_value(PARAM_INT, 'Started at'),
                    'completedat' => new external_value(PARAM_INT, 'Completed at'),
                    'successcount' => new external_value(PARAM_INT, 'Success count'),
                    'failcount' => new external_value(PARAM_INT, 'Fail count'),
                    'cancancel' => new external_value(PARAM_BOOL, 'Can cancel'),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Total count'),
        ]);
    }
}
