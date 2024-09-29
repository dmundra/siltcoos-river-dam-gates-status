<?php

namespace Drupal\tragedy_commons;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Repository for database-related helper methods for Tragedy Commons.
 */
class TragedyCommonsRepository {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  /**
   * Save a request entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insert(array $entry) {
    try {
      $return_value = $this->connection->insert('tragedy_commons_multi')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value ?? NULL;
  }

  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function update(array $entry) {
    try {
      // Connection->update()...->execute() returns the number of rows updated.
      $count = $this->connection->update('tragedy_commons_multi')
        ->fields($entry)
        ->condition('gid', $entry['gid'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Update failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]), 'error');
    }
    return $count ?? 0;
  }

  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the game identifier 'gid' element of the
   *   entry to delete.
   *
   * @see Drupal\Core\Database\Connection::delete()
   */
  public function delete(array $entry) {
    $this->connection->delete('tragedy_commons_multi')
      ->condition('gid', $entry['gid'])
      ->execute();
  }

  /**
   * Read from the database using a filter array.
   *
   * The standard function to perform reads for static queries is
   * Connection::query().
   *
   * Connection::query() uses an SQL query with placeholders and arguments as
   * parameters.
   *
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return array
   *   An object containing the loaded entries if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function load(array $entry = []) {
    // Read all the fields from the tragedy_commons_multi table.
    $select = $this->connection
      ->select('tragedy_commons_multi')
      // Add all the fields into our select query.
      ->fields('tragedy_commons_multi');

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }

    // Order by created desc.
    $select->orderBy('created', 'DESC');

    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Save a player entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $player
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insertPlayer(array $player) {
    try {
      $return_value = $this->connection->insert('tragedy_commons_multi_player')
        ->fields($player)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value ?? NULL;
  }

  /**
   * Read from the database using a filter array.
   *
   * The standard function to perform reads for static queries is
   * Connection::query().
   *
   * Connection::query() uses an SQL query with placeholders and arguments as
   * parameters.
   *
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return array
   *   An object containing the loaded entries if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function loadPlayer(array $entry = []) {
    // Read all the fields from the tragedy_commons_multi table.
    $select = $this->connection
      ->select('tragedy_commons_multi_player')
      // Add all the fields into our select query.
      ->fields('tragedy_commons_multi_player');

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }

    // Order by created desc.
    $select->orderBy('started', 'DESC');

    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Save a round entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $round
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insertRound(array $round) {
    try {
      $return_value = $this->connection->insert('tragedy_commons_multi_round')
        ->fields($round)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value ?? NULL;
  }

  /**
   * Read from the database using a filter array.
   *
   * The standard function to perform reads for static queries is
   * Connection::query().
   *
   * Connection::query() uses an SQL query with placeholders and arguments as
   * parameters.
   *
   * @param array $round
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return array
   *   An object containing the loaded entries if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function loadRound(array $round = []) {
    // Read all the fields from the tragedy_commons_multi table.
    $select = $this->connection
      ->select('tragedy_commons_multi_round')
      // Add all the fields into our select query.
      ->fields('tragedy_commons_multi_round');

    // Add each field and value as a condition to this query.
    foreach ($round as $field => $value) {
      $select->condition($field, $value);
    }

    // Order by created desc.
    $select->orderBy('started', 'DESC');

    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  /**
   * Update a round entry in the database.
   *
   * @param array $game
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function updateRoundsInGame(array $game) {
    try {
      // Connection->update()...->execute() returns the number of rows updated.
      $count = $this->connection->update('tragedy_commons_multi_round')
        ->fields($game)
        ->condition('gid', $game['gid'])
        ->condition('completed', 0)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Update failed. Message = %message, query= %query', [
        '%message' => $e->getMessage(),
        '%query' => $e->query_string,
      ]), 'error');
    }
    return $count ?? 0;
  }

}
