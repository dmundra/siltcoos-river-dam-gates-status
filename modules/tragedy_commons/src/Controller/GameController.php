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
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $stack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new static(
      $container->get('database'),
      $container->get('tragedy_commons.repository'),
      $container->get('form_builder'),
      $container->get('request_stack')
    );
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Construct the new controller.
   */
  public function __construct(Connection $database, TragedyCommonsRepository $repository, FormBuilder $form_builder, RequestStack $stack) {
    $this->database = $database;
    $this->repository = $repository;
    $this->formBuilder = $form_builder;
    $this->stack = $stack;
  }

  /**
   * Render game web page title for game.
   */
  public function gameTitle($gid) {
    $entries = $this->repository->load(['gid' => $gid]);

    if (!empty($entries)) {
      foreach ($entries as $request) {
        return $this->t("@firstname @lastname's Game Start Page", [
          '@firstname' => $request->firstname,
          '@lastname' => $request->lastname,
        ]);
      }
    }

    return $this->t('Tragedy of the Commons Start Page');
  }

  /**
   * Render start page for game.
   */
  public function start($gid) {
    $content = [];

    $entries = $this->repository->load(['gid' => $gid]);

    if (!empty($entries)) {
      foreach ($entries as $request) {
        $test = $this->stack->getCurrentRequest()->query->get('test') ?? 0;

        $content['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\StartPageForm', $request, $test);

        $content['intro']['#attached']['library'][] = 'tragedy_commons/games';

        $rows = [];
        $header = [
          'pid' => ['data' => $this->t('Id'), 'field' => 'p.pid'],
          'firstname' => [
            'data' => $this->t('First name'),
            'field' => 'p.firstname',
          ],
          'lastname' => [
            'data' => $this->t('Last name'),
            'field' => 'p.lastname',
          ],
          'started' => ['data' => $this->t('Started'), 'field' => 'p.started', 'sort' => 'desc'],
        ];

        $query = $this->database->select('tragedy_commons_multi_player', 'p')
          ->condition('p.gid', $gid)
          ->extend('Drupal\Core\Database\Query\TableSortExtender');
        $query->fields('p');

        // Don't forget to tell the query object how to find the header
        // information.
        $players = $query
          ->orderByHeader($header)
          ->execute();

        foreach ($players as $player) {
          $rows[] = [
            'pid' => new Link($player->pid, new Url('tragedy_commons.gamespace_player', [
              'gid' => $gid,
              'pid' => $player->pid,
            ])),
            'firstname' => Html::escape($player->firstname),
            'lastname' => new Link(Html::escape($player->lastname), new Url('tragedy_commons.gamespace_player', [
              'gid' => $gid,
              'pid' => $player->pid,
            ])),
            'started' => date('m/d/Y', $player->started),
          ];
        }
        $content['table'] = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => $this->t('No players.'),
          '#caption' => $this->t('Current players'),
          '#attributes' => ['class' => ['views-table']],
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

  /**
   * Render management page for game.
   */
  public function manage($gid) {
    $content = [];

    $entries = $this->repository->load(['gid' => $gid]);

    if (!empty($entries)) {
      foreach ($entries as $entry) {
        $content['test'] = [
          '#type' => 'markup',
          '#markup' => $this->t('<p>Is this a test? %test</p>', [
            '%test' => $this->stack->getCurrentRequest()->query->get('test') ?? 0,
          ]),
        ];

        $content['intro'] = [
          '#type' => 'markup',
          '#markup' => $this->t('<p>To manage the <strong><em>Tragedy of the Commons Game for @firstname @lastname (:gid)</em></strong>, <strong>WAIT until all students have submitted their number of cows</strong>. ONLY after all the students have completed submitting their number of cows for this round, THEN fill in the following form and click on the SUBMIT button.</p>', [
            '@firstname' => $entry->firstname,
            '@lastname' => $entry->lastname,
            ':gid' => $gid,
          ]),
        ];

        $rounds_form = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\RoundsForm', $entry);

        $items = [
          $this->t('<em>The game allows you to decide AFTER EACH ROUND,
whether you want to reveal the names of the players for the current round and/or
all previous rounds.</em>'),
          $this->t('For round 1, it is probably best to simply click on
          the submit button.'),
          $this->t('For subsequent rounds, you may want to enter the
          numbers of the rounds for which you want players names printed, with
          each round for which you want names printed, separated by commas
          (e.g., 3,4,5,9).'),
          $rounds_form,
          $this->t('<strong>IMPORTANT</strong>: Clicking the submit button
will produce the results page for both you and your students. <em>After you are
directed to that page, you will need to click on your browser\'s BACK button to
return to this page to process the next round of results.</em>'),
        ];

        $content['result'] = [
          '#theme' => 'item_list',
          '#items' => $items,
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

  /**
   * Render game web page title for player.
   */
  public function playTitle($gid, $pid) {
    $entries = $this->repository->load(['gid' => $gid]);

    if (!empty($entries)) {
      foreach ($entries as $request) {
        $players = $this->repository->loadPlayer(['pid' => $pid, 'gid' => $gid]);
        if (!empty($players)) {
          foreach ($players as $player) {
            return $this->t("@first_name @last_name's Game Web Page", [
              '@first_name' => $player->firstname,
              '@last_name' => $player->lastname,
            ]);
          }
        }
      }
    }

    return $this->t('Tragedy of the Commons Game Web Page');
  }

  /**
   * Render game web page for game.
   */
  public function play($gid, $pid) {
    $content = [];

    $entries = $this->repository->load(['gid' => $gid]);

    if (!empty($entries)) {
      foreach ($entries as $request) {
        $players = $this->repository->loadPlayer(['pid' => $pid, 'gid' => $gid]);
        if (!empty($players)) {
          foreach ($players as $player) {
            $test = $this->stack->getCurrentRequest()->query->get('test') ?? 0;

            $content['intro'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<p>Welcome, :first_name! This is your Web Page for playing the Tragedy of the Commons Game. During the course of the game, please stay on this page. Good luck and enjoy!</p>', [
                ':first_name' => $player->firstname,
              ]),
            ];

            $content['intro']['#attached']['library'][] = 'tragedy_commons/games';

            if ($test) {
              $content['test_intro'] = [
                '#type' => 'markup',
                '#markup' => $this->t('<p><strong><em>This is a test play through.</em></strong></p>'),
              ];
            }

            $items = [
              $this->t('Cows cost $100 each.'),
              $this->t('Each round you start with $10,000 (so, if you enter more than 100 cows, only 100 cows will be put on the commons).'),
              [
                '#markup' => $this->t('Your profits will be determined by how fat your cows are at the end of the year. That will depend on the following:'),
                'children' => [
                  $this->t('How many cows you put on the commons.'),
                  $this->t('How many cows other ranchers put on the commons.'),
                ],
              ],
              $this->t('You <em>CAN</em> lose money, since your cows cost $100 each. If the commons is seriously overgrazed, your cow will die.'),
            ];

            $content['facts_title'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h2>Some facts to remember:</h2>'),
            ];

            $content['facts'] = [
              '#theme' => 'item_list',
              '#list_type' => 'ol',
              '#items' => $items,
            ];

            $content['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\NumberOfCowsForm', $request, $player, $test);

            $rows = [];
            $header = [
              'rid' => ['data' => $this->t('Id'), 'field' => 'r.rid'],
              'cows' => [
                'data' => $this->t('Number of cows'),
                'field' => 'r.cows',
              ],
              'started' => ['data' => $this->t('Started'), 'field' => 'r.started', 'sort' => 'desc'],
            ];

            $query = $this->database->select('tragedy_commons_multi_round', 'r')
              ->condition('r.gid', $gid)
              ->condition('r.pid', $pid)
              ->extend('Drupal\Core\Database\Query\TableSortExtender');
            $query->fields('r');

            // Don't forget to tell the query object how to find the header
            // information.
            $rounds = $query
              ->orderByHeader($header)
              ->execute();

            foreach ($rounds as $round) {
              $rows[] = [
                'rid' => new Link($round->rid, new Url('tragedy_commons.gamespace_wait', [
                  'gid' => $gid,
                  'pid' => $pid,
                  'rid' => $round->rid,
                ])),
                'cows' => Html::escape($round->cows),
                'started' => date('m/d/Y', $round->started),
              ];
            }
            $content['table'] = [
              '#type' => 'table',
              '#header' => $header,
              '#rows' => $rows,
              '#empty' => $this->t('No rounds.'),
              '#caption' => $this->t('Current rounds'),
              '#attributes' => ['class' => ['views-table']],
            ];
          }
        }
        else {
          $content['notfound'] = [
            '#type' => 'markup',
            '#markup' => $this->t('<em>Player not found</em>.'),
          ];
        }
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

  /**
   * Render game wait page for game.
   */
  public function wait($gid, $pid, $rid) {
    $content = [];
    $entries = $this->repository->load(['gid' => $gid]);

    if (!empty($entries)) {
      foreach ($entries as $request) {
        $players = $this->repository->loadPlayer(['pid' => $pid, 'gid' => $gid]);
        if (!empty($players)) {
          foreach ($players as $player) {
            $rounds = $this->repository->loadRound(['rid' => $rid, 'pid' => $pid, 'gid' => $gid]);
            if (!empty($rounds)) {
              foreach ($rounds as $round) {
                $test = $this->stack->getCurrentRequest()->query->get('test') ?? 0;
                $content['intro'] = [
                  '#type' => 'markup',
                  '#markup' => $this->t('<p><strong>The program is working. As soon as all the data for the class has been processed, you will be automatically returned to your game page to enter a new round of data.</strong></p>'),
                ];

                if ($test) {
                  $content['test_intro'] = [
                    '#type' => 'markup',
                    '#markup' => $this->t('<p><strong><em>This is a test play through.</em></strong></p>'),
                  ];
                }
              }
            }
            else {
              $content['notfound'] = [
                '#type' => 'markup',
                '#markup' => $this->t('<em>Round not found</em>.'),
              ];
            }
          }
        }
        else {
          $content['notfound'] = [
            '#type' => 'markup',
            '#markup' => $this->t('<em>Player not found</em>.'),
          ];
        }
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
