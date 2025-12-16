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
 * External function to bulk sync all linked sections for a template.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_sync extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'templateid' => new external_value(PARAM_INT, 'Template ID'),
            'mode' => new external_value(PARAM_ALPHA, 'Sync mode: replace, merge', VALUE_DEFAULT, 'merge'),
            'linkids' => new external_value(PARAM_TEXT, 'JSON array of specific link IDs to sync (empty for all)', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Bulk sync all linked sections for a template.
     *
     * @param int $templateid Template ID
     * @param string $mode Sync mode
     * @param string $linkids JSON array of link IDs
     * @return array Results
     */
    public static function execute(int $templateid, string $mode = 'merge', string $linkids = ''): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'templateid' => $templateid,
            'mode' => $mode,
            'linkids' => $linkids,
        ]);

        // Get the template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Check source course access.
        $sourcecontext = context_course::instance($template->source_courseid);
        self::validate_context($sourcecontext);
        require_capability('local/reuseunit:export', $sourcecontext);

        // Parse link IDs if provided.
        $specificlinkids = null;
        if (!empty($params['linkids'])) {
            $specificlinkids = json_decode($params['linkids'], true);
        }

        // Get all links for this template.
        $links = section_helper::get_template_links($params['templateid']);

        // Filter by specific link IDs if provided.
        if ($specificlinkids !== null) {
            $links = array_filter($links, function($link) use ($specificlinkids) {
                return in_array($link->id, $specificlinkids);
            });
        }

        $results = [];
        $successcount = 0;
        $failcount = 0;

        foreach ($links as $link) {
            // Check destination course access.
            try {
                $destcontext = context_course::instance($link->courseid);
                self::validate_context($destcontext);
                require_capability('local/reuseunit:import', $destcontext);
            } catch (\Exception $e) {
                $results[] = [
                    'linkid' => $link->id,
                    'courseid' => $link->courseid,
                    'success' => false,
                    'message' => get_string('error_nopermission', 'local_reuseunit'),
                    'added' => 0,
                    'updated' => 0,
                    'removed' => 0,
                ];
                $failcount++;
                continue;
            }

            // Get sync preview.
            $preview = section_helper::get_sync_preview($link->id);

            // Skip if no changes.
            if (!$preview['has_changes']) {
                $results[] = [
                    'linkid' => $link->id,
                    'courseid' => $link->courseid,
                    'success' => true,
                    'message' => get_string('syncpreview_nochanges', 'local_reuseunit'),
                    'added' => 0,
                    'updated' => 0,
                    'removed' => 0,
                ];
                continue;
            }

            // Skip if there are conflicts (require manual resolution).
            if ($preview['has_conflicts']) {
                $results[] = [
                    'linkid' => $link->id,
                    'courseid' => $link->courseid,
                    'success' => false,
                    'message' => get_string('bulk_sync_has_conflicts', 'local_reuseunit'),
                    'added' => 0,
                    'updated' => 0,
                    'removed' => 0,
                ];
                $failcount++;
                continue;
            }

            // Perform sync.
            try {
                $syncresult = sync_section::execute(
                    $link->id,
                    $params['mode'],
                    false, // includenew
                    '', // selectedadded
                    '', // selectedmodified
                    '', // selectedremoved
                    '', // conflictresolutions
                    true // preservelocal
                );

                $results[] = [
                    'linkid' => $link->id,
                    'courseid' => $link->courseid,
                    'success' => $syncresult['success'],
                    'message' => $syncresult['message'],
                    'added' => $syncresult['added'],
                    'updated' => $syncresult['updated'],
                    'removed' => $syncresult['removed'],
                ];

                if ($syncresult['success']) {
                    $successcount++;
                } else {
                    $failcount++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'linkid' => $link->id,
                    'courseid' => $link->courseid,
                    'success' => false,
                    'message' => $e->getMessage(),
                    'added' => 0,
                    'updated' => 0,
                    'removed' => 0,
                ];
                $failcount++;
            }
        }

        return [
            'success' => $failcount === 0,
            'total' => count($links),
            'success_count' => $successcount,
            'fail_count' => $failcount,
            'results' => $results,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether all syncs were successful'),
            'total' => new external_value(PARAM_INT, 'Total number of links'),
            'success_count' => new external_value(PARAM_INT, 'Number of successful syncs'),
            'fail_count' => new external_value(PARAM_INT, 'Number of failed syncs'),
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'linkid' => new external_value(PARAM_INT, 'Link ID'),
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'success' => new external_value(PARAM_BOOL, 'Whether sync was successful'),
                    'message' => new external_value(PARAM_TEXT, 'Result message'),
                    'added' => new external_value(PARAM_INT, 'Modules added'),
                    'updated' => new external_value(PARAM_INT, 'Modules updated'),
                    'removed' => new external_value(PARAM_INT, 'Modules removed'),
                ])
            ),
        ]);
    }
}
