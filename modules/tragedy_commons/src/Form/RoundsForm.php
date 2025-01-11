<?php

namespace Drupal\tragedy_commons\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tragedy_commons\TragedyCommonsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rounds form.
 */
class RoundsForm extends FormBase implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Our database repository service.
   *
   * @var \Drupal\tragedy_commons\TragedyCommonsRepository
   */
  protected $repository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

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
      $container->get('database'),
      $container->get('tragedy_commons.repository'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail'),
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
  public function __construct(Connection $database, TragedyCommonsRepository $repository, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager, TimeInterface $time_service) {
    $this->database = $database;
    $this->repository = $repository;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->timeService = $time_service;
  }

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

    $form_state->set('request', $request);

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
    $rounds_with_names = explode(',', $form_state->getValue('rounds'));
    $request = $form_state->get('request');

    // Get the previous round number.
    $query = $this->database->select('tragedy_commons_multi_round', 'r')
      ->condition('r.gid', $request->gid)
      ->condition('r.completed', 1)
      ->orderBy('r.updated', 'DESC')
      ->range(0, 1);
    $query->fields('r');
    $round = $query->execute()->fetchAll();
    $round_number = empty($round) ? 1 : ++$round[0]->round_number;

    $entry = [
      'gid' => $request->gid,
      'completed' => 1,
      'updated' => $this->timeService->getRequestTime(),
      'show_names' => is_array($rounds_with_names) ? (in_array($round_number, $rounds_with_names) ? 1 : 0) : ($round_number == $rounds_with_names ? 1 : 0),
      'round_number' => $round_number,
    ];
    $return = $this->repository->updateRoundsInGame($entry);

    // Enable names for any previous rounds if provided.
    if (is_array($rounds_with_names)) {
      foreach ($rounds_with_names as $round_number_to_update) {
        if (!empty($round_number_to_update)) {
          $this->database->update('tragedy_commons_multi_round')
            ->fields(['show_names' => 1])
            ->condition('gid', $request->gid)
            ->condition('round_number', $round_number_to_update)
            ->execute();
        }
      }
    }
    else {
      $this->database->update('tragedy_commons_multi_round')
        ->fields(['show_names' => 1])
        ->condition('gid', $request->gid)
        ->condition('round_number', $rounds_with_names)
        ->execute();
    }

    if ($return) {
      $this->messenger()
        ->addMessage($this->t('You have successfully completed round @round_number for the Tragedy of the Commons game for @gid', [
          '@round_number' => $round_number,
          '@gid' => $request->gid,
        ]));

      // Invalidate results page cache both players and game results page.
      Cache::invalidateTags(['tragedy_commons_results_' . $request->gid]);

      $form_state->setRedirect('tragedy_commons.gamespace_results', ['gid' => $request->gid]);

      // Send email to requester.
      $module = 'tragedy_commons';
      $to = $request->email;
      $from = $this->config('system.site')->get('mail');
      $language_code = $this->languageManager->getDefaultLanguage()->getId();
      $results_page = new Url('tragedy_commons.gamespace_results', ['gid' => $request->gid], ['absolute' => TRUE]);
      $params['results_page'] = $results_page->toString();

      $result = $this->mailManager->mail($module, 'game_played', $to, $language_code, $params, $from);
      if (!$result['result']) {
        $this->messenger()
          ->addMessage($this->t('There was a problem sending round played email to @email and it was not sent.', [
            '@email' => $to,
          ]), 'error');
      }
    }
    else {
      $this->messenger()
        ->addWarning($this->t('No entered uncompleted rounds found the Tragedy of the Commons game for @gid', [
          '@gid' => $request->gid,
        ]));
    }
  }

}
