<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Rounds form.
 */
class RoundsForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_roundsform';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $request = NULL) {
    $form = [];

    $form['rounds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rounds for which you want names printed:'),
      '#description' => $this->t('<em>(leave blank and simply click submit
if you do not want names printed for any rounds)</em>'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
