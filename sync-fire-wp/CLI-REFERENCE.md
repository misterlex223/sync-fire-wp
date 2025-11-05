# SyncFire WP-CLI Commands

Complete command-line interface for managing SyncFire WordPress-Firestore synchronization. This CLI is designed to work seamlessly with ignis-schema-wp for AI-assisted development.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Taxonomy Management](#taxonomy-management)
- [Post Type Management](#post-type-management)
- [Schema Integration](#schema-integration)
- [Testing & Diagnostics](#testing--diagnostics)
- [AI-Assisted Workflows](#ai-assisted-workflows)

## Installation

The SyncFire CLI is automatically available when the plugin is installed and WP-CLI is present.

```bash
# Verify installation
wp syncfire --help

# Check version
wp plugin list | grep sync-fire
```

## Configuration

### Basic Setup

```bash
# Configure for production with service account
wp syncfire config \
  --project-id=my-firebase-project \
  --service-account=/path/to/service-account.json

# Configure specific Firestore database
wp syncfire config \
  --project-id=my-firebase-project \
  --database-id=production \
  --service-account=/path/to/service-account.json

# Configure for development with emulator
wp syncfire config \
  --project-id=demo-project \
  --emulator \
  --emulator-host=localhost \
  --emulator-port=8080
```

### Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| `--project-id` | Firebase project ID | Required |
| `--database-id` | Firestore database ID | `(default)` |
| `--service-account` | Path to service account JSON | Required for production |
| `--api-key` | Firebase API key | Optional |
| `--auth-domain` | Firebase auth domain | Optional |
| `--storage-bucket` | Firebase storage bucket | Optional |
| `--emulator` | Enable emulator mode | `false` |
| `--emulator-host` | Emulator host | `localhost` |
| `--emulator-port` | Emulator port | `8080` |

### Check Configuration Status

```bash
# View current configuration
wp syncfire status

# View as JSON
wp syncfire status --format=json

# View as YAML
wp syncfire status --format=yaml
```

## Taxonomy Management

### Enable Taxonomy Synchronization

```bash
# Enable specific taxonomies
wp syncfire taxonomy enable category post_tag

# Enable custom taxonomies
wp syncfire taxonomy enable movie-genre product-category

# Enable all public taxonomies
wp syncfire taxonomy enable --all
```

### Disable Taxonomy Synchronization

```bash
# Disable specific taxonomies
wp syncfire taxonomy disable category post_tag
```

### List Taxonomies

```bash
# List all taxonomies and their sync status
wp syncfire taxonomy list
```

Output:
```
+------------------+-------------------+--------+
| slug             | label             | synced |
+------------------+-------------------+--------+
| category         | Categories        | Yes    |
| post_tag         | Tags              | No     |
| movie-genre      | Movie Genres      | Yes    |
| product-category | Product Categories| Yes    |
+------------------+-------------------+--------+
```

### Manually Trigger Taxonomy Sync

```bash
# Sync specific taxonomies
wp syncfire taxonomy sync category post_tag

# Sync all enabled taxonomies
wp syncfire taxonomy sync --all
```

## Post Type Management

### Enable Post Type Synchronization

```bash
# Enable specific post types
wp syncfire post-type enable post page

# Enable with specific fields
wp syncfire post-type enable movie --fields=title,content,excerpt,featured_image,acf

# Enable all public post types
wp syncfire post-type enable --all
```

### Disable Post Type Synchronization

```bash
# Disable specific post types
wp syncfire post-type disable movie
```

### List Post Types

```bash
# List all post types and their sync status
wp syncfire post-type list
```

Output:
```
+--------+--------+--------+
| slug   | label  | synced |
+--------+--------+--------+
| post   | Posts  | Yes    |
| page   | Pages  | No     |
| movie  | Movies | Yes    |
+--------+--------+--------+
```

### Manage Post Type Fields

```bash
# View current fields for a post type
wp syncfire post-type fields movie

# Set fields (replaces existing)
wp syncfire post-type fields movie --set=title,content,acf,featured_image

# Add fields to existing configuration
wp syncfire post-type fields movie --add=custom_field,another_field

# Remove fields
wp syncfire post-type fields movie --remove=old_field
```

### Manually Trigger Post Type Sync

```bash
# Sync specific post types
wp syncfire post-type sync movie

# Sync multiple post types
wp syncfire post-type sync movie post page

# Sync all enabled post types
wp syncfire post-type sync --all
```

## Schema Integration

The SyncFire CLI seamlessly integrates with ignis-schema-wp for complete WordPress data management.

### Import Schema and Auto-Configure Sync

```bash
# Import post type schema and enable sync
wp syncfire import post-type movie --auto-sync

# Import taxonomy schema, enable sync, and sync existing data
wp syncfire import taxonomy movie-genre --auto-sync --sync-now

# Import multiple schemas
wp syncfire import post-type product --auto-sync
wp syncfire import taxonomy product-category --auto-sync --sync-now
wp syncfire import taxonomy product-tag --auto-sync --sync-now
```

### Complete Workflow Example

```bash
# 1. Create schema using ignis-schema-wp
wp schema create movie --type=post-type \
  --prompt="Movie database with title, director, rating, release date, and trailer URL"

# 2. Create related taxonomies
wp schema create movie-genre --type=taxonomy \
  --prompt="Movie genres with icons and colors"

# 3. Validate schemas
wp schema validate movie --type=post-type
wp schema validate movie-genre --type=taxonomy

# 4. Register schemas in WordPress
wp schema register --type=all

# 5. Import to SyncFire and enable sync
wp syncfire import post-type movie --auto-sync
wp syncfire import taxonomy movie-genre --auto-sync --sync-now

# 6. Verify configuration
wp syncfire status

# 7. Generate TypeScript types for frontend
wp schema export_all --type=all --output=./frontend/types
```

## Testing & Diagnostics

### Test Firestore Connection

```bash
# Basic connection test
wp syncfire test

# Verbose output with connection details
wp syncfire test --verbose
```

### View Sync Statistics

```bash
# Show comprehensive statistics
wp syncfire stats
```

Output:
```
SyncFire Synchronization Statistics
====================================

Synced Taxonomies: 2
  - category: 5 terms
  - movie-genre: 8 terms

Synced Post Types: 2
  - post: 42 posts
  - movie: 15 posts

Configuration:
  Project ID: my-firebase-project
  Database ID: production
  Emulator Mode: No
```

## AI-Assisted Workflows

### Workflow 1: Quick Setup for New Project

```bash
#!/bin/bash
# setup-syncfire.sh - Complete setup script for AI assistants

# 1. Configure Firebase
wp syncfire config \
  --project-id="${FIREBASE_PROJECT_ID}" \
  --database-id="${FIREBASE_DATABASE_ID:-"(default)"}" \
  --service-account="${SERVICE_ACCOUNT_PATH}"

# 2. Enable sync for standard post types
wp syncfire post-type enable post --fields=title,content,excerpt,featured_image,acf
wp syncfire post-type enable page --fields=title,content,featured_image,acf

# 3. Enable sync for standard taxonomies
wp syncfire taxonomy enable category post_tag --all

# 4. Test connection
wp syncfire test --verbose

# 5. Initial sync
wp syncfire post-type sync --all
wp syncfire taxonomy sync --all

# 6. Show status
wp syncfire stats
```

### Workflow 2: Adding New Content Type with Schema

```bash
#!/bin/bash
# add-content-type.sh - Add new content type with sync

CONTENT_TYPE=$1
PROMPT=$2

# 1. Create schema
wp schema create "$CONTENT_TYPE" --type=post-type --prompt="$PROMPT"

# 2. Validate
wp schema validate "$CONTENT_TYPE" --type=post-type

# 3. Register in WordPress
wp schema register --type=post-type --slug="$CONTENT_TYPE"

# 4. Enable SyncFire sync
wp syncfire import post-type "$CONTENT_TYPE" --auto-sync

# 5. Flush rewrite rules
wp schema flush

# Example usage:
# ./add-content-type.sh "event" "Event management with date, time, location, and RSVP"
```

### Workflow 3: Development to Production Migration

```bash
#!/bin/bash
# migrate-to-production.sh - Switch from emulator to production

# 1. Export current configuration
wp syncfire status --format=json > syncfire-config-backup.json

# 2. Disable emulator mode
wp syncfire config \
  --project-id="${PROD_PROJECT_ID}" \
  --database-id="${PROD_DATABASE_ID}" \
  --service-account="${PROD_SERVICE_ACCOUNT_PATH}"

# 3. Test production connection
wp syncfire test --verbose

# 4. Sync all data to production
wp syncfire taxonomy sync --all
wp syncfire post-type sync --all

# 5. Verify
wp syncfire stats
```

### Workflow 4: AI-Driven Content Modeling

```bash
#!/bin/bash
# ai-content-model.sh - Complete AI-driven workflow

# Example: E-commerce product catalog

# 1. Create product post type
wp schema create product --type=post-type \
  --prompt="E-commerce product with SKU, price, stock, images, and specifications"

# 2. Create product taxonomies
wp schema create product-category --type=taxonomy \
  --prompt="Hierarchical product categories with icons and featured status"

wp schema create product-tag --type=taxonomy \
  --prompt="Flat product tags for filtering (e.g., sale, featured, new-arrival)"

# 3. Validate all schemas
wp schema validate product --type=post-type
wp schema validate product-category --type=taxonomy
wp schema validate product-tag --type=taxonomy

# 4. Register in WordPress
wp schema register --type=all

# 5. Configure SyncFire
wp syncfire import post-type product --auto-sync
wp syncfire import taxonomy product-category --auto-sync --sync-now
wp syncfire import taxonomy product-tag --auto-sync --sync-now

# 6. Generate TypeScript types
wp schema export_all --type=all --output=./frontend/types

# 7. Verify everything
wp syncfire stats
wp schema list --type=all
```

## Command Reference

### `wp syncfire config`
Configure Firebase connection settings.

**Options:**
- `--project-id` - Firebase project ID
- `--database-id` - Firestore database ID
- `--service-account` - Path to service account JSON
- `--api-key` - Firebase API key
- `--auth-domain` - Firebase auth domain
- `--storage-bucket` - Firebase storage bucket
- `--emulator` - Enable emulator mode
- `--emulator-host` - Emulator host
- `--emulator-port` - Emulator port

### `wp syncfire status`
Show current configuration and sync status.

**Options:**
- `--format` - Output format: `table`, `json`, `yaml`

### `wp syncfire taxonomy <subcommand>`
Manage taxonomy synchronization.

**Subcommands:**
- `enable` - Enable sync for taxonomies
- `disable` - Disable sync for taxonomies
- `list` - List all taxonomies with sync status
- `sync` - Manually trigger sync

### `wp syncfire post-type <subcommand>`
Manage post type synchronization.

**Subcommands:**
- `enable` - Enable sync for post types
- `disable` - Disable sync for post types
- `list` - List all post types with sync status
- `sync` - Manually trigger sync
- `fields` - Manage fields to sync

### `wp syncfire import <type> <slug>`
Import schema from ignis-schema-wp and configure sync.

**Arguments:**
- `<type>` - Schema type: `post-type` or `taxonomy`
- `<slug>` - Schema slug

**Options:**
- `--auto-sync` - Enable synchronization automatically
- `--sync-now` - Sync existing data immediately

### `wp syncfire test`
Test Firestore connection.

**Options:**
- `--verbose` - Show detailed connection information

### `wp syncfire stats`
Show comprehensive synchronization statistics.

## Best Practices

### 1. Always Test Configuration

```bash
# After configuration changes
wp syncfire config --project-id=my-project --service-account=/path/to/key.json
wp syncfire test --verbose
```

### 2. Use Schema-First Development

```bash
# Define schema first
wp schema create my-type --type=post-type --prompt="description"

# Validate before registering
wp schema validate my-type --type=post-type

# Then enable sync
wp syncfire import post-type my-type --auto-sync
```

### 3. Sync in Stages for Large Datasets

```bash
# Sync taxonomies first (usually smaller)
wp syncfire taxonomy sync --all

# Then sync post types one at a time
wp syncfire post-type sync movie
wp syncfire post-type sync product
```

### 4. Monitor Sync Status

```bash
# Regular checks
wp syncfire stats

# Check specific post type
wp syncfire post-type list
```

### 5. Use Emulator for Development

```bash
# Development
wp syncfire config --project-id=dev-project --emulator

# Production
wp syncfire config --project-id=prod-project --service-account=/path/to/prod-key.json
```

## Troubleshooting

### Connection Issues

```bash
# Verify configuration
wp syncfire status --format=json

# Test connection with verbose output
wp syncfire test --verbose

# Check WordPress logs
tail -f wp-content/debug.log
```

### Sync Not Working

```bash
# Check if post type/taxonomy is enabled
wp syncfire post-type list
wp syncfire taxonomy list

# Verify fields configuration
wp syncfire post-type fields movie

# Manually trigger sync
wp syncfire post-type sync movie
```

### Schema Not Found

```bash
# List available schemas
wp schema list --type=all

# Validate schema exists
wp schema info movie --type=post-type

# Re-register schema
wp schema register --type=post-type --slug=movie
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Deploy Schemas and Sync

on:
  push:
    branches: [main]
    paths:
      - 'schemas/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Deploy to WordPress
        run: |
          # Import updated schemas
          wp schema register --type=all --ssh=prod

          # Update SyncFire configuration
          wp syncfire import post-type movie --auto-sync --ssh=prod

          # Sync data
          wp syncfire post-type sync movie --ssh=prod

          # Verify
          wp syncfire stats --ssh=prod
```

## Support

For issues and feature requests:
- Check the main [README.md](./README.md)
- Review [CLAUDE.md](./CLAUDE.md) for AI assistant guidelines
- Check SyncFire logs: `wp-content/plugins/sync-fire/logs/`

---

**Made for AI-assisted WordPress development with ignis-schema-wp integration**
