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
require_once($CFG->dirroot . '/local/reuseunit/classes/section_helper.php');
require_once($CFG->dirroot . '/local/reuseunit/classes/constants.php');

/**
 * PHPUnit tests for section_helper class.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_helper_test extends \advanced_testcase {

    /**
     * Test calculate_contenthash generates consistent hash.
     *
     * @covers \local_reuseunit\section_helper::calculate_contenthash
     */
    public function test_calculate_contenthash() {
        $this->resetAfterTest(true);

        // Create a course with activities.
        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);

        // Get section 1 ID.
        global $DB;
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ]);

        // Create activities in section 1.
        $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Test Forum',
        ]);
        $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Test Assignment',
        ]);

        // Calculate hash.
        $hash1 = section_helper::calculate_contenthash($course->id, $section->id);

        // Hash should be a 64-character SHA256 string.
        $this->assertEquals(64, strlen($hash1));

        // Same content should produce same hash.
        $hash2 = section_helper::calculate_contenthash($course->id, $section->id);
        $this->assertEquals($hash1, $hash2);

        // Clear cache and recalculate - should still be the same.
        section_helper::clear_timemodified_cache();
        $hash3 = section_helper::calculate_contenthash($course->id, $section->id);
        $this->assertEquals($hash1, $hash3);
    }

    /**
     * Test calculate_contenthash with partial selection.
     *
     * @covers \local_reuseunit\section_helper::calculate_contenthash
     */
    public function test_calculate_contenthash_partial() {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);

        global $DB;
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ]);

        // Create two activities.
        $forum = $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'section' => 1,
        ]);
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'section' => 1,
        ]);

        // Get course module IDs.
        $modinfo = get_fast_modinfo($course->id);
        $cmids = array_keys($modinfo->cms);

        // Hash with all modules.
        $hashFull = section_helper::calculate_contenthash($course->id, $section->id);

        // Hash with only first module.
        $hashPartial = section_helper::calculate_contenthash($course->id, $section->id, [$cmids[0]]);

        // Partial hash should be different from full hash.
        $this->assertNotEquals($hashFull, $hashPartial);
    }

    /**
     * Test safe_json_decode with valid JSON.
     *
     * @covers \local_reuseunit\section_helper::safe_json_decode
     */
    public function test_safe_json_decode_valid() {
        $json = '{"key": "value", "number": 42}';
        $result = section_helper::safe_json_decode($json);

        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
        $this->assertEquals(42, $result['number']);
    }

    /**
     * Test safe_json_decode with array JSON.
     *
     * @covers \local_reuseunit\section_helper::safe_json_decode
     */
    public function test_safe_json_decode_array() {
        $json = '[1, 2, 3, 4, 5]';
        $result = section_helper::safe_json_decode($json);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    /**
     * Test safe_json_decode with invalid JSON.
     *
     * @covers \local_reuseunit\section_helper::safe_json_decode
     */
    public function test_safe_json_decode_invalid() {
        $json = 'not valid json {';
        $result = section_helper::safe_json_decode($json, []);

        // Should return default value.
        $this->assertEquals([], $result);
    }

    /**
     * Test safe_json_decode with empty string.
     *
     * @covers \local_reuseunit\section_helper::safe_json_decode
     */
    public function test_safe_json_decode_empty() {
        $result = section_helper::safe_json_decode('', 'default');
        $this->assertEquals('default', $result);
    }

    /**
     * Test save_module_mappings creates mapping records.
     *
     * @covers \local_reuseunit\section_helper::save_module_mappings
     * @covers \local_reuseunit\section_helper::get_synced_modules
     */
    public function test_save_and_get_module_mappings() {
        $this->resetAfterTest(true);
        global $DB;

        // Create prerequisite data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Create a template.
        $templateid = $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Test Template',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create section.
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ]);

        // Create a link.
        $linkid = $DB->insert_record('local_reuseunit_links', [
            'courseid' => $course->id,
            'sectionid' => $section->id,
            'templateid' => $templateid,
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Save module mappings.
        $mappings = [
            [
                'source_cmid' => 100,
                'dest_cmid' => 200,
                'modname' => 'forum',
                'name' => 'Test Forum',
                'source_timemodified' => time() - 100,
                'dest_timemodified' => time(),
            ],
            [
                'source_cmid' => 101,
                'dest_cmid' => 201,
                'modname' => 'assign',
                'name' => 'Test Assignment',
            ],
        ];

        section_helper::save_module_mappings($linkid, $mappings);

        // Retrieve mappings.
        $retrieved = section_helper::get_synced_modules($linkid);

        $this->assertCount(2, $retrieved);

        // Verify first mapping.
        $first = array_shift($retrieved);
        $this->assertEquals(100, $first->source_cmid);
        $this->assertEquals(200, $first->dest_cmid);
        $this->assertEquals('forum', $first->modname);
    }

    /**
     * Test is_local_module correctly identifies local modules.
     *
     * @covers \local_reuseunit\section_helper::is_local_module
     */
    public function test_is_local_module() {
        $this->resetAfterTest(true);
        global $DB;

        // Create prerequisite data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $templateid = $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Test Template',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ]);

        $linkid = $DB->insert_record('local_reuseunit_links', [
            'courseid' => $course->id,
            'sectionid' => $section->id,
            'templateid' => $templateid,
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Save a mapping.
        section_helper::save_module_mappings($linkid, [
            [
                'source_cmid' => 100,
                'dest_cmid' => 200,
                'modname' => 'forum',
            ],
        ]);

        // Module 200 should NOT be local (it came from template).
        $this->assertFalse(section_helper::is_local_module($linkid, 200));

        // Module 300 should be local (not in mappings).
        $this->assertTrue(section_helper::is_local_module($linkid, 300));
    }

    /**
     * Test delete_module_mappings removes records.
     *
     * @covers \local_reuseunit\section_helper::delete_module_mappings
     */
    public function test_delete_module_mappings() {
        $this->resetAfterTest(true);
        global $DB;

        // Create prerequisite data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $templateid = $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Test Template',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ]);

        $linkid = $DB->insert_record('local_reuseunit_links', [
            'courseid' => $course->id,
            'sectionid' => $section->id,
            'templateid' => $templateid,
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Save mappings.
        section_helper::save_module_mappings($linkid, [
            ['source_cmid' => 100, 'dest_cmid' => 200, 'modname' => 'forum'],
            ['source_cmid' => 101, 'dest_cmid' => 201, 'modname' => 'assign'],
            ['source_cmid' => 102, 'dest_cmid' => 202, 'modname' => 'quiz'],
        ]);

        $this->assertCount(3, section_helper::get_synced_modules($linkid));

        // Delete specific mapping.
        section_helper::delete_module_mappings($linkid, [201]);
        $this->assertCount(2, section_helper::get_synced_modules($linkid));

        // Delete all remaining mappings.
        section_helper::delete_module_mappings($linkid);
        $this->assertCount(0, section_helper::get_synced_modules($linkid));
    }

    /**
     * Test log_sync_history creates history record.
     *
     * @covers \local_reuseunit\section_helper::log_sync_history
     * @covers \local_reuseunit\section_helper::get_sync_history
     */
    public function test_log_and_get_sync_history() {
        $this->resetAfterTest(true);
        global $DB;

        // Create prerequisite data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $templateid = $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Test Template',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ]);

        $linkid = $DB->insert_record('local_reuseunit_links', [
            'courseid' => $course->id,
            'sectionid' => $section->id,
            'templateid' => $templateid,
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Log sync history.
        $stats = [
            'added' => 3,
            'updated' => 2,
            'removed' => 1,
            'preserved' => 5,
            'conflicts' => 0,
        ];
        $changesdata = ['test' => 'data'];

        $historyid = section_helper::log_sync_history(
            $linkid,
            $user->id,
            constants::SYNC_MODE_SELECTIVE,
            $stats,
            $changesdata,
            constants::STATUS_COMPLETED,
            '',
            'hash_before',
            'hash_after'
        );

        $this->assertGreaterThan(0, $historyid);

        // Retrieve history.
        $history = section_helper::get_sync_history($linkid);
        $this->assertCount(1, $history);

        $record = reset($history);
        $this->assertEquals($linkid, $record->linkid);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals(constants::SYNC_MODE_SELECTIVE, $record->sync_mode);
        $this->assertEquals(3, $record->added_count);
        $this->assertEquals(2, $record->updated_count);
        $this->assertEquals(1, $record->removed_count);
        $this->assertEquals(constants::STATUS_COMPLETED, $record->status);
    }

    /**
     * Test can_rollback returns correct response.
     *
     * @covers \local_reuseunit\section_helper::can_rollback
     */
    public function test_can_rollback() {
        $this->resetAfterTest(true);
        global $DB;

        // Create prerequisite data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $templateid = $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Test Template',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ]);

        $linkid = $DB->insert_record('local_reuseunit_links', [
            'courseid' => $course->id,
            'sectionid' => $section->id,
            'templateid' => $templateid,
            'userid' => $user->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Log completed sync.
        $historyid = section_helper::log_sync_history(
            $linkid,
            $user->id,
            constants::SYNC_MODE_SELECTIVE,
            ['added' => 1],
            [],
            constants::STATUS_COMPLETED
        );

        // Should be able to rollback.
        $result = section_helper::can_rollback($historyid);
        $this->assertTrue($result['possible']);

        // Mark as rolled back.
        section_helper::mark_history_rolled_back($historyid);

        // Should not be able to rollback again.
        $result = section_helper::can_rollback($historyid);
        $this->assertFalse($result['possible']);
    }

    /**
     * Test clear_timemodified_cache clears the cache.
     *
     * @covers \local_reuseunit\section_helper::clear_timemodified_cache
     */
    public function test_clear_timemodified_cache() {
        // This is a simple test to ensure the method doesn't throw errors.
        section_helper::clear_timemodified_cache();

        // No exception means success.
        $this->assertTrue(true);
    }
}
