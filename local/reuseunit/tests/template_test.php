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
require_once($CFG->dirroot . '/local/reuseunit/classes/external/save_template.php');
require_once($CFG->dirroot . '/local/reuseunit/classes/external/get_templates.php');
require_once($CFG->dirroot . '/local/reuseunit/classes/external/delete_template.php');

/**
 * PHPUnit tests for template functionality.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_test extends \advanced_testcase {

    /**
     * Test saving a section as a template.
     *
     * @covers \local_reuseunit\external\save_template::execute
     */
    public function test_save_template() {
        global $DB;

        $this->resetAfterTest(true);

        // Create a course with a section and activities.
        $course = $this->getDataGenerator()->create_course(['numsections' => 3]);
        $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'section' => 1]);
        $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'section' => 1]);

        // Get section ID.
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);

        // Create user with capability.
        $user = $this->getDataGenerator()->create_user();
        $teacherrole = $this->getDataGenerator()->create_role();
        role_change_permission($teacherrole, \context_system::instance(), 'local/reuseunit:export', CAP_ALLOW);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole);

        $this->setUser($user);

        // Save as template.
        $result = \local_reuseunit\external\save_template::execute(
            $course->id,
            $section->id,
            'My Test Template',
            'A test template description',
            'test, unit, demo',
            'personal'
        );

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['templateid']);

        // Verify template was saved.
        $template = $DB->get_record('local_reuseunit_templates', ['id' => $result['templateid']]);
        $this->assertEquals('My Test Template', $template->name);
        $this->assertEquals('personal', $template->sharelevel);
        $this->assertEquals($user->id, $template->userid);
        $this->assertEquals(2, $template->activities_count);
    }

    /**
     * Test getting templates respects share levels.
     *
     * @covers \local_reuseunit\external\get_templates::execute
     */
    public function test_get_templates_share_levels() {
        global $DB;

        $this->resetAfterTest(true);

        // Create category and courses.
        $category = $this->getDataGenerator()->create_category();
        $course1 = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category->id]);

        $section1 = $DB->get_record('course_sections', ['course' => $course1->id, 'section' => 1]);
        $section2 = $DB->get_record('course_sections', ['course' => $course2->id, 'section' => 1]);

        // Create two users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Set up roles.
        $teacherrole = $this->getDataGenerator()->create_role();
        role_change_permission($teacherrole, \context_system::instance(), 'local/reuseunit:export', CAP_ALLOW);

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $teacherrole);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, $teacherrole);

        // User1 creates a personal template.
        $this->setUser($user1);
        \local_reuseunit\external\save_template::execute(
            $course1->id, $section1->id, 'Personal Template', '', '', 'personal'
        );

        // User1 creates a category template.
        \local_reuseunit\external\save_template::execute(
            $course1->id, $section1->id, 'Category Template', '', '', 'category'
        );

        // User2 should see category template but not personal.
        $this->setUser($user2);
        $templates = \local_reuseunit\external\get_templates::execute('', 0, 50);

        $templateNames = array_column($templates, 'name');
        $this->assertContains('Category Template', $templateNames);
        $this->assertNotContains('Personal Template', $templateNames);

        // User1 should see both.
        $this->setUser($user1);
        $templates = \local_reuseunit\external\get_templates::execute('', 0, 50);

        $templateNames = array_column($templates, 'name');
        $this->assertContains('Category Template', $templateNames);
        $this->assertContains('Personal Template', $templateNames);
    }

    /**
     * Test deleting a template.
     *
     * @covers \local_reuseunit\external\delete_template::execute
     */
    public function test_delete_template() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);

        $user = $this->getDataGenerator()->create_user();
        $teacherrole = $this->getDataGenerator()->create_role();
        role_change_permission($teacherrole, \context_system::instance(), 'local/reuseunit:export', CAP_ALLOW);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole);

        $this->setUser($user);

        // Create template.
        $result = \local_reuseunit\external\save_template::execute(
            $course->id, $section->id, 'Template to Delete', '', '', 'personal'
        );
        $templateid = $result['templateid'];

        // Verify exists.
        $this->assertTrue($DB->record_exists('local_reuseunit_templates', ['id' => $templateid]));

        // Delete.
        $result = \local_reuseunit\external\delete_template::execute($templateid);
        $this->assertTrue($result['success']);

        // Verify deleted.
        $this->assertFalse($DB->record_exists('local_reuseunit_templates', ['id' => $templateid]));
    }

    /**
     * Test user cannot delete another user's personal template.
     *
     * @covers \local_reuseunit\external\delete_template::execute
     */
    public function test_cannot_delete_others_template() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $teacherrole = $this->getDataGenerator()->create_role();
        role_change_permission($teacherrole, \context_system::instance(), 'local/reuseunit:export', CAP_ALLOW);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $teacherrole);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, $teacherrole);

        // User1 creates template.
        $this->setUser($user1);
        $result = \local_reuseunit\external\save_template::execute(
            $course->id, $section->id, 'User1 Template', '', '', 'personal'
        );
        $templateid = $result['templateid'];

        // User2 tries to delete.
        $this->setUser($user2);
        $this->expectException(\moodle_exception::class);
        \local_reuseunit\external\delete_template::execute($templateid);
    }
}
