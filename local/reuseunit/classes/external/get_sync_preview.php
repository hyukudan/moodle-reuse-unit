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
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_course;
use local_reuseunit\section_helper;

/**
 * External function to get sync preview for a linked section.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_sync_preview extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'linkid' => new external_value(PARAM_INT, 'Link ID'),
        ]);
    }

    /**
     * Get sync preview for a linked section.
     *
     * @param int $linkid Link ID
     * @return array Preview data
     */
    public static function execute(int $linkid): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'linkid' => $linkid,
        ]);

        // Get the link.
        $link = $DB->get_record('local_reuseunit_links', ['id' => $params['linkid']], '*', MUST_EXIST);

        // Check course access.
        $context = context_course::instance($link->courseid);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        // Get template info.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid], '*', MUST_EXIST);

        // Get the preview.
        $preview = section_helper::get_sync_preview($params['linkid']);

        // Format for web service.
        $result = [
            'linkid' => $params['linkid'],
            'templatename' => $template->name,
            'has_changes' => $preview['has_changes'],
            'added' => self::format_modules($preview['added']),
            'modified' => self::format_modified_modules($preview['modified']),
            'removed' => self::format_removed_modules($preview['removed']),
            'local' => self::format_modules($preview['local']),
            'unchanged' => self::format_modified_modules($preview['unchanged']),
            'added_count' => count($preview['added']),
            'modified_count' => count($preview['modified']),
            'removed_count' => count($preview['removed']),
            'local_count' => count($preview['local']),
            'unchanged_count' => count($preview['unchanged']),
        ];

        return $result;
    }

    /**
     * Format modules for output.
     *
     * @param array $modules Array of modules
     * @return array Formatted modules
     */
    private static function format_modules(array $modules): array {
        $result = [];
        foreach ($modules as $mod) {
            $result[] = [
                'cmid' => $mod['cmid'],
                'name' => $mod['name'],
                'modname' => $mod['modname'],
                'icon' => $mod['icon'] ?? '',
            ];
        }
        return $result;
    }

    /**
     * Format modified modules for output.
     *
     * @param array $modules Array of modified modules with source/dest
     * @return array Formatted modules
     */
    private static function format_modified_modules(array $modules): array {
        $result = [];
        foreach ($modules as $mod) {
            $result[] = [
                'source_cmid' => $mod['source_cmid'],
                'dest_cmid' => $mod['dest_cmid'],
                'name' => $mod['source']['name'],
                'modname' => $mod['source']['modname'],
                'icon' => $mod['source']['icon'] ?? '',
            ];
        }
        return $result;
    }

    /**
     * Format removed modules for output.
     *
     * @param array $modules Array of removed modules
     * @return array Formatted modules
     */
    private static function format_removed_modules(array $modules): array {
        $result = [];
        foreach ($modules as $mod) {
            $result[] = [
                'source_cmid' => $mod['source_cmid'],
                'dest_cmid' => $mod['dest_cmid'],
                'name' => $mod['dest']['name'],
                'modname' => $mod['dest']['modname'],
                'icon' => $mod['dest']['icon'] ?? '',
            ];
        }
        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $modulestructure = new external_single_structure([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Module name'),
            'modname' => new external_value(PARAM_TEXT, 'Module type'),
            'icon' => new external_value(PARAM_URL, 'Module icon URL', VALUE_OPTIONAL),
        ]);

        $mappedmodulestructure = new external_single_structure([
            'source_cmid' => new external_value(PARAM_INT, 'Source course module ID'),
            'dest_cmid' => new external_value(PARAM_INT, 'Destination course module ID'),
            'name' => new external_value(PARAM_TEXT, 'Module name'),
            'modname' => new external_value(PARAM_TEXT, 'Module type'),
            'icon' => new external_value(PARAM_URL, 'Module icon URL', VALUE_OPTIONAL),
        ]);

        return new external_single_structure([
            'linkid' => new external_value(PARAM_INT, 'Link ID'),
            'templatename' => new external_value(PARAM_TEXT, 'Template name'),
            'has_changes' => new external_value(PARAM_BOOL, 'Whether there are changes to sync'),
            'added' => new external_multiple_structure($modulestructure, 'New modules to add'),
            'modified' => new external_multiple_structure($mappedmodulestructure, 'Modified modules to update'),
            'removed' => new external_multiple_structure($mappedmodulestructure, 'Modules removed from template'),
            'local' => new external_multiple_structure($modulestructure, 'Local modules (will be preserved)'),
            'unchanged' => new external_multiple_structure($mappedmodulestructure, 'Unchanged modules'),
            'added_count' => new external_value(PARAM_INT, 'Count of modules to add'),
            'modified_count' => new external_value(PARAM_INT, 'Count of modules to update'),
            'removed_count' => new external_value(PARAM_INT, 'Count of modules removed from template'),
            'local_count' => new external_value(PARAM_INT, 'Count of local modules'),
            'unchanged_count' => new external_value(PARAM_INT, 'Count of unchanged modules'),
        ]);
    }
}
