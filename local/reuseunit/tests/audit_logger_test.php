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
require_once($CFG->dirroot . '/local/reuseunit/classes/audit_logger.php');

/**
 * PHPUnit tests for audit_logger class.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audit_logger_test extends \advanced_testcase {

    /**
     * Test log creates audit record.
     *
     * @covers \local_reuseunit\audit_logger::log
     */
    public function test_log() {
        $this->resetAfterTest(true);
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        $id = audit_logger::log(
            audit_logger::ACTION_TEMPLATE_APPROVED,
            $user->id,
            123,
            'template',
            ['key' => 'value'],
            '192.168.1.1'
        );

        $this->assertGreaterThan(0, $id);

        // Verify record was created.
        $record = $DB->get_record('local_reuseunit_audit', ['id' => $id]);
        $this->assertNotFalse($record);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals(audit_logger::ACTION_TEMPLATE_APPROVED, $record->action);
        $this->assertEquals(123, $record->targetid);
        $this->assertEquals('template', $record->targettype);
        $this->assertEquals('192.168.1.1', $record->ipaddress);

        // Verify details.
        $details = json_decode($record->details, true);
        $this->assertEquals('value', $details['key']);
    }

    /**
     * Test log_approval_action creates proper audit record.
     *
     * @covers \local_reuseunit\audit_logger::log_approval_action
     */
    public function test_log_approval_action() {
        $this->resetAfterTest(true);
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $owner = $this->getDataGenerator()->create_user();

        // Create a mock template object.
        $template = new \stdClass();
        $template->id = 456;
        $template->name = 'Test Template';
        $template->userid = $owner->id;
        $template->sharelevel = 'global';
        $template->approval_status = 'pending';

        $id = audit_logger::log_approval_action(
            audit_logger::ACTION_TEMPLATE_REJECTED,
            $user->id,
            $template,
            'Content not appropriate'
        );

        $this->assertGreaterThan(0, $id);

        // Verify record.
        $record = $DB->get_record('local_reuseunit_audit', ['id' => $id]);
        $this->assertEquals(audit_logger::ACTION_TEMPLATE_REJECTED, $record->action);
        $this->assertEquals(456, $record->targetid);
        $this->assertEquals('template', $record->targettype);

        // Verify details include reason.
        $details = json_decode($record->details, true);
        $this->assertEquals('Test Template', $details['template_name']);
        $this->assertEquals($owner->id, $details['template_owner']);
        $this->assertEquals('Content not appropriate', $details['reason']);
    }

    /**
     * Test get_logs_for_target retrieves logs.
     *
     * @covers \local_reuseunit\audit_logger::get_logs_for_target
     */
    public function test_get_logs_for_target() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // Create multiple logs for same target.
        audit_logger::log(audit_logger::ACTION_TEMPLATE_SUBMITTED, $user->id, 100, 'template');
        audit_logger::log(audit_logger::ACTION_TEMPLATE_APPROVED, $user->id, 100, 'template');

        // Create log for different target.
        audit_logger::log(audit_logger::ACTION_TEMPLATE_SUBMITTED, $user->id, 200, 'template');

        // Get logs for target 100.
        $logs = audit_logger::get_logs_for_target(100, 'template');
        $this->assertCount(2, $logs);
    }

    /**
     * Test get_logs_by_user retrieves user's logs.
     *
     * @covers \local_reuseunit\audit_logger::get_logs_by_user
     */
    public function test_get_logs_by_user() {
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create logs for user1.
        audit_logger::log(audit_logger::ACTION_TEMPLATE_SUBMITTED, $user1->id, 100, 'template');
        audit_logger::log(audit_logger::ACTION_SYNC_COMPLETED, $user1->id, 50, 'sync');

        // Create log for user2.
        audit_logger::log(audit_logger::ACTION_TEMPLATE_APPROVED, $user2->id, 100, 'template');

        // Get logs for user1.
        $logs = audit_logger::get_logs_by_user($user1->id);
        $this->assertCount(2, $logs);

        // Get logs for user2.
        $logs = audit_logger::get_logs_by_user($user2->id);
        $this->assertCount(1, $logs);
    }

    /**
     * Test count_by_action returns correct count.
     *
     * @covers \local_reuseunit\audit_logger::count_by_action
     */
    public function test_count_by_action() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();

        // Create some logs.
        audit_logger::log(audit_logger::ACTION_TEMPLATE_APPROVED, $user->id, 1, 'template');
        audit_logger::log(audit_logger::ACTION_TEMPLATE_APPROVED, $user->id, 2, 'template');
        audit_logger::log(audit_logger::ACTION_TEMPLATE_REJECTED, $user->id, 3, 'template');

        // Count approved.
        $count = audit_logger::count_by_action(audit_logger::ACTION_TEMPLATE_APPROVED);
        $this->assertEquals(2, $count);

        // Count rejected.
        $count = audit_logger::count_by_action(audit_logger::ACTION_TEMPLATE_REJECTED);
        $this->assertEquals(1, $count);

        // Count with time filter.
        $count = audit_logger::count_by_action(audit_logger::ACTION_TEMPLATE_APPROVED, time() - 100);
        $this->assertEquals(2, $count);

        $count = audit_logger::count_by_action(audit_logger::ACTION_TEMPLATE_APPROVED, time() + 100);
        $this->assertEquals(0, $count);
    }

    /**
     * Test cleanup removes old records.
     *
     * @covers \local_reuseunit\audit_logger::cleanup
     */
    public function test_cleanup() {
        $this->resetAfterTest(true);
        global $DB;

        $user = $this->getDataGenerator()->create_user();

        // Create a recent log.
        $recentid = audit_logger::log(audit_logger::ACTION_TEMPLATE_APPROVED, $user->id, 1, 'template');

        // Create an old log by manipulating timecreated.
        $oldid = audit_logger::log(audit_logger::ACTION_TEMPLATE_REJECTED, $user->id, 2, 'template');
        $DB->set_field('local_reuseunit_audit', 'timecreated', time() - (400 * 24 * 60 * 60), ['id' => $oldid]);

        // Cleanup logs older than 365 days.
        $deleted = audit_logger::cleanup(365);

        $this->assertEquals(1, $deleted);

        // Recent log should still exist.
        $this->assertTrue($DB->record_exists('local_reuseunit_audit', ['id' => $recentid]));

        // Old log should be deleted.
        $this->assertFalse($DB->record_exists('local_reuseunit_audit', ['id' => $oldid]));
    }
}
