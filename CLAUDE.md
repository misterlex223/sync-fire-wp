# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SyncFire is a WordPress plugin that provides real-time synchronization between WordPress taxonomies/post types and Google Firestore. The plugin enables WordPress administrators to automatically sync content to Google Firestore, allowing real-time data access for external applications.

## Development Commands

### WP-CLI Commands (Primary Interface for AI Assistants)

SyncFire includes a comprehensive WP-CLI interface designed for AI-assisted development and automation. See [AI_README.md](./AI_README.md) for AI-friendly quick reference and [CLI-REFERENCE.md](./sync-fire-wp/CLI-REFERENCE.md) for complete documentation.

```bash
# Configuration
wp syncfire config --project-id=my-project --service-account=/path/to/key.json
wp syncfire config --project-id=my-project --database-id=production
wp syncfire config --emulator --project-id=dev-project
wp syncfire status

# Taxonomy Management
wp syncfire taxonomy enable category post_tag movie-genre
wp syncfire taxonomy disable category
wp syncfire taxonomy list
wp syncfire taxonomy sync category --all

# Post Type Management
wp syncfire post-type enable post --fields=title,content,acf
wp syncfire post-type disable post
wp syncfire post-type list
wp syncfire post-type sync post --all
wp syncfire post-type fields movie --set=title,director,rating

# Schema Integration (works with ignis-schema-wp)
wp syncfire import post-type movie --auto-sync --sync-now
wp syncfire import taxonomy movie-genre --auto-sync --sync-now

# Testing and Diagnostics
wp syncfire test --verbose
wp syncfire stats
```

### Plugin Deployment and Packaging

```bash
# Deploy plugin to WordPress installation (auto-detects or prompts for path)
./update-plugin.sh

# Deploy and activate the plugin
./update-plugin.sh --activate

# Deploy to a specific WordPress installation
./update-plugin.sh --path /var/www/html

# Check plugin status only
./update-plugin.sh --check

# Create a distributable ZIP package for release
./package-plugin.sh
```

### Dependency Management

```bash
# Install dependencies (development)
composer install

# Install dependencies (production - no dev dependencies)
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

### Firebase Emulator (Development)

```bash
# Start Firestore emulator for local testing
firebase emulators:start --only firestore

# Configure plugin for emulator via CLI
wp syncfire config --emulator --project-id=demo-project

# Or via admin settings:
# Host: localhost
# Port: 8080 (default)
```

## Architecture

### Core Plugin Structure

The plugin follows WordPress plugin conventions with a modular architecture:

- **sync-fire-wp/sync-fire.php** - Main plugin file, defines constants and initializes the SyncFire class
- **sync-fire-wp/includes/** - Core functionality classes
- **sync-fire-wp/admin/** - Admin interface files

### Key Components

1. **class-syncfire-firestore.php** - Handles all Firestore communication with three connection modes:
   - Production gRPC mode (requires PHP gRPC extension)
   - Production REST mode (fallback when gRPC unavailable)
   - Emulator mode (for development)

2. **class-syncfire-taxonomy-sync.php** - Manages taxonomy synchronization to Firestore

3. **class-syncfire-post-type-sync.php** - Manages post type synchronization to Firestore

4. **class-syncfire-options.php** - Handles plugin settings storage in WordPress options table

5. **class-syncfire-admin.php** - Admin interface and settings pages

6. **class-syncfire-hooks.php** - WordPress hooks integration for real-time sync

7. **class-syncfire-acf-helper.php** - Advanced Custom Fields (ACF) integration

8. **class-syncfire-logger.php** - Logging functionality

9. **class-syncfire-migration.php** - Database migration handling

### Data Flow

1. WordPress triggers hooks on content changes (posts, taxonomies, meta updates)
2. SyncFire_Hooks captures these events
3. Data is processed by SyncFire_Post_Type_Sync or SyncFire_Taxonomy_Sync
4. SyncFire_Firestore sends formatted data to Firestore
5. Firestore stores data in configured collections and documents

### Firestore Data Structure

**Taxonomies**: `/taxonomies/{taxonomy_name}`
- Stores all terms for a taxonomy in a single document with an array of term objects

**Posts**: `/posts/{post_type}/items/{post_id}`
- Each post is stored as a separate document
- Fields are configurable via admin interface
- Supports custom field mappings (WordPress field → Firestore field)

## Configuration Storage

Plugin settings are stored in WordPress options table with prefixes:
- `syncfire_firebase_*` - Firebase credentials and configuration
  - `syncfire_firebase_database_id` - Firestore database ID (defaults to `(default)`)
- `syncfire_taxonomies_to_sync` - Selected taxonomies to sync
- `syncfire_post_types_to_sync` - Selected post types to sync
- `syncfire_post_type_fields` - Fields to sync for each post type
- `syncfire_post_type_field_mapping` - Field name mappings

## Important Development Notes

### Firestore Connection Modes

The plugin automatically detects and uses the appropriate Firestore connection mode:

1. **Emulator Mode** (Development)
   - Enabled via admin settings
   - Bypasses authentication
   - Connects to local Firestore emulator
   - Never use in production

2. **gRPC Mode** (Production - Recommended)
   - Requires PHP gRPC extension
   - Best performance
   - Automatically selected if gRPC extension available

3. **REST Mode** (Production - Fallback)
   - Used when gRPC extension not available
   - Uses standard HTTPS requests
   - Wider compatibility

### Multiple Firestore Databases

The plugin supports connecting to specific Firestore databases within a project:

- Configure the **Database ID** field in the Configuration page
- Defaults to `(default)` for the default database
- Can specify named databases (e.g., `production`, `staging`, `dev`)
- All sync operations use the configured database
- Both gRPC and REST modes support named databases

### Real-time Synchronization

The plugin hooks into WordPress actions to sync in real-time:
- Post creation/update/deletion
- Post status changes
- Taxonomy term creation/update/deletion
- Post meta and ACF field updates
- Featured image changes

### Deployment Scripts

**update-plugin.sh**:
- Deploys the plugin from development directory to WordPress installation
- Preserves logs directory during updates
- Excludes development files (.git, .sh scripts, docker files)
- Automatically installs Composer dependencies
- Supports WP-CLI for plugin activation/status checks

**package-plugin.sh**:
- Cleans log files
- Installs production dependencies
- Creates timestamped ZIP package
- Excludes development files from package

## Common Development Tasks

### Adding New Sync Fields

1. Modify the admin interface in `class-syncfire-admin.php` to add field selection UI
2. Update `class-syncfire-post-type-sync.php` to handle the new field during sync
3. Test with Firestore emulator before deploying to production

### Modifying Firestore Structure

1. Update the sync classes (`class-syncfire-taxonomy-sync.php` or `class-syncfire-post-type-sync.php`)
2. Consider backward compatibility with existing data
3. Update documentation if changing collection/document paths

### Testing Changes

1. Use the Firestore emulator for safe testing
2. Deploy to local WordPress with `./update-plugin.sh`
3. Test manual sync via admin dashboard
4. Test automatic sync by creating/updating content
5. Check logs in `sync-fire-wp/logs/` directory

### Security Considerations

- All admin pages require administrator capability
- Firebase credentials stored securely in WordPress options
- All user inputs are sanitized and validated
- Emulator mode should only be used in secure development environments
- Service account JSON credentials should never be committed to version control

## WP-CLI Integration

SyncFire provides a complete WP-CLI interface for automation and AI-assisted development. The CLI is located at `sync-fire-wp/cli/class-syncfire-command.php`.

### CLI Architecture

- **Command Class**: `SyncFire\CLI\SyncFireCommand` extends `WP_CLI_Command`
- **Auto-loaded**: Automatically loaded when WP-CLI is available
- **Namespace**: Commands are available under `wp syncfire`

### Key CLI Components

1. **Configuration Management** (`wp syncfire config`)
   - Firebase project and database configuration
   - Service account setup
   - Emulator mode toggle

2. **Taxonomy Sync** (`wp syncfire taxonomy`)
   - Enable/disable sync for taxonomies
   - List sync status
   - Manual sync trigger

3. **Post Type Sync** (`wp syncfire post-type`)
   - Enable/disable sync for post types
   - Field configuration
   - Manual sync trigger

4. **Schema Integration** (`wp syncfire import`)
   - Import from ignis-schema-wp
   - Auto-configure sync
   - Immediate data sync

5. **Testing & Diagnostics** (`wp syncfire test`, `wp syncfire stats`)
   - Connection testing
   - Sync statistics
   - Status reporting

### Integration with ignis-schema-wp

SyncFire CLI is designed to work seamlessly with ignis-schema-wp for complete WordPress data management:

**Complete Workflow Example:**

```bash
# 1. Create schema with ignis-schema-wp
wp schema create movie --type=post-type \
  --prompt="Movie database with director, rating, release date"

wp schema create movie-genre --type=taxonomy \
  --prompt="Movie genres with icons and colors"

# 2. Validate schemas
wp schema validate movie --type=post-type
wp schema validate movie-genre --type=taxonomy

# 3. Register in WordPress
wp schema register --type=all

# 4. Configure SyncFire and enable sync
wp syncfire config --project-id=my-project --service-account=/path/to/key.json
wp syncfire import post-type movie --auto-sync --sync-now
wp syncfire import taxonomy movie-genre --auto-sync --sync-now

# 5. Generate TypeScript types for frontend
wp schema export_all --type=all --output=./frontend/types

# 6. Verify everything
wp syncfire stats
wp schema list --type=all
```

### AI-Assisted Development Patterns

**Pattern 1: Quick Content Type Creation**
```bash
# AI can execute this entire workflow
CONTENT_TYPE="event"
PROMPT="Event management with date, time, location, RSVP tracking"

wp schema create "$CONTENT_TYPE" --type=post-type --prompt="$PROMPT"
wp schema register --type=post-type --slug="$CONTENT_TYPE"
wp syncfire import post-type "$CONTENT_TYPE" --auto-sync
wp schema flush
```

**Pattern 2: Development to Production Migration**
```bash
# Switch from emulator to production
wp syncfire config \
  --project-id="${PROD_PROJECT_ID}" \
  --database-id="production" \
  --service-account="${PROD_SERVICE_ACCOUNT_PATH}"

wp syncfire test --verbose
wp syncfire taxonomy sync --all
wp syncfire post-type sync --all
```

**Pattern 3: Bulk Configuration**
```bash
# Configure multiple taxonomies and post types at once
for taxonomy in category post_tag movie-genre; do
  wp syncfire taxonomy enable "$taxonomy"
done

for post_type in post page movie; do
  wp syncfire post-type enable "$post_type" --fields=title,content,acf
done

wp syncfire stats
```

## AI Assistant Guidelines

When working with SyncFire, AI assistants should:

1. **Use CLI First**: Always prefer WP-CLI commands over manual admin configuration
2. **Test Connections**: Run `wp syncfire test --verbose` after configuration changes
3. **Verify Status**: Use `wp syncfire status` and `wp syncfire stats` to confirm operations
4. **Schema Integration**: Use `wp syncfire import` to connect ignis-schema-wp schemas with sync
5. **Batch Operations**: Use `--all` flags for bulk operations when appropriate
6. **Check Logs**: Monitor `sync-fire-wp/logs/` for detailed operation logs

### Example AI Workflow

When asked to "set up a movie database with Firestore sync":

```bash
# 1. Create data model
wp schema create movie --type=post-type \
  --prompt="Movie database with title, director, rating, release date, trailer URL, box office revenue"

wp schema create movie-genre --type=taxonomy \
  --prompt="Hierarchical movie genres with icons, colors, and descriptions"

wp schema create movie-tag --type=taxonomy \
  --prompt="Flat movie tags for filtering (e.g., oscar-winner, blockbuster, indie)"

# 2. Validate and register
wp schema validate movie --type=post-type
wp schema validate movie-genre --type=taxonomy
wp schema validate movie-tag --type=taxonomy
wp schema register --type=all

# 3. Configure Firestore sync
wp syncfire config \
  --project-id="${FIREBASE_PROJECT_ID}" \
  --database-id="${FIREBASE_DATABASE_ID}" \
  --service-account="${SERVICE_ACCOUNT_PATH}"

# 4. Enable sync
wp syncfire import post-type movie --auto-sync
wp syncfire import taxonomy movie-genre --auto-sync --sync-now
wp syncfire import taxonomy movie-tag --auto-sync --sync-now

# 5. Test and verify
wp syncfire test --verbose
wp syncfire stats

# 6. Generate frontend types
wp schema export_all --type=all --output=./frontend/types

# 7. Flush rewrite rules
wp schema flush
```

This approach ensures:
- Schema-first development
- Automatic Firestore synchronization
- Type-safe frontend development
- Complete audit trail via CLI output
- Reproducible deployments

## Troubleshooting with CLI

```bash
# Check configuration
wp syncfire status --format=json

# Test connection
wp syncfire test --verbose

# List enabled syncs
wp syncfire taxonomy list
wp syncfire post-type list

# Check specific post type fields
wp syncfire post-type fields movie

# View sync statistics
wp syncfire stats

# Manual sync trigger
wp syncfire taxonomy sync category
wp syncfire post-type sync movie
```
