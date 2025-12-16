@local @local_reuseunit
Feature: Manage section templates
  As a teacher
  I want to save sections as templates
  So that I can quickly reuse them in multiple courses

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "categories" exist:
      | name       | idnumber |
      | Category 1 | CAT1     |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Test Course   | TC        | CAT1     |
      | Other Course  | OC        | CAT1     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC     | editingteacher |
      | teacher2 | OC     | editingteacher |
    And the following "activities" exist:
      | activity | name           | course | section |
      | forum    | Welcome Forum  | TC     | 1       |
      | page     | Course Info    | TC     | 1       |

  @javascript
  Scenario: Teacher can save a section as personal template
    Given I log in as "teacher1"
    And I am on "Test Course" course homepage with editing mode on
    When I click on "Save as template" "link" in the "Topic 1" "section"
    And I wait until the page is ready
    Then I should see "Save as template"
    And I set the field "Template name" to "My Welcome Section"
    And I set the field "Description" to "Contains welcome forum and course info"
    And I set the field "Tags" to "welcome, intro"
    And I click on "Only me" "radio"
    And I click on "Save template" "button"
    Then I should see "Template saved successfully"

  @javascript
  Scenario: Personal template is only visible to creator
    Given the following "local_reuseunit > templates" exist:
      | name              | description    | sharelevel | userid   |
      | Private Template  | My template    | personal   | teacher1 |
    And I log in as "teacher2"
    And I am on "Other Course" course homepage
    When I navigate to "Import unit" in current page administration
    And I click on "Templates" "link"
    Then I should not see "Private Template"

  @javascript
  Scenario: Category template is visible to teachers in same category
    Given the following "local_reuseunit > templates" exist:
      | name               | description       | sharelevel | userid   | categoryid |
      | Shared Template    | Department shared | category   | teacher1 | CAT1       |
    And I log in as "teacher2"
    And I am on "Other Course" course homepage
    When I navigate to "Import unit" in current page administration
    And I click on "Templates" "link"
    Then I should see "Shared Template"
    And I should see "My department"

  @javascript
  Scenario: Global template is visible to all teachers
    Given the following "local_reuseunit > templates" exist:
      | name             | description  | sharelevel |
      | Global Template  | For everyone | global     |
    And I log in as "teacher2"
    And I am on "Other Course" course homepage
    When I navigate to "Import unit" in current page administration
    And I click on "Templates" "link"
    Then I should see "Global Template"
    And I should see "Entire institution"

  @javascript
  Scenario: Teacher can search templates by name and tags
    Given the following "local_reuseunit > templates" exist:
      | name              | tags                | sharelevel |
      | Math Intro        | math, beginner      | global     |
      | Physics Intro     | physics, beginner   | global     |
      | Advanced Math     | math, advanced      | global     |
    And I log in as "teacher1"
    And I am on "Test Course" course homepage
    And I navigate to "Import unit" in current page administration
    And I click on "Templates" "link"
    When I set the field "template-search" to "math"
    And I wait "1" seconds
    Then I should see "Math Intro"
    And I should see "Advanced Math"
    And I should not see "Physics Intro"

  @javascript
  Scenario: Template creator can delete their template
    Given the following "local_reuseunit > templates" exist:
      | name              | sharelevel | userid   |
      | My Template       | personal   | teacher1 |
    And I log in as "teacher1"
    And I am on "Test Course" course homepage
    And I navigate to "Import unit" in current page administration
    And I click on "Templates" "link"
    When I click on "Delete" "link" in the "My Template" "table_row"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    Then I should see "Template deleted successfully"
    And I should not see "My Template"

  @javascript
  Scenario: Teacher cannot delete another teacher's template
    Given the following "local_reuseunit > templates" exist:
      | name              | sharelevel | userid   | categoryid |
      | Other Template    | category   | teacher1 | CAT1       |
    And I log in as "teacher2"
    And I am on "Other Course" course homepage
    And I navigate to "Import unit" in current page administration
    And I click on "Templates" "link"
    Then I should see "Other Template"
    And "Delete" "link" should not exist in the "Other Template" "table_row"
