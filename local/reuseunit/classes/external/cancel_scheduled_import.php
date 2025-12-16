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
use external_value;
use context_system;

/**
 * External function to cancel a scheduled import.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cancel_scheduled_import extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'scheduleid' => new external_value(PARAM_INT, 'Schedule ID'),
        ]);
    }

    /**
     * Cancel a scheduled import.
     *
     * @param int $scheduleid Schedule ID
     * @return array Result
     */
    public static function execute(int $scheduleid): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'scheduleid' => $scheduleid,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        // Get the schedule.
        $schedule = $DB->get_record('local_reuseunit_scheduled', ['id' => $params['scheduleid']], '*', MUST_EXIST);

        // Only owner can cancel.
        if ($schedule->userid != $USER->id) {
            throw new \moodle_exception('error_nopermission', 'local_reuseunit');
        }

        // Can only cancel pending schedules.
        if ($schedule->status !== 'pending') {
            throw new \moodle_exception('error_cannotcancel', 'local_reuseunit');
        }

        // Update status.
        $schedule->status = 'cancelled';
        $schedule->timemodified = time();
        $DB->update_record('local_reuseunit_scheduled', $schedule);

        return [
            'success' => true,
            'message' => get_string('schedulecancelled', 'local_reuseunit'),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether cancellation was successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
