# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SyncFire is a WordPress plugin that provides real-time synchronization between WordPress taxonomies/post types and Google Firestore. The plugin enables WordPress administrators to automatically sync content to Google Firestore, allowing real-time data access for external applications.

## Development Commands

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

# The plugin supports emulator mode - enable in settings with:
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
