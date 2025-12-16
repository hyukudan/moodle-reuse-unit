@local @local_reuseunit
Feature: Schedule imports for later
  As a teacher
  I want to schedule imports for a specific time
  So that large imports can run during off-peak hours

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname       | shortname |
      | Source Course  | SC        |
      | Target Course  | TC        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | SC     | editingteacher |
      | teacher1 | TC     | editingteacher |
    And the following "activities" exist:
      | activity | name        | course | section |
      | forum    | Test Forum  | SC     | 1       |

  @javascript
  Scenario: Teacher can schedule an import for later
    Given I log in as "teacher1"
    And I am on "Target Course" course homepage
    And I navigate to "Import unit" in current page administration
    And I set the field "source-course-search" to "Source"
    And I wait "1" seconds
    And I click on "Source Course" "text" in the ".reuseunit-search-results" "css_element"
    And I wait until the page is ready
    And I click on "section-1" "radio"
    And I click on "Next" "button"
    When I click on "Import later" "radio"
    And I set the field "scheduled-datetime" to "##tomorrow 09:00##"
    And I click on "Schedule import" "button"
    Then I should see "Import scheduled successfully"
    And I should see "Pending"

  @javascript
  Scenario: Teacher can view their scheduled imports
    Given the following "local_reuseunit > scheduled" exist:
      | userid   | dest_courseshortname | status  | scheduled_time     |
      | teacher1 | TC                   | pending | ##tomorrow 09:00## |
    And I log in as "teacher1"
    And I am on "Target Course" course homepage
    When I navigate to "Scheduled imports" in current page administration
    Then I should see "Pending"
    And I should see "Target Course"
    And I should see "Cancel"

  @javascript
  Scenario: Teacher can cancel a scheduled import
    Given the following "local_reuseunit > scheduled" exist:
      | userid   | dest_courseshortname | status  | scheduled_time     |
      | teacher1 | TC                   | pending | ##tomorrow 09:00## |
    And I log in as "teacher1"
    And I am on "Target Course" course homepage
    And I navigate to "Scheduled imports" in current page administration
    When I click on "Cancel" "link"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    Then I should see "Scheduled import cancelled"

  @javascript
  Scenario: Teacher cannot cancel a running import
    Given the following "local_reuseunit > scheduled" exist:
      | userid   | dest_courseshortname | status  | scheduled_time |
      | teacher1 | TC                   | running | ##now##        |
    And I log in as "teacher1"
    And I am on "Target Course" course homepage
    When I navigate to "Scheduled imports" in current page administration
    Then I should see "Running"
    And "Cancel" "link" should not exist
