<?php

namespace Drupal\tragedy_commons\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Single game pages.
 */
class SingleGameController extends ControllerBase {

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
      $container->get('form_builder'),
    );
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }

  /**
   * Construct the new controller.
   */
  public function __construct(FormBuilder $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * Learning to Make Optimal Use of A Private Farm game page.
   */
  public function cow() {
    $output = [];

    $output['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p>Web Page for playing the <em>precursor</em> to the Tragedy of the Commons Game.</p><p>You have a 5 acre farm on which you can put as many cows as you want. Being new to farming, you have to figure out the optimal number of cows to put on your farm so that you maximize your profits, measured by how much milk you sell at market. So, pick a number of cows, then press submit, and see how you do. Good luck and enjoy!</p>',
    ];

    $output['facts_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>Some facts to remember:</h2>'),
    ];

    $output['facts'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [
        $this->t('Cows cost $100 each.'),
        $this->t('Your profits will be determined by how much milk your cows provide by the end of the year. That will depend on how many cows you put on the commons, but unlike the commons case, not on how many cows other ranchers put on their farms.'),
        $this->t('You CAN lose money, since your cows cost $100 each. If the farm is seriously overgrazed, your cows will die.'),
      ],
    ];

    $output['strategy_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>A strategy for optimizing profits:</h2>'),
    ];

    $output['strategy'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [
        $this->t('In round 1, submit any number of cows and see what your profits are.'),
        $this->t('In round 2, submit a higher number of cows.'),
        [
          '#markup' => $this->t('In all subsequent rounds:'),
          'children' => [
            $this->t('If profits in the current round were higher than in the previous round, submit more cows.'),
            $this->t('If profits in the current round were lower than in the previous round, submit fewer cows.'),
          ],
        ],
        $this->t('The program will let you know when you have chosen the optimum number of cows and have maximized your profits.'),
      ],
    ];

    $output['form_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>Play Game Here</h2>'),
    ];

    $output['form_title']['#attached']['library'][] = 'tragedy_commons/singlegames';

    $output['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\SingleGame1Form');

    $output['footer'] = [
      '#type' => 'markup',
      '#markup' => new FormattableMarkup('<p>There is a multi-person Tragedy of the Commons game that I have established to be played simultaneously by students in a class. If you are interested in finding out more, please read more about it on the <a href="@url">main Tragedy of the Commons page</a>.</p>', [
        '@url' => (new Url('tragedy_commons.base'))->toString(),
      ]),
    ];

    return $output;
  }

  /**
   * Tragedy of Indigenous Whaling Game page.
   */
  public function whale() {
    $output = [];

    $output['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p>Web Page for playing the <em>precursor</em> to the Tragedy of the Commons Game involving whales.</p><p>You are an elder of an indigenous people that have caught bowhead whales as part of their cultural heritage for centuries. You are very concerned that your heritage will die out if youth are not trained in the old ways of whaling. Many youth have already left for the life of the big city. You are also aware, however, that bowhead whale stocks are close to extinction. Although they are close to extinction because of past commercial whaling which, unfortunately, cannot be undone. You realize that the current threat to the bowhead stock comes only from the collective kill of bowheads by other indigenous groups like yours (no commercial whaling of bowheads is allowed and none is occurring).</p>',
    ];

    $output['facts_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>You must choose each year how many boats to send out whaling.</h2>'),
    ];

    $output['facts'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [
        $this->t('The number of whales killed - <em>and hence youth trained</em> - will depend on the size of the whale population: a larger population makes it easier to find a whale to kill, a smaller population makes it harder to find a whale to kill.'),
        $this->t('Each boat kills a maximum of one whale per year.'),
        $this->t('Each whale killed (<strong>NOT</strong> boats sent out!) trains 3 youth.'),
      ],
    ];

    $output['strategy_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>The total size of the bowhead population is influenced by:</h2>'),
    ];

    $output['strategy'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [
        $this->t('The current starting population.'),
        $this->t('The annual 1% "recruitment" rate (12% birth rate minus 11% death rate) of the previous years population'),
        $this->t('The number of whales killed by <strong>all</strong> indigenous peoples.'),
        $this->t('The maximum carrying capacity of the ocean for bowhead whales (i.e., if no whales were killed, the natural mortality rate would balance the natural birth rate at a specific carrying capacity.'),
        $this->t('The extinction point of one-tenth of the starting population, at which point so few whales are around that the population cannot recover.'),
      ],
    ];

    $output['goal_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>Your goal in the game is to:</h2>'),
    ];

    $output['goal'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [
        $this->t('Train as many youth as possible by killing whales,'),
        $this->t('But, maintain a healthy whale stock by not killing too many whales,'),
        $this->t('While recognizing that the sum of all bowheads taken by all indigenous peoples trying to train their youth may cause the bowhead population to crash.'),
      ],
    ];

    $output['decision_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>Your decision about how many boats to deploy should reflect:</h2>'),
    ];

    $output['decision'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [
        $this->t('The effort to train as many youths as possible. You will be given a direct measure of how many youths you have trained after each round/year.'),
        $this->t('The effort to keep the whale population stable or growing so that you can train youths in the future. Unfortunately, accurate information about whale populations is hard to get, so you must estimate the whale population based on the "Whales Killed Per Boat" figure that you will be given after each round. As population increases, "Whales Killed Per Boat" will increase; as population decreases, "Whales Killed Per Boat" will decrease.'),
      ],
    ];

    $output['before_form'] = [
      '#type' => 'markup',
      '#markup' => $this->t('So, decide how many boats you want to send out whaling, then press submit, and see whether you can keep both your culture and the whales alive. Good luck and I hope you learn something about how difficult the Tragedy of the Commons may be to solve, especially so when you lack hard scientific information about the environmental resource at stake, such as whale populations!'),
    ];

    $output['form_title'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<h2>Play Game Here</h2>'),
    ];

    $output['form_title']['#attached']['library'][] = 'tragedy_commons/singlegames';

    $output['form'] = $this->formBuilder->getForm('Drupal\tragedy_commons\Form\SingleGame2Form');

    $output['footer'] = [
      '#type' => 'markup',
      '#markup' => new FormattableMarkup("NB: <em>This game doesn't really have a solution - I never quite got around to writing the script for it. But by playing a few rounds, this may give you a sense of the tradeoffs that indigenous cultures MAY face in retaining their indigenous culture while also trying to maintain a scarce environmental resource, like whales, on which their culture depends.</em><hr/><p>There is a multi-person Tragedy of the Commons game that I have established to be played simultaneously by students in a class. If you are interested in finding out more, please read more about it on the <a href='@url'>main Tragedy of the Commons page</a>.</p>", [
        '@url' => (new Url('tragedy_commons.base'))->toString(),
      ]),
    ];

    return $output;
  }

}
