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
use local_reuseunit\section_helper;

/**
 * External function to get sync history for a linked section.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_sync_history extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'linkid' => new external_value(PARAM_INT, 'Link ID'),
            'limit' => new external_value(PARAM_INT, 'Maximum number of records', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Get sync history for a linked section.
     *
     * @param int $linkid Link ID
     * @param int $limit Maximum number of records
     * @return array History records
     */
    public static function execute(int $linkid, int $limit = 20): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'linkid' => $linkid,
            'limit' => $limit,
        ]);

        // Get the link.
        $link = $DB->get_record('local_reuseunit_links', ['id' => $params['linkid']], '*', MUST_EXIST);

        // Check course access.
        $context = context_course::instance($link->courseid);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        // Get history.
        $history = section_helper::get_sync_history($params['linkid'], $params['limit']);

        // Format for output.
        $result = [];
        foreach ($history as $record) {
            $user = $DB->get_record('user', ['id' => $record->userid], 'id, firstname, lastname');

            // Check if rollback is possible.
            $canrollback = section_helper::can_rollback($record->id);

            $result[] = [
                'id' => $record->id,
                'linkid' => $record->linkid,
                'userid' => $record->userid,
                'username' => $user ? fullname($user) : '',
                'sync_mode' => $record->sync_mode,
                'added_count' => (int)$record->added_count,
                'updated_count' => (int)$record->updated_count,
                'removed_count' => (int)$record->removed_count,
                'preserved_count' => (int)$record->preserved_count,
                'conflict_count' => (int)$record->conflict_count,
                'status' => $record->status,
                'error_message' => $record->error_message ?? '',
                'timecreated' => (int)$record->timecreated,
                'timecreated_formatted' => userdate($record->timecreated),
                'can_rollback' => $canrollback['possible'],
                'rollback_reason' => $canrollback['reason'],
            ];
        }

        return ['history' => $result];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'history' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'History record ID'),
                    'linkid' => new external_value(PARAM_INT, 'Link ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'User full name'),
                    'sync_mode' => new external_value(PARAM_TEXT, 'Sync mode'),
                    'added_count' => new external_value(PARAM_INT, 'Modules added'),
                    'updated_count' => new external_value(PARAM_INT, 'Modules updated'),
                    'removed_count' => new external_value(PARAM_INT, 'Modules removed'),
                    'preserved_count' => new external_value(PARAM_INT, 'Local modules preserved'),
                    'conflict_count' => new external_value(PARAM_INT, 'Conflicts resolved'),
                    'status' => new external_value(PARAM_TEXT, 'Status'),
                    'error_message' => new external_value(PARAM_TEXT, 'Error message if failed'),
                    'timecreated' => new external_value(PARAM_INT, 'Timestamp'),
                    'timecreated_formatted' => new external_value(PARAM_TEXT, 'Formatted time'),
                    'can_rollback' => new external_value(PARAM_BOOL, 'Whether rollback is possible'),
                    'rollback_reason' => new external_value(PARAM_TEXT, 'Reason if rollback not possible'),
                ])
            ),
        ]);
    }
}
