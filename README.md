# Elasticsearch Helper

## Drush commands

```
drush elasticsearch-helper-list
drush elasticsearch-helper-setup   [index1[,index2,...]]
drush elasticsearch-helper-drop    [index1[,index2,...]]
drush elasticsearch-helper-reindex [index1[,index2,...]]
drush queue-run elasticsearch_helper_indexing
```

IMPORTANT:

Explicitely SETUP INDICES as the very first step before _any_ indexing. Only this way you get properly set up their mappings and details. Otherwise, implicit mappings are created by elasticsearch itself which will not have the fine tuned field configuration (e.g. for language sepcific analysis like stemming). Once an index is created, it will be ignored by the ES helper setup command and stay the way it is.
So use the following command before any indexing:

```
drush elasticsearch-helper-setup
```
