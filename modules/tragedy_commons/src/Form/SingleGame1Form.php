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
 * Learning to Make Optimal Use of A Private Farm game form.
 */
class SingleGame1Form extends FormBase implements FormInterface, ContainerInjectionInterface {

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
    return 'tragedy_commons_singlegame1form';
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
    $rounds = $session->get('rounds');
    $form['result_fieldset']['result'] = [
      '#type' => 'item',
      '#markup' => $this->generateTable($rounds ?? []),
    ];

    $form['cows'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter the number of cows you want to place on the commons this round:'),
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
    $table = '<table border=5><thead><tr><th>No. of cows</th><th>Profit Per Cow (Sale Price - $100)</th><th>Total Profit/Loss (try to maximize!)</th></tr></thead><tbody>';

    if (!empty($rounds)) {
      foreach ($rounds as $round) {
        if (intval($round)) {
          $avg_rev = 500 - (7 * $round);
          if ($avg_rev <= 0) {
            $avg_rev = 0;
          }
          $profit = $round * ($avg_rev - 100);
          $table .= '<tr>';
          $table .= '<td>' . $round . '</td>';
          $table .= '<td>' . $avg_rev - 100 . '</td>';
          $table .= '<td>' . $profit . '</td>';
          $table .= '</tr>';

          if ($round == 29) {
            $count = count($rounds);
            $table .= '<tr><td colspan="3"><strong>Nice job! You maximized your profits in ' . $count . ' rounds!</strong></td></tr>';
          }
        }
      }
    }
    else {
      $table .= '<tr><td colspan="3">No rounds.</td></tr>';
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
    $cows = $form_state->getValue('cows');
    $new_game = $form_state->getValue('new_game');

    $session = $this->stack->getSession();
    $rounds = $session->get('rounds');
    $rounds[] = $cows;

    $session->set('rounds', $rounds);
    $output = $this->generateTable($rounds);

    // Reset game.
    if ($new_game) {
      $output = 'Game restarted.';
      $session->set('rounds', []);
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
