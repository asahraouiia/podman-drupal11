<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_division\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional test for Division add form.
 *
 * @group ps_division
 */
final class DivisionFormFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'text',
    'ps',
    'ps_dictionary',
    'ps_division',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests form accessibility and basic submission.
   */
  public function testAddFormSubmission(): void {
    $admin = $this->drupalCreateUser(['administer ps_division entities']);
    $this->drupalLogin($admin);

    // Access add form.
    $this->drupalGet('/admin/ps/structure/divisions/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('building_name');

    // Submit form with minimal values.
    $edit = [
      'building_name' => 'Functional Form Building',
      'entity_id' => '900',
      // Surfaces widget first row value field (may vary on rendering order).
      'surfaces[0][value]' => '33.30',
      'surfaces[0][unit]' => 'M2',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('Division Functional Form Building created');
  }
}
