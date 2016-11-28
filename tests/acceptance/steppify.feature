Feature: steppify
  In order to generate steps from a Codeception module
  As a tester and developer
  I need to be able to jump start the translation of module methods into steps from the command line

  Scenario: try steppify
    Given I have post meta in database
      | key   | value |
      | one   | foo   |
      | two   | bar   |
      | three | baz   |

