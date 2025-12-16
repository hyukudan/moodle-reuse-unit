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
 * Audit logger for template approval actions and other significant events.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audit_logger {

    // Action types for templates.
    public const ACTION_TEMPLATE_SUBMITTED = 'template_submitted';
    public const ACTION_TEMPLATE_APPROVED = 'template_approved';
    public const ACTION_TEMPLATE_REJECTED = 'template_rejected';
    public const ACTION_TEMPLATE_DELETED = 'template_deleted';
    public const ACTION_TEMPLATE_UPDATED = 'template_updated';

    // Action types for sync operations.
    public const ACTION_SYNC_COMPLETED = 'sync_completed';
    public const ACTION_SYNC_FAILED = 'sync_failed';
    public const ACTION_BULK_SYNC_COMPLETED = 'bulk_sync_completed';
    public const ACTION_ROLLBACK_COMPLETED = 'rollback_completed';

    // Action types for links.
    public const ACTION_LINK_CREATED = 'link_created';
    public const ACTION_LINK_DELETED = 'link_deleted';
    public const ACTION_LINK_SETTINGS_CHANGED = 'link_settings_changed';

    /**
     * Log an audit event.
     *
     * @param string $action Action type
     * @param int $userid User performing the action
     * @param int|null $targetid ID of the target object (template, link, etc.)
     * @param string $targettype Type of target ('template', 'link', 'sync')
     * @param array $details Additional details as key-value pairs
     * @param string $ipaddress IP address (auto-detected if empty)
     * @return int Audit log ID
     */
    public static function log(
        string $action,
        int $userid,
        ?int $targetid = null,
        string $targettype = '',
        array $details = [],
        string $ipaddress = ''
    ): int {
        global $DB;

        // Auto-detect IP if not provided.
        if (empty($ipaddress)) {
            $ipaddress = getremoteaddr();
        }

        // Sanitize IP address.
        $ipaddress = substr(clean_param($ipaddress, PARAM_HOST), 0, 45);

        $record = new \stdClass();
        $record->userid = $userid;
        $record->action = clean_param($action, PARAM_ALPHANUMEXT);
        $record->targetid = $targetid;
        $record->targettype = clean_param($targettype, PARAM_ALPHANUMEXT);
        $record->details = json_encode($details, JSON_UNESCAPED_UNICODE);
        $record->ipaddress = $ipaddress;
        $record->timecreated = time();

        return $DB->insert_record('local_reuseunit_audit', $record);
    }

    /**
     * Log a template approval action.
     *
     * @param string $action Action type
     * @param int $userid Approver user ID
     * @param object $template Template object
     * @param string $reason Reason (for rejection)
     * @return int Audit log ID
     */
    public static function log_approval_action(
        string $action,
        int $userid,
        object $template,
        string $reason = ''
    ): int {
        $details = [
            'template_name' => $template->name,
            'template_owner' => $template->userid,
            'share_level' => $template->sharelevel ?? 'global',
        ];

        if (!empty($reason)) {
            $details['reason'] = $reason;
        }

        if (isset($template->approval_status)) {
            $details['previous_status'] = $template->approval_status;
        }

        return self::log($action, $userid, $template->id, 'template', $details);
    }

    /**
     * Log a sync operation.
     *
     * @param string $action Action type
     * @param int $userid User ID
     * @param int $linkid Link ID
     * @param array $stats Sync statistics
     * @param string $errormessage Error message if failed
     * @return int Audit log ID
     */
    public static function log_sync_operation(
        string $action,
        int $userid,
        int $linkid,
        array $stats = [],
        string $errormessage = ''
    ): int {
        $details = array_merge($stats, []);

        if (!empty($errormessage)) {
            $details['error'] = $errormessage;
        }

        return self::log($action, $userid, $linkid, 'sync', $details);
    }

    /**
     * Get audit logs for a specific target.
     *
     * @param int $targetid Target ID
     * @param string $targettype Target type
     * @param int $limit Maximum records to return
     * @param int $offset Offset for pagination
     * @return array Array of audit log records
     */
    public static function get_logs_for_target(
        int $targetid,
        string $targettype,
        int $limit = 50,
        int $offset = 0
    ): array {
        global $DB;

        $sql = "SELECT a.*, u.firstname, u.lastname, u.email
                FROM {local_reuseunit_audit} a
                LEFT JOIN {user} u ON u.id = a.userid
                WHERE a.targetid = :targetid AND a.targettype = :targettype
                ORDER BY a.timecreated DESC";

        return $DB->get_records_sql($sql, [
            'targetid' => $targetid,
            'targettype' => $targettype,
        ], $offset, $limit);
    }

    /**
     * Get audit logs by user.
     *
     * @param int $userid User ID
     * @param int $limit Maximum records to return
     * @param int $offset Offset for pagination
     * @return array Array of audit log records
     */
    public static function get_logs_by_user(
        int $userid,
        int $limit = 50,
        int $offset = 0
    ): array {
        global $DB;

        return $DB->get_records('local_reuseunit_audit', [
            'userid' => $userid,
        ], 'timecreated DESC', '*', $offset, $limit);
    }

    /**
     * Get all approval-related audit logs.
     *
     * @param int $limit Maximum records to return
     * @param int $offset Offset for pagination
     * @param int|null $fromtime Filter by time (null for all)
     * @return array Array of audit log records with user info
     */
    public static function get_approval_logs(
        int $limit = 50,
        int $offset = 0,
        ?int $fromtime = null
    ): array {
        global $DB;

        $params = [
            'action1' => self::ACTION_TEMPLATE_SUBMITTED,
            'action2' => self::ACTION_TEMPLATE_APPROVED,
            'action3' => self::ACTION_TEMPLATE_REJECTED,
        ];

        $timesql = '';
        if ($fromtime !== null) {
            $timesql = ' AND a.timecreated >= :fromtime';
            $params['fromtime'] = $fromtime;
        }

        $sql = "SELECT a.*, u.firstname, u.lastname, u.email,
                       t.name as template_name, t.userid as template_owner
                FROM {local_reuseunit_audit} a
                LEFT JOIN {user} u ON u.id = a.userid
                LEFT JOIN {local_reuseunit_templates} t ON t.id = a.targetid AND a.targettype = 'template'
                WHERE a.action IN (:action1, :action2, :action3)
                {$timesql}
                ORDER BY a.timecreated DESC";

        return $DB->get_records_sql($sql, $params, $offset, $limit);
    }

    /**
     * Count audit logs by action type within a time period.
     *
     * @param string $action Action type
     * @param int|null $fromtime Start time (null for all time)
     * @param int|null $totime End time (null for now)
     * @return int Count
     */
    public static function count_by_action(
        string $action,
        ?int $fromtime = null,
        ?int $totime = null
    ): int {
        global $DB;

        $params = ['action' => $action];
        $conditions = ['action = :action'];

        if ($fromtime !== null) {
            $conditions[] = 'timecreated >= :fromtime';
            $params['fromtime'] = $fromtime;
        }

        if ($totime !== null) {
            $conditions[] = 'timecreated <= :totime';
            $params['totime'] = $totime;
        }

        $sql = "SELECT COUNT(*) FROM {local_reuseunit_audit} WHERE " . implode(' AND ', $conditions);

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Cleanup old audit logs.
     *
     * @param int $olderThanDays Delete logs older than this many days
     * @return int Number of deleted records
     */
    public static function cleanup(int $olderThanDays = 365): int {
        global $DB;

        $cutoff = time() - ($olderThanDays * 24 * 60 * 60);

        return $DB->delete_records_select(
            'local_reuseunit_audit',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );
    }
}
