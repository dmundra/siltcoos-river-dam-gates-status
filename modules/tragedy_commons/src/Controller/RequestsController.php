<?php

namespace Drupal\tragedy_commons\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tragedy_commons\TragedyCommonsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Requests controller class for Tragedy of Commons game.
 */
class RequestsController extends ControllerBase {

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
   * Render single result from the database.
   */
  public function result($gid) {
    $content = [];

    $content['intro'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>Request approval page for request :gid</h2>', [':gid' => $gid]),
    ];

    $content['intro']['#attached']['library'][] = 'tragedy_commons/requests';

    $items = [];
    $entries = $this->repository->load(['gid' => $gid]);
    foreach ($entries as $entry) {
      $items = [
        $this->t('<strong>Id:</strong> %gid', ['%gid' => $entry->gid]),
        $this->t('<strong>First name:</strong> %firstname', ['%firstname' => $entry->firstname]),
        $this->t('<strong>Last name:</strong> %lastname', ['%lastname' => $entry->lastname]),
        $this->t('<strong>Email:</strong> %email', ['%email' => $entry->email]),
        $this->t('<strong>Institution:</strong> %institution', ['%institution' => $entry->institution]),
        $this->t('<strong>Description:</strong> %description', ['%description' => $entry->description]),
        $this->t('<strong>Status:</strong> %status', ['%status' => $entry->status]),
        $this->t('<strong>Created:</strong> %created', ['%created' => date('m/d/Y', $entry->created)]),
        $this->t('<strong>Updated:</strong> %updated', ['%updated' => date('m/d/Y', $entry->updated)]),
      ];

      if ($entry->status == TRAGEDY_COMMONS_REQUEST) {
        $items[] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\RequestsApproveForm', $entry);
        $items[] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\RequestsDisapproveForm', $entry);
      }

      if ($entry->status == TRAGEDY_COMMONS_ACCEPTED) {
        $items[] = new Link('Game start page', new Url('tragedy_commons.gamespace', ['gid' => $entry->gid]));
      }
    }

    if (!empty($items)) {
      $content['result'] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }
    else {
      $content['notfound'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<em>Request not found</em>.'),
      ];
    }

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

  /**
   * Render a list of results from the database.
   */
  public function results() {
    $content = [];

    $rows = [];
    $header = [
      'gid' => ['data' => $this->t('Id'), 'field' => 't.gid'],
      'firstname' => [
        'data' => $this->t('First name'),
        'field' => 't.firstname',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'lastname' => [
        'data' => $this->t('Last name'),
        'field' => 't.lastname',
      ],
      'email' => ['data' => $this->t('Email'), 'field' => 't.email'],
      'institution' => [
        'data' => $this->t('Affiliation'),
        'field' => 't.institution',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'description' => [
        'data' => $this->t('Requested Purpose'),
        'field' => 't.description',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => ['data' => $this->t('Status'), 'field' => 't.status'],
      'created' => ['data' => $this->t('Created'), 'field' => 't.created', 'sort' => 'desc'],
      'updated' => [
        'data' => $this->t('Updated'),
        'field' => 't.updated',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $query = $this->database->select('tragedy_commons_multi', 't')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('t');

    // Don't forget to tell the query object how to find the header information.
    $entries = $query
      ->orderByHeader($header)
      ->execute();

    foreach ($entries as $entry) {
      $rows[] = [
        'gid' => new Link($entry->gid, new Url('tragedy_commons.result', ['gid' => $entry->gid])),
        'firstname' => Html::escape($entry->firstname),
        'lastname' => new Link(Html::escape($entry->lastname), new Url('tragedy_commons.result', ['gid' => $entry->gid])),
        'email' => Html::escape($entry->email),
        'institution' => Html::escape($entry->institution),
        'description' => Html::escape($entry->description),
        'status' => $entry->status,
        'created' => date('m/d/Y', $entry->created),
        'updated' => date('m/d/Y', $entry->updated),
      ];
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
      '#caption' => $this->t('Submitted requests'),
    ];
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

}
