<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tragedy_commons\TragedyCommonsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Start page form.
 */
class StartPageForm extends FormBase implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Our database repository service.
   *
   * @var \Drupal\tragedy_commons\TragedyCommonsRepository
   */
  protected $repository;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * {@inheritDoc}
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the string_translation service.
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('tragedy_commons.repository'),
      $container->get('datetime.time')
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
  public function __construct(TragedyCommonsRepository $repository, TimeInterface $time_service) {
    $this->repository = $repository;
    $this->timeService = $time_service;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_startpageform';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $request = NULL) {
    $form = [];

    $form_state->set('request', $request);
    $form_state->set('test', $request->test);

    $form['introduction'] = [
      '#markup' => $this->t('To start to play the <strong><em>Tragedy of the Commons Game for @firstname @lastname (:gid)</em></strong>, fill in the following form and then click on the Submit button at the bottom of the form.', [
        '@firstname' => $request->firstname,
        '@lastname' => $request->lastname,
        ':gid' => $request->gid,
      ]),
    ];

    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
    ];

    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#prefix' => $this->t('Verify the information you have entered and then click the submit button:'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = $form_state->get('request');
    $test = $form_state->get('test');
    $gid = $request->gid;
    $player = [
      'firstname' => $form_state->getValue('firstname'),
      'lastname' => $form_state->getValue('lastname'),
    ];
    $return = FALSE;
    // Load existing player.
    $existing_players = $this->repository->loadPlayer([
      'gid' => $gid,
      'firstname' => $form_state->getValue('firstname'),
      'lastname' => $form_state->getValue('lastname'),
    ]);
    if (!empty($existing_players)) {
      foreach ($existing_players as $existing_player) {
        $return = $existing_player->pid;
      }
    }
    // Create new player.
    else {
      $player = [
        'gid' => $gid,
        'firstname' => $form_state->getValue('firstname'),
        'lastname' => $form_state->getValue('lastname'),
        'started' => $this->timeService->getRequestTime(),
        'test' => $test,
      ];
      $return = $this->repository->insertPlayer($player);
    }
    if ($return) {
      $this->messenger()->addMessage($this->t('Welcome @firstname @lastname!', [
        '@firstname' => $player['firstname'],
        '@lastname' => $player['lastname'],
      ]));
      $form_state->setRedirect('tragedy_commons.gamespace_player', ['gid' => $gid, 'pid' => $return]);
    }
  }

}
