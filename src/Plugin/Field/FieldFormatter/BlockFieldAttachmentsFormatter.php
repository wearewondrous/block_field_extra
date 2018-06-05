<?php

namespace Drupal\block_field_extra\Plugin\Field\FieldFormatter;

use Drupal\block_field\Plugin\Field\FieldFormatter\BlockFieldFormatter;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'block_field_attachments' formatter.
 *
 * @FieldFormatter(
 *   id = "block_field_attachments",
 *   label = @Translation("Block field with attachments"),
 *   field_types = {
 *     "block_field"
 *   }
 * )
 */
class BlockFieldAttachmentsFormatter extends BlockFieldFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $entity = $items->getEntity();
    foreach ($settings['field'] as $field_name) {
      if ($entity->hasField($field_name) && ($field_value = $this->getValue($entity, $field_name))) {
        $build = $this->getFieldBuild($entity, $field_name, $langcode);
        $block_field_attachments[$field_name] = $build ?: $field_value;
      }
    }

    if (!empty($block_field_attachments)) {
      $block_field_attachments['#context'] = [
        'field_definition' => $items->getFieldDefinition(),
        'langcode' => $langcode,
      ];

      //      // Inject the attachment as the field item settings.
      //      // This can be useful if you need this data on the field type plugin.
      //      foreach ($items as $item) {
      //        $settings = $item->settings;
      //        $settings['block_field_attachments'] = $block_field_attachments;
      //        $item->set('settings', $settings);
      //      }
    }

    // Let the parent to build the elements.
    // Calling the parent::viewElements will add by default the $attachment settings under
    // $element #configuration.
    // see $element[DELTA]['#configuration']['block_field_attachments'][FIELD_NAME]
    $elements = parent::viewElements($items, $langcode);

    if (isset($block_field_attachments)) {
      foreach ($elements as &$element) {
        // Let the block theme know about the attachment.
        // FYI, you can access this value on the block template.
        $element['block_field_attachments'] = $block_field_attachments;

        if (isset($element['content']['#view'])) {
          // Let the view know about the attachment.
          // FYI, you can access this value on the view template.
          $view = $element['content']['#view'];
          $view->block_field_attachments = $block_field_attachments;
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
        'field' => 'field_attachment',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Make sure if there's a view options to select.
    $options = $this->getFieldsList();
    $element['field'] = [
      '#title' => $this->t('Attachment field'),
      '#description' => $this->t('The selected fields will be injected into the block instance'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->getEntityProperties() + $options,
      '#default_value' => $this->getSetting('field'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // Todo implement the summary.
    return $summary;
  }

  /**
   * Gets the entity field build.
   *
   * @param $entity
   *   The entity object.
   * @param $field_name
   *   The field name.
   *
   * @return mixed
   */
  public function getFieldBuild($entity, $field_name, $langcode) {
    // Force the field item list to use the entity translation.
    $this->setFieldTranslation($entity, $field_name, $langcode);
    // Get the field build.
    $build = $entity->{$field_name}->view($this->viewMode);

    return $build;
  }

  /**
   * Updates the field item list parent entity value with the entity translation.
   *
   * @param $entity
   *   The entity object.
   * @param $field_name
   *   The field name.
   * @param $langcode
   *   The language code.
   *
   * TODO find why this is happening and remove this function if possible.
   */
  public function setFieldTranslation($entity, $field_name, $langcode) {
    if (!$entity->isTranslatable() || !$entity->hasTranslation($langcode) || !$entity->hasField($field_name)) {
      return;
    }
    $entity_translation = $entity->getTranslation($langcode);
    $field = $entity_translation->{$field_name};
    // The field parents needs to be set else the default langcode will be
    // loaded.
    $field->getParent()->setValue($entity_translation);
  }

  /**
   * Get the the entity property or field value.
   *
   * @param $entity
   *   The entity object.
   * @param $name
   *   The property or field name.
   *
   * @return mixed
   *   The value or FALSE.
   */
  public function getValue($entity, $name) {
    // Check if it's a field.
    if ($entity->hasField($name)) {
      $field_value = $entity->get($name)->getValue();
      // TODO Uggly solution, find a better way to get the value property key.
      $value_keys = ['value', 'target_id'];
      foreach ($value_keys as $key) {
        if (isset($field_value[0][$key])) {
          return $field_value[0][$key];
        }
      }
    }
    else {
      // Check for property.
      if (isset($entity->{$name})) {
        return $entity->{$name}->value;
      }
    }

    return FALSE;
  }

  /**
   * Gets the list of the host entity properties.
   *
   * @return array
   *   The list of available properties.
   */
  function getEntityProperties() {
    // TODO Get the properties dynamically.
    return ['id' => $this->t('Entity ID')];
  }

  /**
   * Gets the list fields from the host entity.
   *
   * @return array
   *   List of fields.
   */
  public function getFieldsList() {
    $fields = [];
    $entity_field_definitions = $this->getFieldDefinitions();
    $current_field = $this->fieldDefinition->getName();

    foreach ($entity_field_definitions as $entity_field_definition) {
      if ($entity_field_definition instanceof \Drupal\field\Entity\FieldConfig && $entity_field_definition->getName() != $current_field ) {
        $fields[$entity_field_definition->getName()] = $entity_field_definition->label();
      }
    }

    return $fields;
  }

  /**
   * Gets the field definitions of the host entity.
   *
   * @return array
   *   The field definitions.
   */
  public function getFieldDefinitions() {
    $field_definition = $this->fieldDefinition;
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_type = $field_definition->get('entity_type');
    $bundle = $field_definition->get('bundle');

    // Get the list fields from the host entity.
    $entity_field_definitions = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);

    return $entity_field_definitions;
  }

}
