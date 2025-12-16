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
require_once($CFG->dirroot . '/local/reuseunit/classes/constants.php');

/**
 * PHPUnit tests for constants class.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants_test extends \basic_testcase {

    /**
     * Test sync mode validation.
     *
     * @covers \local_reuseunit\constants::is_valid_sync_mode
     */
    public function test_is_valid_sync_mode() {
        // Valid modes.
        $this->assertTrue(constants::is_valid_sync_mode(constants::SYNC_MODE_REPLACE));
        $this->assertTrue(constants::is_valid_sync_mode(constants::SYNC_MODE_MERGE));
        $this->assertTrue(constants::is_valid_sync_mode(constants::SYNC_MODE_SELECTIVE));
        $this->assertTrue(constants::is_valid_sync_mode('replace'));
        $this->assertTrue(constants::is_valid_sync_mode('merge'));
        $this->assertTrue(constants::is_valid_sync_mode('selective'));

        // Invalid modes.
        $this->assertFalse(constants::is_valid_sync_mode('invalid'));
        $this->assertFalse(constants::is_valid_sync_mode(''));
        $this->assertFalse(constants::is_valid_sync_mode('REPLACE')); // Case sensitive.
    }

    /**
     * Test share level validation.
     *
     * @covers \local_reuseunit\constants::is_valid_share_level
     */
    public function test_is_valid_share_level() {
        // Valid levels.
        $this->assertTrue(constants::is_valid_share_level(constants::SHARE_LEVEL_PERSONAL));
        $this->assertTrue(constants::is_valid_share_level(constants::SHARE_LEVEL_CATEGORY));
        $this->assertTrue(constants::is_valid_share_level(constants::SHARE_LEVEL_GLOBAL));
        $this->assertTrue(constants::is_valid_share_level('personal'));
        $this->assertTrue(constants::is_valid_share_level('category'));
        $this->assertTrue(constants::is_valid_share_level('global'));

        // Invalid levels.
        $this->assertFalse(constants::is_valid_share_level('public'));
        $this->assertFalse(constants::is_valid_share_level('private'));
        $this->assertFalse(constants::is_valid_share_level(''));
    }

    /**
     * Test sync status validation.
     *
     * @covers \local_reuseunit\constants::is_valid_sync_status
     */
    public function test_is_valid_sync_status() {
        // Valid statuses.
        $this->assertTrue(constants::is_valid_sync_status(constants::STATUS_COMPLETED));
        $this->assertTrue(constants::is_valid_sync_status(constants::STATUS_FAILED));
        $this->assertTrue(constants::is_valid_sync_status(constants::STATUS_ROLLED_BACK));
        $this->assertTrue(constants::is_valid_sync_status('completed'));
        $this->assertTrue(constants::is_valid_sync_status('failed'));
        $this->assertTrue(constants::is_valid_sync_status('rolled_back'));

        // Invalid statuses.
        $this->assertFalse(constants::is_valid_sync_status('pending'));
        $this->assertFalse(constants::is_valid_sync_status('success'));
        $this->assertFalse(constants::is_valid_sync_status(''));
    }

    /**
     * Test task status validation.
     *
     * @covers \local_reuseunit\constants::is_valid_task_status
     */
    public function test_is_valid_task_status() {
        // Valid statuses.
        $this->assertTrue(constants::is_valid_task_status(constants::TASK_STATUS_PENDING));
        $this->assertTrue(constants::is_valid_task_status(constants::TASK_STATUS_RUNNING));
        $this->assertTrue(constants::is_valid_task_status(constants::TASK_STATUS_COMPLETED));
        $this->assertTrue(constants::is_valid_task_status(constants::TASK_STATUS_FAILED));
        $this->assertTrue(constants::is_valid_task_status(constants::TASK_STATUS_CANCELLED));

        // Invalid statuses.
        $this->assertFalse(constants::is_valid_task_status('started'));
        $this->assertFalse(constants::is_valid_task_status('finished'));
    }

    /**
     * Test approval status validation.
     *
     * @covers \local_reuseunit\constants::is_valid_approval_status
     */
    public function test_is_valid_approval_status() {
        // Valid statuses.
        $this->assertTrue(constants::is_valid_approval_status(constants::APPROVAL_PENDING));
        $this->assertTrue(constants::is_valid_approval_status(constants::APPROVAL_APPROVED));
        $this->assertTrue(constants::is_valid_approval_status(constants::APPROVAL_REJECTED));

        // Invalid statuses.
        $this->assertFalse(constants::is_valid_approval_status('declined'));
        $this->assertFalse(constants::is_valid_approval_status('accepted'));
    }

    /**
     * Test constants have expected values.
     *
     * @covers \local_reuseunit\constants
     */
    public function test_constant_values() {
        // Sync modes.
        $this->assertEquals('replace', constants::SYNC_MODE_REPLACE);
        $this->assertEquals('merge', constants::SYNC_MODE_MERGE);
        $this->assertEquals('selective', constants::SYNC_MODE_SELECTIVE);

        // Share levels.
        $this->assertEquals('personal', constants::SHARE_LEVEL_PERSONAL);
        $this->assertEquals('category', constants::SHARE_LEVEL_CATEGORY);
        $this->assertEquals('global', constants::SHARE_LEVEL_GLOBAL);

        // Statuses.
        $this->assertEquals('completed', constants::STATUS_COMPLETED);
        $this->assertEquals('failed', constants::STATUS_FAILED);
        $this->assertEquals('rolled_back', constants::STATUS_ROLLED_BACK);

        // Cache limit should be reasonable.
        $this->assertGreaterThan(0, constants::CACHE_MAX_ENTRIES);
        $this->assertLessThan(100000, constants::CACHE_MAX_ENTRIES);
    }

    /**
     * Test arrays contain all valid values.
     *
     * @covers \local_reuseunit\constants
     */
    public function test_arrays_complete() {
        // SYNC_MODES should contain all sync mode constants.
        $this->assertContains(constants::SYNC_MODE_REPLACE, constants::SYNC_MODES);
        $this->assertContains(constants::SYNC_MODE_MERGE, constants::SYNC_MODES);
        $this->assertContains(constants::SYNC_MODE_SELECTIVE, constants::SYNC_MODES);
        $this->assertCount(3, constants::SYNC_MODES);

        // SHARE_LEVELS should contain all share level constants.
        $this->assertContains(constants::SHARE_LEVEL_PERSONAL, constants::SHARE_LEVELS);
        $this->assertContains(constants::SHARE_LEVEL_CATEGORY, constants::SHARE_LEVELS);
        $this->assertContains(constants::SHARE_LEVEL_GLOBAL, constants::SHARE_LEVELS);
        $this->assertCount(3, constants::SHARE_LEVELS);

        // SYNC_STATUSES should contain all sync status constants.
        $this->assertContains(constants::STATUS_COMPLETED, constants::SYNC_STATUSES);
        $this->assertContains(constants::STATUS_FAILED, constants::SYNC_STATUSES);
        $this->assertContains(constants::STATUS_ROLLED_BACK, constants::SYNC_STATUSES);
        $this->assertCount(3, constants::SYNC_STATUSES);
    }
}
