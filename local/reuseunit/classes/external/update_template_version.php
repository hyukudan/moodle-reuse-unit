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
use external_value;
use context_course;

/**
 * External function to create a new version of a template.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_template_version extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'templateid' => new external_value(PARAM_INT, 'Template ID'),
            'courseid' => new external_value(PARAM_INT, 'New source course ID'),
            'sectionid' => new external_value(PARAM_INT, 'New source section ID'),
            'changelog' => new external_value(PARAM_TEXT, 'Description of changes', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Create a new version of a template.
     *
     * @param int $templateid Template ID
     * @param int $courseid New source course ID
     * @param int $sectionid New source section ID
     * @param string $changelog Description of changes
     * @return array Result
     */
    public static function execute(int $templateid, int $courseid, int $sectionid, string $changelog = ''): array {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'templateid' => $templateid,
            'courseid' => $courseid,
            'sectionid' => $sectionid,
            'changelog' => $changelog,
        ]);

        // Get template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $params['templateid']], '*', MUST_EXIST);

        // Only template owner can update.
        if ($template->userid != $USER->id) {
            throw new \moodle_exception('error_nopermission', 'local_reuseunit');
        }

        // Check course and section exist.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_capability('local/reuseunit:export', $context);

        $section = $DB->get_record('course_sections', ['id' => $params['sectionid'], 'course' => $course->id], '*', MUST_EXIST);

        // Get section content info.
        $modinfo = get_fast_modinfo($course);
        $activitycount = 0;
        $resourcecount = 0;

        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                if (!$cm->uservisible) {
                    continue;
                }
                $archetype = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                    $resourcecount++;
                } else {
                    $activitycount++;
                }
            }
        }

        // Get next version number.
        $maxversion = $DB->get_field_sql(
            "SELECT MAX(version) FROM {local_reuseunit_versions} WHERE templateid = :templateid",
            ['templateid' => $params['templateid']]
        );
        $newversion = ($maxversion ?? 0) + 1;

        // Create version record.
        $versionrecord = new \stdClass();
        $versionrecord->templateid = $params['templateid'];
        $versionrecord->version = $newversion;
        $versionrecord->userid = $USER->id;
        $versionrecord->changelog = $params['changelog'];
        $versionrecord->source_courseid = $params['courseid'];
        $versionrecord->source_sectionid = $params['sectionid'];
        $versionrecord->activities_count = $activitycount;
        $versionrecord->resources_count = $resourcecount;
        $versionrecord->timecreated = time();

        $versionid = $DB->insert_record('local_reuseunit_versions', $versionrecord);

        // Update template with new version.
        $template->source_courseid = $params['courseid'];
        $template->source_sectionid = $params['sectionid'];
        $template->activities_count = $activitycount;
        $template->resources_count = $resourcecount;
        $template->current_version = $newversion;
        $template->timemodified = time();

        $DB->update_record('local_reuseunit_templates', $template);

        return [
            'success' => true,
            'versionid' => $versionid,
            'version' => $newversion,
            'message' => get_string('versioncreated', 'local_reuseunit', $newversion),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether update was successful'),
            'versionid' => new external_value(PARAM_INT, 'New version ID'),
            'version' => new external_value(PARAM_INT, 'New version number'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
