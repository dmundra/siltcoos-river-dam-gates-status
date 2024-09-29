<?php

namespace Drupal\tragedy_commons\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\tragedy_commons\TragedyCommonsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = new static(
      $container->get('database'),
      $container->get('tragedy_commons.repository'),
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('extension.list.module')
    );
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Construct the new controller.
   */
  public function __construct(Connection $database, TragedyCommonsRepository $repository, FormBuilder $form_builder, RequestStack $stack, ModuleExtensionList $extension_list_module) {
    $this->database = $database;
    $this->repository = $repository;
    $this->formBuilder = $form_builder;
    $this->stack = $stack;
    $this->extensionListModule = $extension_list_module;
  }

  /**
   * Render game web page title for game.
   */
  public function gameTitle($gid) {
    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

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

    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

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

    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

    if (!empty($entries)) {
      foreach ($entries as $entry) {
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
    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

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

    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

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

            $rows = [];
            $header = [
              'rid' => ['data' => $this->t('Id'), 'field' => 'r.rid'],
              'round_number' => ['data' => $this->t('Round #'), 'field' => 'r.round_number'],
              'cows' => [
                'data' => $this->t('Number of cows'),
                'field' => 'r.cows',
              ],
              'started' => ['data' => $this->t('Started'), 'field' => 'r.started', 'sort' => 'desc'],
              'updated' => ['data' => $this->t('Updated'), 'field' => 'r.updated'],
              'completed' => ['data' => $this->t('Completed'), 'field' => 'r.completed'],
              'show_names' => ['data' => $this->t('Show names'), 'field' => 'r.show_names'],
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

            $round_completed = FALSE;
            foreach ($rounds as $round) {
              $completed_text = $this->t('No');
              if ($round->completed) {
                $completed_text = $this->t('Yes');
                $round_completed = TRUE;
              }
              $rows[] = [
                'rid' => new Link($round->rid, new Url('tragedy_commons.gamespace_wait', [
                  'gid' => $gid,
                  'pid' => $pid,
                  'rid' => $round->rid,
                ])),
                'round_number' => $round->round_number > 0 ? $round->round_number : $this->t('TBD'),
                'cows' => Html::escape($round->cows),
                'started' => date('m/d/Y', $round->started),
                'updated' => date('m/d/Y', $round->updated),
                'completed' => $completed_text,
                'show_names' => $round->show_names ? $this->t('Yes') : $this->t('No'),
              ];
            }

            if ($round_completed || empty($rows)) {
              $content['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\NumberOfCowsForm', $request, $player, $test);
            }
            else {
              $content['not_completed'] = [
                '#type' => 'markup',
                '#markup' => $this->t('<h2>Current Round</h2><p>Round is not completed. Click on the round to wait for results.</p>'),
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
    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

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
                  '#markup' => $this->t('<p id="wait"><strong>The program is working. As soon as all the data for the class has been processed, you will be automatically returned to your game page to enter a new round of data.</strong></p>'),
                ];

                $content['intro']['#attached']['drupalSettings'] = [
                  'round' => $round,
                  'roundJsonUri' => (new Url('tragedy_commons.gamespace_round_json', [
                    'gid' => $gid,
                    'pid' => $pid,
                    'rid' => $rid,
                  ]))->toString(),
                  'returnUri' => (new Url('tragedy_commons.gamespace_results', [
                    'gid' => $gid,
                  ]))->toString(),
                ];
                $content['intro']['#attached']['library'][] = 'tragedy_commons/wait';

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

  /**
   * Get the round in JSON.
   */
  public function roundJson($gid, $pid, $rid) {
    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

    if (!empty($entries)) {
      foreach ($entries as $request) {
        $players = $this->repository->loadPlayer(['pid' => $pid, 'gid' => $gid]);
        if (!empty($players)) {
          foreach ($players as $player) {
            $rounds = $this->repository->loadRound(['rid' => $rid, 'pid' => $pid, 'gid' => $gid]);
            if (!empty($rounds)) {
              foreach ($rounds as $round) {
                return new JsonResponse($round);
              }
            }
          }
        }
      }
    }

    return new JsonResponse([]);
  }

  /**
   * Render results page for game.
   */
  public function results($gid) {
    global $base_url;
    $content = [];

    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

    if (!empty($entries)) {
      foreach ($entries as $entry) {
        $images = $base_url . '/' . $this->extensionListModule->getPath('tragedy_commons') . '/images';
        $content['intro'] = [
          '#type' => 'markup',
          '#markup' => $this->t('<h2>Interpreting the commons picture:</h2><p>The green area is the size of the commons. When just filled with cows, the collectively optimal number of cows is on the commons. Green area without cows indicates that grazing MORE cows would increase community milk production. Grey cows indicate that too many cows are being grazed and grazing FEWER cows would increase community milk production.</p><p>* = optimal would max total milk production</p>'),
        ];

        $content['intro']['#attached']['library'][] = 'tragedy_commons/results';

        $query = $this->database->select('tragedy_commons_multi_round', 'r')
          ->condition('r.gid', $gid)
          ->condition('r.completed', 1)
          ->orderBy('r.updated', 'DESC')
          ->range(0, 1);
        $query->fields('r');
        $round = $query->execute()->fetchAll();
        $number_of_rounds = $round[0]->round_number;

        $rounds_details_header = [
          $this->t('Name'),
          $this->t('No. of Cows'),
          $this->t('Revenue or Loss'),
          $this->t('Profit Per Cow'),
        ];
        $rounds_summary_header = [
          $this->t('Summary'),
          $this->t('Actual'),
          $this->t('Optimal*'),
        ];
        $a = 500;
        $b = 10;
        $cost = 100;

        for ($round_number = 1; $round_number <= $number_of_rounds; $round_number++) {
          $query = $this->database->select('tragedy_commons_multi_round', 'r')
            ->condition('r.gid', $gid)
            ->condition('r.round_number', $round_number)
            ->orderBy('r.rid');
          $query->fields('r');
          $rounds = $query->execute()->fetchAll();
          if (!empty($rounds)) {
            $content['round_' . $round_number . '_details'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h3>Details of Round @round_number</h3>', [
                '@round_number' => $round_number,
              ]),
              '#prefix' => '<div class="cows-row"><div class="cows-column">',
            ];

            $rows = [];
            $farmers = 0;
            $all_animals = 0;
            foreach ($rounds as $round) {
              $farmers++;
              $all_animals += $round->cows;
            }
            $average_revenue = $a - (($b / $farmers) * $all_animals);

            foreach ($rounds as $round) {
              $player_name = '';
              if ($round->show_names) {
                $player = $this->repository->loadPlayer(['pid' => $round->pid]);
                $player_name .= $player[0]->lastname . ', ' . $player[0]->firstname;
              }

              $rows[] = [
                $player_name,
                $round->cows,
                round($round->cows * ($average_revenue - $cost), 2),
                round($average_revenue - 100, 2),
              ];
            }

            $content['round_' . $round_number . '_details_table'] = [
              '#type' => 'table',
              '#header' => $rounds_details_header,
              '#rows' => $rows,
              '#empty' => $this->t('No players'),
              '#attributes' => ['class' => ['views-table']],
            ];

            $content['round_' . $round_number . '_summary'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h3>Summary of Round @round_number</h3>', [
                '@round_number' => $round_number,
              ]),
              '#prefix' => '</div><div class="cows-column">',
            ];

            $optimal = $farmers * (2 * $b);
            $optimal_profit_per_cow = (($a - $cost) / 2);
            $optimal_profit_per_farmer = (($a - $cost) / 2) * (2 * $b);
            $complete_profit = $optimal_profit_per_cow * $optimal;
            $total_profit = $all_animals * ($average_revenue - $cost);
            $total_average_profit = $total_profit / $farmers;
            $summary_rows = [];
            $summary_rows[] = [
              $this->t('Farmers:'),
              $farmers,
              $this->t('No optimal #'),
            ];
            $summary_rows[] = [
              $this->t('Cows:'),
              $all_animals,
              $optimal,
            ];
            $summary_rows[] = [
              $this->t('Total milk:'),
              '$' . round($total_profit, 2),
              '$' . round($complete_profit, 2),
            ];
            $summary_rows[] = [
              $this->t('Milk/rancher:'),
              '$' . round($total_average_profit, 2),
              '$' . round($optimal_profit_per_farmer, 2),
            ];

            $content['round_' . $round_number . '_summary_table'] = [
              '#type' => 'table',
              '#header' => $rounds_summary_header,
              '#rows' => $summary_rows,
              '#empty' => $this->t('No players'),
              '#attributes' => ['class' => ['views-table']],
              '#suffix' => '</div>',
            ];

            $commons = '';
            $tens = substr($all_animals, 0, -1);
            $ones = substr($all_animals, -1);
            $fulllines = $tens / 2;
            if (substr($fulllines, -2) == '.5') {
              $fulllines = $fulllines - .5;
              $ones = $ones + 10;
            }
            $blanks = 20 - $ones;
            $color = "green";
            if ($ones == 0) {
              $blanks = 0;
            }
            if ($all_animals <= $optimal) {
              $partlines = 1;
              if ($blanks == 0) {
                $partlines = 0;
              }
              $blanklines = $farmers - ($fulllines + $partlines);
              $commons .= str_repeat('<IMG CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><IMG CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><BR>', $fulllines);
              $commons .= str_repeat('<IMG CLASS="cows" SRC="' . $images . '/1' . $color . '.gif" HEIGHT=14 WIDTH=21>', $ones);
              $commons .= str_repeat('<IMG CLASS="cows" SRC="' . $images . '/blank' . $color . '.gif" HEIGHT=14 WIDTH=21>', $blanks);
              $commons .= '<BR>';
              $commons .= str_repeat('<IMG CLASS="cows" SRC="' . $images . '/blank' . $color . '.gif" HEIGHT=14 WIDTH=420><BR>', $blanklines);
            }
            else {
              $commons .= str_repeat('<IMG CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><IMG CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><BR>', $farmers);
              $color = "grey";
              $commons .= str_repeat('<IMG CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><IMG CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><BR>', ($fulllines - $farmers));
              $commons .= str_repeat('<IMG CLASS="cows" SRC="' . $images . '/1' . $color . '.gif" HEIGHT=14 WIDTH=21>', $ones);
            }

            $content['round_' . $round_number . '_pasture'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h3>Area of Round @round_number</h3>', [
                '@round_number' => $round_number,
              ]) . $commons,
              '#prefix' => '<div class="cows-column">',
              '#suffix' => '</div></div>',
            ];
          }
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
