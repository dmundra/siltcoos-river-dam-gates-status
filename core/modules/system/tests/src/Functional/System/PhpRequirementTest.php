<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Core\Utility\PhpRequirements;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tests the output of PHP requirements on the status report.
 *
 * @group system
 */
class PhpRequirementTest extends BrowserTestBase {

  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access site reports',
    ]);
    $this->drupalLogin($admin_user);

    // By default, Drupal installation (and BrowserTestBase) do not configure
    // trusted host patterns, which leads to an error on the status report.
    // Configure them so that the site is properly configured and so that we
    // can cleanly test the errors related to PHP versions.
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . preg_quote(\Drupal::request()->getHost()) . '$'],
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
  }

  /**
   * Tests status report messages regarding the PHP version.
   */
  public function testStatusPage(): void {
    $minimum_php_version = PhpRequirements::getMinimumSupportedPhp();
    // Go to Administration.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $phpversion = phpversion();
    // Verify that the PHP version is shown on the page.
    $this->assertSession()->pageTextContains($phpversion);

    // Verify that an error is displayed about the PHP version if it is below
    // the minimum supported PHP.
    if (version_compare($phpversion, $minimum_php_version) < 0) {
      $this->assertErrorSummaries(['PHP']);
      $this->assertSession()->pageTextContains('Your PHP installation is too old. Drupal requires at least PHP ' . $minimum_php_version);
    }
    // Otherwise, there should be no error.
    else {
      $this->assertSession()->pageTextNotContains('Your PHP installation is too old. Drupal requires at least PHP ' . $minimum_php_version);
      $this->assertSession()->pageTextNotContains('Errors found');
    }

    // There should be an informational message if the PHP version is below the
    // recommended version.
    if (version_compare($phpversion, \Drupal::RECOMMENDED_PHP) < 0) {
      $this->assertSession()->pageTextContains('It is recommended to upgrade to PHP version ' . \Drupal::RECOMMENDED_PHP . ' or higher');
    }
    // Otherwise, the message should not be there.
    else {
      $this->assertSession()->pageTextNotContains('It is recommended to upgrade to PHP version ' . \Drupal::RECOMMENDED_PHP . ' or higher');
    }
  }

}
