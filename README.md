# Elasticsearch Helper

This is the Drupal 7 backport of Elasticsearch Helper which was initially build
for Drupal 8.
It's not feature complete: Most notably it lacks support for deferred indexing,
and hence reindexing.

Index plugins can be implemented as ctools plugins. Find a working and usable
example implementation in the submodule elasticsearch_helper_node_index.
Please take a look a elasticsearch_helper.api.php how to override/alter the
behaviour of this (an possibly any other) elastisearch_helper_index plugin.

Please note the composer.json file in the codebase. In order to not impose
composer as site wide requirement it's deliberately kept within the module. You
may even get away checking in the vendor code to your codebase to avoid nasty
deployment hassle.

## Drush commands

```
drush elasticsearch-helper-list
drush elasticsearch-helper-setup   [index1[,index2,...]]
```

IMPORTANT:

Explicitely SETUP INDICES as the very first step before _any_ indexing. Only this way you get properly set up their mappings and details. Otherwise, implicit mappings are created by elasticsearch itself which will not have the fine tuned field configuration (e.g. for language sepcific analysis like stemming). Once an index is created, it will be ignored by the ES helper setup command and stay the way it is.
So use the following command before any indexing:

```
drush elasticsearch-helper-setup
```
