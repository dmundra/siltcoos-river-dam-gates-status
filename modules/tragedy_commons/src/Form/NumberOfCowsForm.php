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
 * Number of cows form.
 */
class NumberOfCowsForm extends FormBase implements FormInterface, ContainerInjectionInterface {

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
    return 'tragedy_commons_numberofcowsform';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $request = NULL, $player = NULL) {
    $form = [];

    $form_state->set('request', $request);
    $form_state->set('player', $player);
    $form_state->set('test', $request->test);

    $form['cows'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter the number of cows you want to place on the commons this round:'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 100,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#prefix' => $this->t('and click this'),
      '#suffix' => $this->t('button'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = $form_state->get('request');
    $player = $form_state->get('player');
    $test = $form_state->get('test');
    $gid = $request->gid;
    $pid = $player->pid;
    $round = [
      'gid' => $gid,
      'pid' => $pid,
      'cows' => $form_state->getValue('cows'),
      'started' => $this->timeService->getRequestTime(),
      'test' => $test,
      'completed' => 0,
      'updated' => $this->timeService->getRequestTime(),
    ];
    $return = $this->repository->insertRound($round);
    if ($return) {
      $this->messenger()->addMessage($this->t('Thanks for playing another round of the game.'));
      $form_state->setRedirect('tragedy_commons.gamespace_wait', ['gid' => $gid, 'pid' => $pid, 'rid' => $return]);
    }
  }

}
