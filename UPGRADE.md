# Upgrade guide

This guide is intended to describe the process of upgrading
Elasticsearch Helper from version 5.x or 6.x to version 7.x.

The most notable changes in version 7.x:

1. Compatibility with Elasticsearch 6 and 7.
2. Multi-host configuration for use in cluster environment.
3. Support for event subscribers to react to most common Elasticsearch operations.
4. Support for object-oriented description of index settings and field mappings.
5. Simplified index plugin structure (no need to explicitly create an index in `setup()` method).
5. Index plugins can define their own overall content reindex procedures in `reindex()` method.

Minimal upgrade checklist:
1. [ ] Add `getMappingDefinition()` method to index plugins to conform to the interface.
2. [ ] Revise the necessity of `setup()` method in index plugins. Fields and index settings are
already defined in `getMappingDefinition()` and `getIndexDefinition()` methods.
3. [ ] Run `drush updb` to update the configuration structure.
4. [ ] Export configuration of Elasticsearch Helper module.

## Changes in index plugins

### New methods

In Elasticsearch Helper 5.x and 6.x index plugins usually defined field mappings in `setup()`
method as arrays.

In Elasticsearch Helper 7.x `ElasticsearchIndexInterface` requires `public function getMappingDefinition()`
method to be present in all implementation classes. This method should return an instance of
`MappindDefinition` class which contains index field mappings described in an object oriented way.

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
    $name_field = FieldDefinition::create('text')
      ->addOption('analyzer', 'english')
      ->addMultiField('keyword', FieldDefinition::create('keyword'));

    $user_property = FieldDefinition::create('object')
      ->addProperty('uid', FieldDefinition::create('integer'))
      ->addProperty('name', $name_field);

    return MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('keyword'))
      ->addProperty('user', $user_property);
  }
```

Additionally, in Elasticsearch Helper 5.x and 6.x index plugins usually defined index settings in `setup()`
method.

In Elasticsearch Helper 7.x method `public function getIndexDefinition()` has been added to
`ElasticsearchIndexInterface` to make it easier to configure the index settings.

Example:

```
/**
 * {@inheritdoc}
 */
public function getIndexDefinition(array $context = []) {
// Get index definition.
$index_definition = parent::getIndexDefinition($context);

// Add custom settings.
$index_definition->getSettingsDefinition()->addOptions([
  'analysis' => [
    'analyzer' => [
      'english' => [
        'tokenizer' => 'standard',
      ],
    ],
  ],
]);

return $index_definition;
}
```

By default, `ElasticsearchIndexBase` class provides the following
default index settings which can be overridden in index plugins:

```
'number_of_shards' => 1,
'number_of_replicas' => 0,
```

### Changes in existing methods

Change the signature of the following methods if index plugins have them:

Before (Elasticsearch Helper 5.x and 6.x):

```
protected function getIndexName($data)
protected function getTypeName($data)
public function getId($data)
```

After (Elasticsearch Helper 7.x):

```
public function getIndexName(array $data = [])
public function getTypeName(array $data = [])
public function getId(array $data = [])
```

## Changes in configuration

Running `drush updb` and exporting the changed configuration should be sufficient
to convert the old configuration structure to the new structure.

Configuration object `elasticsearch_helper.settings` structure has been changed:

Before (Elasticsearch Helper 5.x and 6.x):

```
elasticsearch_helper:
  scheme: http
  host: localhost
  port: 9200
  authentication: 0
  user: ''
  password: ''
  defer_indexing: false
```

After (Elasticsearch Helper 7.x):

```
hosts:
  -
    scheme: http
    host: localhost
    port: '9200'
    authentication:
      enabled: true
      user: ''
      password: ''
defer_indexing: false
```

### settings.php

Update the configuration overrides in `settings.php` file if necessary:

Before (Elasticsearch Helper 5.x and 6.x):

 ```
 $config['elasticsearch_helper.settings']['elasticsearch_helper']['host'] = 'localhost';
 $config['elasticsearch_helper.settings']['elasticsearch_helper']['port'] = '9200';
 ```

After (Elasticsearch Helper 7.x):

 ```
 $config['elasticsearch_helper.settings']['hosts'][0]['host'] = 'localhost';
 $config['elasticsearch_helper.settings']['hosts'][0]['port'] = '9200';
 ```
