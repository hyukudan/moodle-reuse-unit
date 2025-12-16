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
use context_system;

/**
 * External function to compare two template versions.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compare_versions extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'templateid' => new external_value(PARAM_INT, 'Template ID'),
            'version1' => new external_value(PARAM_INT, 'First version number'),
            'version2' => new external_value(PARAM_INT, 'Second version number'),
        ]);
    }

    /**
     * Compare two template versions.
     *
     * @param int $templateid Template ID
     * @param int $version1 First version
     * @param int $version2 Second version
     * @return array Comparison results
     */
    public static function execute(int $templateid, int $version1, int $version2): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'templateid' => $templateid,
            'version1' => $version1,
            'version2' => $version2,
        ]);

        $context = context_system::instance();
        self::validate_context($context);

        // Get template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Get versions.
        $v1 = $DB->get_record('local_reuseunit_versions', [
            'templateid' => $params['templateid'],
            'version' => $params['version1'],
        ], '*', MUST_EXIST);

        $v2 = $DB->get_record('local_reuseunit_versions', [
            'templateid' => $params['templateid'],
            'version' => $params['version2'],
        ], '*', MUST_EXIST);

        // Build comparison data.
        $changes = [];

        // Compare activity counts.
        if ($v1->activities_count != $v2->activities_count) {
            $changes[] = [
                'field' => 'activities',
                'fieldname' => get_string('activities', 'local_reuseunit'),
                'oldvalue' => (string) $v1->activities_count,
                'newvalue' => (string) $v2->activities_count,
                'type' => $v2->activities_count > $v1->activities_count ? 'increased' : 'decreased',
            ];
        }

        // Compare resource counts.
        if ($v1->resources_count != $v2->resources_count) {
            $changes[] = [
                'field' => 'resources',
                'fieldname' => get_string('resources', 'local_reuseunit'),
                'oldvalue' => (string) $v1->resources_count,
                'newvalue' => (string) $v2->resources_count,
                'type' => $v2->resources_count > $v1->resources_count ? 'increased' : 'decreased',
            ];
        }

        // Compare source course.
        if ($v1->source_courseid != $v2->source_courseid) {
            $course1 = $DB->get_record('course', ['id' => $v1->source_courseid], 'fullname');
            $course2 = $DB->get_record('course', ['id' => $v2->source_courseid], 'fullname');
            $changes[] = [
                'field' => 'sourcecourse',
                'fieldname' => get_string('sourcecourse', 'local_reuseunit'),
                'oldvalue' => $course1->fullname ?? 'Unknown',
                'newvalue' => $course2->fullname ?? 'Unknown',
                'type' => 'changed',
            ];
        }

        // Compare source section.
        if ($v1->source_sectionid != $v2->source_sectionid) {
            $section1 = $DB->get_record('course_sections', ['id' => $v1->source_sectionid], 'name, section');
            $section2 = $DB->get_record('course_sections', ['id' => $v2->source_sectionid], 'name, section');
            $changes[] = [
                'field' => 'sourcesection',
                'fieldname' => get_string('section', 'local_reuseunit'),
                'oldvalue' => $section1->name ?: get_string('section', 'local_reuseunit') . ' ' . $section1->section,
                'newvalue' => $section2->name ?: get_string('section', 'local_reuseunit') . ' ' . $section2->section,
                'type' => 'changed',
            ];
        }

        return [
            'templatename' => $template->name,
            'version1' => $params['version1'],
            'version2' => $params['version2'],
            'version1date' => userdate($v1->timecreated),
            'version2date' => userdate($v2->timecreated),
            'version1changelog' => $v1->changelog ?? '',
            'version2changelog' => $v2->changelog ?? '',
            'changes' => $changes,
            'haschanges' => !empty($changes),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'templatename' => new external_value(PARAM_TEXT, 'Template name'),
            'version1' => new external_value(PARAM_INT, 'First version'),
            'version2' => new external_value(PARAM_INT, 'Second version'),
            'version1date' => new external_value(PARAM_TEXT, 'First version date'),
            'version2date' => new external_value(PARAM_TEXT, 'Second version date'),
            'version1changelog' => new external_value(PARAM_TEXT, 'First version changelog'),
            'version2changelog' => new external_value(PARAM_TEXT, 'Second version changelog'),
            'changes' => new external_multiple_structure(
                new external_single_structure([
                    'field' => new external_value(PARAM_ALPHA, 'Field name'),
                    'fieldname' => new external_value(PARAM_TEXT, 'Field display name'),
                    'oldvalue' => new external_value(PARAM_TEXT, 'Old value'),
                    'newvalue' => new external_value(PARAM_TEXT, 'New value'),
                    'type' => new external_value(PARAM_ALPHA, 'Change type'),
                ])
            ),
            'haschanges' => new external_value(PARAM_BOOL, 'Has changes'),
        ]);
    }
}
