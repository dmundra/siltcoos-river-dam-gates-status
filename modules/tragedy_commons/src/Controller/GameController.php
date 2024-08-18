<?php

namespace Drupal\tragedy_commons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilder;
use Drupal\tragedy_commons\TragedyCommonsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Game pages.
 */
class GameController extends ControllerBase {

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Our database repository service.
   *
   * @var \Drupal\tragedy_commons\TragedyCommonsRepository
   */
  protected $repository;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new static(
      $container->get('database'),
      $container->get('tragedy_commons.repository'),
      $container->get('form_builder')
    );
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Construct the new controller.
   */
  public function __construct(Connection $database, TragedyCommonsRepository $repository, FormBuilder $form_builder) {
    $this->database = $database;
    $this->repository = $repository;
    $this->formBuilder = $form_builder;
  }

  /**
   * Render start page for game.
   */
  public function start($gid) {
    $content = [];

    $entries = $this->repository->load(['gid' => $gid]);

    if (!empty($entries)) {
      foreach ($entries as $entry) {
        $content['intro'] = [
          '#type' => 'markup',
          '#markup' => $this->t('<p>Game for @firstname @lastname (:gid)</p>', [
            '@firstname' => $entry->firstname,
            '@lastname' => $entry->lastname,
            ':gid' => $gid,
          ]),
        ];

        $content['test'] = [
          '#type' => 'markup',
          '#markup' => $this->t('<p>Is this a test? %test</p>', [
            '%test' => $_REQUEST['test'] ?? 0,
          ]),
        ];
      }
    }
    else {
      $content['notfound'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<em>Game not found</em>.'),
      ];
    }

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

}
