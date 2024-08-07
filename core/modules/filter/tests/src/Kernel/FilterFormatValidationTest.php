<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of filter_format entities.
 *
 * @group filter
 * @group #slow
 */
class FilterFormatValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = FilterFormat::create([
      'format' => 'test',
      'name' => 'Test',
    ]);
    $this->entity->save();
  }

}
