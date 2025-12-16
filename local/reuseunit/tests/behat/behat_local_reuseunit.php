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

/**
 * Behat step definitions for local_reuseunit.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Behat step definitions for local_reuseunit.
 *
 * @package    local_reuseunit
 * @category   test
 * @copyright  2025 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_reuseunit extends behat_base {

    /**
     * Opens the import wizard for a specific course.
     *
     * @Given /^I open the import wizard for course "(?P<coursefullname_string>(?:[^"]|\\")*)"$/
     * @param string $coursefullname The course full name
     */
    public function i_open_the_import_wizard_for_course($coursefullname) {
        global $DB;

        $course = $DB->get_record('course', ['fullname' => $coursefullname], 'id', MUST_EXIST);
        $this->getSession()->visit($this->locate_path('/local/reuseunit/index.php?courseid=' . $course->id));
    }

    /**
     * Checks that a section is listed with specific activity count.
     *
     * @Then /^I should see section "(?P<sectionname_string>(?:[^"]|\\")*)" with "(?P<count_number>\d+)" activities$/
     * @param string $sectionname The section name
     * @param int $count Expected activity count
     */
    public function i_should_see_section_with_activities($sectionname, $count) {
        $xpath = "//div[contains(@class, 'reuseunit-section-item')]" .
                 "[contains(., '{$sectionname}')]" .
                 "[contains(., '{$count} activities')]";

        $this->find('xpath', $xpath);
    }

    /**
     * Clicks on a section radio button.
     *
     * @When /^I select section "(?P<sectionname_string>(?:[^"]|\\")*)" for import$/
     * @param string $sectionname The section name
     */
    public function i_select_section_for_import($sectionname) {
        $xpath = "//div[contains(@class, 'reuseunit-section-item')]" .
                 "[contains(., '{$sectionname}')]" .
                 "//input[@type='radio']";

        $radio = $this->find('xpath', $xpath);
        $radio->click();
    }

    /**
     * Checks that a template is visible with correct share level badge.
     *
     * @Then /^I should see template "(?P<templatename_string>(?:[^"]|\\")*)" with share level "(?P<sharelevel_string>(?:[^"]|\\")*)"$/
     * @param string $templatename The template name
     * @param string $sharelevel Expected share level
     */
    public function i_should_see_template_with_share_level($templatename, $sharelevel) {
        $xpath = "//div[contains(@class, 'reuseunit-template-item')]" .
                 "[contains(., '{$templatename}')]" .
                 "[contains(., '{$sharelevel}')]";

        $this->find('xpath', $xpath);
    }

    /**
     * Waits for AJAX operations to complete.
     *
     * @Given /^I wait for reuseunit ajax to complete$/
     */
    public function i_wait_for_reuseunit_ajax_to_complete() {
        $this->getSession()->wait(5000, '(typeof jQuery !== "undefined" && jQuery.active === 0)');
    }
}
