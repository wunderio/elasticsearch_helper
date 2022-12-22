# Elasticsearch Helper

[![<wunderio>](https://circleci.com/gh/wunderio/elasticsearch_helper/tree/8.x.svg?style=svg)](<https://app.circleci.com/pipelines/github/wunderio/elasticsearch_helper?branch=8.x-7.x>)

## System requirements
* Drupal ^9.4
* [Elasticsearch-PHP](https://github.com/elastic/elasticsearch-php) library

## Installation

The module requires [Elasticsearch-PHP](https://github.com/elastic/elasticsearch-php)
library to communicate with Elasticsearch server. The recommended installation
method for this module is with composer, which will automatically install
the correct version of the library. See how to [install modules with
Composer](https://www.drupal.org/docs/develop/using-composer/manage-dependencies#managing-contributed).

## Drush commands

```
drush elasticsearch-helper-list
drush elasticsearch-helper-setup   [index1[,index2,...]]
drush elasticsearch-helper-drop    [index1[,index2,...]]
drush elasticsearch-helper-reindex [index1[,index2,...]]
drush queue-run elasticsearch_helper_indexing
```

IMPORTANT:

Explicitly SETUP INDICES as the very first step before _any_ indexing. Only this way you get properly set up their
mappings and details. Otherwise, implicit mappings are created by Elasticsearch itself which will not have the
fine-tuned field configuration (e.g. for language specific analysis like stemming). Once an index is created, it will
be ignored by the Elasticsearch Helper setup command and stay the way it is.

Use the following command before any indexing:

```
drush elasticsearch-helper-setup
```
