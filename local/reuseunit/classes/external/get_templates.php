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

namespace local_reuseunit\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use context_system;
use context_coursecat;

/**
 * External function to get available templates.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_templates extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search query', VALUE_DEFAULT, ''),
            'categoryid' => new external_value(PARAM_INT, 'Filter by category ID', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum results', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * Get available templates for the user.
     *
     * @param string $query Search query
     * @param int $categoryid Category filter
     * @param int $limit Maximum results
     * @return array List of templates
     */
    public static function execute(string $query = '', int $categoryid = 0, int $limit = 50): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'categoryid' => $categoryid,
            'limit' => $limit,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        $limit = min($params['limit'], 100);
        $templates = [];

        // Get user's categories for category-level templates.
        $usercategories = self::get_user_categories();

        // Build SQL for templates the user can access.
        $sqlparts = [];
        $sqlparams = [];

        // 1. Personal templates (user's own).
        $sqlparts[] = "(userid = :userid1 AND sharelevel = 'personal')";
        $sqlparams['userid1'] = $USER->id;

        // 2. Category templates (in user's categories).
        if (!empty($usercategories)) {
            list($incatsql, $incatparams) = $DB->get_in_or_equal($usercategories, SQL_PARAMS_NAMED, 'cat');
            $sqlparts[] = "(sharelevel = 'category' AND categoryid $incatsql)";
            $sqlparams = array_merge($sqlparams, $incatparams);
        }

        // 3. Global templates.
        $sqlparts[] = "(sharelevel = 'global')";

        $wheresql = '(' . implode(' OR ', $sqlparts) . ')';

        // Add search filter.
        if (!empty($params['query'])) {
            $wheresql .= ' AND (' . $DB->sql_like('name', ':search1', false, false);
            $wheresql .= ' OR ' . $DB->sql_like('description', ':search2', false, false);
            $wheresql .= ' OR ' . $DB->sql_like('tags', ':search3', false, false) . ')';
            $searchterm = '%' . $DB->sql_like_escape($params['query']) . '%';
            $sqlparams['search1'] = $searchterm;
            $sqlparams['search2'] = $searchterm;
            $sqlparams['search3'] = $searchterm;
        }

        // Add category filter.
        if (!empty($params['categoryid'])) {
            $wheresql .= ' AND categoryid = :filtercatid';
            $sqlparams['filtercatid'] = $params['categoryid'];
        }

        $sql = "SELECT t.*, u.firstname, u.lastname
                FROM {local_reuseunit_templates} t
                LEFT JOIN {user} u ON u.id = t.userid
                WHERE $wheresql
                ORDER BY t.usagecount DESC, t.timecreated DESC";

        $records = $DB->get_records_sql($sql, $sqlparams, 0, $limit);

        foreach ($records as $record) {
            // Determine share level display.
            $sharelevelclass = 'secondary';
            $sharelevelicon = 'i/user';
            $sharelevellabel = get_string('sharelevel_personal', 'local_reuseunit');

            if ($record->sharelevel === 'category') {
                $sharelevelclass = 'info';
                $sharelevelicon = 'i/folder';
                $sharelevellabel = get_string('sharelevel_category', 'local_reuseunit');
            } else if ($record->sharelevel === 'global') {
                $sharelevelclass = 'success';
                $sharelevelicon = 'i/siteevent';
                $sharelevellabel = get_string('sharelevel_global', 'local_reuseunit');
            }

            // Parse tags.
            $tagsarray = [];
            if (!empty($record->tags)) {
                $tagsarray = array_map('trim', explode(',', $record->tags));
            }

            $templates[] = [
                'id' => $record->id,
                'name' => $record->name,
                'description' => $record->description ?? '',
                'tags' => $tagsarray,
                'sharelevel' => $record->sharelevel,
                'sharelevelclass' => $sharelevelclass,
                'sharelevelicon' => $sharelevelicon,
                'sharelevellabel' => $sharelevellabel,
                'activities' => $record->activities_count,
                'resources' => $record->resources_count,
                'creatorid' => $record->userid,
                'creatorname' => fullname($record),
                'usagecount' => $record->usagecount,
                'timecreated' => $record->timecreated,
                'timecreatedformatted' => userdate($record->timecreated, get_string('strftimedatefullshort', 'langconfig')),
                'candelete' => ($record->userid == $USER->id) || has_capability('local/reuseunit:managetemplates', $context),
            ];
        }

        return $templates;
    }

    /**
     * Get categories the user has access to.
     *
     * @return array Category IDs
     */
    private static function get_user_categories(): array {
        global $DB, $USER;

        $categories = [];

        // Get all categories where user has a role.
        $sql = "SELECT DISTINCT cc.id
                FROM {course_categories} cc
                JOIN {context} ctx ON ctx.instanceid = cc.id AND ctx.contextlevel = :contextlevel
                JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = :userid";

        $records = $DB->get_records_sql($sql, [
            'contextlevel' => CONTEXT_COURSECAT,
            'userid' => $USER->id,
        ]);

        foreach ($records as $record) {
            $categories[] = $record->id;
        }

        // Also get categories from user's courses.
        $sql = "SELECT DISTINCT c.category
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = :userid";

        $records = $DB->get_records_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $USER->id,
        ]);

        foreach ($records as $record) {
            if (!in_array($record->category, $categories)) {
                $categories[] = $record->category;
            }
        }

        return $categories;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Template ID'),
                'name' => new external_value(PARAM_TEXT, 'Template name'),
                'description' => new external_value(PARAM_TEXT, 'Template description'),
                'tags' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Tag'),
                    'Tags', VALUE_OPTIONAL
                ),
                'sharelevel' => new external_value(PARAM_ALPHA, 'Share level'),
                'sharelevelclass' => new external_value(PARAM_TEXT, 'CSS class for share level badge'),
                'sharelevelicon' => new external_value(PARAM_TEXT, 'Icon for share level'),
                'sharelevellabel' => new external_value(PARAM_TEXT, 'Label for share level'),
                'activities' => new external_value(PARAM_INT, 'Number of activities'),
                'resources' => new external_value(PARAM_INT, 'Number of resources'),
                'creatorid' => new external_value(PARAM_INT, 'Creator user ID'),
                'creatorname' => new external_value(PARAM_TEXT, 'Creator full name'),
                'usagecount' => new external_value(PARAM_INT, 'Number of times used'),
                'timecreated' => new external_value(PARAM_INT, 'Creation timestamp'),
                'timecreatedformatted' => new external_value(PARAM_TEXT, 'Formatted creation date'),
                'candelete' => new external_value(PARAM_BOOL, 'Can user delete this template'),
            ])
        );
    }
}
