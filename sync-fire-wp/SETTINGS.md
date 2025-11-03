# SyncFire Plugin Settings Structure

## Options Group
- `SyncFire_Options::GROUP` (`syncfire_settings`): Main settings group

## Option Names

### Firebase Settings
- `SyncFire_Options::FIREBASE_API_KEY` (`syncfire_firebase_api_key`): Firebase API Key
- `SyncFire_Options::FIREBASE_AUTH_DOMAIN` (`syncfire_firebase_auth_domain`): Firebase Auth Domain
- `SyncFire_Options::FIREBASE_PROJECT_ID` (`syncfire_firebase_project_id`): Firebase Project ID
- `SyncFire_Options::FIREBASE_STORAGE_BUCKET` (`syncfire_firebase_storage_bucket`): Firebase Storage Bucket
- `SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID` (`syncfire_firebase_messaging_sender_id`): Firebase Messaging Sender ID
- `SyncFire_Options::FIREBASE_APP_ID` (`syncfire_firebase_app_id`): Firebase App ID
- `SyncFire_Options::FIREBASE_SERVICE_ACCOUNT` (`syncfire_firebase_service_account`): Firebase Service Account JSON

### Firestore Emulator Settings
- `SyncFire_Options::FIRESTORE_EMULATOR_ENABLED` (`syncfire_firestore_emulator_enabled`): Boolean flag to enable/disable emulator mode
- `SyncFire_Options::FIRESTORE_EMULATOR_HOST` (`syncfire_firestore_emulator_host`): Host address of the Firestore emulator (default: localhost)
- `SyncFire_Options::FIRESTORE_EMULATOR_PORT` (`syncfire_firestore_emulator_port`): Port number of the Firestore emulator (default: 8080)

### Taxonomy Sync Settings
- `SyncFire_Options::TAXONOMIES_TO_SYNC` (`syncfire_taxonomies_to_sync`): List of taxonomies to synchronize with Firestore
- `SyncFire_Options::TAXONOMY_ORDER_FIELD` (`syncfire_taxonomy_order_field`): Field to use for ordering taxonomies
- `SyncFire_Options::TAXONOMY_SORT_ORDER` (`syncfire_taxonomy_sort_order`): Sort order for taxonomies (ASC/DESC)

### Post Type Sync Settings
- `SyncFire_Options::POST_TYPES_TO_SYNC` (`syncfire_post_types_to_sync`): List of post types to synchronize with Firestore
- `SyncFire_Options::POST_TYPE_FIELDS` (`syncfire_post_type_fields`): Fields to include when syncing post types
- `SyncFire_Options::POST_TYPE_FIELD_MAPPING` (`syncfire_post_type_field_mapping`): Mapping of WordPress fields to Firestore fields

## Form Structure
1. Main settings form: `admin/partials/syncfire-settings-display.php`
   - Uses `settings_fields(SyncFire_Options::GROUP)`
   - Contains all Firebase configuration options
   - Contains taxonomy and post type sync settings

## Helper Functions
- `syncfire_register_option($option_name, $args)`: Register a plugin option
- `syncfire_get_option($option_name, $default = '')`: Get a plugin option value
- `syncfire_update_option($option_name, $value)`: Update a plugin option value
- `syncfire_delete_option($option_name)`: Delete a plugin option

## Troubleshooting
- Check `logs/form_submission.log` for submitted form data
- Verify `option_page` value is correct (should be `syncfire_settings`)
- Ensure all options are correctly registered
- If options are not being saved, check that the form is using the correct option group
- For array options, ensure they are properly sanitized using `syncfire_sanitize_array()`

## Migration
When changing option names or structure, use the migration function to ensure backward compatibility. The migration status is tracked using the `SyncFire_Options::MIGRATION_COMPLETE` option.
