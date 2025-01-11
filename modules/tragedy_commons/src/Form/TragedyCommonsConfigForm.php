<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the module's config/settings admin page.
 */
class TragedyCommonsConfigForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [
      'tragedy_commons.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('tragedy_commons.settings');

    $form['request_approved_email'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Request approved email'),
      '#default_value' => $config->get('request_approved_email'),
      '#description' => $this->t('Ensure the email keeps the placeholders: @firstname, @lastname, @hbase (site base URL), @test_password (test game password), @test_gid (test game ID), @password (main game password), @gid (game ID).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('tragedy_commons.settings');
    $config->set('request_approved_email', $form_state->getValue('request_approved_email'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
