@local @local_reuseunit
Feature: Import sections between courses
  As a teacher
  I want to import sections from other courses
  So that I can reuse content efficiently

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
    And the following "categories" exist:
      | name       | idnumber |
      | Category 1 | CAT1     |
    And the following "courses" exist:
      | fullname         | shortname | category |
      | Source Course    | SC        | CAT1     |
      | Destination      | DC        | CAT1     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | SC     | editingteacher |
      | teacher1 | DC     | editingteacher |
      | teacher2 | DC     | editingteacher |
    And the following "activities" exist:
      | activity | name           | course | section |
      | forum    | Test Forum     | SC     | 1       |
      | quiz     | Test Quiz      | SC     | 1       |
      | assign   | Test Assignment| SC     | 1       |

  @javascript
  Scenario: Teacher can access import unit from course navigation
    Given I log in as "teacher1"
    And I am on "Destination" course homepage
    When I navigate to "Import unit" in current page administration
    Then I should see "Select source"
    And I should see "Search course"

  @javascript
  Scenario: Teacher can search and find accessible courses
    Given I log in as "teacher1"
    And I am on "Destination" course homepage
    And I navigate to "Import unit" in current page administration
    When I set the field "source-course-search" to "Source"
    And I wait "1" seconds
    Then I should see "Source Course" in the ".reuseunit-search-results" "css_element"

  @javascript
  Scenario: Teacher cannot see courses they don't have access to
    Given I log in as "teacher2"
    And I am on "Destination" course homepage
    And I navigate to "Import unit" in current page administration
    When I set the field "source-course-search" to "Source"
    And I wait "1" seconds
    Then I should not see "Source Course" in the ".reuseunit-search-results" "css_element"

  @javascript
  Scenario: Teacher can select a section and see preview
    Given I log in as "teacher1"
    And I am on "Destination" course homepage
    And I navigate to "Import unit" in current page administration
    And I set the field "source-course-search" to "Source"
    And I wait "1" seconds
    And I click on "Source Course" "text" in the ".reuseunit-search-results" "css_element"
    And I wait until the page is ready
    When I click on "section-1" "radio"
    And I click on "Next" "button"
    Then I should see "Content to be imported"
    And I should see "Test Forum"
    And I should see "Test Quiz"
    And I should see "Test Assignment"

  @javascript
  Scenario: Teacher can configure import options
    Given I log in as "teacher1"
    And I am on "Destination" course homepage
    And I navigate to "Import unit" in current page administration
    And I set the field "source-course-search" to "Source"
    And I wait "1" seconds
    And I click on "Source Course" "text" in the ".reuseunit-search-results" "css_element"
    And I wait until the page is ready
    And I click on "section-1" "radio"
    And I click on "Next" "button"
    Then I should see "Import options"
    And the field "Reset dates" matches value "1"
    And the field "Include access restrictions" matches value "0"
