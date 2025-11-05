<?php
/**
 * Define all option constants for the plugin
 *
 * @since      1.0.0
 * @package    SyncFire
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * SyncFire Plugin Options Constants Class
 *
 * @since      1.0.0
 */
class SyncFire_Options {
    // Options group
    const GROUP = 'syncfire_settings';
    const SYNC_SETTINGS = 'syncfire_sync_settings';
    // Firebase settings
    const FIREBASE_API_KEY = 'syncfire_firebase_api_key';
    const FIREBASE_AUTH_DOMAIN = 'syncfire_firebase_auth_domain';
    const FIREBASE_PROJECT_ID = 'syncfire_firebase_project_id';
    const FIREBASE_STORAGE_BUCKET = 'syncfire_firebase_storage_bucket';
    const FIREBASE_MESSAGING_SENDER_ID = 'syncfire_firebase_messaging_sender_id';
    const FIREBASE_APP_ID = 'syncfire_firebase_app_id';
    const FIREBASE_SERVICE_ACCOUNT = 'syncfire_firebase_service_account';
    const FIREBASE_DATABASE_ID = 'syncfire_firebase_database_id';

    // Google MAP API settings
    const GOOGLE_MAP_API_KEY = 'syncfire_google_maps_api_key';

    // Taxonomy sync settings
    const TAXONOMIES_TO_SYNC = 'syncfire_taxonomies_to_sync';
    const TAXONOMY_ORDER_FIELD = 'syncfire_taxonomy_order_field';
    const TAXONOMY_SORT_ORDER = 'syncfire_taxonomy_sort_order';

    // Post type sync settings
    const POST_TYPES_TO_SYNC = 'syncfire_post_types_to_sync';
    const POST_TYPE_FIELDS = 'syncfire_post_type_fields';
    const POST_TYPE_FIELD_MAPPING = 'syncfire_post_type_field_mapping';

    // Firestore emulator settings
    const FIRESTORE_EMULATOR_ENABLED = 'syncfire_firestore_emulator_enabled';
    const FIRESTORE_EMULATOR_HOST = 'syncfire_firestore_emulator_host';
    const FIRESTORE_EMULATOR_PORT = 'syncfire_firestore_emulator_port';

    // Migration tracking
    const MIGRATION_COMPLETE = 'syncfire_migration_complete';

    /**
     * Get an array of all option names
     *
     * @return array All option names
     */
    public static function get_all_options() {
        return [
            self::FIREBASE_API_KEY,
            self::FIREBASE_AUTH_DOMAIN,
            self::FIREBASE_PROJECT_ID,
            self::FIREBASE_STORAGE_BUCKET,
            self::FIREBASE_MESSAGING_SENDER_ID,
            self::FIREBASE_APP_ID,
            self::FIREBASE_SERVICE_ACCOUNT,
            self::FIREBASE_DATABASE_ID,
            self::FIRESTORE_EMULATOR_ENABLED,
            self::FIRESTORE_EMULATOR_HOST,
            self::FIRESTORE_EMULATOR_PORT,
            self::TAXONOMIES_TO_SYNC,
            self::TAXONOMY_ORDER_FIELD,
            self::TAXONOMY_SORT_ORDER,
            self::POST_TYPES_TO_SYNC,
            self::POST_TYPE_FIELDS,
            self::POST_TYPE_FIELD_MAPPING,
        ];
    }

    /**
     * Get an array of all array-type options
     *
     * @return array All array-type option names
     */
    public static function get_array_options() {
        return [
            self::TAXONOMIES_TO_SYNC,
            self::POST_TYPES_TO_SYNC,
            self::POST_TYPE_FIELDS,
            self::POST_TYPE_FIELD_MAPPING,
        ];
    }
}
