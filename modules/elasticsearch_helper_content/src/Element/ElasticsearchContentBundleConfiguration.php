<?php

namespace Drupal\elasticsearch_helper_content\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Defines an element for Elasticsearch content configuration for a single field.
 *
 * @FormElement("elasticsearch_content_bundle_configuration")
 */
class ElasticsearchContentBundleConfiguration extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [
        [$class, 'processElasticsearchBundleConfiguration'],
      ],
    ];
  }

  /**
   * Process handler for the language_configuration form element.
   */
  public static function processElasticsearchBundleConfiguration(&$element, FormStateInterface $form_state, &$form) {
    $options = isset($element['#options']) ? $element['#options'] : [];
    // Avoid validation failure since we are moving the '#options' key in the
    // nested 'language' select element.
    unset($element['#options']);
    /** @var \Drupal\elasticsearch_helper_content\Entity\ElasticsearchContentSettings $default_config */
    $default_config = $element['#default_value'];
    $element['indexcode'] = [
      '#type' => 'select',
      '#title' => t('Index option'),
      '#options' => $options + static::getDefaultOptions(),
      '#description' => t('Index option selection'),
      '#default_value' => ($default_config != NULL) ? $default_config->getDefaultLangcode() : NULL,
    ];

    // Per-field options.
    $bundleFields_labels =[];
    $entity_type_id = $element['#entity_information']['entity_type'];
    $bundle = $element['#entity_information']['bundle'];
    foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
      if ($field_definition->getFieldStorageDefinition()->isBaseField() == FALSE) {
        $bundleFields_types[] = $field_definition->getType();
        $bundleFields_labels[] = $field_definition->getLabel();
      }
    }

    $element['per_field_options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Per-field options'),
      '#options' => $bundleFields_labels,
      '#description' => t('Select fileds to be indexed'),
      '#default_value' => [],
    ];

    // Add the entity type and bundle information to the form if they are set.
    // They will be used, in the submit handler, to generate the names of the
    // configuration entities that will store the settings and are a way to uniquely
    // identify the entity.
//    $elasticsearch = $form_state->get('elasticsearch') ?: [];
//    $elasticsearch += [
//      $element['#name'] => [
//        'entity_type' => $element['#entity_information']['entity_type'],
//        'bundle' => $element['#entity_information']['bundle'],
//      ],
//    ];
//    $form_state->set('elasticsearch', $elasticsearch);

    // Do not add the submit callback for the elasticsearch content settings page,
    // which is handled separately.
//    if ($form['#form_id'] != 'elasticsearch_helper_content_settings_form') {
//      // Determine where to attach the elasticsearch_configuration element submit
//      // handler.
//      // @todo Form API: Allow form widgets/sections to declare #submit
//      //   handlers.
//      $submit_name = isset($form['actions']['save_continue']) ? 'save_continue' : 'submit';
//      if (isset($form['actions'][$submit_name]['#submit']) && array_search('elasticsearch_configuration_element_submit', $form['actions'][$submit_name]['#submit']) === FALSE) {
//        $form['actions'][$submit_name]['#submit'][] = 'elasticsearch_configuration_element_submit';
//      }
//      elseif (array_search('elasticsearch_configuration_element_submit', $form['#submit']) === FALSE) {
//        $form['#submit'][] = 'elasticsearch_configuration_element_submit';
//      }
//    }
    return $element;
  }

  /**
   * Returns the default options for the language configuration form element.
   *
   * @return array
   *   An array containing the default options.
   */
  protected static function getDefaultOptions() {
    $elasticsearch_options = [
      'none' => t('Not indexed at all'),
      'content' => t('Content-only indexing'),
      'per_field' => t("Per-field indexing"),
    ];

    return $elasticsearch_options;
  }

}
