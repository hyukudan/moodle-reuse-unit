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
require_once($CFG->dirroot . '/local/reuseunit/classes/external/search_courses.php');
require_once($CFG->dirroot . '/local/reuseunit/classes/external/get_sections.php');

/**
 * PHPUnit tests for external API functions.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external_test extends \advanced_testcase {

    /**
     * Test search_courses returns accessible courses.
     *
     * @covers \local_reuseunit\external\search_courses::execute
     */
    public function test_search_courses() {
        $this->resetAfterTest(true);

        // Create a user and courses.
        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Mathematics 101']);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Physics 101']);
        $course3 = $this->getDataGenerator()->create_course(['fullname' => 'Chemistry 101']);

        // Enrol user as editing teacher in course1 and course2.
        $teacherrole = $this->getDataGenerator()->create_role();
        role_change_permission($teacherrole, \context_system::instance(), 'local/reuseunit:export', CAP_ALLOW);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, $teacherrole);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, $teacherrole);

        $this->setUser($user);

        // Search for "101".
        $result = \local_reuseunit\external\search_courses::execute('101', 10);

        // Should find 2 courses (not Chemistry because user has no role).
        $this->assertCount(2, $result);

        // Search for "Math".
        $result = \local_reuseunit\external\search_courses::execute('Math', 10);
        $this->assertCount(1, $result);
        $this->assertEquals('Mathematics 101', $result[0]['fullname']);
    }

    /**
     * Test get_sections returns sections with activity counts.
     *
     * @covers \local_reuseunit\external\get_sections::execute
     */
    public function test_get_sections() {
        $this->resetAfterTest(true);

        // Create a course with sections.
        $course = $this->getDataGenerator()->create_course([
            'numsections' => 5,
        ]);

        // Create some activities in section 1.
        $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'section' => 1]);
        $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'section' => 1]);
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'section' => 1]);

        // Create a resource in section 2.
        $this->getDataGenerator()->create_module('resource', ['course' => $course->id, 'section' => 2]);

        // Create user with capability.
        $user = $this->getDataGenerator()->create_user();
        $teacherrole = $this->getDataGenerator()->create_role();
        role_change_permission($teacherrole, \context_system::instance(), 'local/reuseunit:export', CAP_ALLOW);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole);

        $this->setUser($user);

        // Get sections.
        $result = \local_reuseunit\external\get_sections::execute($course->id);

        // Should return 5 sections (excluding section 0).
        $this->assertCount(5, $result);

        // Section 1 should have 3 activities.
        $section1 = $result[0];
        $this->assertEquals(1, $section1['section']);
        $this->assertEquals(3, $section1['activities']);
        $this->assertEquals(0, $section1['resources']);
        $this->assertTrue($section1['hascontents']);

        // Section 2 should have 1 resource.
        $section2 = $result[1];
        $this->assertEquals(2, $section2['section']);
        $this->assertEquals(0, $section2['activities']);
        $this->assertEquals(1, $section2['resources']);

        // Section 3 should be empty.
        $section3 = $result[2];
        $this->assertFalse($section3['hascontents']);
    }

    /**
     * Test user without capability cannot search courses.
     *
     * @covers \local_reuseunit\external\search_courses::execute
     */
    public function test_search_courses_no_capability() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Test Course']);

        // Enrol as student (no export capability).
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $this->setUser($user);

        // Search should return empty (student has no export capability).
        $result = \local_reuseunit\external\search_courses::execute('Test', 10);
        $this->assertEmpty($result);
    }

    /**
     * Test get_sections requires capability.
     *
     * @covers \local_reuseunit\external\get_sections::execute
     */
    public function test_get_sections_requires_capability() {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Enrol as student (no export capability).
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        \local_reuseunit\external\get_sections::execute($course->id);
    }
}
