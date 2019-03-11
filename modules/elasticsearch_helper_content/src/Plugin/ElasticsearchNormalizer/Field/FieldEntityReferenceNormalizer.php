<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_entity_reference",
 *   label = @Translation("Entity reference (ID, label)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FieldEntityReferenceNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   *
   * @param $object \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   */
  public function normalize($object, array $context = []) {
    $result = [];

    try {
      $cardinality = $this->getCardinality($object);

      foreach ($object->referencedEntities() as $entity) {
        $value = $this->getEntityValues($entity);

        if ($cardinality === 1) {
          return $value;
        }

        // Do not pass empty strings.
        if ($value !== '') {
          $result[] = $value;
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
   *
   * @return array
   */
  protected function getEntityValues(EntityInterface $entity) {
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
