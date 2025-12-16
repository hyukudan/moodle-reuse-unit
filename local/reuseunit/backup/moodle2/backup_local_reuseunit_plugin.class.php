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
 * Backup plugin for local_reuseunit.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_local_plugin.class.php');

/**
 * Backup plugin class for local_reuseunit.
 *
 * This handles backing up plugin-specific data when a course is backed up.
 * It includes section links for synchronization tracking.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_reuseunit_plugin extends backup_local_plugin {

    /**
     * Define the structure for the plugin backup.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, null, null);

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container to the parent/main element.
        $plugin->add_child($pluginwrapper);

        // Create the reuseunit_links element.
        $links = new backup_nested_element('reuseunit_links');
        $pluginwrapper->add_child($links);

        // Define link elements.
        $link = new backup_nested_element('link', ['id'], [
            'sectionid',
            'templateid',
            'userid',
            'autosync',
            'template_version',
            'last_synced',
            'timecreated',
            'timemodified',
        ]);
        $links->add_child($link);

        // Define source for links (only for this course).
        $link->set_source_table('local_reuseunit_links', ['courseid' => backup::VAR_COURSEID]);

        // Annotate ids.
        $link->annotate_ids('user', 'userid');

        return $plugin;
    }

    /**
     * Define the structure for section-level backup.
     *
     * @return backup_plugin_element
     */
    protected function define_section_plugin_structure() {
        // Define the virtual plugin element.
        $plugin = $this->get_plugin_element(null, null, null);

        // Create wrapper element.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        // Create section link element.
        $link = new backup_nested_element('section_link', ['id'], [
            'templateid',
            'userid',
            'autosync',
            'template_version',
            'last_synced',
            'timecreated',
            'timemodified',
        ]);
        $pluginwrapper->add_child($link);

        // Define source - links for this specific section.
        $link->set_source_sql(
            'SELECT * FROM {local_reuseunit_links}
             WHERE courseid = ? AND sectionid = ?',
            [backup::VAR_COURSEID, backup::VAR_SECTIONID]
        );

        // Annotate ids.
        $link->annotate_ids('user', 'userid');

        return $plugin;
    }
}
