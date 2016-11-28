Feature: steppify
  In order to generate steps from a Codeception module
  As a tester and developer
  I need to be able to jump start the translation of module methods into steps from the command line

  Scenario: adding posts with WPDb methods
    Given I have post in database
      | post_title      |
      | Steppify post 1 |
      | Steppify post 2 |
    When I am on page '/'
    Then I see 'Steppify post 1'
    Then I see 'Steppify post 2'

  Scenario: adding pages with WPDb methods
    Given I have page in database
      | post_title      |
      | Steppify page 1 |
    When I am on page '/page-1'
    Then I see 'Steppify page 1'

