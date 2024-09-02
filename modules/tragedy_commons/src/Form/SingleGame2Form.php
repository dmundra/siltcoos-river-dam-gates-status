<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tragedy of Indigenous Whaling Game form.
 */
class SingleGame2Form extends FormBase implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $stack;

  /**
   * {@inheritDoc}
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the string_translation service.
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('request_stack')
    );
    // The StringTranslationTrait trait manages the string translation service
    // for us. We can inject the service here.
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Construct the new form object.
   */
  public function __construct(RequestStack $stack) {
    $this->stack = $stack;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_singlegame2form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['result_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Your Results'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $session = $this->stack->getSession();
    $rounds = $session->get('boats');
    $form['result_fieldset']['result'] = [
      '#type' => 'item',
      '#markup' => $this->generateTable($rounds ?? []),
    ];

    $form['boats'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter the number of boats you want to send out whaling this year:'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $form['new_game'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restart game'),
    ];

    $form['submit'] = [
      '#type' => 'button',
      '#value' => 'Submit',
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::calculateResult',
        'wrapper' => 'edit-result',
      ],
    ];

    return $form;
  }

  /**
   * Rendered results table.
   *
   * @param array $rounds
   *   Number of rounds.
   *
   * @return string
   *   Rendered table.
   */
  private function generateTable(array $rounds) {
    $table            = '<table border=5><thead><tr><th>Year</th><th>Boat-trips Made</th><th>Whales Harvested</th><th>Whales Struck/Lost</th><th>Whales Killed Per Boat</th><th>Youths Trained</th></tr></thead><tbody>';
    $recruitrate      = 0.04;
    $maxperboat       = 1;
    $tech             = 1;
    $startpop         = 400;
    $carryingcapacity = 1000;
    $lossrate         = 1;
    $traineerate      = 3;
    $popn             = $startpop;
    $extinctpoint     = $startpop / 10;

    if (!empty($rounds)) {
      $year = 1;
      foreach ($rounds as $round) {
        if (intval($round)) {
          $cpue = $popn / 1000;
          if ($cpue > $maxperboat) {
            $cpue = $maxperboat;
          }
          $harvest = $tech * ($round * $cpue);
          $lost    = $lossrate * $harvest;
          $spawn   = $popn * ($recruitrate);
          $popn    = $popn + $spawn - $harvest - $lost;
          if ($popn > $carryingcapacity) {
            $popn = $carryingcapacity;
          }
          $trainees = $harvest * $traineerate;
          if ($popn <= $extinctpoint) {
            $table .= '<tr><td colspan="6"><strong>OOPPS!!! You killed them all!</strong></td></tr>';
          }
          else {
            $table .= '<tr>';
            $table .= '<td>' . $year . '</td>';
            $table .= '<td>' . $round . '</td>';
            $table .= '<td>' . round($harvest) . '</td>';
            $table .= '<td>' . round($lost) . '</td>';
            $table .= '<td>' . round($cpue, 4) . '</td>';
            $table .= '<td>' . round($trainees) . '</td>';
            $table .= '</tr>';

          }

          $year++;
        }
      }
    }
    else {
      $table .= '<tr><td colspan="6">No rounds.</td></tr>';
    }

    $table .= '</tbody></table>';

    return $table;
  }

  /**
   * Function to output the table based on the inputs of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The updated 'result' form structure.
   */
  public function calculateResult(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $boats = $form_state->getValue('boats');
    $new_game = $form_state->getValue('new_game');

    $session = $this->stack->getSession();
    $rounds = $session->get('boats');
    $rounds[] = $boats;

    $session->set('boats', $rounds);
    $output = $this->generateTable($rounds);

    // Reset game.
    if ($new_game) {
      $output = 'Game restarted.';
      $session->set('boats', []);
    }

    $response->addCommand(new ReplaceCommand('#edit-result', '<div id="edit-result">' . $output . '</div>'));

    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
