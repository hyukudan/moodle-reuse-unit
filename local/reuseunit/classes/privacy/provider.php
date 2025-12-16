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
 * Privacy provider implementation for local_reuseunit.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_reuseunit\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_reuseunit.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the metadata for the plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        // Templates table.
        $collection->add_database_table(
            'local_reuseunit_templates',
            [
                'userid' => 'privacy:metadata:templates:userid',
                'name' => 'privacy:metadata:templates:name',
                'description' => 'privacy:metadata:templates:description',
                'tags' => 'privacy:metadata:templates:tags',
                'timecreated' => 'privacy:metadata:templates:timecreated',
                'timemodified' => 'privacy:metadata:templates:timemodified',
            ],
            'privacy:metadata:templates'
        );

        // Import history table.
        $collection->add_database_table(
            'local_reuseunit_history',
            [
                'userid' => 'privacy:metadata:history:userid',
                'source_courseid' => 'privacy:metadata:history:source_courseid',
                'source_sectionid' => 'privacy:metadata:history:source_sectionid',
                'target_courseid' => 'privacy:metadata:history:target_courseid',
                'target_sectionid' => 'privacy:metadata:history:target_sectionid',
                'timecreated' => 'privacy:metadata:history:timecreated',
            ],
            'privacy:metadata:history'
        );

        // Favorites table.
        $collection->add_database_table(
            'local_reuseunit_favorites',
            [
                'userid' => 'privacy:metadata:favorites:userid',
                'templateid' => 'privacy:metadata:favorites:templateid',
                'timecreated' => 'privacy:metadata:favorites:timecreated',
            ],
            'privacy:metadata:favorites'
        );

        // Scheduled imports table.
        $collection->add_database_table(
            'local_reuseunit_scheduled',
            [
                'userid' => 'privacy:metadata:scheduled:userid',
                'scheduled_time' => 'privacy:metadata:scheduled:scheduled_time',
                'status' => 'privacy:metadata:scheduled:status',
                'timecreated' => 'privacy:metadata:scheduled:timecreated',
            ],
            'privacy:metadata:scheduled'
        );

        // Section links table.
        $collection->add_database_table(
            'local_reuseunit_links',
            [
                'userid' => 'privacy:metadata:links:userid',
                'templateid' => 'privacy:metadata:links:templateid',
                'timecreated' => 'privacy:metadata:links:timecreated',
            ],
            'privacy:metadata:links'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user ID.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // User context for all plugin data.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {user} u ON ctx.instanceid = u.id AND ctx.contextlevel = :contextlevel
                 WHERE u.id = :userid";

        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        // Add users from templates table.
        $sql = "SELECT userid FROM {local_reuseunit_templates} WHERE userid = :userid";
        $params = ['userid' => $context->instanceid];
        $userlist->add_from_sql('userid', $sql, $params);

        // Add users from history table.
        $sql = "SELECT userid FROM {local_reuseunit_history} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Add users from favorites table.
        $sql = "SELECT userid FROM {local_reuseunit_favorites} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Add users from scheduled imports table.
        $sql = "SELECT userid FROM {local_reuseunit_scheduled} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);

        // Add users from links table.
        $sql = "SELECT userid FROM {local_reuseunit_links} WHERE userid = :userid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $context = \context_user::instance($user->id);

        // Export templates.
        $templates = $DB->get_records('local_reuseunit_templates', ['userid' => $user->id]);
        if (!empty($templates)) {
            $data = [];
            foreach ($templates as $template) {
                $data[] = [
                    'name' => $template->name,
                    'description' => $template->description,
                    'tags' => $template->tags,
                    'sharelevel' => $template->sharelevel,
                    'timecreated' => transform::datetime($template->timecreated),
                    'timemodified' => transform::datetime($template->timemodified),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('templates', 'local_reuseunit')],
                (object) ['templates' => $data]
            );
        }

        // Export import history.
        $history = $DB->get_records('local_reuseunit_history', ['userid' => $user->id]);
        if (!empty($history)) {
            $data = [];
            foreach ($history as $record) {
                $data[] = [
                    'source_courseid' => $record->source_courseid,
                    'source_sectionid' => $record->source_sectionid,
                    'target_courseid' => $record->target_courseid,
                    'target_sectionid' => $record->target_sectionid,
                    'activities_imported' => $record->activities_imported,
                    'resources_imported' => $record->resources_imported,
                    'timecreated' => transform::datetime($record->timecreated),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('importhistory', 'local_reuseunit')],
                (object) ['history' => $data]
            );
        }

        // Export favorites.
        $favorites = $DB->get_records('local_reuseunit_favorites', ['userid' => $user->id]);
        if (!empty($favorites)) {
            $data = [];
            foreach ($favorites as $favorite) {
                $template = $DB->get_record('local_reuseunit_templates', ['id' => $favorite->templateid]);
                $data[] = [
                    'template_name' => $template ? $template->name : 'Deleted',
                    'timecreated' => transform::datetime($favorite->timecreated),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('favorites', 'local_reuseunit')],
                (object) ['favorites' => $data]
            );
        }

        // Export scheduled imports.
        $scheduled = $DB->get_records('local_reuseunit_scheduled', ['userid' => $user->id]);
        if (!empty($scheduled)) {
            $data = [];
            foreach ($scheduled as $record) {
                $data[] = [
                    'target_courseid' => $record->target_courseid,
                    'scheduled_time' => transform::datetime($record->scheduled_time),
                    'status' => $record->status,
                    'timecreated' => transform::datetime($record->timecreated),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('scheduledimports', 'local_reuseunit')],
                (object) ['scheduled_imports' => $data]
            );
        }

        // Export section links.
        $links = $DB->get_records('local_reuseunit_links', ['userid' => $user->id]);
        if (!empty($links)) {
            $data = [];
            foreach ($links as $link) {
                $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);
                $data[] = [
                    'template_name' => $template ? $template->name : 'Deleted',
                    'courseid' => $link->courseid,
                    'sectionid' => $link->sectionid,
                    'autosync' => $link->autosync ? 'Yes' : 'No',
                    'timecreated' => transform::datetime($link->timecreated),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('linkedsections', 'local_reuseunit')],
                (object) ['linked_sections' => $data]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_user) {
            return;
        }

        $userid = $context->instanceid;

        // Delete all user data.
        $DB->delete_records('local_reuseunit_templates', ['userid' => $userid]);
        $DB->delete_records('local_reuseunit_history', ['userid' => $userid]);
        $DB->delete_records('local_reuseunit_favorites', ['userid' => $userid]);
        $DB->delete_records('local_reuseunit_scheduled', ['userid' => $userid]);
        $DB->delete_records('local_reuseunit_links', ['userid' => $userid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        // Delete all user data.
        $DB->delete_records('local_reuseunit_templates', ['userid' => $user->id]);
        $DB->delete_records('local_reuseunit_history', ['userid' => $user->id]);
        $DB->delete_records('local_reuseunit_favorites', ['userid' => $user->id]);
        $DB->delete_records('local_reuseunit_scheduled', ['userid' => $user->id]);
        $DB->delete_records('local_reuseunit_links', ['userid' => $user->id]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete all user data for the specified users.
        $DB->delete_records_select('local_reuseunit_templates', "userid $insql", $inparams);
        $DB->delete_records_select('local_reuseunit_history', "userid $insql", $inparams);
        $DB->delete_records_select('local_reuseunit_favorites', "userid $insql", $inparams);
        $DB->delete_records_select('local_reuseunit_scheduled', "userid $insql", $inparams);
        $DB->delete_records_select('local_reuseunit_links', "userid $insql", $inparams);
    }
}
