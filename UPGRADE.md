# Upgrade guide

This guide is intended to describe the process of upgrading
Elasticsearch Helper from version 5.x or 6.x to version 7.0.

The most notable changes in version 7.0:

1. Elasticsearch Helper 7.0 is compatible only with Elasticsearch 7.
2. Multi-host configuration for use in a cluster environment.
3. Support for event subscribers to react to most common Elasticsearch operations.
4. Support for object-oriented description of index settings and field mappings.
5. Simplified index plugin structure (no need to explicitly create an index in `setup()` method).
6. Index plugins can define their own overall content reindex procedures in `reindex()` method.
7. Sub-modules are split into their own separate projects:
    * [Elasticsearch Helper AWS](https://drupal.org/project/elasticsearch_helper_aws)
    * [Elasticsearch Helper Content](https://www.drupal.org/project/elasticsearch_helper_content)
    * [Elasticsearch Helper Instant](https://www.drupal.org/project/elasticsearch_helper_instant)
    * [Elasticsearch Helper Views](https://www.drupal.org/project/elasticsearch_helper_views)
8. Typo in permission name changed (`configured elasticsearch helper => configure elasticsearch helper`).

Minimal upgrade checklist:
1. [ ] Add `getMappingDefinition()` method to index plugins to conform to the interface.
2. [ ] Revise the necessity of `setup()` method in index plugins. Fields and index settings are
already defined in `getMappingDefinition()` and `getIndexDefinition()` methods. Consider defining
index settings in `getIndexDefinition()` rather than in `setup()` method.
3. [ ] Run `drush updb` to update the configuration.
4. [ ] Run `drush cr` to clear caches (this is necessary to discover changed permission name).
5. [ ] Run `drush cex` to export the configuration.
6. [ ] Commit the changes in exported Elasticsearch Helper configuration and in role configuration
with updated permission name.

## Changes in included sub-modules

As sub-modules in Elasticsearch Helper 7.0 have been moved to their own projects, add them
manually to the project if necessary:

```
composer require drupal/elasticsearch_helper_aws
composer require drupal/elasticsearch_helper_content
composer require drupal/elasticsearch_helper_instant
composer require drupal/elasticsearch_helper_views
```

## Notable changes

### New methods

Three new methods are added to `ElasticsearchIndexInterface`:

1. `public function getMappingDefinition(array $context = [])`
2. `public function getIndexDefinition(array $context = [])`
3. `public function reindex(array $context = [])`

#### getMappingDefinition()

In Elasticsearch Helper 5.x and 6.x index plugins usually defined field mappings in `setup()`
method as arrays.

In Elasticsearch Helper 7.0 `ElasticsearchIndexInterface` requires `getMappingDefinition()`
method to be present in all implementation classes. This method should return an instance of
`MappindDefinition` class which contains index field mappings described in an object-oriented way.

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

This method needs to be explicitly implemented in every index plugin.

#### getIndexDefinition()

In Elasticsearch Helper 5.x and 6.x index plugins usually defined index settings in `setup()`
method.

In Elasticsearch Helper 7.0 method `getIndexDefinition()` makes it easier to configure index settings.

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

By default `ElasticsearchIndexBase::getIndexDefinition()` provides the following
default index settings which can be overridden in `getIndexDefinition()` method in extending index plugins:

```
'number_of_shards' => 1,
'number_of_replicas' => 0,
```

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
    'number_of_shards' => 2,
    'number_of_replicas' => 1,
  ]);

  return $index_definition;
}
```

#### reindex()

In Elasticsearch Helper 5.x and 6.x running `drush elasticsearch-helper-reindex` would only affect index plugins
that index entities of type defined in `entityType` plugin definition.

In Elasticsearch Helper 7.0 index plugins can define their own reindex logic in `reindex()` method, allowing index
plugins that manage non-entity content react when `drush elasticsearch-helper-reindex` command is run.

By default `ElasticsearchIndexBase::reindex()` method re-indexes entities managed by index plugins that define entity
type in `entityType` plugin definition.

### Changes in existing methods

Change the signature of the following methods if index plugins have them:

Before (Elasticsearch Helper 5.x and 6.x):

```
protected function getIndexName($data)
protected function getTypeName($data)
public function getId($data)
protected function indexNamePattern()
protected function typeNamePattern()
public function replacePlaceholders($haystack, array $data)
```

After (Elasticsearch Helper 7.0):

```
public function getIndexName(array $data = [])
public function getTypeName(array $data = [])
public function getId(array $data = [])
public function indexNamePattern()
public function typeNamePattern()
public function replacePlaceholders($haystack, array $data)
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

After (Elasticsearch Helper 7.0):

```
scheme: http
hosts:
  -
    host: localhost
    port: '9200'
authentication:
  method: ''
  configuration: {  }
ssl:
  certificate: ''
  skip_verification: false
defer_indexing: false
```

Permission name `configured elasticsearch helper` has been changed to `configure elasticsearch helper`.

### settings.php

Update the configuration overrides in `settings.php` file if necessary:

Before (Elasticsearch Helper 5.x and 6.x):

 ```
 $config['elasticsearch_helper.settings']['elasticsearch_helper']['host'] = 'localhost';
 $config['elasticsearch_helper.settings']['elasticsearch_helper']['port'] = '9200';
 ```

After (Elasticsearch Helper 7.0):

 ```
 $config['elasticsearch_helper.settings']['hosts'][0]['host'] = 'localhost';
 $config['elasticsearch_helper.settings']['hosts'][0]['port'] = '9200';
 ```

More advanced configuration (Elasticsearch Helper 7.0):

```
$config['elasticsearch_helper.settings']['hosts'][0]['host'] = 'localhost';
$config['elasticsearch_helper.settings']['hosts'][0]['port'] = '9200';
$config['elasticsearch_helper.settings']['scheme'] = 'http';
$config['elasticsearch_helper.settings']['authentication']['method'] = 'basic_auth';
$config['elasticsearch_helper.settings']['authentication']['configuration']['basic_auth']['user'] = 'elastic';
$config['elasticsearch_helper.settings']['authentication']['configuration']['basic_auth']['password'] = '[password]';
```

### Event listening

Events are emitted when certain Elasticsearch operations are about to be performed. Other
modules can listen to these events and react.

1. `ElasticsearchEvents::OPERATION` event is fired before operation is performed. Event
listeners can block entity indexing if entity should not be indexed for certain reasons.

2. `ElasticsearchEvents::OPERATION_REQUEST` event is fired before request is sent to
Elasticsearch client. Event listeners can change the request callback or request parameters.

3. `ElasticsearchEvents::OPERATION_REQUEST_RESULT` event is fired when request to Elasticsearch
has been successfully completed without errors. Event listeners can log the event or perform
other actions.

4. `ElasticsearchEvents::OPERATION_ERROR` event is fired when Elasticsearch operation could
not be completed (for example, call to `index()` throws an instance of `\Throwable` for any
reason). Event listeners can log the error or perform other actions.

See event listeners provided with the module for inspiration.

### Authentication methods

Modules can provide `ElasticsearchAuth` plugins which authenticate with Elasticsearch server.

By default Elasticsearch Helper 7.0 provides two authentication method plugins:
  - Basic authentication (`basic_auth`) for authentication against built-in and native Elasticsearch users.
  - API key (`api_key`) for authentication using API keys.

## Updating from 7.x dev version to 7.0 release version

If Elasticsearch Helper is being updated from 7.x development version, it's important to see
that an `elasticsearch_helper_update_8004()` update hook is run in order to make proper changes
to the configuration structure.

If Elasticsearch Helper configuration was stored in the `settings.php` file, make adjustments to the
configuration override code according to configuration structure described in the [settings.php](#settingsphp)
section of this file.
