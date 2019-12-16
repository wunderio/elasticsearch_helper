<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "date",
 *   label = @Translation("Date"),
 *   field_types = {
 *     "datetime",
 *     "timestamp",
 *     "created",
 *     "changed"
 *   }
 * )
 */
class DateNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * DateNormalizer constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function getFieldItemValue(FieldItemInterface $item, array $context = []) {
    $value = NULL;

    if ($item instanceof DateTimeItemInterface) {
      /** @var \DateTime $date */
      $date = $item->date;
      $value = $date->getTimestamp();
    }
    else {
      $value = $item->value;
    }

    if ($this->configuration['storage_type'] == 'date') {
      $format = $this->configuration['format_custom'];
      $timezone = DateTimeItemInterface::STORAGE_TIMEZONE;
      $value = $this->dateFormatter->format($value, 'custom', $format, $timezone);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('date');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'storage_type' => 'timestamp',
      'format_custom' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [
      'storage_type' => [
        '#type' => 'select',
        '#title' => t('Storage type'),
        '#options' => [
          'timestamp' => t('Timestamp'),
          'date' => t('Date (formatted)'),
        ],
        '#default_value' => $this->configuration['storage_type'],
      ],
      'format_custom' => [
        '#type' => 'textfield',
        '#title' => t('Custom format'),
        '#default_value' => $this->configuration['format_custom'],
        '#attributes' => [
          'data-drupal-date-formatter' => 'source',
        ],
        '#field_suffix' => ' <small class="js-hide" data-drupal-date-formatter="preview">' . t('Displayed as %date_format', ['%date_format' => '']) . '</small>',
        '#states' => [
          'visible' => [
            ':input[name*="[storage_type]"]' => [
              'value' => 'date',
            ],
          ],
        ],
      ],
    ];

    $form['#attached']['drupalSettings']['dateFormats'] = $this->dateFormatter->getSampleDateFormats();
    $form['#attached']['library'][] = 'system/drupal.system.date';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $storage_type = $form_state->getValue('storage_type');
    $this->configuration['storage_type'] = $storage_type;

    if ($format_custom = $storage_type == 'date' ? $form_state->getValue('format_custom') : NULL) {
      $this->configuration['format_custom'] = $format_custom;
    }
    else {
      unset($this->configuration['format_custom']);
    }
  }

}
