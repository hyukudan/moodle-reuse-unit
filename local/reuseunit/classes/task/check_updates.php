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
            // Set admin user for the operation.
            $admin = get_admin();
            \core\session\manager::set_user($admin);

            // Determine if we should include new items.
            $includenew = !empty($link->partial_import) ? false : true;

            $result = \local_reuseunit\external\sync_section::execute(
                $link->id,
                'replace',
                $includenew
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
