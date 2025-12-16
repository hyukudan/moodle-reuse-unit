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

/**
 * Restore plugin for local_reuseunit.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_local_plugin.class.php');

/**
 * Restore plugin class for local_reuseunit.
 *
 * This handles restoring plugin-specific data when a course is restored.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_reuseunit_plugin extends restore_local_plugin {

    /**
     * Define the paths to restore for course level.
     *
     * @return array
     */
    protected function define_course_plugin_structure() {
        $paths = [];

        // Define the path for reuseunit links.
        $elename = 'reuseunit_link';
        $elepath = $this->get_pathfor('/reuseunit_links/link');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Define the paths to restore for section level.
     *
     * @return array
     */
    protected function define_section_plugin_structure() {
        $paths = [];

        // Define the path for section links.
        $elename = 'reuseunit_section_link';
        $elepath = $this->get_pathfor('/section_link');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Process the restored reuseunit link data.
     *
     * @param array $data The link data from backup
     */
    public function process_reuseunit_link($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        // Map the user id.
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            $data->userid = $this->task->get_userid();
        }

        // Set the new course id.
        $data->courseid = $this->task->get_courseid();

        // Map section id if available.
        $data->sectionid = $this->get_mappingid('course_section', $data->sectionid);

        // Check if the template still exists.
        if (!$DB->record_exists('local_reuseunit_templates', ['id' => $data->templateid])) {
            // Template doesn't exist, don't restore the link.
            return;
        }

        // Check if section mapping was successful.
        if (!$data->sectionid) {
            // Section mapping failed, don't restore.
            return;
        }

        // Check for duplicate link.
        if ($DB->record_exists('local_reuseunit_links', [
            'courseid' => $data->courseid,
            'sectionid' => $data->sectionid,
        ])) {
            // Link already exists, skip.
            return;
        }

        // Update timestamps.
        $data->timecreated = time();
        $data->timemodified = time();
        $data->last_synced = null; // Reset sync timestamp.

        // Insert the new link.
        $newitemid = $DB->insert_record('local_reuseunit_links', $data);

        // Set mapping for potential future use.
        $this->set_mapping('reuseunit_link', $oldid, $newitemid);
    }

    /**
     * Process restored section link data.
     *
     * @param array $data The section link data from backup
     */
    public function process_reuseunit_section_link($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        // Map the user id.
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            $data->userid = $this->task->get_userid();
        }

        // Set the new course and section ids.
        $data->courseid = $this->task->get_courseid();
        $data->sectionid = $this->task->get_sectionid();

        // Check if the template still exists.
        if (!$DB->record_exists('local_reuseunit_templates', ['id' => $data->templateid])) {
            // Template doesn't exist, don't restore the link.
            return;
        }

        // Check for duplicate link.
        if ($DB->record_exists('local_reuseunit_links', [
            'courseid' => $data->courseid,
            'sectionid' => $data->sectionid,
        ])) {
            // Link already exists, skip.
            return;
        }

        // Update timestamps.
        $data->timecreated = time();
        $data->timemodified = time();
        $data->last_synced = null;

        // Insert the new link.
        $newitemid = $DB->insert_record('local_reuseunit_links', $data);

        // Set mapping.
        $this->set_mapping('reuseunit_section_link', $oldid, $newitemid);
    }
}
