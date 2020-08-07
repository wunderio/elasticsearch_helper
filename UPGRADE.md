# Upgrade guide

This guild is intended to describe the process of upgrading
Elasticsearch Helper from version 5.x or 6.x to version 7.x.

The most notable changes in version 7.x:

1. Compatibility with Elasticsearch 6 and 7.
2. Multi-host configuration for use in cluster environment.
3. Support for event subscribers to react to most common Elasticsearch operations.
4. Support for object-oriented description of index settings and field mappings.
5. Simplified index plugin structure (no need to explicitly create index in `setup()` method).
5. Index plugins can define their own overall content reindex procedures in `reindex()` method.

Upgrade checklist:
1. [ ] Add `getMappingDefinition()` method to index plugins.
2. [ ] Run `drush updb`.
3. [ ] Export configuration for Elasticsearch Helper module.

## Index plugins

In Elasticsearch Helper 5.x and 6.x index plugins usually defined field mappings in `setup()`
method as arrays.

In Elasticsearch Helper 7.x `ElasticsearchIndexInterface` requires `public function getMappingDefinition()` method to
be present in all implementation classes. This method should return an instance of `MappindDefinition`
class which contains index field mappings described in an object oriented way.

Each field can be described with an instance of `FieldDefinition` class. It has the following methods:
* Nested fields can be added using `addProperty()` or `addProperties()` methods.
* Multi-fields can be added using `addMultiField()` method.
* Options can be added using `addOption()` or `addOptions()` methods.


Example:

```
  /**
   * {@inheritdoc}
   */
  public function getMappingDefinition(array $context = []) {
    $name_property = FieldDefinition::create('text')
      ->addOption('analyzer', 'english')
      ->addMultiField('keyword', FieldDefinition::create('keyword'));

    $user_property = FieldDefinition::create('object')
      ->addProperty('uid', FieldDefinition::create('integer'))
      ->addProperty('name', $name_property);

    return MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('keyword'))
      ->addProperty('user', $user_property);
  }
```

