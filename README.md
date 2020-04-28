# Elasticsearch Helper

Elasticsearch Helper is a helper module to work with Elasticsearch on a Drupal project.

## Installation

This module requires [elasticsearch/elasticsearch](https://github.com/elastic/elasticsearch-php)
PHP library to communicate with Elasticsearch server. The exact version of the library that
works with specific version of Elasticsearch server can be found on library's project page.

After determining the version of Elasticsearch-PHP library, run the following command to add
it to the project, for example:
```
composer require elasticsearch/elasticsearch:~7.0
```

## Drush commands

```
drush elasticsearch-helper-list
drush elasticsearch-helper-setup   [index1[,index2,...]]
drush elasticsearch-helper-drop    [index1[,index2,...]]
drush elasticsearch-helper-reindex [index1[,index2,...]]
drush queue-run elasticsearch_helper_indexing
```

IMPORTANT:

Explicitly setup indices as the very first step before _any_ indexing. Only this way you get properly set up their mappings and details. Otherwise, implicit mappings are created by elasticsearch itself which will not have the fine tuned field configuration (e.g. for language sepcific analysis like stemming). Once an index is created, it will be ignored by the ES helper setup command and stay the way it is.
So use the following command before any indexing:

```
drush elasticsearch-helper-setup
```
