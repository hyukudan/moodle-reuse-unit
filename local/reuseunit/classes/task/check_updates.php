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

use local_reuseunit\section_helper;
use local_reuseunit\notification_helper;

/**
 * Scheduled task to check for updates on linked sections.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_updates extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_checkupdates', 'local_reuseunit');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting check for linked section updates...');

        // Get all active links.
        $links = $DB->get_records('local_reuseunit_links');
        $checked = 0;
        $updatesfound = 0;

        foreach ($links as $link) {
            // Skip if template doesn't exist anymore.
            $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);
            if (!$template) {
                mtrace("  Link {$link->id}: Template not found, skipping");
                continue;
            }

            // Check for updates.
            $result = section_helper::check_for_updates($link);
            $checked++;

            $now = time();
            $updatedata = ['id' => $link->id, 'last_checked' => $now];

            if ($result['has_updates']) {
                $updatesfound++;
                $updatedata['update_available'] = 1;
                $updatedata['contenthash'] = $result['new_hash'];

                mtrace("  Link {$link->id}: Updates available");

                // Send notification if auto-sync is enabled.
                if (!empty($link->autosync)) {
                    mtrace("    Auto-sync enabled, triggering sync...");
                    $this->perform_autosync($link);
                } else {
                    // Send notification to user about available update.
                    $this->notify_update_available($link, $template, $result['changes']);
                }
            } else {
                $updatedata['update_available'] = 0;
                mtrace("  Link {$link->id}: No updates");
            }

            $DB->update_record('local_reuseunit_links', (object)$updatedata);
        }

        mtrace("Check complete: {$checked} links checked, {$updatesfound} updates found");
    }

    /**
     * Perform automatic synchronization.
     *
     * @param \stdClass $link The link record
     */
    private function perform_autosync(\stdClass $link): void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/reuseunit/classes/external/sync_section.php');

        try {
            // Get the link owner user.
            $user = $DB->get_record('user', ['id' => $link->userid]);
            if (!$user || $user->deleted || $user->suspended) {
                mtrace("    Auto-sync skipped: Link owner unavailable or inactive");
                return;
            }

            // Get the course for capability check.
            $course = $DB->get_record('course', ['id' => $link->courseid]);
            if (!$course) {
                mtrace("    Auto-sync skipped: Course not found");
                return;
            }

            // Get the template for source course capability check.
            $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);
            if (!$template) {
                mtrace("    Auto-sync skipped: Template not found");
                return;
            }

            // Verify user has required capabilities in destination course.
            $destcontext = \context_course::instance($course->id, IGNORE_MISSING);
            if (!$destcontext || !has_capability('local/reuseunit:import', $destcontext, $user)) {
                mtrace("    Auto-sync skipped: User lacks import capability in destination course");
                return;
            }

            // Verify user has required capabilities in source course (if it still exists).
            if ($template->source_courseid) {
                $sourcecontext = \context_course::instance($template->source_courseid, IGNORE_MISSING);
                if (!$sourcecontext || !has_capability('local/reuseunit:export', $sourcecontext, $user)) {
                    mtrace("    Auto-sync skipped: User lacks export capability in source course");
                    return;
                }
            }

            // Set user context for the operation (using link owner, not admin).
            \core\session\manager::set_user($user);

            // Determine sync mode based on granular autosync settings.
            $addnew = !empty($link->autosync_add);
            $updateexisting = !empty($link->autosync_update);
            $removeold = !empty($link->autosync_remove);

            // Only proceed if at least one action is enabled.
            if (!$addnew && !$updateexisting && !$removeold) {
                mtrace("    Auto-sync skipped: No sync actions enabled");
                return;
            }

            $result = \local_reuseunit\external\sync_section::execute(
                $link->id,
                'selective',
                $addnew
            );

            if ($result['success']) {
                mtrace("    Auto-sync completed successfully");

                // Update link.
                $DB->update_record('local_reuseunit_links', (object)[
                    'id' => $link->id,
                    'update_available' => 0,
                    'last_synced' => time(),
                ]);

                // Notify user.
                $this->notify_autosync_completed($link);
            } else {
                mtrace("    Auto-sync failed: " . $result['message']);
            }
        } catch (\Exception $e) {
            mtrace("    Auto-sync error: " . $e->getMessage());
            debugging('Auto-sync exception for link ' . $link->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Send notification about available update.
     *
     * @param \stdClass $link The link record
     * @param \stdClass $template The template record
     * @param array $changes The detected changes
     */
    private function notify_update_available(\stdClass $link, \stdClass $template, array $changes): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $link->userid]);
        if (!$user) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $link->courseid]);
        if (!$course) {
            return;
        }

        // Build changes summary.
        $changessummary = [];
        if (!empty($changes['added'])) {
            $changessummary[] = count($changes['added']) . ' new';
        }
        if (!empty($changes['modified'])) {
            $changessummary[] = count($changes['modified']) . ' modified';
        }
        if (!empty($changes['removed'])) {
            $changessummary[] = count($changes['removed']) . ' removed';
        }

        $messagedata = (object)[
            'templatename' => $template->name,
            'coursename' => $course->fullname,
            'changes' => implode(', ', $changessummary),
        ];

        notification_helper::send(
            $user->id,
            'templateupdated',
            get_string('notification_updateavailable_subject', 'local_reuseunit'),
            get_string('notification_updateavailable_message', 'local_reuseunit', $messagedata),
            new \moodle_url('/course/view.php', ['id' => $course->id])
        );
    }

    /**
     * Send notification about completed auto-sync.
     *
     * @param \stdClass $link The link record
     */
    private function notify_autosync_completed(\stdClass $link): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $link->userid]);
        if (!$user) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $link->courseid]);
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);

        if (!$course || !$template) {
            return;
        }

        $messagedata = (object)[
            'templatename' => $template->name,
            'coursename' => $course->fullname,
        ];

        notification_helper::send(
            $user->id,
            'templateupdated',
            get_string('notification_autosynccompleted_subject', 'local_reuseunit'),
            get_string('notification_autosynccompleted_message', 'local_reuseunit', $messagedata),
            new \moodle_url('/course/view.php', ['id' => $course->id])
        );
    }
}
