<?php

namespace Drupal\srdgs_custom\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Views;

/**
 * Hook implementations for the SRDGS Custom module.
 */
class SrdgsCustomHooks {

  /**
   * Implements hook_form_FORM_ID_alter() for node_dam_gates_status_form.
   */
  #[Hook('form_node_dam_gates_status_form_alter')]
  public function formNodeDamGatesStatusFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Create form.
    $current_dam_gate_status_view = Views::getViewResult('dam_gates_status');

    $field_status = 'field_status';
    $status = $current_dam_gate_status_view[0]->_entity->get($field_status)->getValue();
    $form[$field_status]['widget']['#default_value'] = $status[0]['value'];

    $field_expected_duration = 'field_expected_duration';
    $expected_duration = $current_dam_gate_status_view[0]->_entity->get($field_expected_duration)->getValue();
    $form[$field_expected_duration]['widget']['#default_value'] = $expected_duration[0]['value'];
  }

}
