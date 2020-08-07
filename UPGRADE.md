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
