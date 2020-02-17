<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference (ID, label)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   */
  public function normalize($entity, $field, array $context = []) {
    $result = [];

    try {
      if ($field) {
        $langcode = $entity->language()->getId();
        $cardinality = $this->getCardinality($field);

        foreach ($field as $field_item) {
          $value = NULL;

          if ($referenced_entity = $field_item->entity) {
            if ($referenced_entity instanceof TranslatableInterface) {
              $referenced_entity = \Drupal::service('entity.repository')->getTranslationFromContext($referenced_entity, $langcode);
            }

            $value = $this->getEntityValues($referenced_entity, $field_item, $context);
          }

          if ($cardinality === 1) {
            return $value;
          }

          // Do not pass empty strings.
          if ($value !== '') {
            $result[] = $value;
          }
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return $result;
  }

  /**
   * Returns values of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   * @param array $context
   *
   * @return array
   */
  protected function getEntityValues(EntityInterface $entity, FieldItemInterface $field_item, array $context = []) {
    return [
      'id' => $entity->id(),
      'label' => $entity->label(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $label_definition = ElasticsearchDataTypeDefinition::create('text')
      ->addField('keyword', ElasticsearchDataTypeDefinition::create('keyword'));

    return ElasticsearchDataTypeDefinition::create('object')
      ->addProperty('id', ElasticsearchDataTypeDefinition::create('integer'))
      ->addProperty('label', $label_definition);
  }

}
