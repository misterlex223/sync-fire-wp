# SyncFire WordPress Plugin - Project Overview

## Project Description

SyncFire is a WordPress plugin that provides real-time synchronization between WordPress taxonomies/post types and Google Firestore. The plugin allows WordPress site administrators to automatically sync their site's content to Google Firestore, enabling real-time data access for external applications.

## Architecture Overview

### Core Components

The plugin follows a modular architecture with the following main components:

- `sync-fire.php` - Main plugin file that initializes the SyncFire class and handles plugin lifecycle
- `includes/` - Contains core functionality classes:
  - `class-syncfire-firestore.php` - Handles communication with Google Firestore API
  - `class-syncfire-taxonomy-sync.php` - Manages taxonomy synchronization
  - `class-syncfire-post-type-sync.php` - Manages post type synchronization  
  - `class-syncfire-options.php` - Handles plugin settings and options
  - `class-syncfire-admin.php` - Manages admin interface
  - `class-syncfire-hooks.php` - Handles WordPress hooks and actions
  - `class-syncfire-migration.php` - Handles database migration
  - `syncfire-functions.php` - Utility functions

### Synchronization Features

- **Taxonomy Synchronization**: Sync specified taxonomies to Firestore with order control
- **Post Type Synchronization**: Sync post type contents to Firestore with configurable field mapping
- **Real-time Synchronization**: Updates Firestore when content changes in WordPress
- **Admin Configuration Interface**: Secure backend interface for sync settings

### Technical Requirements

- **PHP**: 7.4 or higher
- **WordPress**: 5.0 or higher
- **Google Firebase**: Account with Firestore enabled
- **Dependencies**: 
  - Google Cloud Firestore client library (`google/cloud-firestore`)
  - Guzzle HTTP client (`guzzlehttp/guzzle`)
  - Optional: PHP gRPC extension for better performance

## Development Environment

### Project Structure

```
/home/flexy/workspace/
├── .gitignore                    # Root git ignore
├── DEPLOYMENT.md                 # Deployment instructions
├── QWEN.md                       # Current file
├── composer.phar                 # PHP dependency manager
├── package-plugin.sh             # Packaging script
├── update-plugin.sh              # Update/deployment script
├── docs/                         # Documentation files
└── sync-fire-wp/                 # Main plugin directory
    ├── .gitignore               # Plugin-specific git ignore
    ├── README.md                # Plugin documentation
    ├── composer.json            # PHP dependencies
    ├── composer.lock            # Locked dependency versions
    ├── index.php                # WordPress plugin entry point
    ├── sync-fire.php            # Main plugin file
    ├── uninstall.php            # Plugin uninstallation logic
    ├── admin/                   # Admin interface files
    ├── includes/                # Core functionality
    │   ├── class-syncfire-*.php # Individual component classes
    │   ├── syncfire-functions.php # Utility functions
    │   └── vendor-autoload.php  # Composer autoloader
    ├── languages/               # Translation files
    └── logs/                    # Log directory
```

### Development Workflow

1. **Development**: Make changes in the `/home/flexy/workspace/sync-fire-wp` directory
2. **Testing**: Use the `update-plugin.sh` script to deploy changes to a WordPress installation
3. **Packaging**: Use the `package-plugin.sh` script to create a distributable plugin package

### Deployment Scripts

#### `update-plugin.sh` - Development Deployment
```bash
# Update plugin to detected WordPress installation
./update-plugin.sh

# Update and activate plugin at a specific path
./update-plugin.sh --path /var/www/html --activate

# Check plugin status only
./update-plugin.sh --check

# Dry run to see what would be done
./update-plugin.sh --dry-run
```

#### `package-plugin.sh` - Plugin Packaging
```bash
# Create a package with current timestamp
./package-plugin.sh
```

### Building and Testing

The plugin uses Composer for dependency management:

1. **Install dependencies**:
```bash
cd sync-fire-wp
composer install --no-dev --optimize-autoloader
```

2. **Deploy to WordPress**:
```bash
./update-plugin.sh  # Automatically installs dependencies during deployment
```

3. **Package for distribution**:
```bash
./package-plugin.sh  # Creates a ZIP package with all dependencies
```

### Development Conventions

- **Code Style**: Follow WordPress PHP coding standards
- **File Structure**: Use WordPress plugin directory structure conventions
- **Settings API**: Use WordPress Settings API for configuration
- **Hooks**: Follow WordPress hook conventions for actions and filters
- **Security**: Sanitize and validate all input, use nonces where appropriate
- **Logging**: Use the plugin's logging system for debugging

### Configuration Settings

The plugin stores configuration in WordPress options table with the following prefixes:
- `syncfire_firebase_*` - Firebase configuration settings
- `syncfire_taxonomies_to_sync` - Taxonomies to synchronize
- `syncfire_post_types_to_sync` - Post types to synchronize
- `syncfire_post_type_fields` - Fields to synchronize for post types
- `syncfire_post_type_field_mapping` - Field mapping between WordPress and Firestore

### Firestore Connection Modes

The plugin supports three connection modes to Firestore:
1. **gRPC Mode** (Recommended): Uses gRPC protocol for optimal performance
2. **REST Mode** (Fallback): Uses standard HTTP REST calls for wider compatibility
3. **Emulator Mode** (Development): Connects to a local Firestore emulator for development and testing

The plugin automatically detects which mode to use based on server configuration and settings.

### Firestore Emulator Support

The plugin now includes support for connecting to a local Firestore emulator for development and testing. Configuration options include:
- `FIRESTORE_EMULATOR_ENABLED`: Boolean to enable/disable emulator mode
- `FIRESTORE_EMULATOR_HOST`: Host address of the emulator (default: localhost)
- `FIRESTORE_EMULATOR_PORT`: Port number of the emulator (default: 8080)

When emulator mode is enabled, the plugin bypasses authentication and connects directly to the local emulator instance, allowing for safe testing without affecting production data.

## Key Files and Functions

### `sync-fire.php`
Main plugin file containing the SyncFire class which:
- Loads all dependencies
- Initializes hooks and actions
- Handles plugin activation/deactivation
- Manages settings registration

### `includes/class-syncfire-firestore.php`
Handles communication with Google Firestore, including:
- Authentication with Firebase
- Document creation, update, and deletion
- Error handling and retry logic

### `includes/class-syncfire-taxonomy-sync.php`
Manages taxonomy synchronization, including:
- Hooking into WordPress taxonomy actions
- Converting WordPress term data to Firestore format
- Handling taxonomy creation, update, and deletion

### `includes/class-syncfire-post-type-sync.php`
Manages post type synchronization, including:
- Hooking into WordPress post actions
- Converting WordPress post data to Firestore format
- Handling post creation, update, and deletion