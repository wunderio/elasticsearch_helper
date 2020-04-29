
# Elasticsearch Helper Index Alias

- Adds version index capabilities
- Adds index alias functionality for pointing aliases to index version.
- Useful for re-indexing large datasets after changes to index mappings.

# How to use

### Setting up Index versions
- Create and setup versioned indexes (See. VersionedIndex.php from examples)

### Incrementing Index versions
- Go to index alias management from the Admin menu: Configuration > Search and Metadata > Elasticsearch helper > Elasticsearch helper index management
- Go to the "Aliases management" tab
- Increment the index version as needed after changes to index field mapping.
- Export the new version configuration and commit.

### Deploying New Index Versions and Pointing Aliases
- Run `drush eshs` to setup new index versions during deployment
- Re-index entities from Index management tab
- When re-indexing is done, verify that the new index version contains documents from the "Indices status" tab
- Finally point the aliases to the new index version by clicking "Update index aliases to the latest index version" from the "Aliases management tab"