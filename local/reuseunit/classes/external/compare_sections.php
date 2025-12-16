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
 * External function to compare two sections and show differences.
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class compare_sections extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'source_courseid' => new external_value(PARAM_INT, 'Source course ID'),
            'source_sectionid' => new external_value(PARAM_INT, 'Source section ID'),
            'target_courseid' => new external_value(PARAM_INT, 'Target course ID'),
            'target_sectionid' => new external_value(PARAM_INT, 'Target section ID'),
        ]);
    }

    /**
     * Compare two sections and return differences.
     *
     * @param int $source_courseid Source course ID
     * @param int $source_sectionid Source section ID
     * @param int $target_courseid Target course ID
     * @param int $target_sectionid Target section ID
     * @return array Comparison results
     */
    public static function execute(int $source_courseid, int $source_sectionid, int $target_courseid, int $target_sectionid): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'source_courseid' => $source_courseid,
            'source_sectionid' => $source_sectionid,
            'target_courseid' => $target_courseid,
            'target_sectionid' => $target_sectionid,
        ]);

        // Check course access.
        $sourcecontext = context_course::instance($params['source_courseid']);
        self::validate_context($sourcecontext);
        require_capability('local/reuseunit:export', $sourcecontext);

        $targetcontext = context_course::instance($params['target_courseid']);
        self::validate_context($targetcontext);
        require_capability('local/reuseunit:import', $targetcontext);

        // Get sections.
        $sourcesection = $DB->get_record('course_sections', ['id' => $params['source_sectionid']], '*', MUST_EXIST);
        $targetsection = $DB->get_record('course_sections', ['id' => $params['target_sectionid']], '*', MUST_EXIST);

        // Get module info for both sections.
        $sourcemodinfo = get_fast_modinfo($params['source_courseid']);
        $targetmodinfo = get_fast_modinfo($params['target_courseid']);

        // Build content lists.
        $sourcecontent = self::get_section_content($sourcemodinfo, $sourcesection->section);
        $targetcontent = self::get_section_content($targetmodinfo, $targetsection->section);

        // Compare content.
        $comparison = self::compare_content($sourcecontent, $targetcontent);

        return [
            'sourcename' => $sourcesection->name ?: get_string('section', 'local_reuseunit') . ' ' . $sourcesection->section,
            'targetname' => $targetsection->name ?: get_string('section', 'local_reuseunit') . ' ' . $targetsection->section,
            'sourcecount' => count($sourcecontent),
            'targetcount' => count($targetcontent),
            'added' => $comparison['added'],
            'removed' => $comparison['removed'],
            'modified' => $comparison['modified'],
            'unchanged' => $comparison['unchanged'],
            'addedcount' => count($comparison['added']),
            'removedcount' => count($comparison['removed']),
            'modifiedcount' => count($comparison['modified']),
            'unchangedcount' => count($comparison['unchanged']),
            'hasdifferences' => !empty($comparison['added']) || !empty($comparison['removed']) || !empty($comparison['modified']),
        ];
    }

    /**
     * Get section content as array of modules.
     *
     * @param \course_modinfo $modinfo Module info
     * @param int $sectionnumber Section number
     * @return array Content items
     */
    protected static function get_section_content(\course_modinfo $modinfo, int $sectionnumber): array {
        $content = [];

        if (!isset($modinfo->sections[$sectionnumber])) {
            return $content;
        }

        foreach ($modinfo->sections[$sectionnumber] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if (!$cm->uservisible) {
                continue;
            }

            $content[] = [
                'id' => $cm->id,
                'modname' => $cm->modname,
                'name' => $cm->name,
                'instanceid' => $cm->instance,
            ];
        }

        return $content;
    }

    /**
     * Compare two content arrays.
     *
     * @param array $source Source content
     * @param array $target Target content
     * @return array Comparison results
     */
    protected static function compare_content(array $source, array $target): array {
        $result = [
            'added' => [],
            'removed' => [],
            'modified' => [],
            'unchanged' => [],
        ];

        // Index source by modname+name for matching.
        $sourceindex = [];
        foreach ($source as $item) {
            $key = $item['modname'] . ':' . $item['name'];
            $sourceindex[$key] = $item;
        }

        // Index target by modname+name.
        $targetindex = [];
        foreach ($target as $item) {
            $key = $item['modname'] . ':' . $item['name'];
            $targetindex[$key] = $item;
        }

        // Find items in source not in target (would be added).
        foreach ($source as $item) {
            $key = $item['modname'] . ':' . $item['name'];
            if (!isset($targetindex[$key])) {
                $result['added'][] = [
                    'name' => $item['name'],
                    'modname' => $item['modname'],
                    'modicon' => self::get_mod_icon($item['modname']),
                ];
            } else {
                // Same item exists - check if modified (simplified comparison).
                $result['unchanged'][] = [
                    'name' => $item['name'],
                    'modname' => $item['modname'],
                    'modicon' => self::get_mod_icon($item['modname']),
                ];
            }
        }

        // Find items in target not in source (would be removed if replacing).
        foreach ($target as $item) {
            $key = $item['modname'] . ':' . $item['name'];
            if (!isset($sourceindex[$key])) {
                $result['removed'][] = [
                    'name' => $item['name'],
                    'modname' => $item['modname'],
                    'modicon' => self::get_mod_icon($item['modname']),
                ];
            }
        }

        return $result;
    }

    /**
     * Get module icon URL.
     *
     * @param string $modname Module name
     * @return string Icon URL
     */
    protected static function get_mod_icon(string $modname): string {
        global $OUTPUT;
        return $OUTPUT->image_url('monologo', $modname)->out(false);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $itemstructure = new external_single_structure([
            'name' => new external_value(PARAM_TEXT, 'Item name'),
            'modname' => new external_value(PARAM_ALPHA, 'Module type'),
            'modicon' => new external_value(PARAM_URL, 'Module icon URL'),
        ]);

        return new external_single_structure([
            'sourcename' => new external_value(PARAM_TEXT, 'Source section name'),
            'targetname' => new external_value(PARAM_TEXT, 'Target section name'),
            'sourcecount' => new external_value(PARAM_INT, 'Source item count'),
            'targetcount' => new external_value(PARAM_INT, 'Target item count'),
            'added' => new external_multiple_structure($itemstructure, 'Items to be added'),
            'removed' => new external_multiple_structure($itemstructure, 'Items to be removed'),
            'modified' => new external_multiple_structure($itemstructure, 'Items to be modified'),
            'unchanged' => new external_multiple_structure($itemstructure, 'Unchanged items'),
            'addedcount' => new external_value(PARAM_INT, 'Added count'),
            'removedcount' => new external_value(PARAM_INT, 'Removed count'),
            'modifiedcount' => new external_value(PARAM_INT, 'Modified count'),
            'unchangedcount' => new external_value(PARAM_INT, 'Unchanged count'),
            'hasdifferences' => new external_value(PARAM_BOOL, 'Has differences'),
        ]);
    }
}
