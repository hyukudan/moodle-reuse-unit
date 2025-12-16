@local @local_reuseunit
Feature: Synchronize sections with templates
  As a teacher
  I want to keep sections linked to templates
  So that I can receive updates when templates change

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname      | shortname |
      | Test Course   | TC        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC     | editingteacher |
    And the following "local_reuseunit > templates" exist:
      | name              | description       | sharelevel | userid   |
      | Linked Template   | Test template     | global     | teacher1 |

  @javascript
  Scenario: Teacher can link a section to a template when importing
    Given I log in as "teacher1"
    And I am on "Test Course" course homepage
    And I navigate to "Import unit" in current page administration
    And I click on "Templates" "link"
    And I click on "Linked Template" "text"
    When I click on "Keep linked for future updates" "checkbox"
    And I click on "Import" "button"
    Then I should see "Import successful"
    And I should see "Section linked to template successfully"

  @javascript
  Scenario: Teacher can view linked sections in a course
    Given the following "local_reuseunit > links" exist:
      | templatename    | courseshortname | sectionnum |
      | Linked Template | TC              | 1          |
    And I log in as "teacher1"
    And I am on "Test Course" course homepage with editing mode on
    Then I should see "Linked to: Linked Template" in the "Topic 1" "section"
    And I should see "Sync now" in the "Topic 1" "section"

  @javascript
  Scenario: Teacher can unlink a section from a template
    Given the following "local_reuseunit > links" exist:
      | templatename    | courseshortname | sectionnum |
      | Linked Template | TC              | 1          |
    And I log in as "teacher1"
    And I am on "Test Course" course homepage with editing mode on
    When I click on "Unlink from template" "link" in the "Topic 1" "section"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    Then I should see "Section unlinked from template"
    And I should not see "Linked to:" in the "Topic 1" "section"

  @javascript
  Scenario: Teacher can sync a section when template is updated
    Given the following "local_reuseunit > links" exist:
      | templatename    | courseshortname | sectionnum | updateavailable |
      | Linked Template | TC              | 1          | 1               |
    And I log in as "teacher1"
    And I am on "Test Course" course homepage with editing mode on
    Then I should see "Update available" in the "Topic 1" "section"
    When I click on "Sync now" "link" in the "Topic 1" "section"
    And I click on "Confirm" "button" in the "Confirm sync" "dialogue"
    Then I should see "Synchronization completed successfully"
