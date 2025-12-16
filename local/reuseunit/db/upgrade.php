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
 * Database upgrade script.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the local_reuseunit plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool
 */
function xmldb_local_reuseunit_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024121603) {
        // Define table local_reuseunit_versions to be created.
        $table = new xmldb_table('local_reuseunit_versions');

        // Adding fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('version', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('changelog', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('source_courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('source_sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('activities_count', XMLDB_TYPE_INTEGER, '5', null, null, null, '0');
        $table->add_field('resources_count', XMLDB_TYPE_INTEGER, '5', null, null, null, '0');
        $table->add_field('fileitemid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('templateid', XMLDB_KEY_FOREIGN, ['templateid'], 'local_reuseunit_templates', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes.
        $table->add_index('templateid_version', XMLDB_INDEX_UNIQUE, ['templateid', 'version']);
        $table->add_index('templateid_timecreated', XMLDB_INDEX_NOTUNIQUE, ['templateid', 'timecreated']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add current_version field to templates table if not exists.
        $table = new xmldb_table('local_reuseunit_templates');
        $field = new xmldb_field('current_version', XMLDB_TYPE_INTEGER, '5', null, null, null, '1', 'usagecount');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reuseunit savepoint reached.
        upgrade_plugin_savepoint(true, 2024121603, 'local', 'reuseunit');
    }

    if ($oldversion < 2024121606) {
        // Add approval workflow fields to templates table.
        $table = new xmldb_table('local_reuseunit_templates');

        // Add approval_status field.
        $field = new xmldb_field('approval_status', XMLDB_TYPE_CHAR, '20', null, null, null, 'none', 'share_level');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add submitted_at field.
        $field = new xmldb_field('submitted_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'approval_status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add approved_by field.
        $field = new xmldb_field('approved_by', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'submitted_at');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add approved_at field.
        $field = new xmldb_field('approved_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'approved_by');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add rejection_reason field.
        $field = new xmldb_field('rejection_reason', XMLDB_TYPE_TEXT, null, null, null, null, null, 'approved_at');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update existing global templates to approved status.
        $DB->execute("UPDATE {local_reuseunit_templates} SET approval_status = 'approved' WHERE share_level = 'global'");
        $DB->execute("UPDATE {local_reuseunit_templates} SET approval_status = 'none' WHERE share_level != 'global' OR approval_status IS NULL");

        // Reuseunit savepoint reached.
        upgrade_plugin_savepoint(true, 2024121606, 'local', 'reuseunit');
    }

    if ($oldversion < 2024121607) {
        // Define table local_reuseunit_links for section synchronization.
        $table = new xmldb_table('local_reuseunit_links');

        // Adding fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('templateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('autosync', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('template_version', XMLDB_TYPE_INTEGER, '5', null, null, null, '1');
        $table->add_field('last_synced', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('sectionid', XMLDB_KEY_FOREIGN, ['sectionid'], 'course_sections', ['id']);
        $table->add_key('templateid', XMLDB_KEY_FOREIGN, ['templateid'], 'local_reuseunit_templates', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes.
        $table->add_index('courseid_sectionid', XMLDB_INDEX_UNIQUE, ['courseid', 'sectionid']);
        $table->add_index('templateid_autosync', XMLDB_INDEX_NOTUNIQUE, ['templateid', 'autosync']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Reuseunit savepoint reached.
        upgrade_plugin_savepoint(true, 2024121607, 'local', 'reuseunit');
    }

    if ($oldversion < 2024121608) {
        // Define table local_reuseunit_scheduled for scheduled imports.
        $table = new xmldb_table('local_reuseunit_scheduled');

        // Adding fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dest_courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('import_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('scheduled_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, null, null, 'pending');
        $table->add_field('started_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('completed_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('result_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('dest_courseid', XMLDB_KEY_FOREIGN, ['dest_courseid'], 'course', ['id']);

        // Adding indexes.
        $table->add_index('userid_status', XMLDB_INDEX_NOTUNIQUE, ['userid', 'status']);
        $table->add_index('scheduled_time_status', XMLDB_INDEX_NOTUNIQUE, ['scheduled_time', 'status']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Reuseunit savepoint reached.
        upgrade_plugin_savepoint(true, 2024121608, 'local', 'reuseunit');
    }

    if ($oldversion < 2025121602) {
        // Add imported_cmids field to local_reuseunit_links for granular import tracking.
        $table = new xmldb_table('local_reuseunit_links');

        // Add imported_cmids field - stores JSON array of imported course module IDs.
        $field = new xmldb_field('imported_cmids', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add partial_import field - indicates if only some activities were imported.
        $field = new xmldb_field('partial_import', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'imported_cmids');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reuseunit savepoint reached.
        upgrade_plugin_savepoint(true, 2025121602, 'local', 'reuseunit');
    }

    if ($oldversion < 2025121603) {
        // Add contenthash field to local_reuseunit_links for change detection.
        $table = new xmldb_table('local_reuseunit_links');

        // Add contenthash field - stores SHA256 hash of source section content.
        $field = new xmldb_field('contenthash', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'partial_import');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add update_available field - flag indicating updates are available.
        $field = new xmldb_field('update_available', XMLDB_TYPE_INTEGER, '1', null, null, null, '0', 'contenthash');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add last_checked field - timestamp of last update check.
        $field = new xmldb_field('last_checked', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'update_available');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Reuseunit savepoint reached.
        upgrade_plugin_savepoint(true, 2025121603, 'local', 'reuseunit');
    }

    if ($oldversion < 2025121604) {
        // Define table local_reuseunit_synced_modules for module mapping tracking.
        $table = new xmldb_table('local_reuseunit_synced_modules');

        // Adding fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('linkid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('source_cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dest_cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modname', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('source_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('linkid', XMLDB_KEY_FOREIGN, ['linkid'], 'local_reuseunit_links', ['id']);

        // Adding indexes.
        $table->add_index('linkid_source_cmid', XMLDB_INDEX_UNIQUE, ['linkid', 'source_cmid']);
        $table->add_index('linkid_dest_cmid', XMLDB_INDEX_NOTUNIQUE, ['linkid', 'dest_cmid']);

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Reuseunit savepoint reached.
        upgrade_plugin_savepoint(true, 2025121604, 'local', 'reuseunit');
    }

    return true;
}
