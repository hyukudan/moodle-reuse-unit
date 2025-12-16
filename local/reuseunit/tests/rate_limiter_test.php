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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/reuseunit/classes/rate_limiter.php');

/**
 * PHPUnit tests for rate_limiter class.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter_test extends \advanced_testcase {

    /**
     * Test check allows first request.
     *
     * @covers \local_reuseunit\rate_limiter::check
     */
    public function test_check_allows_first_request() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $result = rate_limiter::check($user->id, 'test_operation', 5, 60);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(4, $result['remaining']); // 5 - 1 for this check.
        $this->assertEmpty($result['message']);
    }

    /**
     * Test record stores request.
     *
     * @covers \local_reuseunit\rate_limiter::record
     * @covers \local_reuseunit\rate_limiter::get_remaining
     */
    public function test_record_stores_request() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // Initial remaining should be max.
        $remaining = rate_limiter::get_remaining($user->id, 'test_op', 5, 60);
        $this->assertEquals(5, $remaining);

        // Record a request.
        rate_limiter::record($user->id, 'test_op', 60);

        // Remaining should decrease.
        $remaining = rate_limiter::get_remaining($user->id, 'test_op', 5, 60);
        $this->assertEquals(4, $remaining);
    }

    /**
     * Test throttle combines check and record.
     *
     * @covers \local_reuseunit\rate_limiter::throttle
     */
    public function test_throttle() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // First request should be allowed.
        $result = rate_limiter::throttle($user->id, 'throttle_test', 3, 60);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(2, $result['remaining']);

        // Second request.
        $result = rate_limiter::throttle($user->id, 'throttle_test', 3, 60);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['remaining']);

        // Third request.
        $result = rate_limiter::throttle($user->id, 'throttle_test', 3, 60);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(0, $result['remaining']);

        // Fourth request should be blocked.
        $result = rate_limiter::throttle($user->id, 'throttle_test', 3, 60);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test reset clears rate limit.
     *
     * @covers \local_reuseunit\rate_limiter::reset
     */
    public function test_reset() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // Use up the limit.
        for ($i = 0; $i < 3; $i++) {
            rate_limiter::record($user->id, 'reset_test', 60);
        }

        // Should have 0 remaining.
        $remaining = rate_limiter::get_remaining($user->id, 'reset_test', 3, 60);
        $this->assertEquals(0, $remaining);

        // Reset.
        rate_limiter::reset($user->id, 'reset_test');

        // Should have full limit again.
        $remaining = rate_limiter::get_remaining($user->id, 'reset_test', 3, 60);
        $this->assertEquals(3, $remaining);
    }

    /**
     * Test get_limits returns operation-specific limits.
     *
     * @covers \local_reuseunit\rate_limiter::get_limits
     */
    public function test_get_limits() {
        // Known operation.
        $limits = rate_limiter::get_limits('bulk_sync');
        $this->assertArrayHasKey('max_requests', $limits);
        $this->assertArrayHasKey('window_seconds', $limits);
        $this->assertEquals(5, $limits['max_requests']);

        // Unknown operation uses defaults.
        $limits = rate_limiter::get_limits('unknown_operation');
        $this->assertEquals(rate_limiter::DEFAULT_MAX_REQUESTS, $limits['max_requests']);
        $this->assertEquals(rate_limiter::DEFAULT_WINDOW_SECONDS, $limits['window_seconds']);
    }
}
