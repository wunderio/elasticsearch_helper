
# Elasticsearch Helper Content

## Start


* Enable thew modules: `drush -y en elasticsearch_helper_instant`
* Configure the following viewmodes of your relevant entities to contain sensible data (or have the default view mode handle it):
  * search_index
  * search_result
* Setup new indices: `drush elasticsearch-helper-setup`
* (Re-) Index the data:
  * `drush elasticsearch-helper-reindex`
  * `drush queue-run elasticsearch_helper_indexing`

## Decide whether an entity is indexed

You can decide whether an entity is indexed via custom hook implementation:

```
function HOOK_elasticsearch_helper_content_source_alter(&$source) {
  // Only index nodes of bundle article or event.
  if ($source instanceof \Drupal\node\Entity\Node) {
    if (!in_array($source->bundle(), ['article', 'event'])) {
      $source = FALSE;
    }
  }
}
```

## Manually set a render theme

When indexing an entity, the entity's render output for viewmodes search_index and search_result are also stored in ES.
By default, the default frontend theme is used to do that. But you can specify a different theme in settings.php:

```
$settings['elasticsearch_helper_content'] = [
  'render_theme' => 'my_awesome_theme_name',
];
```

## Skip default normalize

When indexing an entity, the provided ElasticsearchContentNormalizer provides some metadata across entity types as well as it's parent classContentEntityNormalizer's default normalization, which is quite verbose and might not be intended in some cases. To skip providing this default normalization, set the following in your settings.php: 
```
$settings['elasticsearch_helper_content'] = [
  'skip_default_normalize' => TRUE, // or _any_ other value.
];
```

