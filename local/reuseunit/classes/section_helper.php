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

namespace local_reuseunit;

/**
 * Helper class for section content operations.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_helper {

    /**
     * Calculate a content hash for a section.
     *
     * The hash is based on:
     * - Section name and summary
     * - List of course modules (cmid, modname, name, timemodified)
     * - For partial imports, only selected cmids are included
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID
     * @param array $selectedcmids Optional array of cmids to include (for partial imports)
     * @return string SHA256 hash of section content
     */
    public static function calculate_contenthash(int $courseid, int $sectionid, array $selectedcmids = []): string {
        global $DB;

        $section = $DB->get_record('course_sections', ['id' => $sectionid, 'course' => $courseid]);
        if (!$section) {
            return '';
        }

        $modinfo = get_fast_modinfo($courseid);
        $sectionnum = $section->section;

        $contentdata = [
            'section_name' => $section->name ?? '',
            'section_summary' => $section->summary ?? '',
            'modules' => [],
        ];

        if (isset($modinfo->sections[$sectionnum])) {
            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                // Skip if we have a selection and this cmid is not in it.
                if (!empty($selectedcmids) && !in_array($cmid, $selectedcmids)) {
                    continue;
                }

                $cm = $modinfo->cms[$cmid];
                $contentdata['modules'][] = [
                    'cmid' => $cmid,
                    'modname' => $cm->modname,
                    'name' => $cm->name,
                    'timemodified' => self::get_module_timemodified($cm),
                ];
            }
        }

        // Sort modules by cmid for consistent hashing.
        usort($contentdata['modules'], function($a, $b) {
            return $a['cmid'] <=> $b['cmid'];
        });

        return hash('sha256', json_encode($contentdata));
    }

    /**
     * Get the timemodified for a course module.
     *
     * @param \cm_info $cm Course module info
     * @return int Timemodified timestamp
     */
    private static function get_module_timemodified(\cm_info $cm): int {
        global $DB;

        $tablename = $cm->modname;
        $record = $DB->get_record($tablename, ['id' => $cm->instance], 'timemodified');

        return $record ? (int)$record->timemodified : 0;
    }

    /**
     * Check if a linked section has updates available.
     *
     * @param \stdClass $link The link record from local_reuseunit_links
     * @return array ['has_updates' => bool, 'new_hash' => string, 'changes' => array]
     */
    public static function check_for_updates(\stdClass $link): array {
        global $DB;

        $result = [
            'has_updates' => false,
            'new_hash' => '',
            'changes' => [],
        ];

        // Get the template.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);
        if (!$template) {
            return $result;
        }

        // Determine which cmids to check.
        $selectedcmids = [];
        if (!empty($link->partial_import) && !empty($link->imported_cmids)) {
            $selectedcmids = json_decode($link->imported_cmids, true) ?: [];
        }

        // Calculate current hash of source section.
        $newhash = self::calculate_contenthash(
            $template->source_courseid,
            $template->source_sectionid,
            $selectedcmids
        );

        $result['new_hash'] = $newhash;

        // Compare with stored hash.
        $oldhash = $link->contenthash ?? '';
        if ($newhash !== $oldhash) {
            $result['has_updates'] = true;

            // Get detailed changes.
            $result['changes'] = self::get_content_changes(
                $template->source_courseid,
                $template->source_sectionid,
                $link->courseid,
                $link->sectionid,
                $selectedcmids
            );
        }

        return $result;
    }

    /**
     * Get detailed content changes between source and destination sections.
     *
     * @param int $sourcecourseid Source course ID
     * @param int $sourcesectionid Source section ID
     * @param int $destcourseid Destination course ID
     * @param int $destsectionid Destination section ID
     * @param array $selectedcmids Optional cmids filter
     * @return array Array of changes
     */
    public static function get_content_changes(
        int $sourcecourseid,
        int $sourcesectionid,
        int $destcourseid,
        int $destsectionid,
        array $selectedcmids = []
    ): array {
        global $DB;

        $changes = [
            'added' => [],
            'modified' => [],
            'removed' => [],
        ];

        // Get source section modules.
        $sourcemodinfo = get_fast_modinfo($sourcecourseid);
        $sourcesection = $DB->get_record('course_sections', ['id' => $sourcesectionid]);
        if (!$sourcesection) {
            return $changes;
        }

        $sourcemods = [];
        if (isset($sourcemodinfo->sections[$sourcesection->section])) {
            foreach ($sourcemodinfo->sections[$sourcesection->section] as $cmid) {
                if (!empty($selectedcmids) && !in_array($cmid, $selectedcmids)) {
                    continue;
                }
                $cm = $sourcemodinfo->cms[$cmid];
                $sourcemods[$cm->name . '_' . $cm->modname] = [
                    'cmid' => $cmid,
                    'name' => $cm->name,
                    'modname' => $cm->modname,
                    'timemodified' => self::get_module_timemodified($cm),
                ];
            }
        }

        // Get destination section modules.
        $destmodinfo = get_fast_modinfo($destcourseid);
        $destsection = $DB->get_record('course_sections', ['id' => $destsectionid]);
        if (!$destsection) {
            return $changes;
        }

        $destmods = [];
        if (isset($destmodinfo->sections[$destsection->section])) {
            foreach ($destmodinfo->sections[$destsection->section] as $cmid) {
                $cm = $destmodinfo->cms[$cmid];
                $destmods[$cm->name . '_' . $cm->modname] = [
                    'cmid' => $cmid,
                    'name' => $cm->name,
                    'modname' => $cm->modname,
                    'timemodified' => self::get_module_timemodified($cm),
                ];
            }
        }

        // Find added (in source but not in dest).
        foreach ($sourcemods as $key => $mod) {
            if (!isset($destmods[$key])) {
                $changes['added'][] = $mod;
            }
        }

        // Find removed (in dest but not in source) - only for non-partial imports.
        if (empty($selectedcmids)) {
            foreach ($destmods as $key => $mod) {
                if (!isset($sourcemods[$key])) {
                    $changes['removed'][] = $mod;
                }
            }
        }

        // Find modified (in both but source is newer).
        foreach ($sourcemods as $key => $mod) {
            if (isset($destmods[$key])) {
                if ($mod['timemodified'] > $destmods[$key]['timemodified']) {
                    $changes['modified'][] = $mod;
                }
            }
        }

        return $changes;
    }

    /**
     * Count new items available in template since partial import.
     *
     * @param \stdClass $link The link record
     * @return int Number of new items
     */
    public static function count_new_items(\stdClass $link): int {
        global $DB;

        if (empty($link->partial_import) || empty($link->imported_cmids)) {
            return 0;
        }

        $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);
        if (!$template) {
            return 0;
        }

        $importedcmids = json_decode($link->imported_cmids, true) ?: [];

        // Get all current cmids in source section.
        $modinfo = get_fast_modinfo($template->source_courseid);
        $section = $DB->get_record('course_sections', ['id' => $template->source_sectionid]);
        if (!$section || !isset($modinfo->sections[$section->section])) {
            return 0;
        }

        $currentcmids = $modinfo->sections[$section->section];

        // Count cmids that are in current but not in imported.
        $newcount = 0;
        foreach ($currentcmids as $cmid) {
            if (!in_array($cmid, $importedcmids)) {
                $newcount++;
            }
        }

        return $newcount;
    }
}
