<?php

namespace Drupal\metatag\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag\MetatagTagPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Advanced widget for metatag field.
 *
 * @FieldWidget(
 *   id = "metatag_firehose",
 *   label = @Translation("Advanced meta tags form"),
 *   field_types = {
 *     "metatag"
 *   }
 * )
 */
class MetatagFirehose extends WidgetBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Instance of MetatagManager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * Instance of MetatagTagPluginManager service.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $metatagPluginManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('metatag.manager'),
      $container->get('plugin.manager.metatag.tag'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'sidebar' => TRUE,
      'use_details' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['sidebar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Place field in sidebar'),
      '#default_value' => $this->getSetting('sidebar'),
      '#description' => $this->t('If checked, the field will be placed in the sidebar on entity forms.'),
    ];
    $element['use_details'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap the meta tags in a collapsed details container.'),
      '#default_value' => $this->getSetting('use_details'),
      '#description' => $this->t('If checked, the contents of the field will be placed inside a collapsed details container.'),
      '#states' => [
        'visible' => [
          'input[name$="[sidebar]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    if ($this->getSetting('sidebar')) {
      $summary['sidebar'] = $this->t('Use sidebar: Yes');
    }
    else {
      $summary['sidebar'] = $this->t('Use sidebar: No');

      if ($this->getSetting('use_details')) {
        $summary['use_details'] = $this->t('Use details container: Yes');
      }
      else {
        $summary['use_details'] = $this->t('Use details container: No');
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, MetatagManagerInterface $manager, MetatagTagPluginManager $plugin_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->metatagManager = $manager;
    $this->metatagPluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $default_tags = metatag_get_default_tags($items->getEntity());

    // Retrieve the values for each metatag from the serialized array.
    $values = [];
    if (!empty($item->value)) {
      $values = unserialize($item->value);
    }

    // Populate fields which have not been overridden in the entity.
    if (!empty($default_tags)) {
      foreach ($default_tags as $tag_id => $tag_value) {
        if (!isset($values[$tag_id]) && !empty($tag_value)) {
          $values[$tag_id] = $tag_value;
        }
      }
    }

    // Retrieve configuration settings.
    $settings = $this->configFactory->get('metatag.settings');
    $entity_type_groups = $settings->get('entity_type_groups');

    // Find the current entity type and bundle.
    $entity_type = $item->getEntity()->getentityTypeId();
    $entity_bundle = $item->getEntity()->bundle();

    // See if there are requested groups for this entity type and bundle.
    $groups = [];
    if (!empty($entity_type_groups[$entity_type]) && !empty($entity_type_groups[$entity_type][$entity_bundle])) {
      $groups = $entity_type_groups[$entity_type][$entity_bundle];
    }

    // Limit the form to requested groups, if any.
    if (!empty($groups)) {
      $element = $this->metatagManager->form($values, $element, [$entity_type], $groups);
    }

    // Otherwise, display all groups.
    else {
      $element = $this->metatagManager->form($values, $element, [$entity_type]);
    }

    // If the "sidebar" option was checked on the field widget, put the form
    // element into the form's "advanced" group. Otherwise, let it default to
    // the main field area.
    $sidebar = $this->getSetting('sidebar');
    if ($sidebar) {
      $element['#group'] = 'advanced';
    }

    // If the "use_details" option was not checked on the field widget, put the
    // form element into normal container. Otherwise, let it default to a
    // detail's container.
    $details = $this->getSetting('use_details');
    if (!$sidebar && !$details) {
      $element['#type'] = 'container';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Flatten the values array to remove the groups and then serialize all the
    // meta tags into one value for storage.
    $tag_manager = $this->metatagPluginManager;
    foreach ($values as &$value) {
      $flattened_value = [];
      foreach ($value as $group) {
        // Exclude the "original delta" value.
        if (is_array($group)) {
          foreach ($group as $tag_id => $tag_value) {
            $tag = $tag_manager->createInstance($tag_id);
            $tag->setValue($tag_value);
            if (!empty($tag->value())) {
              $flattened_value[$tag_id] = $tag->value();
            }
          }
        }
      }
      $value = serialize($flattened_value);
    }

    return $values;
  }

}
