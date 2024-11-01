<?php

namespace Drupal\tragedy_commons\Controller;

use Drupal\Component\Render\FormattableMarkup;
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
        return $this->t("@firstname @lastname's Game Start Page%test", [
          '@firstname' => $request->firstname,
          '@lastname' => $request->lastname,
          '%test' => $request->test ? ' - Test' : '',
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
        $content['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\StartPageForm', $request);

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

        if ($entry->test) {
          $this->messenger()->addWarning($this->t('This is a test play through.'));
        }

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
    $a = 500;
    $b = 10;
    $cost = 100;

    $entries = $this->repository->load(['gid' => $gid, 'status' => TRAGEDY_COMMONS_ACCEPTED]);

    if (!empty($entries)) {
      foreach ($entries as $request) {
        $players = $this->repository->loadPlayer(['pid' => $pid, 'gid' => $gid]);
        if (!empty($players)) {
          foreach ($players as $player) {
            $content['player_intro'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<p>Welcome, :first_name! This is your Web Page for playing the Tragedy of the Commons Game. During the course of the game, please stay on this page. Good luck and enjoy!</p>', [
                ':first_name' => $player->firstname,
              ]),
            ];

            $content['player_intro']['#attached']['library'][] = 'tragedy_commons/games';

            if ($request->test) {
              $this->messenger()->addWarning($this->t('This is a test play through.'));
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
              'round_number' => ['data' => $this->t('Round #'), 'field' => 'r.round_number'],
              'cows' => [
                'data' => $this->t('Number of cows'),
                'field' => 'r.cows',
              ],
              'revenue' => $this->t('Revenue or Loss'),
              'profit' => $this->t('Profit Per Cow'),
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

            $round_completed = FALSE;
            foreach ($rounds as $round) {
              $completed_text = $this->t('No');
              if ($round->completed) {
                $completed_text = $this->t('Yes');
                $round_completed = TRUE;
              }

              // Get average revenue amongst all farmers.
              $average_revenue = 0;
              if ($round->round_number > 0) {
                $query = $this->database->select('tragedy_commons_multi_round', 'r')
                  ->condition('r.gid', $gid)
                  ->condition('r.round_number', $round->round_number)
                  ->orderBy('r.rid');
                $query->fields('r');
                $get_all_rounds = $query->execute()->fetchAll();
                if (!empty($get_all_rounds)) {
                  $farmers = 0;
                  $all_animals = 0;
                  foreach ($get_all_rounds as $get_all_round) {
                    $farmers++;
                    $all_animals += $get_all_round->cows;
                  }
                  $average_revenue = $a - (($b / $farmers) * $all_animals);
                }
              }

              $rows[] = [
                'round_number' => new Link($round->round_number > 0 ? $round->round_number : $this->t('TBD'), new Url('tragedy_commons.gamespace_wait', [
                  'gid' => $gid,
                  'pid' => $pid,
                  'rid' => $round->rid,
                ])),
                'cows' => Html::escape($round->cows),
                'revenue' => $round->round_number > 0 ? round($round->cows * ($average_revenue - $cost), 2) : $this->t('TBD'),
                'profit' => $round->round_number > 0 ? round($average_revenue - 100, 2) : $this->t('TBD'),
                'started' => date('m/d/Y', $round->started),
              ];
            }

            $content['results_title'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h2>Class Results:</h2>'),
            ];
            $results_content = $this->results($gid);
            $content += $results_content;

            $content['table'] = [
              '#type' => 'table',
              '#header' => $header,
              '#rows' => $rows,
              '#empty' => $this->t('No rounds.'),
              '#caption' => $this->t('Your Results So Far:'),
              '#attributes' => ['class' => ['views-table']],
            ];

            if ($round_completed || empty($rows)) {
              $content['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\NumberOfCowsForm', $request, $player);
            }
            else {
              $content['not_completed'] = [
                '#type' => 'markup',
                '#markup' => $this->t('<h2>Current Round</h2><p>Round is not completed. Click on the round to wait for results.</p>'),
              ];
            }
          }
        }
        else {
          $content['player_notfound'] = [
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
                  'returnUri' => (new Url('tragedy_commons.gamespace_player', [
                    'gid' => $gid,
                    'pid' => $pid,
                  ]))->toString(),
                ];
                $content['intro']['#attached']['library'][] = 'tragedy_commons/wait';

                if ($request->test) {
                  $this->messenger()->addWarning($this->t('This is a test play through.'));
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
          '#type' => 'details',
          '#title' => $this->t('Interpreting the commons picture:'),
        ];

        $content['intro']['text'] = [
          '#type' => 'markup',
          '#markup' => $this->t('<p>The green area is the size of the commons. When just filled with cows, the collectively optimal number of cows is on the commons. Green area without cows indicates that grazing MORE cows would increase community milk production. Grey cows indicate that too many cows are being grazed and grazing FEWER cows would increase community milk production.</p><p>* = optimal would max total milk production</p>'),
        ];

        if ($entry->test) {
          $this->messenger()->addWarning($this->t('This is a test play through.'));
        }

        $content['intro']['#attached']['library'][] = 'tragedy_commons/results';

        $query = $this->database->select('tragedy_commons_multi_round', 'r')
          ->condition('r.gid', $gid)
          ->condition('r.completed', 1)
          ->orderBy('r.updated', 'DESC')
          ->range(0, 1);
        $query->fields('r');
        $round = $query->execute()->fetchAll();
        $number_of_rounds = isset($round[0]) ? $round[0]->round_number : 0;

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
            $farmers = 0;
            $all_animals = 0;
            foreach ($rounds as $round) {
              $farmers++;
              $all_animals += $round->cows;
            }
            $average_revenue = $a - (($b / $farmers) * $all_animals);
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

            $content['round_' . $round_number . '_summary'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h3>Summary of Round @round_number</h3>', [
                '@round_number' => $round_number,
              ]),
              '#prefix' => '<div class="cows-summary">',
            ];

            $content['round_' . $round_number . '_summary_table'] = [
              '#type' => 'table',
              '#header' => $rounds_summary_header,
              '#rows' => $summary_rows,
              '#empty' => $this->t('No players'),
              '#attributes' => ['class' => ['views-table']],
              '#suffix' => '<p>total milk production</p></div>',
            ];

            $content['round_' . $round_number . '_details'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h3>Details of Round @round_number</h3>', [
                '@round_number' => $round_number,
              ]),
              '#prefix' => '<div class="cows-row clearfix"><div class="cows-column left">',
            ];

            $rows = [];
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
              $commons .= str_repeat('<IMG ALT="10 cows of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><IMG ALT="10 cows of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210>', $fulllines);
              $commons .= str_repeat('<IMG ALT="1 cow of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/1' . $color . '.gif" HEIGHT=14 WIDTH=21>', $ones);
              $commons .= str_repeat('<IMG ALT="Blank field of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/blank' . $color . '.gif" HEIGHT=14 WIDTH=21>', $blanks);
              $commons .= str_repeat('<IMG ALT="Blank line of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/blank' . $color . '.gif" HEIGHT=14 WIDTH=420>', $blanklines);
            }
            else {
              $commons .= str_repeat('<IMG ALT="10 cows of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><IMG ALT="10 cows of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210>', $farmers);
              $color = "grey";
              $commons .= str_repeat('<IMG ALT="10 cows of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210><IMG ALT="10 cows of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/10' . $color . '.gif" HEIGHT=14 WIDTH=210>', ($fulllines - $farmers));
              $commons .= str_repeat('<IMG ALT="1 cow of ' . $color . ' color" CLASS="cows" SRC="' . $images . '/1' . $color . '.gif" HEIGHT=14 WIDTH=21>', $ones);
            }

            $content['round_' . $round_number . '_pasture'] = [
              '#type' => 'markup',
              '#markup' => $this->t('<h3>Area of Round @round_number</h3>', [
                '@round_number' => $round_number,
              ]) . $commons,
              '#prefix' => '</div><div class="cows-column right">',
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

  /**
   * Instructions after submitting request.
   */
  public function instructions() {
    $output = [];

    $output['intro'] = [
      '#type' => 'markup',
      '#markup' => new FormattableMarkup("<p>Your game has been approved and is ready to play. This game allows you to play one game, of multiple rounds for one class session.</p><p><strong>If you want to play the game in more than one class</strong>, <a href='@url'>fill in the form again</a> - filling it in one time for each class but add a different letter (NOT a number) to the end of your last name (e.g., mitchella, mitchellb, mitchellc). Why? Because last names are the passwords for each game.</p><p>If the game doesn't work or the results are problematic fill out the request form again to get a new game.</p>", [
        '@url' => (new Url('tragedy_commons.requests'))->toString(),
      ]),
    ];

    return $output;
  }

}
