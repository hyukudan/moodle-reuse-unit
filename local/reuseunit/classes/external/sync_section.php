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
 * External function to sync a linked section with its template.
 *
 * Supports selective synchronization:
 * - Choose which new modules to add
 * - Choose which modified modules to update
 * - Choose which removed modules to delete
 * - Local modules (added in destination) are always preserved
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_section extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'linkid' => new external_value(PARAM_INT, 'Link ID'),
            'mode' => new external_value(PARAM_ALPHA, 'Sync mode: replace, merge, or selective', VALUE_DEFAULT, 'replace'),
            'includenew' => new external_value(PARAM_BOOL, 'Include new activities not in original import', VALUE_DEFAULT, false),
            'selectedadded' => new external_value(PARAM_TEXT, 'JSON array of source cmids to add (for selective mode)', VALUE_DEFAULT, ''),
            'selectedmodified' => new external_value(PARAM_TEXT, 'JSON array of source cmids to update (for selective mode)', VALUE_DEFAULT, ''),
            'selectedremoved' => new external_value(PARAM_TEXT, 'JSON array of dest cmids to remove (for selective mode)', VALUE_DEFAULT, ''),
            'preservelocal' => new external_value(PARAM_BOOL, 'Preserve local modules not from template', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Sync a linked section with its template.
     *
     * @param int $linkid Link ID
     * @param string $mode Sync mode: replace, merge, or selective
     * @param bool $includenew Include new activities not in original partial import
     * @param string $selectedadded JSON array of source cmids to add
     * @param string $selectedmodified JSON array of source cmids to update
     * @param string $selectedremoved JSON array of dest cmids to remove
     * @param bool $preservelocal Preserve local modules
     * @return array Result
     */
    public static function execute(
        int $linkid,
        string $mode = 'replace',
        bool $includenew = false,
        string $selectedadded = '',
        string $selectedmodified = '',
        string $selectedremoved = '',
        bool $preservelocal = true
    ): array {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/course/lib.php');

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'linkid' => $linkid,
            'mode' => $mode,
            'includenew' => $includenew,
            'selectedadded' => $selectedadded,
            'selectedmodified' => $selectedmodified,
            'selectedremoved' => $selectedremoved,
            'preservelocal' => $preservelocal,
        ]);

        // Get the link.
        $link = $DB->get_record('local_reuseunit_links', ['id' => $params['linkid']], '*', MUST_EXIST);

        // Check course access.
        $context = context_course::instance($link->courseid);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        // Get the template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid], '*', MUST_EXIST);

        // Get source section info.
        $sourcecourse = $DB->get_record('course', ['id' => $template->source_courseid]);
        $sourcesection = $DB->get_record('course_sections', ['id' => $template->source_sectionid]);

        if (!$sourcecourse || !$sourcesection) {
            throw new \moodle_exception('error_sourcenotfound', 'local_reuseunit');
        }

        // Get destination section.
        $destsection = $DB->get_record('course_sections', ['id' => $link->sectionid], '*', MUST_EXIST);

        // Parse selective parameters.
        $addcmids = !empty($params['selectedadded']) ? json_decode($params['selectedadded'], true) : null;
        $updatecmids = !empty($params['selectedmodified']) ? json_decode($params['selectedmodified'], true) : null;
        $removecmids = !empty($params['selectedremoved']) ? json_decode($params['selectedremoved'], true) : null;

        // Determine if selective mode.
        $isselective = $params['mode'] === 'selective' ||
                       $addcmids !== null ||
                       $updatecmids !== null ||
                       $removecmids !== null;

        // Get sync preview to understand current state.
        $preview = section_helper::get_sync_preview($params['linkid']);

        // Get template dest cmids (modules that came from template).
        $templateDestCmids = section_helper::get_template_dest_cmids($params['linkid']);

        // Stats for reporting.
        $stats = [
            'added' => 0,
            'updated' => 0,
            'removed' => 0,
            'preserved' => 0,
        ];

        try {
            // Step 1: Handle removals (modules no longer in template).
            if ($params['mode'] === 'replace' || $removecmids !== null) {
                foreach ($preview['removed'] as $removed) {
                    $destcmid = $removed['dest_cmid'];

                    // In selective mode, only remove if explicitly selected.
                    if ($removecmids !== null && !in_array($destcmid, $removecmids)) {
                        continue;
                    }

                    // Delete the module.
                    course_delete_module($destcmid);
                    section_helper::delete_module_mappings($params['linkid'], [$destcmid]);
                    $stats['removed']++;
                }
            }

            // Step 2: Handle updates (modules that have changed).
            $cmidsToUpdate = [];
            if ($params['mode'] === 'replace' || $updatecmids !== null) {
                foreach ($preview['modified'] as $modified) {
                    $sourcecmid = $modified['source_cmid'];
                    $destcmid = $modified['dest_cmid'];

                    // In selective mode, only update if explicitly selected.
                    if ($updatecmids !== null && !in_array($sourcecmid, $updatecmids)) {
                        continue;
                    }

                    // Delete the old module.
                    course_delete_module($destcmid);
                    section_helper::delete_module_mappings($params['linkid'], [$destcmid]);

                    // Mark for re-import.
                    $cmidsToUpdate[] = $sourcecmid;
                    $stats['updated']++;
                }
            }

            // Step 3: Determine which modules to add.
            $cmidsToAdd = [];
            foreach ($preview['added'] as $added) {
                $sourcecmid = $added['cmid'];

                // In selective mode, only add if explicitly selected.
                if ($addcmids !== null && !in_array($sourcecmid, $addcmids)) {
                    continue;
                }

                // In merge mode or if includenew is false, skip new modules unless selected.
                if ($params['mode'] === 'merge' && $addcmids === null && !$params['includenew']) {
                    continue;
                }

                $cmidsToAdd[] = $sourcecmid;
                $stats['added']++;
            }

            // Combine modules to import (updates + adds).
            $cmidsToImport = array_merge($cmidsToUpdate, $cmidsToAdd);

            // Step 4: Import the selected modules.
            $newMappings = [];
            if (!empty($cmidsToImport)) {
                $newMappings = self::import_modules(
                    $sourcesection->id,
                    $template->source_courseid,
                    $link->courseid,
                    $destsection->id,
                    $cmidsToImport,
                    $USER->id
                );
            }

            // Step 5: Save module mappings.
            if (!empty($newMappings)) {
                section_helper::save_module_mappings($params['linkid'], $newMappings);
            }

            // Count preserved local modules.
            $stats['preserved'] = count($preview['local']);

            // Step 6: Update link record.
            $link->template_version = $template->current_version ?? 1;
            $link->last_synced = time();
            $link->timemodified = time();
            $link->update_available = 0;
            $link->last_checked = time();

            // Update imported_cmids if includenew was used.
            $ispartialimport = !empty($link->partial_import) && !empty($link->imported_cmids);
            $importedcmids = $ispartialimport ? (json_decode($link->imported_cmids, true) ?: []) : [];

            if ($ispartialimport && $params['includenew']) {
                $sourcemodinfo = get_fast_modinfo($sourcecourse);
                $allsourcecmids = [];
                if (isset($sourcemodinfo->sections[$sourcesection->section])) {
                    $allsourcecmids = $sourcemodinfo->sections[$sourcesection->section];
                }
                $link->imported_cmids = json_encode($allsourcecmids);
                $link->partial_import = 0;
                $importedcmids = $allsourcecmids;
            }

            // Calculate and save contenthash.
            $link->contenthash = section_helper::calculate_contenthash(
                $template->source_courseid,
                $template->source_sectionid,
                $ispartialimport ? $importedcmids : []
            );

            $DB->update_record('local_reuseunit_links', $link);

            // Build result message.
            $messageparts = [];
            if ($stats['added'] > 0) {
                $messageparts[] = get_string('sync_added', 'local_reuseunit', $stats['added']);
            }
            if ($stats['updated'] > 0) {
                $messageparts[] = get_string('sync_updated', 'local_reuseunit', $stats['updated']);
            }
            if ($stats['removed'] > 0) {
                $messageparts[] = get_string('sync_removed', 'local_reuseunit', $stats['removed']);
            }
            if ($stats['preserved'] > 0) {
                $messageparts[] = get_string('sync_preserved', 'local_reuseunit', $stats['preserved']);
            }

            $message = get_string('synccompleted', 'local_reuseunit');
            if (!empty($messageparts)) {
                $message .= ': ' . implode(', ', $messageparts);
            }

            return [
                'success' => true,
                'message' => $message,
                'added' => $stats['added'],
                'updated' => $stats['updated'],
                'removed' => $stats['removed'],
                'preserved' => $stats['preserved'],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('syncfailed', 'local_reuseunit') . ': ' . $e->getMessage(),
                'added' => 0,
                'updated' => 0,
                'removed' => 0,
                'preserved' => 0,
            ];
        }
    }

    /**
     * Import specific modules from source to destination.
     *
     * @param int $sourcesectionid Source section ID
     * @param int $sourcecourseid Source course ID
     * @param int $destcourseid Destination course ID
     * @param int $destsectionid Destination section ID
     * @param array $cmids Array of source cmids to import
     * @param int $userid User performing the import
     * @return array Mappings of source_cmid => ['source_cmid', 'dest_cmid', 'modname', 'name']
     */
    private static function import_modules(
        int $sourcesectionid,
        int $sourcecourseid,
        int $destcourseid,
        int $destsectionid,
        array $cmids,
        int $userid
    ): array {
        global $DB;

        // Get modules info before backup.
        $sourcemodinfo = get_fast_modinfo($sourcecourseid);
        $moduleinfo = [];
        foreach ($cmids as $cmid) {
            if (isset($sourcemodinfo->cms[$cmid])) {
                $cm = $sourcemodinfo->cms[$cmid];
                $moduleinfo[$cmid] = [
                    'modname' => $cm->modname,
                    'name' => $cm->name,
                ];
            }
        }

        // Get destination modules before import for tracking new ones.
        $destsection = $DB->get_record('course_sections', ['id' => $destsectionid]);
        $destmodinfobefore = get_fast_modinfo($destcourseid);
        $destcmidsbefore = [];
        if (isset($destmodinfobefore->sections[$destsection->section])) {
            $destcmidsbefore = $destmodinfobefore->sections[$destsection->section];
        }

        // Perform backup of source section.
        $bc = new \backup_controller(
            \backup::TYPE_1SECTION,
            $sourcesectionid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $userid
        );

        $bc->get_plan()->get_setting('users')->set_value(false);

        // Filter to only include selected modules.
        $tasks = $bc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if ($task instanceof \backup_activity_task) {
                $taskmoduleid = $task->get_moduleid();
                if (!in_array($taskmoduleid, $cmids)) {
                    // Exclude this module from backup.
                    $tasksettings = $task->get_settings();
                    foreach ($tasksettings as $setting) {
                        $name = $setting->get_name();
                        if (preg_match('/_included$/', $name) && $setting->get_status() == \backup_setting::NOT_LOCKED) {
                            $setting->set_value(0);
                        }
                    }
                }
            }
        }

        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $bc->destroy();

        // Perform restore to destination.
        $rc = new \restore_controller(
            $backupid,
            $destcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $userid,
            \backup::TARGET_EXISTING_ADDING
        );

        $rc->get_plan()->get_setting('overwrite_conf')->set_value(false);
        $rc->get_plan()->get_setting('users')->set_value(false);

        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        // Rebuild course cache.
        rebuild_course_cache($destcourseid, true);

        // Get destination modules after import.
        $destmodinfoafter = get_fast_modinfo($destcourseid);
        $destsection = $DB->get_record('course_sections', ['id' => $destsectionid]);
        $destcmidsafter = [];
        if (isset($destmodinfoafter->sections[$destsection->section])) {
            $destcmidsafter = $destmodinfoafter->sections[$destsection->section];
        }

        // Find new modules (added by restore).
        $newcmids = array_diff($destcmidsafter, $destcmidsbefore);

        // Build mappings by matching module name and type.
        $mappings = [];
        $destmods = [];
        foreach ($newcmids as $destcmid) {
            $cm = $destmodinfoafter->cms[$destcmid];
            $destmods[$destcmid] = [
                'modname' => $cm->modname,
                'name' => $cm->name,
            ];
        }

        // Match source to dest by name.
        foreach ($cmids as $sourcecmid) {
            if (!isset($moduleinfo[$sourcecmid])) {
                continue;
            }

            $srcmod = $moduleinfo[$sourcecmid];
            $matchedDestCmid = null;

            // Find matching dest module.
            foreach ($destmods as $destcmid => $destmod) {
                if ($destmod['modname'] === $srcmod['modname'] && $destmod['name'] === $srcmod['name']) {
                    $matchedDestCmid = $destcmid;
                    unset($destmods[$destcmid]); // Remove from pool.
                    break;
                }
            }

            if ($matchedDestCmid !== null) {
                $mappings[] = [
                    'source_cmid' => $sourcecmid,
                    'dest_cmid' => $matchedDestCmid,
                    'modname' => $srcmod['modname'],
                    'name' => $srcmod['name'],
                ];
            }
        }

        return $mappings;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether sync was successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'added' => new external_value(PARAM_INT, 'Number of modules added', VALUE_DEFAULT, 0),
            'updated' => new external_value(PARAM_INT, 'Number of modules updated', VALUE_DEFAULT, 0),
            'removed' => new external_value(PARAM_INT, 'Number of modules removed', VALUE_DEFAULT, 0),
            'preserved' => new external_value(PARAM_INT, 'Number of local modules preserved', VALUE_DEFAULT, 0),
        ]);
    }
}
