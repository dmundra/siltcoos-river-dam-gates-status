<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tragedy_commons\TragedyCommonsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Requests approve form class for Tragedy of Commons game.
 */
class RequestsApproveForm extends FormBase implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Our database repository service.
   *
   * @var \Drupal\tragedy_commons\TragedyCommonsRepository
   */
  protected $repository;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
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
  public function __construct(TragedyCommonsRepository $repository, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, TimeInterface $time_service) {
    $this->repository = $repository;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->timeService = $time_service;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_requestsapproveform';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $request = NULL) {
    $form = [];

    $form_state->set('request', $request);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Approve Request'),
      '#prefix' => $this->t('To approve the following request, click this'),
      '#suffix' => $this->t('button.'),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = $form_state->get('request');
    $entry = [
      'gid' => $request->gid,
      'status' => TRAGEDY_COMMONS_ACCEPTED,
      'updated' => $this->timeService->getRequestTime(),
    ];
    $return = $this->repository->update($entry);
    if ($return) {
      $this->messenger()->addMessage($this->t('You have successfully approved the following request to play the Tragedy of the Commons game for @gid', [
        '@gid' => $request->gid,
      ]));

      // Send email to requester.
      $module = 'tragedy_commons';
      $to = $request->email;
      $from = $this->config('system.site')->get('mail');
      $params = [
        'gid' => $request->gid,
        'test_gid' => $request->gid + 1,
        'firstname' => $request->firstname,
        'lastname' => $request->lastname,
      ];
      $language_code = $this->languageManager->getDefaultLanguage()->getId();

      $result = $this->mailManager->mail($module, 'request_approved', $to, $language_code, $params, $from);
      if (!$result['result']) {
        $this->messenger()
          ->addMessage($this->t('There was a problem sending request approved email to @email and it was not sent.', [
            '@email' => $to,
          ]), 'error');
      }
    }
  }

}
