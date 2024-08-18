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
 * Requests disapprove form class for Tragedy of Commons game.
 */
class RequestsDisapproveForm extends FormBase implements FormInterface, ContainerInjectionInterface {

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
    return 'tragedy_commons_requestsdisapproveform';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $request = NULL) {
    $form = [];

    $form_state->set('request', $request);

    $message_name = [
      ':first_name' => $request->firstname,
      ':last_name' => $request->lastname,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('DISapprove Request'),
      '#prefix' => $this->t('To disapprove this request, change the response information and then click this'),
      '#suffix' => $this->t('button.'),
    ];

    $form['disapprove_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Response'),
      '#default_value' => $this->t('Dear :first_name :last_name,

Thank you for your interest in my Tragedy of the Commons Game.
Unfortunately, I cannot grant your request to play the Tragedy of the
Commons game.  I restrict use of the game to academic institutions and
other educational enterprises, except in special circumstances.
Maintaining the game requires considerable time that I am willing to
devote for educational but not for other purposes.

Sincerely,
Ron Mitchell', $message_name),
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
      'status' => TRAGEDY_COMMONS_REJECTED,
      'updated' => $this->timeService->getRequestTime(),
    ];
    $return = $this->repository->update($entry);
    if ($return) {
      $this->messenger()->addMessage($this->t('You have successfully disapproved the following request to play the Tragedy of the Commons game for @gid', [
        '@gid' => $request->gid,
      ]));

      // Send email to requester.
      $module = 'tragedy_commons';
      $to = $request->email;
      $from = $this->config('system.site')->get('mail');
      $params = [
        'reason' => $form_state->getValue('disapprove_message'),
      ];
      $language_code = $this->languageManager->getDefaultLanguage()->getId();

      $result = $this->mailManager->mail($module, 'request_disapproved', $to, $language_code, $params, $from);
      if ($result['result']) {
        $this->messenger()->addMessage($this->t('Request disapproved email sent to @email.', [
          '@email' => $to,
        ]));
      }
      else {
        $this->messenger()->addMessage($this->t('There was a problem sending request disapproved email to @email and it was not sent.', [
          '@email' => $to,
        ]), 'error');
      }
    }
  }

}
