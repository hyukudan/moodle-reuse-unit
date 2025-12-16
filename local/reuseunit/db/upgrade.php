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
 * @copyright  2024 hyukudan
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

    return true;
}
