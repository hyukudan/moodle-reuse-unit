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
 * Rate limiter class for bulk operations.
 *
 * @package    local_reuseunit
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter {

    /** @var string Cache definition name for rate limiting */
    private const CACHE_AREA = 'ratelimit';

    /** @var int Default max requests per window */
    public const DEFAULT_MAX_REQUESTS = 10;

    /** @var int Default time window in seconds (1 hour) */
    public const DEFAULT_WINDOW_SECONDS = 3600;

    /** @var int Default cooldown period after hitting limit (minutes) */
    public const DEFAULT_COOLDOWN_MINUTES = 15;

    /**
     * Check if user is rate limited for a specific operation.
     *
     * @param int $userid User ID
     * @param string $operation Operation type (e.g., 'bulk_sync', 'batch_import')
     * @param int $maxrequests Maximum requests allowed
     * @param int $windowseconds Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int, 'message' => string]
     */
    public static function check(
        int $userid,
        string $operation,
        int $maxrequests = self::DEFAULT_MAX_REQUESTS,
        int $windowseconds = self::DEFAULT_WINDOW_SECONDS
    ): array {
        $key = self::get_cache_key($userid, $operation);
        $cache = \cache::make('local_reuseunit', self::CACHE_AREA);
        $now = time();

        // Get current rate limit data.
        $data = $cache->get($key);

        if ($data === false) {
            // No existing data, user hasn't made any requests yet.
            return [
                'allowed' => true,
                'remaining' => $maxrequests - 1,
                'reset_time' => $now + $windowseconds,
                'message' => '',
            ];
        }

        // Clean up old requests outside the window.
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $windowseconds) {
            return $timestamp > ($now - $windowseconds);
        });

        $requestcount = count($data['requests']);
        $remaining = max(0, $maxrequests - $requestcount);

        // Check if rate limit is exceeded.
        if ($requestcount >= $maxrequests) {
            // Find when the oldest request will expire.
            $oldestrequest = min($data['requests']);
            $resettime = $oldestrequest + $windowseconds;
            $waitminutes = ceil(($resettime - $now) / 60);

            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => $resettime,
                'message' => get_string('ratelimit_exceeded', 'local_reuseunit', $waitminutes),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining - 1,
            'reset_time' => $now + $windowseconds,
            'message' => '',
        ];
    }

    /**
     * Record a request for rate limiting.
     *
     * @param int $userid User ID
     * @param string $operation Operation type
     * @param int $windowseconds Time window in seconds
     * @return void
     */
    public static function record(
        int $userid,
        string $operation,
        int $windowseconds = self::DEFAULT_WINDOW_SECONDS
    ): void {
        $key = self::get_cache_key($userid, $operation);
        $cache = \cache::make('local_reuseunit', self::CACHE_AREA);
        $now = time();

        // Get current data.
        $data = $cache->get($key);

        if ($data === false) {
            $data = ['requests' => []];
        }

        // Clean up old requests.
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $windowseconds) {
            return $timestamp > ($now - $windowseconds);
        });

        // Add new request timestamp.
        $data['requests'][] = $now;

        // Store with TTL equal to window.
        $cache->set($key, $data);
    }

    /**
     * Check and record in one operation (convenience method).
     *
     * @param int $userid User ID
     * @param string $operation Operation type
     * @param int $maxrequests Maximum requests allowed
     * @param int $windowseconds Time window in seconds
     * @return array Same as check() return value
     */
    public static function throttle(
        int $userid,
        string $operation,
        int $maxrequests = self::DEFAULT_MAX_REQUESTS,
        int $windowseconds = self::DEFAULT_WINDOW_SECONDS
    ): array {
        $result = self::check($userid, $operation, $maxrequests, $windowseconds);

        if ($result['allowed']) {
            self::record($userid, $operation, $windowseconds);
        }

        return $result;
    }

    /**
     * Get remaining requests for a user operation.
     *
     * @param int $userid User ID
     * @param string $operation Operation type
     * @param int $maxrequests Maximum requests allowed
     * @param int $windowseconds Time window in seconds
     * @return int Remaining requests
     */
    public static function get_remaining(
        int $userid,
        string $operation,
        int $maxrequests = self::DEFAULT_MAX_REQUESTS,
        int $windowseconds = self::DEFAULT_WINDOW_SECONDS
    ): int {
        $result = self::check($userid, $operation, $maxrequests, $windowseconds);
        return $result['remaining'] + ($result['allowed'] ? 1 : 0);
    }

    /**
     * Reset rate limit for a user operation.
     *
     * @param int $userid User ID
     * @param string $operation Operation type
     * @return void
     */
    public static function reset(int $userid, string $operation): void {
        $key = self::get_cache_key($userid, $operation);
        $cache = \cache::make('local_reuseunit', self::CACHE_AREA);
        $cache->delete($key);
    }

    /**
     * Get cache key for user operation.
     *
     * @param int $userid User ID
     * @param string $operation Operation type
     * @return string Cache key
     */
    private static function get_cache_key(int $userid, string $operation): string {
        return "ratelimit_{$userid}_{$operation}";
    }

    /**
     * Get rate limit settings for an operation.
     *
     * @param string $operation Operation type
     * @return array ['max_requests' => int, 'window_seconds' => int]
     */
    public static function get_limits(string $operation): array {
        // Could be configured via admin settings in the future.
        $limits = [
            'bulk_sync' => ['max_requests' => 5, 'window_seconds' => 3600],
            'batch_import' => ['max_requests' => 10, 'window_seconds' => 3600],
            'export_section' => ['max_requests' => 20, 'window_seconds' => 3600],
            'create_template' => ['max_requests' => 15, 'window_seconds' => 3600],
        ];

        return $limits[$operation] ?? [
            'max_requests' => self::DEFAULT_MAX_REQUESTS,
            'window_seconds' => self::DEFAULT_WINDOW_SECONDS,
        ];
    }
}
