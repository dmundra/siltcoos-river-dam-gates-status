<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Prisoners Dilemma Game 1 class.
 */
class PDGame1Form extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_pdgame1form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['strategy_partner'] = [
      '#type' => 'radios',
      '#title' => $this->t('Strategy partner'),
      '#default_value' => $form_state->getValue('strategy_partner'),
      '#options' => [
        'random' => $this->t('<i>Random</i> strategy partner'),
        'rational' => $this->t('<i>Rational</i> strategy partner'),
        'cooperative' => $this->t('<i>Cooperative</i> strategy partner'),
      ],
      '#required' => TRUE,
    ];

    $form['your_choice'] = [
      '#type' => 'radios',
      '#title' => $this->t('Your choice'),
      '#default_value' => $form_state->getValue('your_choice'),
      '#options' => [
        'cooperate' => $this->t('Cooperate'),
        'defect' => $this->t('Defect'),
      ],
      '#required' => TRUE,
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

    $form['result_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Results'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['result_fieldset']['result'] = [
      '#type' => 'item',
      '#markup' => '',
    ];

    return $form;
  }

  /**
   * Function to output the table based on the inputs of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated 'result' form structure.
   */
  public function calculateResult(array &$form, FormStateInterface $form_state) {
    $strategy_partner = $form_state->getValue('strategy_partner');
    $your_choice = $form_state->getValue('your_choice');
    $text = "<b><i>Possible payoffs</i></b>";

    if ($your_choice != NULL) {
      $text = "<b><i>RESULTS</i></b>";
    }

    $partner_choice = '';
    if ($strategy_partner == "random" && rand(0, 10) > 5) {
      $partner_choice = "cooperate";
    }
    else {
      $partner_choice = "defect";
    }
    if ($strategy_partner == "rational") {
      $partner_choice = "defect";
    }
    if ($strategy_partner == "cooperative") {
      $partner_choice = "cooperate";
    }

    $cell_a = '';
    $cell_b = '';
    $cell_c = '';
    $cell_d = '';
    $you_cooperate = '';
    $you_defect = '';
    $partner_cooperate = '';
    $partner_defect = '';
    if ($your_choice == "cooperate" && $partner_choice == "cooperate") {
      $cell_a = "yellow";
      $you_cooperate = "cyan";
      $partner_cooperate = "cyan";
    }
    if ($your_choice == "defect" && $partner_choice == "cooperate") {
      $cell_b = "green";
      $you_defect = "pink";
      $partner_cooperate = "cyan";
    }
    if ($your_choice == "cooperate" && $partner_choice == "defect") {
      $cell_c = "red";
      $you_cooperate = "cyan";
      $partner_defect = "pink";
    }
    if ($your_choice == "defect" && $partner_choice == "defect") {
      $cell_d = "orange";
      $you_defect = "pink";
      $partner_defect = "pink";
    }

    $table_result = '
    <table border=5>
      <tr>
        <td class="result" colspan=2 rowspan=2>' . $text . '</td>
        <td class="you" colspan=2 id="column">YOU</td>
      </tr>
      <tr>
        <td class="holdout ' . $you_cooperate . '">Hold out <br> ("Cooperate" with partner)</td>
        <td class="confess ' . $you_defect . '">Confess <br> ("Defect" from partner)</td>
      </tr>
      <tr>
        <td class="partner" id="row" rowspan=2>PARTNER</td>
        <td class="holdout ' . $partner_cooperate . '">Hold out <br> ("Cooperate" with partner)</td>
        <td colspan=2 rowspan=2>
          <table border=1>
            <tr>
              <td class="inner ' . $cell_a . '"><p class=right>Jail term: 1 year</p><br><br><br><p class=left>Jail term: 1 year</p></td>
              <td class="inner ' . $cell_b . '"><p class=right>Jail term: 0 years</p><br><br><br><p class=left>Jail term: 4 years</p></td>
            </tr>
            <tr>
              <td class="inner ' . $cell_c . '"><p class=right>Jail term: 4 years</p><br><br><br><p class=left>Jail term: 0 years</p></td>
              <td class="inner ' . $cell_d . '"><p class=right>Jail term: 2 years</p><br><br><br><p class=left>Jail term: 2 years</p></td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td class="confess ' . $partner_defect . '">Confess <br> ("Defect" from partner)</td>
      </tr>
    </table>';

    return [
      '#markup' => '<div id="edit-result">' . $table_result . '</div>',
      '#allowed_tags' => [
        'div',
        'table',
        'tr',
        'td',
        'p',
        'br',
        'b',
        'i',
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
