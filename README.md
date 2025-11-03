# SyncFire WordPress Plugin

SyncFire is a WordPress plugin that provides real-time synchronization between WordPress taxonomies/post types and Google Firestore. The plugin allows WordPress site administrators to automatically sync their site's content to Google Firestore, enabling real-time data access for external applications.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [Data Structure](#data-structure)
- [Firestore Connection Modes](#firestore-connection-modes)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Security](#security)
- [Support](#support)
- [License](#license)

## Features

- **Taxonomy Synchronization**: Sync specified taxonomies to Firestore with order control.
- **Post Type Synchronization**: Sync post type contents to Firestore, allowing selection of fields and configurable Firestore document mappings.
- **Real-time Synchronization**: Update Firestore concurrently when post types and taxonomies are updated in WordPress.
- **Admin Configuration Interface**: A secure backend interface for configuration and management of sync settings.
- **ACF Integration**: Sync Advanced Custom Fields (ACF) custom fields and taxonomies.
- **Firestore Emulator Support**: Development support for local Firestore emulator.
- **Multiple Connection Modes**: Support for gRPC, REST, and emulator modes for different environments.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Google Firebase account with Firestore enabled
- Composer for dependency management

### Optional Requirements

- PHP gRPC extension (for optimal performance in production gRPC mode)

## Installation

1. Clone or download the repository to your local machine
2. Navigate to the plugin directory:
   ```bash
   cd sync-fire-wp
   ```
3. Install dependencies using Composer:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. Upload the `sync-fire-wp` directory to the `/wp-content/plugins/` directory of your WordPress installation
5. Activate the plugin through the 'Plugins' menu in WordPress
6. Configure the plugin settings in the SyncFire menu under WordPress admin dashboard

### Using the Update Script

For development, you can use the provided update script to deploy changes:

```bash
./update-plugin.sh
```

## Configuration

### Firebase Configuration

1. Go to the SyncFire Settings page in your WordPress admin dashboard
2. Enter your Firebase credentials:
   - API Key
   - Auth Domain
   - Project ID
   - Storage Bucket
   - Messaging Sender ID
   - App ID
   - Service Account JSON (for server authentication)

### Taxonomy Synchronization

1. Select the taxonomies you want to synchronize with Firestore from the available WordPress taxonomies
2. Choose the ordering field (name, description, or slug) for the terms
3. Select the sort order (ascending or descending)

### Post Type Synchronization

1. Select the post types you want to synchronize with Firestore
2. Choose which fields from each post type to sync (WordPress native fields and ACF custom fields)
3. Define the mapping of WordPress post type fields to Firestore document fields

### Firestore Emulator Settings (Development Only)

For development and testing, you can configure the plugin to use a local Firestore emulator:

1. Enable the "Firestore Emulator" option
2. Set the emulator host (default: localhost) and port (default: 8080)
3. The plugin will bypass authentication and connect directly to the local emulator instance

## Usage

### Manual Synchronization

1. Go to the SyncFire Dashboard in your WordPress admin
2. Click "Sync All" to synchronize all configured post types and taxonomies
3. Alternatively, select a specific taxonomy from the dropdown and click "Sync Selected Taxonomy"

### Automatic Synchronization

The plugin automatically synchronizes changes to post types and taxonomies in real-time when:

- A post is created, updated, or deleted
- A post's status changes (e.g., from draft to published)
- A taxonomy term is created, updated, or deleted
- Post meta data or ACF fields are updated
- Post featured images are changed

### Testing Connection

You can test your Firestore connection directly from the admin interface to verify that your credentials are correct and that your WordPress installation can communicate with Firestore.

## Architecture

### Core Components

The plugin follows a modular architecture with the following main components:

- `sync-fire.php` - Main plugin file that initializes the SyncFire class and handles plugin lifecycle
- `includes/class-syncfire-firestore.php` - Handles communication with Google Firestore API
- `includes/class-syncfire-taxonomy-sync.php` - Manages taxonomy synchronization
- `includes/class-syncfire-post-type-sync.php` - Manages post type synchronization  
- `includes/class-syncfire-options.php` - Handles plugin settings and options
- `includes/class-syncfire-admin.php` - Manages admin interface
- `includes/class-syncfire-hooks.php` - Handles WordPress hooks and actions
- `includes/class-syncfire-migration.php` - Handles database migration
- `includes/syncfire-functions.php` - Utility functions

### Data Flow

1. WordPress triggers hooks on content changes (posts, taxonomies)
2. SyncFire hooks into these events via `SyncFire_Hooks` class
3. Changed data is processed by `SyncFire_Post_Type_Sync` or `SyncFire_Taxonomy_Sync`
4. Data is formatted appropriately and sent to Firestore via `SyncFire_Firestore`
5. Firestore receives and stores the data in its document structure

## Data Structure

### WordPress Database Options

SyncFire stores configuration in WordPress options table with the following prefixes:

- `syncfire_firebase_*` - Firebase configuration settings
- `syncfire_taxonomies_to_sync` - Taxonomies to synchronize
- `syncfire_post_types_to_sync` - Post types to synchronize
- `syncfire_post_type_fields` - Fields to synchronize for post types
- `syncfire_post_type_field_mapping` - Field mapping between WordPress and Firestore

### Firestore Collections

#### Taxonomies Collection

Path: `/taxonomies/{taxonomy_name}`

Structure:
```json
{
  "taxonomy": "taxonomy_name",
  "terms": [
    {
      "term_id": 123,
      "name": "Term Name",
      "slug": "term-slug",
      "description": "Term description",
      "parent": 0,
      "count": 5,
      "meta": {
        "custom_field": "value"
      }
    }
  ]
}
```

#### Posts Collection

Path: `/posts/{post_type}/items/{post_id}`

Structure based on configured fields and mappings:
```json
{
  "ID": 123,
  "post_title": "Post Title",
  "post_content": "Post content",
  "post_excerpt": "Post excerpt",
  "post_date": "2023-01-01 12:00:00",
  "post_status": "publish",
  "custom_field": "Custom field value",
  "featured_image": {
    "id": 456,
    "url": "https://example.com/image.jpg",
    "width": 800,
    "height": 600
  },
  "categories": [
    {
      "term_id": 789,
      "name": "Category Name",
      "slug": "category-slug"
    }
  ]
}
```

## Firestore Connection Modes

SyncFire supports three connection modes to Firestore:

### 1. Production gRPC Mode (Recommended)
- Uses the gRPC protocol for optimal performance and efficiency
- Requires the PHP gRPC extension to be installed on your server
- Provides the best performance for production environments

### 2. Production REST Mode (Fallback)
- Automatically used when the gRPC extension is not available
- Uses standard HTTP REST calls to communicate with production Firestore
- Provides wider compatibility at the cost of some performance

### 3. Emulator Mode (Development)
- Connects directly to a local Firestore emulator instance
- Bypasses authentication for development and testing
- Allows safe testing without affecting production data

The plugin automatically detects which mode to use based on your server configuration and settings - no manual setup required!

## Development

### Setting Up Local Development Environment

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/syncfire-wp.git
   cd syncfire-wp
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Use the Firestore emulator for development:
   ```bash
   firebase setup:emulators:firestore
   firebase emulators:start --only firestore
   ```

4. Enable emulator mode in the plugin settings

### Available Scripts

- `update-plugin.sh` - Deploy changes to a WordPress installation for testing
- `package-plugin.sh` - Create a distributable plugin package for release

### Development Workflow

1. Make changes to the code
2. Test locally with the update script
3. Use the Firestore emulator for testing without affecting production data
4. Package the plugin for distribution when ready

## Troubleshooting

### Common Issues

#### Connection Problems
- Verify your Firebase credentials are correct
- Check that your WordPress server can access Google Firestore APIs
- For gRPC mode, ensure the PHP gRPC extension is installed

#### Sync Issues
- Check that the appropriate post types and taxonomies are selected for sync
- Verify field mappings are correctly configured
- Check WordPress error logs for any sync errors

#### Emulator Mode
- Ensure the Firestore emulator is running when using emulator mode
- Verify emulator host and port settings match your emulator configuration
- Remember to disable emulator mode when switching to production

### Debugging

- Check WordPress error logs for any PHP errors or warnings
- Use the test connection feature to verify Firestore connectivity
- Enable WordPress debug mode to get more detailed error information

## Security

- Only users with administrator permissions can access the configuration interface
- Firebase API keys and other sensitive configurations are stored securely in the WordPress database
- The plugin sanitizes all user inputs and validates data before processing
- Connection to Firestore uses secure HTTPS/TLS protocols
- When using emulator mode, authentication is bypassed intentionally for development (only use in secure development environments)

## Support

For support or feature requests, please:

1. Check the troubleshooting section above for common issues
2. Review the documentation in the `docs/` directory
3. Submit an issue on the GitHub repository if you encounter a bug
4. Contact the plugin developer for specific inquiries

## License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This plugin is under active development and may be updated regularly with new features and improvements.