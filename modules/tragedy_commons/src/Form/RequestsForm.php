<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\EmailValidator;
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
 * Requests form class for Tragedy of Commons game.
 */
class RequestsForm extends FormBase implements FormInterface, ContainerInjectionInterface {

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
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidator
   */
  protected $emailValidator;

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
      $container->get('email.validator'),
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
  public function __construct(TragedyCommonsRepository $repository, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, EmailValidator $email_validator, TimeInterface $time_service) {
    $this->repository = $repository;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->emailValidator = $email_validator;
    $this->timeService = $time_service;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'tragedy_commons_requestsform';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['introduction'] = [
      '#markup' => $this->t('If you would like to use the Tragedy of the
      Commons Game for your class, please submit the following request form.
      Within a week, you will receive an email response informing you whether
      your request has been approved along with instructions on how to manage
      playing of the game by a classroom of 25 students or less.'),
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

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#description' => $this->t("Make sure you give a full email address
      including name and domain, e.g., username@university.edu - if you don't,
      the program will NOT work)."),
    ];

    $form['institution'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Educational Institution at which game will be
       played (University, High School, etc.)'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please describe below the purposes for which
      and dates on which you will use the game'),
      '#default_value' => 'Purpose of use of game:

Dates game will be played:

Important note: ****Please do not request that a game be created for more than 3 months into the future. Games that are created but not used within three months will be deleted automatically and you will need to come back to this page and resubmit your request.****',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Request'),
    ];

    $form['credits'] = [
      '#markup' => $this->t('<p>This game was created by:<br/>
Ronald Mitchell<br/>
Department of Political Science<br/>
University of Oregon<br/>
Eugene, OR 97503-1284<br/>
Tel: 541-346-4880<br/>
Email: <a href="mailto:rmitchel@uoregon.edu">rmitchel@uoregon.edu</a><br/>
Web: <a href="https://ronaldbmitchell.com/">https://ronaldbmitchell.com/</a><br/>
&copy; Ronald Mitchell</p>'),
    ];

    $form['#attached']['library'][] = 'tragedy_commons/commons';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If the email is not valid, set error on the form.
    if (!$this->emailValidator->isValid($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('The email is not valid.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entry = [
      'firstname' => $form_state->getValue('firstname'),
      'lastname' => $form_state->getValue('lastname'),
      'email' => $form_state->getValue('email'),
      'institution' => $form_state->getValue('institution'),
      'description' => $form_state->getValue('description'),
      'status' => TRAGEDY_COMMONS_ACCEPTED,
      'created' => $this->timeService->getRequestTime(),
      'updated' => $this->timeService->getRequestTime(),
      'test' => 0,
    ];
    // Create test entry.
    $test_entry = $entry;
    $test_entry['test'] = TRUE;
    $test_return = $this->repository->insert($test_entry);
    // Create main entry.
    $return = $this->repository->insert($entry);
    if ($return && $test_return) {
      $this->messenger()->addMessage($this->t('Request for @firstname @lastname @email (@institution) received and approved.<br/>Purpose: @description', [
        '@firstname' => $entry['firstname'],
        '@lastname' => $entry['lastname'],
        '@email' => $entry['email'],
        '@institution' => $entry['institution'],
        '@description' => $entry['description'],
      ]));

      // Send email to requester.
      $module = 'tragedy_commons';
      $to = $entry['email'];
      $from = $this->config('system.site')->get('mail');
      $params = $form_state->getValues();
      $params['gid'] = $return;
      $params['test_gid'] = $test_return;
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
