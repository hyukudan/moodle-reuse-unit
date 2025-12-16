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

/**
 * Scheduled task for cleaning up old Reuse Unit data.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup', 'local_reuseunit');
    }

    /**
     * Execute the cleanup task.
     */
    public function execute() {
        global $DB;

        // Check if auto cleanup is enabled.
        if (!get_config('local_reuseunit', 'autocleanup')) {
            mtrace('Auto cleanup is disabled.');
            return;
        }

        $cleanupdays = (int)get_config('local_reuseunit', 'cleanup_age');
        if ($cleanupdays < 1) {
            $cleanupdays = 365; // Default to 1 year.
        }

        $cutoff = time() - ($cleanupdays * 24 * 60 * 60);
        $totaldeleted = 0;

        mtrace('Starting Reuse Unit cleanup...');
        mtrace("Cleaning up data older than {$cleanupdays} days.");

        // Clean up completed scheduled imports.
        $where = "status IN ('completed', 'cancelled', 'failed') AND timecreated < :cutoff";
        $params = ['cutoff' => $cutoff];
        $count = $DB->count_records_select('local_reuseunit_scheduled', $where, $params);
        if ($count > 0) {
            $DB->delete_records_select('local_reuseunit_scheduled', $where, $params);
            mtrace("Deleted {$count} old scheduled imports.");
            $totaldeleted += $count;
        }

        // Clean up old history (keep minimum 90 days).
        $historydays = max($cleanupdays, 90);
        $historycutoff = time() - ($historydays * 24 * 60 * 60);
        $where = "timecreated < :cutoff";
        $params = ['cutoff' => $historycutoff];
        $count = $DB->count_records_select('local_reuseunit_history', $where, $params);
        if ($count > 0) {
            $DB->delete_records_select('local_reuseunit_history', $where, $params);
            mtrace("Deleted {$count} old history records.");
            $totaldeleted += $count;
        }

        // Clean up orphaned favorites (template deleted).
        $sql = "SELECT f.id FROM {local_reuseunit_favorites} f
                LEFT JOIN {local_reuseunit_templates} t ON f.templateid = t.id
                WHERE t.id IS NULL";
        $orphanedfavorites = $DB->get_records_sql($sql);
        $count = count($orphanedfavorites);
        if ($count > 0) {
            $ids = array_keys($orphanedfavorites);
            $DB->delete_records_list('local_reuseunit_favorites', 'id', $ids);
            mtrace("Deleted {$count} orphaned favorites.");
            $totaldeleted += $count;
        }

        // Clean up orphaned links (template deleted).
        $sql = "SELECT l.id FROM {local_reuseunit_links} l
                LEFT JOIN {local_reuseunit_templates} t ON l.templateid = t.id
                WHERE t.id IS NULL";
        $orphanedlinks = $DB->get_records_sql($sql);
        $count = count($orphanedlinks);
        if ($count > 0) {
            $ids = array_keys($orphanedlinks);
            $DB->delete_records_list('local_reuseunit_links', 'id', $ids);
            mtrace("Deleted {$count} orphaned links (template deleted).");
            $totaldeleted += $count;
        }

        // Clean up orphaned links (course deleted).
        $sql = "SELECT l.id FROM {local_reuseunit_links} l
                LEFT JOIN {course} c ON l.courseid = c.id
                WHERE c.id IS NULL";
        $orphanedcourselinks = $DB->get_records_sql($sql);
        $count = count($orphanedcourselinks);
        if ($count > 0) {
            $ids = array_keys($orphanedcourselinks);
            $DB->delete_records_list('local_reuseunit_links', 'id', $ids);
            mtrace("Deleted {$count} orphaned links (course deleted).");
            $totaldeleted += $count;
        }

        // Clean up orphaned template versions (template deleted).
        $sql = "SELECT v.id FROM {local_reuseunit_versions} v
                LEFT JOIN {local_reuseunit_templates} t ON v.templateid = t.id
                WHERE t.id IS NULL";
        $orphanedversions = $DB->get_records_sql($sql);
        $count = count($orphanedversions);
        if ($count > 0) {
            $ids = array_keys($orphanedversions);
            $DB->delete_records_list('local_reuseunit_versions', 'id', $ids);
            mtrace("Deleted {$count} orphaned template versions.");
            $totaldeleted += $count;
        }

        mtrace("Cleanup completed. Total records deleted: {$totaldeleted}");
    }
}
