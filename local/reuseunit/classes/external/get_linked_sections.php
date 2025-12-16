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

/**
 * External function to get linked sections for a course.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_linked_sections extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get linked sections for a course.
     *
     * @param int $courseid Course ID
     * @return array Linked sections data
     */
    public static function execute(int $courseid): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        // Check course access.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/reuseunit:import', $context);

        // Get linked sections.
        $sql = "SELECT l.*, t.name as templatename, t.current_version as latest_version,
                       cs.name as sectionname, cs.section as sectionnumber
                FROM {local_reuseunit_links} l
                JOIN {local_reuseunit_templates} t ON t.id = l.templateid
                JOIN {course_sections} cs ON cs.id = l.sectionid
                WHERE l.courseid = :courseid
                ORDER BY cs.section";

        $links = $DB->get_records_sql($sql, ['courseid' => $params['courseid']]);

        $result = [];
        foreach ($links as $link) {
            $updateavailable = ($link->latest_version ?? 1) > ($link->template_version ?? 1);
            $result[] = [
                'id' => (int) $link->id,
                'sectionid' => (int) $link->sectionid,
                'sectionname' => $link->sectionname ?: get_string('section', 'local_reuseunit') . ' ' . $link->sectionnumber,
                'sectionnumber' => (int) $link->sectionnumber,
                'templateid' => (int) $link->templateid,
                'templatename' => $link->templatename,
                'autosync' => (bool) $link->autosync,
                'currentversion' => (int) ($link->template_version ?? 1),
                'latestversion' => (int) ($link->latest_version ?? 1),
                'updateavailable' => $updateavailable,
                'lastsynced' => (int) $link->last_synced,
                'lastsyncedformatted' => $link->last_synced ? userdate($link->last_synced) : '-',
            ];
        }

        return ['links' => $result, 'count' => count($result)];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'links' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Link ID'),
                    'sectionid' => new external_value(PARAM_INT, 'Section ID'),
                    'sectionname' => new external_value(PARAM_TEXT, 'Section name'),
                    'sectionnumber' => new external_value(PARAM_INT, 'Section number'),
                    'templateid' => new external_value(PARAM_INT, 'Template ID'),
                    'templatename' => new external_value(PARAM_TEXT, 'Template name'),
                    'autosync' => new external_value(PARAM_BOOL, 'Auto-sync enabled'),
                    'currentversion' => new external_value(PARAM_INT, 'Current synced version'),
                    'latestversion' => new external_value(PARAM_INT, 'Latest template version'),
                    'updateavailable' => new external_value(PARAM_BOOL, 'Update available'),
                    'lastsynced' => new external_value(PARAM_INT, 'Last sync timestamp'),
                    'lastsyncedformatted' => new external_value(PARAM_TEXT, 'Formatted last sync date'),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Total count'),
        ]);
    }
}
