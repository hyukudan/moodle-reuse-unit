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
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_course;
use backup_controller;
use backup;
use restore_controller;
use restore_dbops;
use local_reuseunit\section_helper;

/**
 * External function to import a section from one course to another.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_section extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sourcecourseid' => new external_value(PARAM_INT, 'Source course ID'),
            'sourcesectionid' => new external_value(PARAM_INT, 'Source section ID'),
            'destcourseid' => new external_value(PARAM_INT, 'Destination course ID'),
            'position' => new external_value(PARAM_TEXT, 'Position: start, end, or after:X', VALUE_DEFAULT, 'end'),
            'newsectionname' => new external_value(PARAM_TEXT, 'New section name (optional)', VALUE_DEFAULT, ''),
            'selectedcmids' => new external_value(PARAM_TEXT, 'JSON array of selected cmids for partial import', VALUE_DEFAULT, ''),
            'resetdates' => new external_value(PARAM_BOOL, 'Reset activity dates', VALUE_DEFAULT, true),
            'includerestrictions' => new external_value(PARAM_BOOL, 'Include access restrictions', VALUE_DEFAULT, false),
            'includegradebook' => new external_value(PARAM_BOOL, 'Include gradebook structure', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Import a section from one course to another.
     *
     * @param int $sourcecourseid Source course ID
     * @param int $sourcesectionid Source section ID
     * @param int $destcourseid Destination course ID
     * @param string $position Position to insert
     * @param string $newsectionname New section name
     * @param string $selectedcmids JSON array of selected cmids
     * @param bool $resetdates Whether to reset dates
     * @param bool $includerestrictions Whether to include restrictions
     * @param bool $includegradebook Whether to include gradebook
     * @return array Import result
     */
    public static function execute(
        int $sourcecourseid,
        int $sourcesectionid,
        int $destcourseid,
        string $position = 'end',
        string $newsectionname = '',
        string $selectedcmids = '',
        bool $resetdates = true,
        bool $includerestrictions = false,
        bool $includegradebook = false
    ): array {
        global $DB, $USER, $CFG;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'sourcecourseid' => $sourcecourseid,
            'sourcesectionid' => $sourcesectionid,
            'destcourseid' => $destcourseid,
            'position' => $position,
            'newsectionname' => $newsectionname,
            'selectedcmids' => $selectedcmids,
            'resetdates' => $resetdates,
            'includerestrictions' => $includerestrictions,
            'includegradebook' => $includegradebook,
        ]);

        // Parse selected cmids for partial import.
        $selectedcmidsarray = [];
        $ispartialimport = false;
        if (!empty($params['selectedcmids'])) {
            $selectedcmidsarray = section_helper::safe_json_decode($params['selectedcmids'], []);
            if (is_array($selectedcmidsarray) && count($selectedcmidsarray) > 0) {
                $ispartialimport = true;
            }
        }

        // Get source course and context.
        $sourcecourse = $DB->get_record('course', ['id' => $params['sourcecourseid']], '*', MUST_EXIST);
        $sourcecontext = context_course::instance($sourcecourse->id);
        self::validate_context($sourcecontext);
        require_capability('local/reuseunit:export', $sourcecontext);

        // Get destination course and context.
        $destcourse = $DB->get_record('course', ['id' => $params['destcourseid']], '*', MUST_EXIST);
        $destcontext = context_course::instance($destcourse->id);
        self::validate_context($destcontext);
        require_capability('local/reuseunit:import', $destcontext);

        // Get source section info.
        $modinfo = get_fast_modinfo($sourcecourse);
        $sourcesection = null;
        $sourcesectionnum = null;

        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->id == $params['sourcesectionid']) {
                $sourcesection = $section;
                $sourcesectionnum = $section->section;
                break;
            }
        }

        if (!$sourcesection) {
            throw new \moodle_exception('error_sectionnotfound', 'local_reuseunit');
        }

        // Create history record.
        $history = new \stdClass();
        $history->userid = $USER->id;
        $history->source_courseid = $sourcecourse->id;
        $history->source_sectionid = $sourcesection->id;
        $history->source_sectionname = get_section_name($sourcecourse, $sourcesection);
        $history->dest_courseid = $destcourse->id;
        $history->options = json_encode([
            'resetdates' => $params['resetdates'],
            'includerestrictions' => $params['includerestrictions'],
            'includegradebook' => $params['includegradebook'],
            'newsectionname' => $params['newsectionname'],
            'partial_import' => $ispartialimport,
            'selected_cmids' => $selectedcmidsarray,
        ]);
        $history->status = 'running';
        $history->timecreated = time();
        $historyid = $DB->insert_record('local_reuseunit_history', $history);

        try {
            // Perform the import using Moodle's import functionality.
            $result = self::do_import(
                $sourcecourse,
                $sourcesectionnum,
                $destcourse,
                $params['position'],
                $params['newsectionname'],
                $params['resetdates'],
                $params['includerestrictions'],
                $params['includegradebook'],
                $selectedcmidsarray
            );

            // Update history.
            $DB->update_record('local_reuseunit_history', (object)[
                'id' => $historyid,
                'dest_sectionid' => $result['sectionid'],
                'activities_count' => $result['activities'],
                'resources_count' => $result['resources'],
                'status' => 'completed',
                'timecompleted' => time(),
            ]);

            return [
                'success' => true,
                'sectionid' => $result['sectionid'],
                'sectionnum' => $result['sectionnum'],
                'activities' => $result['activities'],
                'resources' => $result['resources'],
                'partial_import' => $ispartialimport,
                'message' => get_string('importcompleted', 'local_reuseunit'),
                'courseurl' => (new \moodle_url('/course/view.php', [
                    'id' => $destcourse->id,
                    'section' => $result['sectionnum'],
                ]))->out(false),
            ];
        } catch (\Exception $e) {
            // Update history with error.
            $DB->update_record('local_reuseunit_history', (object)[
                'id' => $historyid,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'timecompleted' => time(),
            ]);

            throw $e;
        }
    }

    /**
     * Perform the actual import using Moodle's backup/restore.
     *
     * @param object $sourcecourse Source course
     * @param int $sourcesectionnum Source section number
     * @param object $destcourse Destination course
     * @param string $position Position to insert
     * @param string $newsectionname New section name
     * @param bool $resetdates Reset dates
     * @param bool $includerestrictions Include restrictions
     * @param bool $includegradebook Include gradebook
     * @param array $selectedcmids Array of selected cmids for partial import
     * @return array Result with sectionid, activities, resources
     */
    private static function do_import(
        $sourcecourse,
        $sourcesectionnum,
        $destcourse,
        $position,
        $newsectionname,
        $resetdates,
        $includerestrictions,
        $includegradebook,
        array $selectedcmids = []
    ): array {
        global $USER, $DB, $CFG;

        // Determine if this is a partial import.
        $ispartialimport = !empty($selectedcmids);

        // Use Moodle's import controller (which is a specialized backup+restore).
        // First, we need to create a backup of just the section.

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $sourcecourse->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );

        // Configure backup to only include the specific section.
        $plan = $bc->get_plan();

        // Disable everything first.
        foreach ($plan->get_settings() as $setting) {
            $name = $setting->get_name();
            if (strpos($name, 'setting_root_') === 0) {
                continue;
            }
            // Disable users, user data, etc.
            if (in_array($name, ['users', 'anonymize', 'role_assignments', 'activities', 'blocks',
                'filters', 'comments', 'badges', 'calendarevents', 'userscompletion',
                'logs', 'grade_histories', 'questionbank', 'groups', 'competencies',
                'contentbankcontent', 'legacyfiles'])) {
                if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                    $setting->set_value(0);
                }
            }
        }

        // Enable only activities for the section.
        $tasks = $bc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if ($task instanceof \backup_section_task) {
                $tasksettings = $task->get_settings();
                foreach ($tasksettings as $setting) {
                    if ($setting->get_status() != \backup_setting::NOT_LOCKED) {
                        continue;
                    }
                    $name = $setting->get_name();
                    // Enable only our target section.
                    if (preg_match('/section_(\d+)_included/', $name, $matches)) {
                        $sectionnum = (int)$matches[1];
                        $setting->set_value($sectionnum == $sourcesectionnum ? 1 : 0);
                    }
                }
            }

            // For partial import, exclude activities not in the selection.
            if ($ispartialimport && $task instanceof \backup_activity_task) {
                $cmid = $task->get_moduleid();
                if (!in_array($cmid, $selectedcmids)) {
                    // Exclude this activity from backup.
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
        $results = $bc->get_results();
        $backupid = $bc->get_backupid();
        $bc->destroy();

        // Now restore to destination course.
        $rc = new restore_controller(
            $backupid,
            $destcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id,
            backup::TARGET_CURRENT_ADDING
        );

        // Configure restore options.
        $plan = $rc->get_plan();
        foreach ($plan->get_settings() as $setting) {
            if ($setting->get_status() != \backup_setting::NOT_LOCKED) {
                continue;
            }
            $name = $setting->get_name();
            // Set date offset if needed.
            if ($name === 'keep_roles_and_enrolments') {
                $setting->set_value(0);
            }
            if ($name === 'keep_groups_and_groupings') {
                $setting->set_value(0);
            }
        }

        if (!$rc->execute_precheck()) {
            $errors = $rc->get_precheck_results();
            $rc->destroy();
            throw new \moodle_exception('error_restorefailed', 'local_reuseunit');
        }

        $rc->execute_plan();

        // Get the new section info.
        $info = $rc->get_info();
        $rc->destroy();

        // Rebuild course cache.
        rebuild_course_cache($destcourse->id, true);

        // Get updated modinfo.
        $destmodinfo = get_fast_modinfo($destcourse->id, 0, true);

        // Find the new section (last one if position was 'end').
        $newsection = null;
        $newsectionnum = 0;
        $sections = $destmodinfo->get_section_info_all();

        // For now, assume it's the last section.
        $lastsection = end($sections);
        $newsection = $lastsection;
        $newsectionnum = $lastsection->section;

        // Update section name if provided.
        if (!empty($newsectionname) && $newsection) {
            $DB->update_record('course_sections', (object)[
                'id' => $newsection->id,
                'name' => $newsectionname,
            ]);
        }

        // Count imported content.
        $activities = 0;
        $resources = 0;

        if ($newsection && !empty($destmodinfo->sections[$newsectionnum])) {
            foreach ($destmodinfo->sections[$newsectionnum] as $cmid) {
                $cm = $destmodinfo->cms[$cmid];
                $archetype = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                    $resources++;
                } else {
                    $activities++;
                }
            }
        }

        return [
            'sectionid' => $newsection ? $newsection->id : 0,
            'sectionnum' => $newsectionnum,
            'activities' => $activities,
            'resources' => $resources,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether import was successful'),
            'sectionid' => new external_value(PARAM_INT, 'New section ID'),
            'sectionnum' => new external_value(PARAM_INT, 'New section number'),
            'activities' => new external_value(PARAM_INT, 'Number of activities imported'),
            'resources' => new external_value(PARAM_INT, 'Number of resources imported'),
            'partial_import' => new external_value(PARAM_BOOL, 'Whether this was a partial import'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'courseurl' => new external_value(PARAM_URL, 'URL to the course with imported section'),
        ]);
    }
}
