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

namespace local_reuseunit\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use local_reuseunit\notification_helper;

/**
 * Adhoc task for scheduled section imports.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduled_import extends adhoc_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_scheduledimport', 'local_reuseunit');
    }

    /**
     * Execute the scheduled import task.
     */
    public function execute() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $data = $this->get_custom_data();

        if (empty($data->scheduleid)) {
            mtrace('No schedule ID provided');
            return;
        }

        // Get the scheduled import record.
        $schedule = $DB->get_record('local_reuseunit_scheduled', ['id' => $data->scheduleid]);

        if (!$schedule) {
            mtrace('Schedule record not found');
            return;
        }

        // Update status to running.
        $schedule->status = 'running';
        $schedule->started_at = time();
        $DB->update_record('local_reuseunit_scheduled', $schedule);

        mtrace("Starting scheduled import {$schedule->id}...");

        $successcount = 0;
        $failcount = 0;
        $importdetails = [];

        // Decode import data.
        $imports = json_decode($schedule->import_data, true);

        if (empty($imports)) {
            mtrace('No imports to process');
            $schedule->status = 'completed';
            $schedule->completed_at = time();
            $DB->update_record('local_reuseunit_scheduled', $schedule);
            return;
        }

        foreach ($imports as $import) {
            try {
                mtrace("Processing import: section {$import['sectionid']} from course {$import['courseid']}");

                // Get source info.
                $sourcecourse = $DB->get_record('course', ['id' => $import['courseid']], '*', MUST_EXIST);
                $sourcesection = $DB->get_record('course_sections', ['id' => $import['sectionid']], '*', MUST_EXIST);
                $destcourse = $DB->get_record('course', ['id' => $schedule->dest_courseid], '*', MUST_EXIST);

                // Perform backup.
                $bc = new \backup_controller(
                    \backup::TYPE_1SECTION,
                    $sourcesection->id,
                    \backup::FORMAT_MOODLE,
                    \backup::INTERACTIVE_NO,
                    \backup::MODE_SAMESITE,
                    $schedule->userid
                );

                $bc->get_plan()->get_setting('users')->set_value(false);
                $bc->execute_plan();
                $backupid = $bc->get_backupid();
                $bc->destroy();

                // Perform restore.
                $rc = new \restore_controller(
                    $backupid,
                    $schedule->dest_courseid,
                    \backup::INTERACTIVE_NO,
                    \backup::MODE_SAMESITE,
                    $schedule->userid,
                    \backup::TARGET_EXISTING_ADDING
                );

                $rc->get_plan()->get_setting('overwrite_conf')->set_value(false);
                $rc->get_plan()->get_setting('users')->set_value(false);

                $rc->execute_precheck();
                $rc->execute_plan();
                $rc->destroy();

                $successcount++;
                $importdetails[] = [
                    'sectionname' => $sourcesection->name ?: get_string('section', 'local_reuseunit') . ' ' . $sourcesection->section,
                    'status' => 'success',
                ];

                mtrace("  Success");

            } catch (\Exception $e) {
                $failcount++;
                $importdetails[] = [
                    'sectionname' => $import['sectionname'] ?? 'Unknown',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                mtrace("  Failed: " . $e->getMessage());
            }
        }

        // Update schedule record.
        $schedule->status = ($failcount > 0) ? 'completed_with_errors' : 'completed';
        $schedule->completed_at = time();
        $schedule->result_data = json_encode([
            'success' => $successcount,
            'failed' => $failcount,
            'details' => $importdetails,
        ]);
        $DB->update_record('local_reuseunit_scheduled', $schedule);

        // Send notification.
        $taskdata = new \stdClass();
        $taskdata->destcourseid = $schedule->dest_courseid;
        $taskdata->sectionsimported = $successcount;
        $taskdata->status = ($failcount > 0)
            ? get_string('completedwitherrors', 'local_reuseunit')
            : get_string('completed', 'local_reuseunit');

        notification_helper::notify_scheduled_import_completed($schedule->userid, $taskdata);

        mtrace("Scheduled import completed. Success: {$successcount}, Failed: {$failcount}");
    }
}
