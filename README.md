# Elasticsearch Helper

[![<wunderio>](https://circleci.com/gh/wunderio/elasticsearch_helper/tree/7.x.svg?style=svg)](<https://app.circleci.com/pipelines/github/wunderio/elasticsearch_helper?branch=8.x-7.x>)

## System requirements
* Drupal ^9.4
* [Elasticsearch-PHP](https://github.com/elastic/elasticsearch-php) library

## Installation

The module requires [Elasticsearch-PHP](https://github.com/elastic/elasticsearch-php) library to
communicate with Elasticsearch server. Please make sure you install the version of the library compatible with
the version of the server (see the [compatibility matrix](https://github.com/elastic/elasticsearch-php#version-matrix)).

To install the library for use with Elasticsearch 7.0:
```
composer require elasticsearch/elasticsearch:^7.0
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

Explicitly SETUP INDICES as the very first step before _any_ indexing. Only this way you get properly set up their
mappings and details. Otherwise, implicit mappings are created by Elasticsearch itself which will not have the
fine-tuned field configuration (e.g. for language specific analysis like stemming). Once an index is created, it will
be ignored by the Elasticsearch Helper setup command and stay the way it is.

Use the following command before any indexing:

```
drush elasticsearch-helper-setup
```
