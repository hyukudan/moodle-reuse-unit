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
use context_course;
use moodle_url;

/**
 * External function to get detailed section content.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_section_content extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
        ]);
    }

    /**
     * Get detailed content of a section for preview.
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID
     * @return array Section content details
     */
    public static function execute(int $courseid, int $sectionid): array {
        global $DB, $OUTPUT;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'sectionid' => $sectionid,
        ]);

        $courseid = $params['courseid'];
        $sectionid = $params['sectionid'];

        // Check course exists and get context.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = context_course::instance($courseid);

        // Check capability.
        self::validate_context($context);
        require_capability('local/reuseunit:export', $context);

        // Get section info.
        $modinfo = get_fast_modinfo($course);
        $sectioninfo = null;

        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->id == $sectionid) {
                $sectioninfo = $section;
                break;
            }
        }

        if (!$sectioninfo) {
            throw new \moodle_exception('error_sectionnotfound', 'local_reuseunit');
        }

        // Get section contents.
        $contents = [];
        $activities = 0;
        $resources = 0;

        if (!empty($modinfo->sections[$sectioninfo->section])) {
            foreach ($modinfo->sections[$sectioninfo->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];
                if (!$cm->uservisible) {
                    continue;
                }

                // Get module icon.
                $iconurl = $cm->get_icon_url()->out(false);

                // Determine type.
                $archetype = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                $isresource = ($archetype == MOD_ARCHETYPE_RESOURCE);

                if ($isresource) {
                    $resources++;
                    $type = 'resource';
                } else {
                    $activities++;
                    $type = 'activity';
                }

                $contents[] = [
                    'id' => $cm->id,
                    'name' => format_string($cm->name, true, ['context' => $context]),
                    'modname' => $cm->modname,
                    'modplural' => get_string('pluginname', 'mod_' . $cm->modname),
                    'iconurl' => $iconurl,
                    'type' => $type,
                    'visible' => $cm->visible,
                    'indent' => $cm->indent,
                ];
            }
        }

        return [
            'sectionid' => $sectioninfo->id,
            'sectionnum' => $sectioninfo->section,
            'name' => get_section_name($course, $sectioninfo),
            'summary' => format_text($sectioninfo->summary, $sectioninfo->summaryformat, [
                'context' => $context,
                'noclean' => true,
            ]),
            'activities' => $activities,
            'resources' => $resources,
            'contents' => $contents,
            'isempty' => empty($contents),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'sectionnum' => new external_value(PARAM_INT, 'Section number'),
            'name' => new external_value(PARAM_TEXT, 'Section name'),
            'summary' => new external_value(PARAM_RAW, 'Section summary HTML'),
            'activities' => new external_value(PARAM_INT, 'Number of activities'),
            'resources' => new external_value(PARAM_INT, 'Number of resources'),
            'contents' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course module ID'),
                    'name' => new external_value(PARAM_TEXT, 'Module name'),
                    'modname' => new external_value(PARAM_TEXT, 'Module type'),
                    'modplural' => new external_value(PARAM_TEXT, 'Module type plural name'),
                    'iconurl' => new external_value(PARAM_URL, 'Module icon URL'),
                    'type' => new external_value(PARAM_TEXT, 'Content type: activity or resource'),
                    'visible' => new external_value(PARAM_BOOL, 'Visibility'),
                    'indent' => new external_value(PARAM_INT, 'Indent level'),
                ])
            ),
            'isempty' => new external_value(PARAM_BOOL, 'Whether section is empty'),
        ]);
    }
}
