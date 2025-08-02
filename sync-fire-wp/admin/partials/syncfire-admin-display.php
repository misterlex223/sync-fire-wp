<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/admin/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="syncfire-admin-container">
        <div class="syncfire-admin-header">
            <h2><?php _e('SyncFire - WordPress to Firestore Synchronization', 'sync-fire'); ?></h2>
            <p><?php _e('Synchronize your WordPress taxonomies and post types with Google Firestore.', 'sync-fire'); ?></p>
        </div>
        
        <div class="syncfire-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="?page=syncfire" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'sync-fire'); ?></a>
                <a href="?page=syncfire-settings" class="nav-tab"><?php _e('Settings', 'sync-fire'); ?></a>
            </nav>
            
            <div class="tab-content">
                <div class="syncfire-dashboard">
                    <div class="syncfire-card">
                        <h3><?php _e('Firebase Connection Status', 'sync-fire'); ?></h3>
                        <div class="syncfire-connection-status">
                            <?php
                            $firestore = new SyncFire_Firestore();
                            $connection_status = $firestore->test_connection();
                            
                            if ($connection_status) {
                                echo '<div class="syncfire-status-success">';
                                echo '<span class="dashicons dashicons-yes-alt"></span>';
                                echo '<span>' . __('Connected to Firestore', 'sync-fire') . '</span>';
                                echo '</div>';
                            } else {
                                echo '<div class="syncfire-status-error">';
                                echo '<span class="dashicons dashicons-warning"></span>';
                                echo '<span>' . __('Not connected to Firestore', 'sync-fire') . '</span>';
                                echo '</div>';
                                echo '<p>' . __('Please check your Firebase configuration in the Settings tab.', 'sync-fire') . '</p>';
                            }
                            ?>
                            <button id="syncfire-test-connection" class="button button-secondary">
                                <?php _e('Test Connection', 'sync-fire'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="syncfire-card">
                        <h3><?php _e('Synchronization Actions', 'sync-fire'); ?></h3>
                        <div class="syncfire-actions">
                            <div class="syncfire-action">
                                <h4><?php _e('Sync All Post Types', 'sync-fire'); ?></h4>
                                <p><?php _e('Synchronize all configured post types and their related taxonomies to Firestore.', 'sync-fire'); ?></p>
                                <button id="syncfire-sync-all" class="button button-primary">
                                    <?php _e('Sync All', 'sync-fire'); ?>
                                </button>
                            </div>
                            
                            <div class="syncfire-action">
                                <h4><?php _e('Sync Taxonomies', 'sync-fire'); ?></h4>
                                <p><?php _e('Synchronize selected taxonomies to Firestore.', 'sync-fire'); ?></p>
                                <?php
                                $taxonomies_to_sync = get_option('syncfire_taxonomies_to_sync', array());
                                
                                if (empty($taxonomies_to_sync)) {
                                    echo '<p>' . __('No taxonomies configured for synchronization.', 'sync-fire') . '</p>';
                                } else {
                                    echo '<select id="syncfire-taxonomy-select">';
                                    foreach ($taxonomies_to_sync as $taxonomy) {
                                        $tax_obj = get_taxonomy($taxonomy);
                                        $label = $tax_obj ? $tax_obj->labels->name : $taxonomy;
                                        echo '<option value="' . esc_attr($taxonomy) . '">' . esc_html($label) . '</option>';
                                    }
                                    echo '</select>';
                                    echo '<button id="syncfire-sync-taxonomy" class="button button-secondary">';
                                    echo __('Sync Selected Taxonomy', 'sync-fire');
                                    echo '</button>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="syncfire-card">
                        <h3><?php _e('Synchronization Status', 'sync-fire'); ?></h3>
                        <div id="syncfire-sync-status">
                            <p><?php _e('No synchronization has been performed yet.', 'sync-fire'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
