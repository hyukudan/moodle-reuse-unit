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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_reuseunit\privacy\provider;

/**
 * PHPUnit tests for privacy provider.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_reuseunit\privacy\provider
 */
class privacy_test extends \core_privacy\tests\provider_testcase {

    /**
     * Test that contexts are returned for a user who has data.
     */
    public function test_get_contexts_for_userid(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Add a template for the user.
        $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Test Template',
            'description' => 'Test',
            'tags' => '',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);

        $context = \context_user::instance($user->id);
        $this->assertEquals($context->id, $contextlist->get_contextids()[0]);
    }

    /**
     * Test that user data is exported correctly.
     */
    public function test_export_user_data(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Add a template.
        $templateid = $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Export Test Template',
            'description' => 'Description for export',
            'tags' => 'test, export',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Add import history.
        $DB->insert_record('local_reuseunit_history', [
            'userid' => $user->id,
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'target_courseid' => $course->id,
            'target_sectionid' => 2,
            'activities_imported' => 5,
            'resources_imported' => 3,
            'timecreated' => time(),
        ]);

        // Add favorite.
        $DB->insert_record('local_reuseunit_favorites', [
            'userid' => $user->id,
            'templateid' => $templateid,
            'timecreated' => time(),
        ]);

        $context = \context_user::instance($user->id);
        $contextlist = new approved_contextlist($user, 'local_reuseunit', [$context->id]);

        provider::export_user_data($contextlist);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test that user data is deleted correctly.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Add data.
        $templateid = $DB->insert_record('local_reuseunit_templates', [
            'userid' => $user->id,
            'name' => 'Delete Test',
            'description' => '',
            'tags' => '',
            'sharelevel' => 'personal',
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $DB->insert_record('local_reuseunit_history', [
            'userid' => $user->id,
            'source_courseid' => $course->id,
            'source_sectionid' => 1,
            'target_courseid' => $course->id,
            'target_sectionid' => 2,
            'activities_imported' => 1,
            'resources_imported' => 1,
            'timecreated' => time(),
        ]);

        $DB->insert_record('local_reuseunit_favorites', [
            'userid' => $user->id,
            'templateid' => $templateid,
            'timecreated' => time(),
        ]);

        // Verify data exists.
        $this->assertEquals(1, $DB->count_records('local_reuseunit_templates', ['userid' => $user->id]));
        $this->assertEquals(1, $DB->count_records('local_reuseunit_history', ['userid' => $user->id]));
        $this->assertEquals(1, $DB->count_records('local_reuseunit_favorites', ['userid' => $user->id]));

        // Delete data.
        $context = \context_user::instance($user->id);
        $contextlist = new approved_contextlist($user, 'local_reuseunit', [$context->id]);
        provider::delete_data_for_user($contextlist);

        // Verify data is deleted.
        $this->assertEquals(0, $DB->count_records('local_reuseunit_templates', ['userid' => $user->id]));
        $this->assertEquals(0, $DB->count_records('local_reuseunit_history', ['userid' => $user->id]));
        $this->assertEquals(0, $DB->count_records('local_reuseunit_favorites', ['userid' => $user->id]));
    }

    /**
     * Test metadata is described correctly.
     */
    public function test_get_metadata(): void {
        $collection = new \core_privacy\local\metadata\collection('local_reuseunit');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();
        $this->assertNotEmpty($items);

        // Check that all expected tables are described.
        $tableNames = [];
        foreach ($items as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\database_table) {
                $tableNames[] = $item->get_name();
            }
        }

        $this->assertContains('local_reuseunit_templates', $tableNames);
        $this->assertContains('local_reuseunit_history', $tableNames);
        $this->assertContains('local_reuseunit_favorites', $tableNames);
        $this->assertContains('local_reuseunit_scheduled', $tableNames);
        $this->assertContains('local_reuseunit_links', $tableNames);
    }
}
