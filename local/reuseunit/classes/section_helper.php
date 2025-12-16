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
            // Collect cmids for batch loading.
            $cmidstoload = [];
            foreach ($modinfo->sections[$sectionnum] as $cmid) {
                if (!empty($selectedcmids) && !in_array($cmid, $selectedcmids)) {
                    continue;
                }
                $cmidstoload[] = $cmid;
            }

            // Batch load timemodified values.
            $preloaded = self::batch_load_timemodified($cmidstoload, $modinfo);

            foreach ($cmidstoload as $cmid) {
                $cm = $modinfo->cms[$cmid];
                $contentdata['modules'][] = [
                    'cmid' => $cmid,
                    'modname' => $cm->modname,
                    'name' => $cm->name,
                    'timemodified' => self::get_module_timemodified($cm, $preloaded),
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
     * Cache for batch-loaded timemodified values.
     * @var array
     */
    private static $timemodifiedcache = [];

    /**
     * Get the timemodified for a course module.
     *
     * @param \cm_info $cm Course module info
     * @param array|null $preloaded Optional preloaded timemodified values keyed by modname_instance
     * @return int Timemodified timestamp
     */
    private static function get_module_timemodified(\cm_info $cm, ?array $preloaded = null): int {
        // Check preloaded values first.
        if ($preloaded !== null) {
            $key = $cm->modname . '_' . $cm->instance;
            if (isset($preloaded[$key])) {
                return (int)$preloaded[$key];
            }
        }

        // Check static cache.
        $cachekey = $cm->modname . '_' . $cm->instance;
        if (isset(self::$timemodifiedcache[$cachekey])) {
            return self::$timemodifiedcache[$cachekey];
        }

        global $DB;

        $tablename = $cm->modname;
        $record = $DB->get_record($tablename, ['id' => $cm->instance], 'timemodified');
        $timemodified = $record ? (int)$record->timemodified : 0;

        // Cache the result.
        self::$timemodifiedcache[$cachekey] = $timemodified;

        return $timemodified;
    }

    /**
     * Batch load timemodified values for multiple modules.
     *
     * Groups modules by type and performs a single query per module type.
     *
     * @param array $cmids Array of course module IDs
     * @param \course_modinfo $modinfo Course mod info object
     * @return array Keyed by modname_instance => timemodified
     */
    private static function batch_load_timemodified(array $cmids, \course_modinfo $modinfo): array {
        global $DB;

        $result = [];

        // Group cmids by module type.
        $bymodtype = [];
        foreach ($cmids as $cmid) {
            if (!isset($modinfo->cms[$cmid])) {
                continue;
            }
            $cm = $modinfo->cms[$cmid];
            if (!isset($bymodtype[$cm->modname])) {
                $bymodtype[$cm->modname] = [];
            }
            $bymodtype[$cm->modname][$cm->instance] = $cmid;
        }

        // Query each module type table once.
        foreach ($bymodtype as $modname => $instances) {
            $instanceids = array_keys($instances);
            if (empty($instanceids)) {
                continue;
            }

            list($insql, $inparams) = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
            $records = $DB->get_records_select($modname, "id $insql", $inparams, '', 'id, timemodified');

            foreach ($records as $record) {
                $key = $modname . '_' . $record->id;
                $result[$key] = (int)$record->timemodified;
                // Also update static cache.
                self::$timemodifiedcache[$key] = (int)$record->timemodified;
            }
        }

        return $result;
    }

    /**
     * Clear the timemodified cache.
     *
     * @return void
     */
    public static function clear_timemodified_cache(): void {
        self::$timemodifiedcache = [];
    }

    /**
     * Safely decode JSON with validation.
     *
     * @param string $json JSON string to decode
     * @param mixed $default Default value if decoding fails
     * @param bool $assoc Whether to return associative array
     * @return mixed Decoded value or default
     */
    public static function safe_json_decode(string $json, $default = null, bool $assoc = true) {
        if (empty($json)) {
            return $default;
        }

        $decoded = json_decode($json, $assoc);

        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('JSON decode error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return $default;
        }

        return $decoded ?? $default;
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

        // Collect cmids for batch loading from source.
        $sourcecmids = [];
        if (isset($sourcemodinfo->sections[$sourcesection->section])) {
            foreach ($sourcemodinfo->sections[$sourcesection->section] as $cmid) {
                if (!empty($selectedcmids) && !in_array($cmid, $selectedcmids)) {
                    continue;
                }
                $sourcecmids[] = $cmid;
            }
        }

        // Batch load source timemodified values.
        $sourcepreloaded = self::batch_load_timemodified($sourcecmids, $sourcemodinfo);

        $sourcemods = [];
        foreach ($sourcecmids as $cmid) {
            $cm = $sourcemodinfo->cms[$cmid];
            $sourcemods[$cm->name . '_' . $cm->modname] = [
                'cmid' => $cmid,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'timemodified' => self::get_module_timemodified($cm, $sourcepreloaded),
            ];
        }

        // Get destination section modules.
        $destmodinfo = get_fast_modinfo($destcourseid);
        $destsection = $DB->get_record('course_sections', ['id' => $destsectionid]);
        if (!$destsection) {
            return $changes;
        }

        // Collect cmids for batch loading from destination.
        $destcmids = [];
        if (isset($destmodinfo->sections[$destsection->section])) {
            $destcmids = $destmodinfo->sections[$destsection->section];
        }

        // Batch load destination timemodified values.
        $destpreloaded = self::batch_load_timemodified($destcmids, $destmodinfo);

        $destmods = [];
        foreach ($destcmids as $cmid) {
            $cm = $destmodinfo->cms[$cmid];
            $destmods[$cm->name . '_' . $cm->modname] = [
                'cmid' => $cmid,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'timemodified' => self::get_module_timemodified($cm, $destpreloaded),
            ];
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
     * @param array $mappings Array of mappings with keys:
     *   - source_cmid: int
     *   - dest_cmid: int
     *   - modname: string
     *   - name: string (optional)
     *   - source_timemodified: int (optional)
     *   - dest_timemodified: int (optional)
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
                $existing->source_timemodified = $mapping['source_timemodified'] ?? null;
                $existing->dest_timemodified = $mapping['dest_timemodified'] ?? null;
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
                $record->source_timemodified = $mapping['source_timemodified'] ?? null;
                $record->dest_timemodified = $mapping['dest_timemodified'] ?? null;
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
     * Get detailed sync preview for a link with conflict detection.
     *
     * Returns what will happen when sync is performed:
     * - added: New modules in template that will be added
     * - modified: Modules that have changed in source and will be updated
     * - conflicts: Modules modified in BOTH source AND destination (requires user decision)
     * - local_edits: Modules that were edited locally (dest modified since last sync)
     * - removed: Modules in destination that are no longer in template
     * - local: Modules in destination that were added locally (will be preserved)
     * - unchanged: Modules that haven't changed
     *
     * @param int $linkid Link ID
     * @return array Preview data with added, modified, conflicts, local_edits, removed, local, unchanged arrays
     */
    public static function get_sync_preview(int $linkid): array {
        global $DB;

        $preview = [
            'added' => [],
            'modified' => [],
            'conflicts' => [],
            'local_edits' => [],
            'removed' => [],
            'local' => [],
            'unchanged' => [],
            'has_changes' => false,
            'has_conflicts' => false,
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

        // Collect source cmids for batch loading.
        $sourcecmids = [];
        if (isset($sourcemodinfo->sections[$sourcesection->section])) {
            foreach ($sourcemodinfo->sections[$sourcesection->section] as $cmid) {
                if (!empty($selectedcmids) && !in_array($cmid, $selectedcmids)) {
                    continue;
                }
                $sourcecmids[] = $cmid;
            }
        }

        // Batch load source timemodified values.
        $sourcepreloaded = self::batch_load_timemodified($sourcecmids, $sourcemodinfo);

        // Get source modules.
        $sourcemods = [];
        foreach ($sourcecmids as $cmid) {
            $cm = $sourcemodinfo->cms[$cmid];
            $sourcemods[$cmid] = [
                'cmid' => $cmid,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'timemodified' => self::get_module_timemodified($cm, $sourcepreloaded),
                'icon' => $cm->get_icon_url()->out(false),
            ];
        }

        // Collect destination cmids for batch loading.
        $destcmids = [];
        if (isset($destmodinfo->sections[$destsection->section])) {
            $destcmids = $destmodinfo->sections[$destsection->section];
        }

        // Batch load destination timemodified values.
        $destpreloaded = self::batch_load_timemodified($destcmids, $destmodinfo);

        // Get destination modules.
        $destmods = [];
        foreach ($destcmids as $cmid) {
            $cm = $destmodinfo->cms[$cmid];
            $destmods[$cmid] = [
                'cmid' => $cmid,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'timemodified' => self::get_module_timemodified($cm, $destpreloaded),
                'icon' => $cm->get_icon_url()->out(false),
            ];
        }

        // Analyze changes with conflict detection.
        foreach ($sourcemods as $sourcecmid => $sourcemod) {
            if (isset($mappingbysource[$sourcecmid])) {
                // This source module was previously synced.
                $mapping = $mappingbysource[$sourcecmid];
                $destcmid = $mapping->dest_cmid;

                if (isset($destmods[$destcmid])) {
                    $destmod = $destmods[$destcmid];

                    // Check if source was modified since last sync.
                    $sourcemodified = !empty($mapping->source_timemodified) &&
                                      $sourcemod['timemodified'] > $mapping->source_timemodified;

                    // Check if dest was modified since last sync (local edit).
                    $destmodified = !empty($mapping->dest_timemodified) &&
                                    $destmod['timemodified'] > $mapping->dest_timemodified;

                    if ($sourcemodified && $destmodified) {
                        // CONFLICT: Both source and dest were modified.
                        $preview['conflicts'][] = [
                            'source' => $sourcemod,
                            'dest' => $destmod,
                            'source_cmid' => $sourcecmid,
                            'dest_cmid' => $destcmid,
                            'source_time' => $sourcemod['timemodified'],
                            'dest_time' => $destmod['timemodified'],
                            'last_sync' => $mapping->timemodified,
                        ];
                    } else if ($sourcemodified) {
                        // Source modified, can update.
                        $preview['modified'][] = [
                            'source' => $sourcemod,
                            'dest' => $destmod,
                            'source_cmid' => $sourcecmid,
                            'dest_cmid' => $destcmid,
                        ];
                    } else if ($destmodified) {
                        // Only dest modified (local edit, not in conflict).
                        $preview['local_edits'][] = [
                            'source' => $sourcemod,
                            'dest' => $destmod,
                            'source_cmid' => $sourcecmid,
                            'dest_cmid' => $destcmid,
                            'dest_time' => $destmod['timemodified'],
                            'last_sync' => $mapping->timemodified,
                        ];
                    } else {
                        // Neither modified.
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
                                   !empty($preview['conflicts']) ||
                                   !empty($preview['removed']);

        $preview['has_conflicts'] = !empty($preview['conflicts']);

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

    /**
     * Log a sync operation to history.
     *
     * @param int $linkid Link ID
     * @param int $userid User ID
     * @param string $syncmode Sync mode (replace, merge, selective)
     * @param array $stats Stats array with added, updated, removed, preserved, conflicts counts
     * @param array $changesdata Detailed changes data for potential rollback
     * @param string $status Status (completed, failed, rolled_back)
     * @param string $errormessage Error message if failed
     * @param string $hashbefore Content hash before sync
     * @param string $hashafter Content hash after sync
     * @return int The sync history record ID
     */
    public static function log_sync_history(
        int $linkid,
        int $userid,
        string $syncmode,
        array $stats,
        array $changesdata,
        string $status = 'completed',
        string $errormessage = '',
        string $hashbefore = '',
        string $hashafter = ''
    ): int {
        global $DB;

        $record = new \stdClass();
        $record->linkid = $linkid;
        $record->userid = $userid;
        $record->sync_mode = $syncmode;
        $record->added_count = $stats['added'] ?? 0;
        $record->updated_count = $stats['updated'] ?? 0;
        $record->removed_count = $stats['removed'] ?? 0;
        $record->preserved_count = $stats['preserved'] ?? 0;
        $record->conflict_count = $stats['conflicts'] ?? 0;
        $record->changes_data = json_encode($changesdata);
        $record->status = $status;
        $record->error_message = $errormessage;
        $record->contenthash_before = $hashbefore;
        $record->contenthash_after = $hashafter;
        $record->timecreated = time();

        return $DB->insert_record('local_reuseunit_sync_history', $record);
    }

    /**
     * Get sync history for a link.
     *
     * @param int $linkid Link ID
     * @param int $limit Maximum number of records to return
     * @return array Array of sync history records
     */
    public static function get_sync_history(int $linkid, int $limit = 20): array {
        global $DB;

        return $DB->get_records('local_reuseunit_sync_history', [
            'linkid' => $linkid,
        ], 'timecreated DESC', '*', 0, $limit);
    }

    /**
     * Get a specific sync history record.
     *
     * @param int $historyid History record ID
     * @return \stdClass|false The history record or false
     */
    public static function get_sync_history_record(int $historyid) {
        global $DB;
        return $DB->get_record('local_reuseunit_sync_history', ['id' => $historyid]);
    }

    /**
     * Mark a sync history record as rolled back.
     *
     * @param int $historyid History record ID
     * @return bool Success
     */
    public static function mark_history_rolled_back(int $historyid): bool {
        global $DB;

        return $DB->update_record('local_reuseunit_sync_history', (object)[
            'id' => $historyid,
            'status' => 'rolled_back',
        ]);
    }

    /**
     * Get all linked sections for a template (for bulk sync).
     *
     * @param int $templateid Template ID
     * @param bool $autosynconly Only return links with autosync enabled
     * @return array Array of link records
     */
    public static function get_template_links(int $templateid, bool $autosynconly = false): array {
        global $DB;

        $params = ['templateid' => $templateid];

        if ($autosynconly) {
            $params['autosync'] = 1;
        }

        return $DB->get_records('local_reuseunit_links', $params);
    }

    /**
     * Get links that need auto-sync based on granular options.
     *
     * @param int $templateid Template ID
     * @return array Array of link records with their granular sync settings
     */
    public static function get_autosync_links(int $templateid): array {
        global $DB;

        return $DB->get_records('local_reuseunit_links', [
            'templateid' => $templateid,
            'autosync' => 1,
        ]);
    }

    /**
     * Prepare changes data for rollback storage.
     *
     * @param array $addedmods Modules that were added
     * @param array $updatedmods Modules that were updated (with before/after data)
     * @param array $removedmods Modules that were removed
     * @return array Structured changes data
     */
    public static function prepare_rollback_data(
        array $addedmods,
        array $updatedmods,
        array $removedmods
    ): array {
        return [
            'added' => $addedmods,
            'updated' => $updatedmods,
            'removed' => $removedmods,
            'timestamp' => time(),
        ];
    }

    /**
     * Check if a rollback is possible for a sync history record.
     *
     * A rollback is possible if:
     * - The history record exists and status is 'completed'
     * - The destination section still exists
     * - The modules referenced in changes_data are still valid
     *
     * @param int $historyid History record ID
     * @return array ['possible' => bool, 'reason' => string]
     */
    public static function can_rollback(int $historyid): array {
        global $DB;

        $history = self::get_sync_history_record($historyid);
        if (!$history) {
            return ['possible' => false, 'reason' => 'History record not found'];
        }

        if ($history->status !== 'completed') {
            return ['possible' => false, 'reason' => 'Sync was not completed successfully'];
        }

        $link = $DB->get_record('local_reuseunit_links', ['id' => $history->linkid]);
        if (!$link) {
            return ['possible' => false, 'reason' => 'Link no longer exists'];
        }

        $section = $DB->get_record('course_sections', ['id' => $link->sectionid]);
        if (!$section) {
            return ['possible' => false, 'reason' => 'Section no longer exists'];
        }

        // Check if there are newer syncs that would make rollback unsafe.
        $newersyncs = $DB->count_records_select('local_reuseunit_sync_history',
            'linkid = ? AND timecreated > ? AND status = ?',
            [$history->linkid, $history->timecreated, 'completed']
        );

        if ($newersyncs > 0) {
            return ['possible' => false, 'reason' => 'Newer syncs exist - rollback would cause data loss'];
        }

        return ['possible' => true, 'reason' => ''];
    }

    /**
     * Get all links for a course with sync status.
     *
     * @param int $courseid Course ID
     * @return array Array of links with additional status info
     */
    public static function get_course_links_with_status(int $courseid): array {
        global $DB;

        $links = $DB->get_records('local_reuseunit_links', ['courseid' => $courseid]);

        foreach ($links as &$link) {
            $template = $DB->get_record('local_reuseunit_templates', ['id' => $link->templateid]);
            $link->templatename = $template ? $template->name : '';
            $link->templatesource = $template ? $template->source_courseid : 0;

            // Get preview to check for updates.
            $preview = self::get_sync_preview($link->id);
            $link->has_updates = $preview['has_changes'];
            $link->has_conflicts = $preview['has_conflicts'];
            $link->updates_count = count($preview['added']) + count($preview['modified']);
            $link->conflicts_count = count($preview['conflicts']);
        }

        return $links;
    }

    /**
     * Calculate hash of destination section content.
     *
     * @param int $courseid Course ID
     * @param int $sectionid Section ID
     * @return string SHA256 hash
     */
    public static function calculate_dest_contenthash(int $courseid, int $sectionid): string {
        return self::calculate_contenthash($courseid, $sectionid);
    }
}
