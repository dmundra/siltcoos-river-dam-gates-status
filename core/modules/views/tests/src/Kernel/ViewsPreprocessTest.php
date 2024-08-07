<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Views;

/**
 * Tests the preprocessing functionality in views.theme.inc.
 *
 * @group views
 */
class ViewsPreprocessTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_preprocess'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests css classes on displays are cleaned correctly.
   */
  public function testCssClassCleaning(): void {
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();

    $entity = EntityTest::create();
    $entity->save();
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_preprocess');
    $build = $view->buildRenderable();
    $renderer->renderRoot($build);
    $this->assertStringContainsString('class="entity-test__default', (string) $build['#markup']);
    $view->destroy();

    $view->setDisplay('display_2');
    $build = $view->buildRenderable();
    $renderer->renderRoot($build);
    $markup = (string) $build['#markup'];
    $this->assertStringContainsString('css_class: entity-test__default and-another-class', $markup);
    $this->assertStringContainsString('attributes: class="entity-test__default and-another-class', $markup);
  }

  /**
   * Tests template_preprocess_views_mini_pager() when an empty pagination_heading_level value is passed.
   *
   * @covers ::template_preprocess_views_mini_pager
   */
  public function testEmptyPaginationHeadingLevelSet(): void {
    require_once $this->root . '/core/modules/views/views.theme.inc';
    $variables = [
      'tags' => [],
      'quantity' => 9,
      'element' => 0,
      'pagination_heading_level' => '',
      'parameters' => [],
    ];
    template_preprocess_views_mini_pager($variables);

    $this->assertEquals('h4', $variables['pagination_heading_level']);
  }

  /**
   * Tests template_preprocess_views_mini_pager() when no pagination_heading_level is passed.
   *
   * @covers ::template_preprocess_views_mini_pager
   */
  public function testPaginationHeadingLevelNotSet(): void {
    require_once $this->root . '/core/modules/views/views.theme.inc';
    $variables = [
      'tags' => [],
      'quantity' => 9,
      'element' => 0,
      'parameters' => [],
    ];
    template_preprocess_views_mini_pager($variables);

    $this->assertEquals('h4', $variables['pagination_heading_level']);
  }

  /**
   * Tests template_preprocess_views_mini_pager() when a pagination_heading_level value is passed.
   *
   * @covers ::template_preprocess_views_mini_pager
   */
  public function testPaginationHeadingLevelSet(): void {
    require_once $this->root . '/core/modules/views/views.theme.inc';
    $variables = [
      'tags' => [],
      'quantity' => 9,
      'element' => 0,
      'pagination_heading_level' => 'h5',
      'parameters' => [],
    ];
    template_preprocess_views_mini_pager($variables);

    $this->assertEquals('h5', $variables['pagination_heading_level']);
  }

}
