<?php
/**
 * Provide a admin area view for the plugin settings
 *
 * This file is used to markup the admin-facing settings aspects of the plugin.
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

// Ensure the options class is loaded
if (!class_exists('SyncFire_Options')) {
    require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-options.php';
}

// Load the ACF helper class
if (!class_exists('SyncFire_ACF_Helper')) {
    require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-acf-helper.php';
}

// Load the Firestore class for connection testing
if (!class_exists('SyncFire_Firestore')) {
    require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-firestore.php';
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="syncfire-admin-container">
        <div class="syncfire-admin-header">
            <h2><?php _e('SyncFire Settings', 'sync-fire'); ?></h2>
            <p><?php _e('Configure your Firebase settings and synchronization options.', 'sync-fire'); ?></p>
        </div>

        <div class="syncfire-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="?page=syncfire" class="nav-tab"><?php _e('Dashboard', 'sync-fire'); ?></a>
                <a href="?page=syncfire-settings" class="nav-tab nav-tab-active"><?php _e('Settings', 'sync-fire'); ?></a>
            </nav>

            <div class="tab-content">
                <div class="syncfire-settings">
                    <?php
                    // Initialize Firestore before using it
                    $firestore = new SyncFire_Firestore();
                    $connection_status = $firestore->test_connection();
                    ?>
                    <!-- Hidden field to store connection status -->
                    <input type="hidden" id="syncfire-connection-status-value" value="<?php echo $connection_status ? 'connected' : 'disconnected'; ?>" />

                    <!-- Connection status indicator -->
                    <div class="syncfire-connection-status-indicator">
                        <?php
                        if ($connection_status) {
                            echo '<div class="notice notice-success inline"><p>';
                            echo '<span class="dashicons dashicons-yes-alt"></span> ';
                            echo __('Connected to Firestore. You can now configure synchronization settings.', 'sync-fire');
                            echo '</p></div>';
                        } else {
                            echo '<div class="notice notice-warning inline"><p>';
                            echo '<span class="dashicons dashicons-warning"></span> ';
                            echo __('Not connected to Firestore. Please configure your Firebase settings and test the connection.', 'sync-fire');
                            echo '</p></div>';
                        }
                        ?>
                        <div id="syncfire-sync-status"></div>
                    </div>
                    <!-- <?php
                    // 調試信息：檢查選項值是否存在
                    $api_key = syncfire_get_option(SyncFire_Options::FIREBASE_API_KEY, '');
                    $auth_domain = syncfire_get_option(SyncFire_Options::FIREBASE_AUTH_DOMAIN, '');
                    $project_id = syncfire_get_option(SyncFire_Options::FIREBASE_PROJECT_ID, '');
                    $storage_bucket = syncfire_get_option(SyncFire_Options::FIREBASE_STORAGE_BUCKET, '');
                    $messaging_sender_id = syncfire_get_option(SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID, '');
                    $app_id = syncfire_get_option(SyncFire_Options::FIREBASE_APP_ID, '');

                    // 在頁面頂部顯示詳細的調試信息
                    echo '<div class="notice notice-info is-dismissible"><p>';
                    echo '<strong>調試信息</strong><br>';
                    echo 'API Key: ' . (!empty($api_key) ? '已設定 (' . substr($api_key, 0, 3) . '...)' : '未設定') . '<br>';
                    echo 'Auth Domain: ' . (!empty($auth_domain) ? '已設定 (' . $auth_domain . ')' : '未設定') . '<br>';
                    echo 'Project ID: ' . (!empty($project_id) ? '已設定 (' . $project_id . ')' : '未設定') . '<br>';
                    echo 'Storage Bucket: ' . (!empty($storage_bucket) ? '已設定 (' . $storage_bucket . ')' : '未設定') . '<br>';
                    echo 'Messaging Sender ID: ' . (!empty($messaging_sender_id) ? '已設定 (' . $messaging_sender_id . ')' : '未設定') . '<br>';
                    echo 'App ID: ' . (!empty($app_id) ? '已設定 (' . $app_id . ')' : '未設定') . '<br>';

                    // 顯示選項表的名稱
                    global $wpdb;
                    echo 'Options table: ' . $wpdb->options . '<br>';

                    // 顯示表單提交的數據
                    if (!empty($_POST)) {
                        echo '<strong>表單提交數據</strong><br>';
                        echo '<pre>' . print_r($_POST, true) . '</pre>';
                    }

                    // 檢查當前用戶權限
                    echo '<strong>用戶權限</strong><br>';
                    echo 'Can manage_options: ' . (current_user_can('manage_options') ? 'Yes' : 'No') . '<br>';
                    echo '</p></div>';
                    ?> -->

                    <form method="post" action="<?php echo admin_url('options.php'); ?>" class="syncfire-settings-form">
                        <?php
                        // 使用單獨的設定群組，避免覆蓋 Configuration 頁面的設定
                        settings_fields(SyncFire_Options::SYNC_SETTINGS);

                        // 保存 Firebase 設定的隱藏欄位
                        $firebase_options = array(
                            SyncFire_Options::FIREBASE_API_KEY,
                            SyncFire_Options::FIREBASE_AUTH_DOMAIN,
                            SyncFire_Options::FIREBASE_PROJECT_ID,
                            SyncFire_Options::FIREBASE_STORAGE_BUCKET,
                            SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID,
                            SyncFire_Options::FIREBASE_APP_ID,
                            SyncFire_Options::FIREBASE_SERVICE_ACCOUNT,
                            SyncFire_Options::GOOGLE_MAP_API_KEY
                        );

                        // 添加隱藏欄位，保留現有的 Firebase 設定值
                        foreach ($firebase_options as $option) {
                            $value = get_option($option, '');
                            echo '<input type="hidden" name="' . $option . '" value="' . esc_attr($value) . '" />';
                        }
                        ?>

                        <?php
                        // Include the Taxonomy Synchronization template
                        include_once(plugin_dir_path(__FILE__) . 'syncfire-taxonomy-sync-display.php');
                        ?>

                        <?php
                        // Include the Post Type Synchronization template
                        include_once(plugin_dir_path(__FILE__) . 'syncfire-post-type-sync-display.php');
                        ?>

                        <div style="justify-items: right;">
                            <?php
                            // 顯示表單提交按鈕並添加調試信息
                            submit_button(__('Save Settings', 'sync-fire'));
                            ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
