<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin options when uninstalled
 */
function syncfire_uninstall() {
    // Remove Firebase settings
    delete_option('syncfire_firebase_api_key');
    delete_option('syncfire_firebase_auth_domain');
    delete_option('syncfire_firebase_project_id');
    delete_option('syncfire_firebase_storage_bucket');
    delete_option('syncfire_firebase_messaging_sender_id');
    delete_option('syncfire_firebase_app_id');
    
    // Remove taxonomy sync settings
    delete_option('syncfire_taxonomies_to_sync');
    delete_option('syncfire_taxonomy_order_field');
    delete_option('syncfire_taxonomy_sort_order');
    
    // Remove post type sync settings
    delete_option('syncfire_post_types_to_sync');
    delete_option('syncfire_post_type_fields');
    delete_option('syncfire_post_type_field_mapping');
    
    // Remove version option
    delete_option('syncfire_version');
}

// Run uninstall function
syncfire_uninstall();
