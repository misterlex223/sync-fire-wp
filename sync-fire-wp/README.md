# SyncFire - WordPress to Firestore Synchronization

SyncFire is a WordPress plugin that synchronizes specified taxonomies and post types with Google Firestore, providing real-time data synchronization between your WordPress site and Firebase applications.

## Features

- **Taxonomy Synchronization**: Sync specified taxonomies to Firestore with order control.
- **Post Type Synchronization**: Sync post type contents to Firestore, allowing selection of fields and configurable Firestore document mappings.
- **Real-time Synchronization**: Update Firestore concurrently when post types and taxonomies are updated in WordPress.
- **Admin Configuration Interface**: A secure backend interface for configuration and management of sync settings.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Google Firebase account with Firestore enabled

## Installation

1. Upload the `sync-fire-wp` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings in the SyncFire menu

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

### Taxonomy Synchronization

1. Select the taxonomies you want to synchronize with Firestore
2. Choose the ordering field (name, description, or slug)
3. Select the sort order (ascending or descending)

### Post Type Synchronization

1. Select the post types you want to synchronize with Firestore
2. Choose which fields from each post type to sync
3. Define the mapping of WordPress post type fields to Firestore document fields

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

## Firestore Data Structure

## Technical Requirements

### Firestore Connection Modes

SyncFire supports two connection modes to Firestore:

1. **gRPC Mode** (Recommended): Uses the gRPC protocol for optimal performance and efficiency when communicating with Firestore. This mode requires the PHP gRPC extension to be installed on your server.

2. **REST Mode** (Fallback): Automatically used when the gRPC extension is not available. This mode uses standard HTTP REST calls to communicate with Firestore, providing wider compatibility at the cost of some performance.

The plugin automatically detects which mode to use based on your server configuration - no manual setup required!

### PHP Requirements

- PHP 7.4 or higher
- For gRPC Mode: PHP gRPC extension (`ext-grpc`)
- Composer dependencies are included in the plugin

### Installing gRPC Extension (Optional)

To enable the more efficient gRPC mode:

**For shared hosting:**
Contact your hosting provider to request the gRPC extension installation.

**For self-managed servers:**
```bash
pecl install grpc
docker-php-ext-enable grpc  # For Docker environments
```

### Taxonomies

Taxonomies are stored in Firestore with the following structure:

```
/taxonomies/{taxonomy_name}
  - taxonomy: string (taxonomy name)
  - terms: array
    - term_id: number
    - name: string
    - slug: string
    - description: string
    - parent: number
    - count: number
    - meta: map (custom fields)
```

### Post Types

Post types are stored in Firestore with the following structure:

```
/posts/{post_type}/items/{post_id}
  - [selected fields based on configuration]
```

## Security

- Only users with administrator permissions can access the configuration interface
- Firebase API keys and other sensitive configurations are stored securely

## Support

For support or feature requests, please contact the plugin developer.

## License

This plugin is licensed under the GPL v2 or later.
