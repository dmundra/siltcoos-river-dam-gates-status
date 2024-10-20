<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tragedy_commons\TragedyCommonsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Game password entry form.
 */
class MultiPlayerForm extends FormBase implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Our database repository service.
   *
   * @var \Drupal\tragedy_commons\TragedyCommonsRepository
   */
  protected $repository;

  /**
   * {@inheritDoc}
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the string_translation service.
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('tragedy_commons.repository'),
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
  public function __construct(TragedyCommonsRepository $repository) {
    $this->repository = $repository;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_multiplayerform';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Enter Password'),
      '#required' => TRUE,
      '#description' => $this->t('You will then be sent to a page to fill
in your name and start the game. <em>If you were returned to this page after
entering a password, you have entered an invalid password. Please try again.</em>'),
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
    $password = $form_state->getValue('password');
    $split = explode('-', $password);

    $last_name = $split[0];
    $gid = $split[1] ?? FALSE;
    $valid = FALSE;

    if ($gid) {
      $entries = $this->repository->load(['gid' => $gid]);
      if (!empty($entries)) {
        foreach ($entries as $entry) {
          if (strtolower($last_name) === strtolower($entry->lastname)) {
            $form_state->setRedirect('tragedy_commons.gamespace', ['gid' => $gid]);
            $valid = TRUE;
          }
        }
      }
    }

    if (!$valid) {
      $this->messenger()->addError($this->t('Invalid password.'));
    }
  }

}
