# AI README - SyncFire

This guide is designed specifically for AI assistants to help with the usage of SyncFire, a WordPress plugin that synchronizes content to Google Firestore in real-time.

## 🚀 Overview

SyncFire automatically synchronizes WordPress taxonomies and post types to Google Firestore. It works seamlessly with the WordPress Schema System (ignis-schema-wp) for complete data management from schema definition to cloud synchronization.

## ⚡ Quick Start

```bash
# 1. Configure Firebase
wp syncfire config --project-id=my-project --service-account=/path/to/key.json

# 2. Enable sync for content
wp syncfire taxonomy enable category post_tag
wp syncfire post-type enable post --fields=title,content,excerpt,acf

# 3. Test connection
wp syncfire test --verbose

# 4. Sync data
wp syncfire taxonomy sync --all
wp syncfire post-type sync --all

# 5. Verify
wp syncfire stats
```

## 🔧 Configuration

### Firebase Configuration

```bash
# Production setup with service account
wp syncfire config \
  --project-id=my-firebase-project \
  --service-account=/path/to/service-account.json

# Production with specific database
wp syncfire config \
  --project-id=my-firebase-project \
  --database-id=production \
  --service-account=/path/to/service-account.json

# Development with emulator
wp syncfire config \
  --project-id=demo-project \
  --emulator \
  --emulator-host=localhost \
  --emulator-port=8080
```

### Check Configuration

```bash
# View current settings
wp syncfire status

# View as JSON (useful for parsing)
wp syncfire status --format=json

# View as YAML
wp syncfire status --format=yaml
```

## 📊 Taxonomy Synchronization

### Enable/Disable Sync

```bash
# Enable specific taxonomies
wp syncfire taxonomy enable category post_tag movie-genre

# Enable all public taxonomies
wp syncfire taxonomy enable --all

# Disable taxonomies
wp syncfire taxonomy disable category post_tag
```

### List and Sync

```bash
# List all taxonomies and their sync status
wp syncfire taxonomy list

# Manually sync specific taxonomies
wp syncfire taxonomy sync category post_tag

# Sync all enabled taxonomies
wp syncfire taxonomy sync --all
```

## 📝 Post Type Synchronization

### Enable/Disable Sync

```bash
# Enable post type with specific fields
wp syncfire post-type enable post --fields=title,content,excerpt,featured_image,acf

# Enable multiple post types
wp syncfire post-type enable post page movie

# Enable all public post types
wp syncfire post-type enable --all

# Disable post types
wp syncfire post-type disable movie
```

### Manage Fields

```bash
# View current fields for a post type
wp syncfire post-type fields movie

# Set fields (replaces existing)
wp syncfire post-type fields movie --set=title,content,acf,featured_image

# Add fields to existing configuration
wp syncfire post-type fields movie --add=custom_field,another_field

# Remove specific fields
wp syncfire post-type fields movie --remove=old_field
```

### List and Sync

```bash
# List all post types and their sync status
wp syncfire post-type list

# Manually sync specific post types
wp syncfire post-type sync movie post

# Sync all enabled post types
wp syncfire post-type sync --all
```

## 🔗 Integration with WordPress Schema System

SyncFire works seamlessly with ignis-schema-wp for complete WordPress data management.

### Complete Workflow

```bash
# 1. Create schema (using ignis-schema-wp)
wp schema create movie --type=post-type \
  --prompt="Movie database with title, director, rating, release date, trailer URL"

wp schema create movie-genre --type=taxonomy \
  --prompt="Movie genres with icons, colors, and descriptions"

# 2. Validate schemas
wp schema validate movie --type=post-type
wp schema validate movie-genre --type=taxonomy

# 3. Register in WordPress
wp schema register --type=all

# 4. Configure SyncFire
wp syncfire config --project-id=my-project --service-account=/path/to/key.json

# 5. Import schemas to SyncFire and enable sync
wp syncfire import post-type movie --auto-sync --sync-now
wp syncfire import taxonomy movie-genre --auto-sync --sync-now

# 6. Generate TypeScript types (optional)
wp schema export_all --type=all --output=./frontend/types

# 7. Verify everything
wp syncfire stats
wp schema list --type=all
```

### Quick Import

```bash
# Import schema and automatically enable sync
wp syncfire import post-type product --auto-sync

# Import, enable sync, AND sync existing data immediately
wp syncfire import taxonomy product-category --auto-sync --sync-now
```

## 🧪 Testing & Diagnostics

### Connection Testing

```bash
# Basic connection test
wp syncfire test

# Verbose output with connection details
wp syncfire test --verbose
```

### Statistics

```bash
# Show comprehensive sync statistics
wp syncfire stats
```

Example output:
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

## 🗄️ Firestore Data Structure

### Taxonomies
**Collection Path**: `/taxonomies/{taxonomy_name}`
- Each taxonomy is stored as a single document
- Contains an array of all terms
- Real-time sync on term create/update/delete

### Post Types
**Collection Path**: `/posts/{post_type}/items/{post_id}`
- Each post is a separate document
- Only configured fields are synced
- Real-time sync on post create/update/delete
- Supports ACF fields, featured images, and meta data

## 🎯 Common Workflows for AI Assistants

### Workflow 1: E-commerce Product Setup

```bash
# Create product schemas
wp schema create product --type=post-type \
  --prompt="E-commerce product with SKU, price, stock quantity, images, and specifications"

wp schema create product-category --type=taxonomy \
  --prompt="Hierarchical product categories with icons and featured status"

wp schema create product-tag --type=taxonomy \
  --prompt="Flat product tags for filtering (sale, featured, new-arrival)"

# Register schemas
wp schema validate product --type=post-type
wp schema validate product-category --type=taxonomy
wp schema validate product-tag --type=taxonomy
wp schema register --type=all

# Configure SyncFire and enable sync
wp syncfire config --project-id=my-store --service-account=/path/to/key.json
wp syncfire import post-type product --auto-sync
wp syncfire import taxonomy product-category --auto-sync --sync-now
wp syncfire import taxonomy product-tag --auto-sync --sync-now

# Generate types for frontend
wp schema export_all --type=all --output=./frontend/types

# Verify
wp syncfire stats
```

### Workflow 2: Blog with Topics

```bash
# Create topic taxonomy
wp schema create topic --type=taxonomy \
  --prompt="Hierarchical blog topics with icons, colors, and related posts"

# Register and sync
wp schema register --type=taxonomy --slug=topic
wp syncfire taxonomy enable topic
wp syncfire taxonomy sync topic

# Enable sync for posts
wp syncfire post-type enable post --fields=title,content,excerpt,featured_image,acf
wp syncfire post-type sync post
```

### Workflow 3: Development to Production

```bash
# Development (emulator)
wp syncfire config --project-id=dev-project --emulator

# Test with emulator
wp syncfire test --verbose
wp syncfire taxonomy sync --all
wp syncfire post-type sync --all

# Switch to production
wp syncfire config \
  --project-id=prod-project \
  --database-id=production \
  --service-account=/path/to/prod-key.json

# Test production connection
wp syncfire test --verbose

# Sync all data to production
wp syncfire taxonomy sync --all
wp syncfire post-type sync --all

# Verify
wp syncfire stats
```

### Workflow 4: Bulk Enable Sync

```bash
# Enable sync for all standard content
wp syncfire taxonomy enable category post_tag
wp syncfire post-type enable post page --fields=title,content,excerpt,featured_image,acf

# Enable custom content
for taxonomy in movie-genre product-category; do
  wp syncfire taxonomy enable "$taxonomy"
done

for post_type in movie product event; do
  wp syncfire post-type enable "$post_type" --fields=title,content,acf
done

# Sync everything
wp syncfire taxonomy sync --all
wp syncfire post-type sync --all
```

## 🔑 Configuration Options

### Database Support

```bash
# Default database
wp syncfire config --project-id=my-project --database-id='(default)'

# Named database
wp syncfire config --project-id=my-project --database-id=production

# Different databases for different environments
wp syncfire config --project-id=my-project --database-id=staging
```

### Emulator Mode

```bash
# Enable emulator (for development)
wp syncfire config --emulator --project-id=demo-project

# Specify emulator host and port
wp syncfire config \
  --emulator \
  --emulator-host=localhost \
  --emulator-port=8080 \
  --project-id=demo-project
```

## 🧠 AI Usage Tips

When working with SyncFire:

1. **Always configure first**: Run `wp syncfire config` before enabling sync
2. **Test connections**: Use `wp syncfire test --verbose` after configuration changes
3. **Start with taxonomies**: Sync taxonomies before post types (they're usually smaller)
4. **Use import command**: Prefer `wp syncfire import` for schema integration
5. **Check status regularly**: Use `wp syncfire status` and `wp syncfire stats`
6. **Field control**: Always specify fields for post types to avoid syncing unnecessary data
7. **Batch operations**: Use `--all` flag for bulk sync operations
8. **Verify operations**: Check `wp syncfire stats` after major changes

## ⚠️ Important Notes

### Requirements

- WordPress 5.0+
- WP-CLI 2.0+ (for CLI commands)
- Firebase project with Firestore enabled
- Service account JSON for production
- PHP 7.4+

### Field Types Supported

Common fields synced for post types:
- `title` - Post title
- `content` - Post content
- `excerpt` - Post excerpt
- `featured_image` - Featured image URL
- `acf` - All ACF fields
- `author` - Author information
- `date` - Publication date
- `modified` - Last modified date
- `slug` - Post slug
- `status` - Post status

### Real-time Sync

SyncFire automatically syncs on these WordPress events:
- Post create/update/delete
- Post status changes
- Taxonomy term create/update/delete
- Post meta updates
- ACF field updates
- Featured image changes

### Connection Modes

1. **gRPC Mode** (Recommended for production)
   - Best performance
   - Requires PHP gRPC extension
   - Automatically selected if available

2. **REST Mode** (Fallback)
   - Standard HTTPS requests
   - Works without gRPC extension
   - Wider compatibility

3. **Emulator Mode** (Development only)
   - For local testing
   - No authentication required
   - Never use in production

## 🔍 Troubleshooting

```bash
# Check configuration
wp syncfire status --format=json

# Test connection
wp syncfire test --verbose

# List what's enabled
wp syncfire taxonomy list
wp syncfire post-type list

# Check specific post type fields
wp syncfire post-type fields movie

# View statistics
wp syncfire stats

# Manual sync (if auto-sync isn't working)
wp syncfire taxonomy sync category
wp syncfire post-type sync post
```

### Common Issues

**Connection fails**: Check service account JSON and project ID
**No data syncing**: Verify sync is enabled with `wp syncfire taxonomy list` and `wp syncfire post-type list`
**Missing fields**: Check field configuration with `wp syncfire post-type fields <post-type>`
**Emulator not working**: Ensure Firebase emulator is running and port is correct

## 📚 Additional Resources

- **Main Documentation**: See `README.md`
- **Development Guide**: See `CLAUDE.md`
- **CLI Reference**: See `sync-fire-wp/CLI.md` for complete command documentation
- **Schema Documentation**: See `ignis-schema-wp/AI_README.md` for schema creation
- **Logs**: Check `sync-fire-wp/logs/` for detailed operation logs

---

**Quick Reference Card**

```bash
# Setup
wp syncfire config --project-id=X --service-account=Y
wp syncfire test --verbose

# Enable
wp syncfire taxonomy enable <taxonomies>...
wp syncfire post-type enable <post-types>... --fields=X,Y,Z

# Sync
wp syncfire taxonomy sync --all
wp syncfire post-type sync --all

# Import (from ignis-schema-wp)
wp syncfire import post-type <slug> --auto-sync --sync-now

# Status
wp syncfire status
wp syncfire stats
```
