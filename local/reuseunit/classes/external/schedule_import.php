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
use context_course;

/**
 * External function to schedule an import for later execution.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class schedule_import extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'destcourseid' => new external_value(PARAM_INT, 'Destination course ID'),
            'imports' => new external_multiple_structure(
                new external_single_structure([
                    'courseid' => new external_value(PARAM_INT, 'Source course ID'),
                    'sectionid' => new external_value(PARAM_INT, 'Source section ID'),
                    'sectionname' => new external_value(PARAM_TEXT, 'Section name', VALUE_DEFAULT, ''),
                ])
            ),
            'scheduledtime' => new external_value(PARAM_INT, 'Unix timestamp to execute', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Schedule an import for later execution.
     *
     * @param int $destcourseid Destination course ID
     * @param array $imports Import data
     * @param int $scheduledtime Scheduled execution time
     * @return array Result
     */
    public static function execute(int $destcourseid, array $imports, int $scheduledtime = 0): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'destcourseid' => $destcourseid,
            'imports' => $imports,
            'scheduledtime' => $scheduledtime,
        ]);

        // Check destination course access.
        $context = context_course::instance($params['destcourseid']);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        // Default to now if no time specified.
        if ($params['scheduledtime'] <= 0) {
            $params['scheduledtime'] = time();
        }

        // Validate scheduled time is in the future (or now).
        if ($params['scheduledtime'] < time() - 60) { // Allow 60 second buffer.
            throw new \moodle_exception('error_pasttime', 'local_reuseunit');
        }

        // Create scheduled import record.
        $schedule = new \stdClass();
        $schedule->userid = $USER->id;
        $schedule->dest_courseid = $params['destcourseid'];
        $schedule->import_data = json_encode($params['imports']);
        $schedule->scheduled_time = $params['scheduledtime'];
        $schedule->status = 'pending';
        $schedule->timecreated = time();
        $schedule->timemodified = time();

        $scheduleid = $DB->insert_record('local_reuseunit_scheduled', $schedule);

        // Create adhoc task.
        $task = new \local_reuseunit\task\scheduled_import();
        $task->set_custom_data(['scheduleid' => $scheduleid]);
        $task->set_userid($USER->id);
        $task->set_next_run_time($params['scheduledtime']);

        \core\task\manager::queue_adhoc_task($task);

        return [
            'success' => true,
            'scheduleid' => $scheduleid,
            'scheduledtime' => $params['scheduledtime'],
            'scheduledtimeformatted' => userdate($params['scheduledtime']),
            'message' => get_string('importscheduled', 'local_reuseunit'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether scheduling was successful'),
            'scheduleid' => new external_value(PARAM_INT, 'Schedule ID'),
            'scheduledtime' => new external_value(PARAM_INT, 'Scheduled time'),
            'scheduledtimeformatted' => new external_value(PARAM_TEXT, 'Formatted scheduled time'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
