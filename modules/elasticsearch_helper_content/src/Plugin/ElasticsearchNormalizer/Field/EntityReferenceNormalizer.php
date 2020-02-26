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
        $cardinality = $this->getCardinality($field);
        $langcode = $entity->language()->getId();
        $context['langcode'] = $langcode;

        foreach ($field as $field_item) {
          $value = NULL;

          if ($referenced_entity = $field_item->entity) {
            if ($referenced_entity instanceof TranslatableInterface) {
              $referenced_entity = \Drupal::service('entity.repository')->getTranslationFromContext($referenced_entity, $langcode);
            }

            $value = $this->getReferencedEntityValues($referenced_entity, $field_item, $entity, $context);
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
   * Returns values of the referenced entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $referenced_entity
   *   Referenced entity from the field item.
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   Field item from original entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Original entity.
   * @param array $context
   *
   * @return array
   */
  protected function getReferencedEntityValues(EntityInterface $referenced_entity, FieldItemInterface $field_item, EntityInterface $entity, array $context = []) {
    return [
      'id' => $referenced_entity->id(),
      'label' => $referenced_entity->label(),
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
