<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Controller;

use Drupal\Tests\UnitTestCase;

/**
 * Tests that the base controller class.
 *
 * @group Controller
 */
class ControllerBaseTest extends UnitTestCase {

  /**
   * The tested controller base class.
   */
  protected StubControllerBase $controllerBase;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->controllerBase = new StubControllerBase();
  }

  /**
   * Tests the config method.
   */
  public function testGetConfig(): void {
    $config_factory = $this->getConfigFactoryStub([
      'config_name' => [
        'key' => 'value',
      ],
      'config_name2' => [
        'key2' => 'value2',
      ],
    ]);

    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->once())
      ->method('get')
      ->with('config.factory')
      ->willReturn($config_factory);
    \Drupal::setContainer($container);

    $config_method = new \ReflectionMethod(StubControllerBase::class, 'config');

    // Call config twice to ensure that the container is just called once.
    $config = $config_method->invoke($this->controllerBase, 'config_name');
    $this->assertEquals('value', $config->get('key'));

    $config = $config_method->invoke($this->controllerBase, 'config_name2');
    $this->assertEquals('value2', $config->get('key2'));
  }

}
