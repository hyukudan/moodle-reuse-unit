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
 * Constants for local_reuseunit plugin.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {

    // Sync modes.
    /** @var string Replace mode - replaces all content */
    public const SYNC_MODE_REPLACE = 'replace';

    /** @var string Merge mode - adds new content without removing existing */
    public const SYNC_MODE_MERGE = 'merge';

    /** @var string Selective mode - user chooses what to sync */
    public const SYNC_MODE_SELECTIVE = 'selective';

    /** @var array All valid sync modes */
    public const SYNC_MODES = [
        self::SYNC_MODE_REPLACE,
        self::SYNC_MODE_MERGE,
        self::SYNC_MODE_SELECTIVE,
    ];

    // Share levels.
    /** @var string Personal - only visible to creator */
    public const SHARE_LEVEL_PERSONAL = 'personal';

    /** @var string Category - visible to users in same category */
    public const SHARE_LEVEL_CATEGORY = 'category';

    /** @var string Global - visible to all users */
    public const SHARE_LEVEL_GLOBAL = 'global';

    /** @var array All valid share levels */
    public const SHARE_LEVELS = [
        self::SHARE_LEVEL_PERSONAL,
        self::SHARE_LEVEL_CATEGORY,
        self::SHARE_LEVEL_GLOBAL,
    ];

    // Sync history statuses.
    /** @var string Sync completed successfully */
    public const STATUS_COMPLETED = 'completed';

    /** @var string Sync failed */
    public const STATUS_FAILED = 'failed';

    /** @var string Sync was rolled back */
    public const STATUS_ROLLED_BACK = 'rolled_back';

    /** @var array All valid sync statuses */
    public const SYNC_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_ROLLED_BACK,
    ];

    // Import/task statuses.
    /** @var string Task is pending */
    public const TASK_STATUS_PENDING = 'pending';

    /** @var string Task is running */
    public const TASK_STATUS_RUNNING = 'running';

    /** @var string Task completed */
    public const TASK_STATUS_COMPLETED = 'completed';

    /** @var string Task failed */
    public const TASK_STATUS_FAILED = 'failed';

    /** @var string Task was cancelled */
    public const TASK_STATUS_CANCELLED = 'cancelled';

    /** @var array All valid task statuses */
    public const TASK_STATUSES = [
        self::TASK_STATUS_PENDING,
        self::TASK_STATUS_RUNNING,
        self::TASK_STATUS_COMPLETED,
        self::TASK_STATUS_FAILED,
        self::TASK_STATUS_CANCELLED,
    ];

    // Template approval statuses.
    /** @var string Template is pending approval */
    public const APPROVAL_PENDING = 'pending';

    /** @var string Template is approved */
    public const APPROVAL_APPROVED = 'approved';

    /** @var string Template is rejected */
    public const APPROVAL_REJECTED = 'rejected';

    /** @var array All valid approval statuses */
    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING,
        self::APPROVAL_APPROVED,
        self::APPROVAL_REJECTED,
    ];

    // Insert positions.
    /** @var string Insert at start of course */
    public const POSITION_START = 'start';

    /** @var string Insert at end of course */
    public const POSITION_END = 'end';

    /** @var string Insert after specific section (prefix) */
    public const POSITION_AFTER_PREFIX = 'after:';

    // Cache limits.
    /** @var int Maximum number of entries in timemodified cache */
    public const CACHE_MAX_ENTRIES = 1000;

    // Defaults.
    /** @var int Default template version */
    public const DEFAULT_VERSION = 1;

    /** @var int Default sync limit for batch operations */
    public const DEFAULT_BATCH_LIMIT = 50;

    /**
     * Check if a sync mode is valid.
     *
     * @param string $mode The mode to check
     * @return bool True if valid
     */
    public static function is_valid_sync_mode(string $mode): bool {
        return in_array($mode, self::SYNC_MODES, true);
    }

    /**
     * Check if a share level is valid.
     *
     * @param string $level The level to check
     * @return bool True if valid
     */
    public static function is_valid_share_level(string $level): bool {
        return in_array($level, self::SHARE_LEVELS, true);
    }

    /**
     * Check if a sync status is valid.
     *
     * @param string $status The status to check
     * @return bool True if valid
     */
    public static function is_valid_sync_status(string $status): bool {
        return in_array($status, self::SYNC_STATUSES, true);
    }

    /**
     * Check if a task status is valid.
     *
     * @param string $status The status to check
     * @return bool True if valid
     */
    public static function is_valid_task_status(string $status): bool {
        return in_array($status, self::TASK_STATUSES, true);
    }

    /**
     * Check if an approval status is valid.
     *
     * @param string $status The status to check
     * @return bool True if valid
     */
    public static function is_valid_approval_status(string $status): bool {
        return in_array($status, self::APPROVAL_STATUSES, true);
    }
}
