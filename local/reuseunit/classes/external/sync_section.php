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

/**
 * External function to sync a linked section with its template.
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
            'mode' => new external_value(PARAM_ALPHA, 'Sync mode: replace or merge', VALUE_DEFAULT, 'replace'),
            'includenew' => new external_value(PARAM_BOOL, 'Include new activities not in original import', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Sync a linked section with its template.
     *
     * @param int $linkid Link ID
     * @param string $mode Sync mode
     * @param bool $includenew Include new activities not in original partial import
     * @return array Result
     */
    public static function execute(int $linkid, string $mode = 'replace', bool $includenew = false): array {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'linkid' => $linkid,
            'mode' => $mode,
            'includenew' => $includenew,
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

        // Check if this is a partial import.
        $ispartialimport = !empty($link->partial_import) && !empty($link->imported_cmids);
        $importedcmids = [];
        if ($ispartialimport) {
            $importedcmids = json_decode($link->imported_cmids, true) ?: [];
        }

        try {
            // If replace mode, delete existing content (respecting partial import).
            if ($params['mode'] === 'replace') {
                // Get all course modules in the section.
                $cms = $DB->get_records('course_modules', [
                    'course' => $link->courseid,
                    'section' => $destsection->id,
                ]);

                // Delete each module (but keep local additions if partial import).
                foreach ($cms as $cm) {
                    // In partial import mode, we track which modules came from the template.
                    // For now, delete all - the restore will add back the synced content.
                    course_delete_module($cm->id);
                }
            }

            // Perform backup of source section.
            $bc = new \backup_controller(
                \backup::TYPE_1SECTION,
                $sourcesection->id,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_SAMESITE,
                $USER->id
            );

            $bc->get_plan()->get_setting('users')->set_value(false);

            // For partial sync, filter activities to only those originally imported.
            if ($ispartialimport && !$params['includenew']) {
                $tasks = $bc->get_plan()->get_tasks();
                foreach ($tasks as $task) {
                    if ($task instanceof \backup_activity_task) {
                        $cmid = $task->get_moduleid();
                        // Only include if this cmid was in the original import.
                        if (!in_array($cmid, $importedcmids)) {
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
            }

            $bc->execute_plan();
            $results = $bc->get_results();
            $backupid = $bc->get_backupid();
            $bc->destroy();

            // Perform restore to destination.
            $rc = new \restore_controller(
                $backupid,
                $link->courseid,
                \backup::INTERACTIVE_NO,
                \backup::MODE_SAMESITE,
                $USER->id,
                \backup::TARGET_EXISTING_ADDING
            );

            // Configure restore settings.
            $rc->get_plan()->get_setting('overwrite_conf')->set_value(false);
            $rc->get_plan()->get_setting('users')->set_value(false);

            $rc->execute_precheck();
            $rc->execute_plan();
            $rc->destroy();

            // Update link record.
            $link->template_version = $template->current_version ?? 1;
            $link->last_synced = time();
            $link->timemodified = time();

            // If includenew was used, update the imported_cmids with all current cmids from source.
            if ($ispartialimport && $params['includenew']) {
                $sourcemodinfo = get_fast_modinfo($sourcecourse);
                $allsourcecmids = [];
                if (isset($sourcemodinfo->sections[$sourcesection->section])) {
                    $allsourcecmids = $sourcemodinfo->sections[$sourcesection->section];
                }
                $link->imported_cmids = json_encode($allsourcecmids);
                $link->partial_import = 0; // No longer partial since we included all new.
            }

            $DB->update_record('local_reuseunit_links', $link);

            $message = get_string('synccompleted', 'local_reuseunit');
            if ($ispartialimport && !$params['includenew']) {
                $message .= ' (' . count($importedcmids) . ' elementos sincronizados)';
            }

            return [
                'success' => true,
                'message' => $message,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => get_string('syncfailed', 'local_reuseunit') . ': ' . $e->getMessage(),
            ];
        }
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
        ]);
    }
}
