<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Prisoners Dilemma Game 1 class.
 */
class PDGame2Form extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_pdgame2form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['header'] = [
      '#type' => 'markup',
      '#markup' => $this->t("<h2>Let's Play</h2>"),
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
      '#type' => 'submit',
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
    $your_choice = $form_state->getValue('your_choice');

    $partner_choice = '';
    if (rand(0, 10) > 5) {
      $partner_choice = "cooperate";
    }
    else {
      $partner_choice = "defect";
    }

    $inner_table = '';
    if ($your_choice == "" && $partner_choice == "cooperate") {
      $inner_table = '';
    }
    if ($your_choice == "cooperate" && $partner_choice == "cooperate") {
      $inner_table = '
<table border=5>
  <tr>
    <td id="ul" class="yellow">2,2</td>
    <td id="ur">10,0</td>
  </tr>
  <tr>
    <td id="ll">0,10</td>
    <td id="lr">5,5</td>
  </tr>
</table>';
    }
    if ($your_choice == "defect" && $partner_choice == "cooperate") {
      $inner_table = '
<table border=5>
  <tr>
    <td id="ul">2,2</td>
    <td id="ur" class="green">10,0</td>
  </tr>
  <tr>
    <td id="ll">0,10</td>
    <td id="lr">5,5</td>
  </tr>
</table>';
    }
    if ($your_choice == "cooperate" && $partner_choice == "defect") {
      $inner_table = '
<table border=5>
  <tr>
    <td id="ul">2,2</td>
    <td id="ur">10,0</td>
  </tr>
  <tr>
    <td id="ll" class="red">0,10</td>
    <td id="lr">5,5</td>
  </tr>
</table>';
    }
    if ($your_choice == "defect" && $partner_choice == "defect") {
      $inner_table = '
<table border=5>
  <tr>
    <td id="ul">2,2</td>
    <td id="ur">10,0</td>
  </tr>
  <tr>
    <td id="ll">0,10</td>
    <td id="lr" class="orange">5,5</td>
  </tr>
</table>';
    }

    $table_result = '
<table border="5">
  <tr>
    <td colspan=2 rowspan=2>&nbsp;</td>
    <td colspan=2 id="column">Column</td>
  </tr>
  <tr>
    <td>Cooperate</td>
    <td>Defect</td>
  </tr>
  <tr>
    <td class="vertical" id="row" rowspan=2>Row</td>
    <td class="vertical">Cooperate</td>
    <td colspan=2 rowspan=2>' . $inner_table . '</td>
  </tr>
  <tr>
    <td class="vertical">Defect</td>
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
