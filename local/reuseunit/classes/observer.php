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

namespace local_reuseunit;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for local_reuseunit.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle course deletion.
     *
     * Cleans up references to the deleted course in our tables.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;

        $courseid = $event->objectid;

        // Delete links to this course.
        $DB->delete_records('local_reuseunit_links', ['courseid' => $courseid]);

        // Delete scheduled imports for this course.
        $DB->delete_records('local_reuseunit_scheduled', ['dest_courseid' => $courseid]);

        // Update templates that reference this course as source.
        // We don't delete templates, just mark source as unavailable by setting to 0.
        $DB->set_field('local_reuseunit_templates', 'source_courseid', 0, ['source_courseid' => $courseid]);

        // Delete history entries for this course.
        // History entries where this course was either source or target.
        $DB->delete_records('local_reuseunit_history', ['source_courseid' => $courseid]);
        $DB->delete_records('local_reuseunit_history', ['target_courseid' => $courseid]);
    }

    /**
     * Handle user deletion.
     *
     * Cleans up user data from our tables.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;

        // Delete user's favorites.
        $DB->delete_records('local_reuseunit_favorites', ['userid' => $userid]);

        // Delete user's import history.
        $DB->delete_records('local_reuseunit_history', ['userid' => $userid]);

        // Delete user's scheduled imports.
        $DB->delete_records('local_reuseunit_scheduled', ['userid' => $userid]);

        // Handle user's templates - decide based on share level.
        // Personal templates: delete them.
        $DB->delete_records('local_reuseunit_templates', ['userid' => $userid, 'sharelevel' => 'personal']);

        // Category and global templates: transfer to admin or keep (just clear userid).
        // This preserves shared resources. The template will show as "Unknown author".
        $DB->set_field('local_reuseunit_templates', 'userid', 0, ['userid' => $userid]);
    }

    /**
     * Handle section deletion.
     *
     * Cleans up links to the deleted section.
     *
     * @param \core\event\course_section_deleted $event
     */
    public static function section_deleted(\core\event\course_section_deleted $event) {
        global $DB;

        $sectionid = $event->objectid;
        $courseid = $event->courseid;

        // Delete links for this section.
        $DB->delete_records('local_reuseunit_links', [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
        ]);

        // Update templates that reference this section as source.
        // Set to 0 to indicate source is no longer available.
        $DB->set_field('local_reuseunit_templates', 'source_sectionid', 0, ['source_sectionid' => $sectionid]);
    }

    /**
     * Handle category deletion.
     *
     * Updates templates that were shared at category level.
     *
     * @param \core\event\course_category_deleted $event
     */
    public static function category_deleted(\core\event\course_category_deleted $event) {
        global $DB;

        $categoryid = $event->objectid;

        // Templates shared at category level should be downgraded to personal.
        // We need to check if categoryid field exists and is used.
        // For now, let's assume category templates reference the category differently.
        // If there's a categoryid field in templates:
        if ($DB->get_manager()->field_exists('local_reuseunit_templates', 'categoryid')) {
            $DB->set_field_select(
                'local_reuseunit_templates',
                'sharelevel',
                'personal',
                'categoryid = :categoryid AND sharelevel = :sharelevel',
                ['categoryid' => $categoryid, 'sharelevel' => 'category']
            );
            $DB->set_field('local_reuseunit_templates', 'categoryid', 0, ['categoryid' => $categoryid]);
        }
    }
}
