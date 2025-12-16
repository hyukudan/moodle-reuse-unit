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
use context_course;
use local_reuseunit\section_helper;

/**
 * External function to rollback a previous sync operation.
 *
 * Note: Full rollback (restoring deleted modules) is not supported yet.
 * This marks the sync as rolled back and can remove modules that were added.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rollback_sync extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'historyid' => new external_value(PARAM_INT, 'Sync history record ID'),
            'removeadded' => new external_value(PARAM_BOOL, 'Remove modules that were added', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Rollback a previous sync operation.
     *
     * @param int $historyid History record ID
     * @param bool $removeadded Whether to remove modules that were added
     * @return array Result
     */
    public static function execute(int $historyid, bool $removeadded = true): array {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'historyid' => $historyid,
            'removeadded' => $removeadded,
        ]);

        // Check if rollback is possible.
        $canrollback = section_helper::can_rollback($params['historyid']);
        if (!$canrollback['possible']) {
            return [
                'success' => false,
                'message' => $canrollback['reason'],
                'removed_count' => 0,
            ];
        }

        // Get the history record.
        $history = section_helper::get_sync_history_record($params['historyid']);

        // Get the link.
        $link = $DB->get_record('local_reuseunit_links', ['id' => $history->linkid]);

        // Check course access.
        $context = context_course::instance($link->courseid);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        $removedcount = 0;

        // Parse changes data.
        $changesdata = json_decode($history->changes_data, true) ?: [];

        // Remove modules that were added during the sync.
        if ($params['removeadded'] && !empty($changesdata['added'])) {
            // Get current mappings.
            $mappings = section_helper::get_synced_modules($link->id);

            foreach ($changesdata['added'] as $added) {
                // Find the mapping for this added module.
                foreach ($mappings as $mapping) {
                    if ($mapping->source_cmid == $added['source_cmid']) {
                        // Check if the module still exists.
                        $cm = $DB->get_record('course_modules', ['id' => $mapping->dest_cmid]);
                        if ($cm) {
                            try {
                                course_delete_module($mapping->dest_cmid);
                                section_helper::delete_module_mappings($link->id, [$mapping->dest_cmid]);
                                $removedcount++;
                            } catch (\Exception $e) {
                                // Module may have already been deleted.
                            }
                        }
                        break;
                    }
                }
            }
        }

        // Mark the history record as rolled back.
        section_helper::mark_history_rolled_back($params['historyid']);

        // Rebuild course cache.
        rebuild_course_cache($link->courseid, true);

        return [
            'success' => true,
            'message' => get_string('rollback_completed', 'local_reuseunit', $removedcount),
            'removed_count' => $removedcount,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether rollback was successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'removed_count' => new external_value(PARAM_INT, 'Number of modules removed'),
        ]);
    }
}
