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
 * Uninstall script for local_reuseunit.
 *
 * This script is called when the plugin is being uninstalled.
 * It cleans up all plugin data including files, database records,
 * and configuration settings.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom uninstallation procedure.
 *
 * @return bool True on success.
 */
function xmldb_local_reuseunit_uninstall() {
    global $DB;

    // Delete all files stored by this plugin.
    $fs = get_file_storage();
    $fs->delete_area_files_select(
        \context_system::instance()->id,
        'local_reuseunit',
        false,
        '',
        []
    );

    // Clean up any adhoc tasks.
    $DB->delete_records_select(
        'task_adhoc',
        $DB->sql_like('classname', ':classname'),
        ['classname' => '%local_reuseunit%']
    );

    // Delete all plugin configuration.
    unset_all_config_for_plugin('local_reuseunit');

    // Delete user preferences related to this plugin.
    $DB->delete_records_select(
        'user_preferences',
        $DB->sql_like('name', ':name'),
        ['name' => 'local_reuseunit_%']
    );

    // Note: The database tables are automatically dropped by Moodle
    // based on the install.xml file. We don't need to manually drop them.

    return true;
}
