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

    /**
     * Save module mappings after import/sync.
     *
     * @param int $linkid Link ID
     * @param array $mappings Array of ['source_cmid' => int, 'dest_cmid' => int, 'modname' => string, 'name' => string]
     * @return void
     */
    public static function save_module_mappings(int $linkid, array $mappings): void {
        global $DB;

        $now = time();

        foreach ($mappings as $mapping) {
            $existing = $DB->get_record('local_reuseunit_synced_modules', [
                'linkid' => $linkid,
                'source_cmid' => $mapping['source_cmid'],
            ]);

            if ($existing) {
                // Update existing mapping.
                $existing->dest_cmid = $mapping['dest_cmid'];
                $existing->modname = $mapping['modname'];
                $existing->source_name = $mapping['name'] ?? '';
                $existing->timemodified = $now;
                $DB->update_record('local_reuseunit_synced_modules', $existing);
            } else {
                // Create new mapping.
                $record = new \stdClass();
                $record->linkid = $linkid;
                $record->source_cmid = $mapping['source_cmid'];
                $record->dest_cmid = $mapping['dest_cmid'];
                $record->modname = $mapping['modname'];
                $record->source_name = $mapping['name'] ?? '';
                $record->timecreated = $now;
                $record->timemodified = $now;
                $DB->insert_record('local_reuseunit_synced_modules', $record);
            }
        }
    }

    /**
     * Get all synced module mappings for a link.
     *
     * @param int $linkid Link ID
     * @return array Array of mapping records
     */
    public static function get_synced_modules(int $linkid): array {
        global $DB;
        return $DB->get_records('local_reuseunit_synced_modules', ['linkid' => $linkid]);
    }

    /**
     * Get destination cmids that came from the template.
     *
     * @param int $linkid Link ID
     * @return array Array of destination cmids
     */
    public static function get_template_dest_cmids(int $linkid): array {
        global $DB;
        $records = $DB->get_records('local_reuseunit_synced_modules', ['linkid' => $linkid], '', 'dest_cmid');
        return array_keys($records);
    }

    /**
     * Check if a course module is local (not from template).
     *
     * @param int $linkid Link ID
     * @param int $cmid Course module ID in destination
     * @return bool True if module is local (not from template)
     */
    public static function is_local_module(int $linkid, int $cmid): bool {
        global $DB;
        return !$DB->record_exists('local_reuseunit_synced_modules', [
            'linkid' => $linkid,
            'dest_cmid' => $cmid,
        ]);
    }

    /**
     * Delete module mappings for a link.
     *
     * @param int $linkid Link ID
     * @param array $destcmids Optional specific dest cmids to delete (all if empty)
     * @return void
     */
    public static function delete_module_mappings(int $linkid, array $destcmids = []): void {
        global $DB;

        if (empty($destcmids)) {
            $DB->delete_records('local_reuseunit_synced_modules', ['linkid' => $linkid]);
        } else {
            foreach ($destcmids as $cmid) {
                $DB->delete_records('local_reuseunit_synced_modules', [
                    'linkid' => $linkid,
                    'dest_cmid' => $cmid,
                ]);
            }
        }
    }

    /**
     * Get detailed sync preview for a link.
     *
     * Returns what will happen when sync is performed:
     * - added: New modules in template that will be added
     * - modified: Modules that have changed and will be updated
     * - removed: Modules in destination that are no longer in template (only for full sync)
     * - local: Modules in destination that were added locally (will be preserved)
     *
     * @param int $linkid Link ID
     * @return array Preview data with added, modified, removed, local arrays
     */
    public static function get_sync_preview(int $linkid): array {
        global $DB;

        $preview = [
            'added' => [],
            'modified' => [],
            'removed' => [],
            'local' => [],
            'unchanged' => [],
            'has_changes' => false,
        ];

        $link = $DB->get_record('local_reuseunit_links', ['id' => $linkid]);
        if (!$link) {
            return $preview;
        }

        $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);
        if (!$template) {
            return $preview;
        }

        // Get source section modules.
        $sourcemodinfo = get_fast_modinfo($template->source_courseid);
        $sourcesection = $DB->get_record('course_sections', ['id' => $template->source_sectionid]);
        if (!$sourcesection) {
            return $preview;
        }

        // Get destination section modules.
        $destmodinfo = get_fast_modinfo($link->courseid);
        $destsection = $DB->get_record('course_sections', ['id' => $link->sectionid]);
        if (!$destsection) {
            return $preview;
        }

        // Get existing module mappings.
        $syncedmodules = self::get_synced_modules($linkid);
        $mappingbysource = [];
        $mappingbydest = [];
        foreach ($syncedmodules as $mapping) {
            $mappingbysource[$mapping->source_cmid] = $mapping;
            $mappingbydest[$mapping->dest_cmid] = $mapping;
        }

        // Determine which source cmids to consider (for partial imports).
        $selectedcmids = [];
        if (!empty($link->partial_import) && !empty($link->imported_cmids)) {
            $selectedcmids = json_decode($link->imported_cmids, true) ?: [];
        }

        // Get source modules.
        $sourcemods = [];
        if (isset($sourcemodinfo->sections[$sourcesection->section])) {
            foreach ($sourcemodinfo->sections[$sourcesection->section] as $cmid) {
                if (!empty($selectedcmids) && !in_array($cmid, $selectedcmids)) {
                    continue;
                }
                $cm = $sourcemodinfo->cms[$cmid];
                $sourcemods[$cmid] = [
                    'cmid' => $cmid,
                    'name' => $cm->name,
                    'modname' => $cm->modname,
                    'timemodified' => self::get_module_timemodified($cm),
                    'icon' => $cm->get_icon_url()->out(false),
                ];
            }
        }

        // Get destination modules.
        $destmods = [];
        if (isset($destmodinfo->sections[$destsection->section])) {
            foreach ($destmodinfo->sections[$destsection->section] as $cmid) {
                $cm = $destmodinfo->cms[$cmid];
                $destmods[$cmid] = [
                    'cmid' => $cmid,
                    'name' => $cm->name,
                    'modname' => $cm->modname,
                    'timemodified' => self::get_module_timemodified($cm),
                    'icon' => $cm->get_icon_url()->out(false),
                ];
            }
        }

        // Analyze changes.
        foreach ($sourcemods as $sourcecmid => $sourcemod) {
            if (isset($mappingbysource[$sourcecmid])) {
                // This source module was previously synced.
                $mapping = $mappingbysource[$sourcecmid];
                $destcmid = $mapping->dest_cmid;

                if (isset($destmods[$destcmid])) {
                    // Check if modified.
                    $destmod = $destmods[$destcmid];
                    if ($sourcemod['timemodified'] > $mapping->timemodified) {
                        $preview['modified'][] = [
                            'source' => $sourcemod,
                            'dest' => $destmod,
                            'source_cmid' => $sourcecmid,
                            'dest_cmid' => $destcmid,
                        ];
                    } else {
                        $preview['unchanged'][] = [
                            'source' => $sourcemod,
                            'dest' => $destmod,
                            'source_cmid' => $sourcecmid,
                            'dest_cmid' => $destcmid,
                        ];
                    }
                } else {
                    // Destination module was deleted, treat as added again.
                    $preview['added'][] = $sourcemod;
                }
            } else {
                // New module in source.
                $preview['added'][] = $sourcemod;
            }
        }

        // Find removed (modules that were synced but no longer in source).
        foreach ($mappingbysource as $sourcecmid => $mapping) {
            if (!isset($sourcemods[$sourcecmid])) {
                // Source module no longer exists.
                if (isset($destmods[$mapping->dest_cmid])) {
                    $preview['removed'][] = [
                        'dest' => $destmods[$mapping->dest_cmid],
                        'source_cmid' => $sourcecmid,
                        'dest_cmid' => $mapping->dest_cmid,
                    ];
                }
            }
        }

        // Find local modules (in dest but not from template).
        foreach ($destmods as $destcmid => $destmod) {
            if (!isset($mappingbydest[$destcmid])) {
                $preview['local'][] = $destmod;
            }
        }

        $preview['has_changes'] = !empty($preview['added']) ||
                                   !empty($preview['modified']) ||
                                   !empty($preview['removed']);

        return $preview;
    }

    /**
     * Build a module name mapping key.
     *
     * Used as fallback for mapping modules when cmid mapping doesn't exist.
     *
     * @param string $modname Module type
     * @param string $name Module name
     * @return string Mapping key
     */
    public static function build_module_key(string $modname, string $name): string {
        return $modname . '::' . $name;
    }

    /**
     * Find matching module in destination by name.
     *
     * @param array $destmods Array of destination modules
     * @param string $modname Module type to match
     * @param string $name Module name to match
     * @return int|null Matching cmid or null
     */
    public static function find_module_by_name(array $destmods, string $modname, string $name): ?int {
        foreach ($destmods as $cmid => $mod) {
            if ($mod['modname'] === $modname && $mod['name'] === $name) {
                return $cmid;
            }
        }
        return null;
    }
}
