<?php
/**
 * Displays the Firebase Configuration section of the SyncFire settings page.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure we have the connection_status variable
if (!isset($connection_status)) {
    $connection_status = false;
}
?>

<!-- Firebase Configuration Section (Collapsible) -->
<div id="syncfire-firebase-config" class="syncfire-settings-section syncfire-collapsible-section">
    <h3><?php _e('Firebase Configuration', 'sync-fire'); ?></h3>
    <div class="syncfire-section-content">

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="<?php echo SyncFire_Options::FIREBASE_API_KEY; ?>"><?php _e('API Key', 'sync-fire'); ?></label>
            </th>
            <td>
                <input type="password" name="<?php echo SyncFire_Options::FIREBASE_API_KEY; ?>" id="<?php echo SyncFire_Options::FIREBASE_API_KEY; ?>" class="regular-text" value="<?php echo esc_attr(get_option(SyncFire_Options::FIREBASE_API_KEY, '')); ?>" />
                <p class="description"><?php _e('Your Firebase API Key.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="<?php echo SyncFire_Options::FIREBASE_AUTH_DOMAIN; ?>"><?php _e('Auth Domain', 'sync-fire'); ?></label>
            </th>
            <td>
                <input type="text" name="<?php echo SyncFire_Options::FIREBASE_AUTH_DOMAIN; ?>" id="<?php echo SyncFire_Options::FIREBASE_AUTH_DOMAIN; ?>" class="regular-text" value="<?php echo esc_attr(get_option(SyncFire_Options::FIREBASE_AUTH_DOMAIN, '')); ?>" />
                <p class="description"><?php _e('Your Firebase Auth Domain.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="<?php echo SyncFire_Options::FIREBASE_PROJECT_ID; ?>"><?php _e('Project ID', 'sync-fire'); ?></label>
            </th>
            <td>
                <input type="text" name="<?php echo SyncFire_Options::FIREBASE_PROJECT_ID; ?>" id="<?php echo SyncFire_Options::FIREBASE_PROJECT_ID; ?>" class="regular-text" value="<?php echo esc_attr(get_option(SyncFire_Options::FIREBASE_PROJECT_ID, '')); ?>" />
                <p class="description"><?php _e('Your Firebase Project ID.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="<?php echo SyncFire_Options::FIREBASE_STORAGE_BUCKET; ?>"><?php _e('Storage Bucket', 'sync-fire'); ?></label>
            </th>
            <td>
                <input type="text" name="<?php echo SyncFire_Options::FIREBASE_STORAGE_BUCKET; ?>" id="<?php echo SyncFire_Options::FIREBASE_STORAGE_BUCKET; ?>" class="regular-text" value="<?php echo esc_attr(get_option(SyncFire_Options::FIREBASE_STORAGE_BUCKET, '')); ?>" />
                <p class="description"><?php _e('Your Firebase Storage Bucket.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="<?php echo SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID; ?>"><?php _e('Messaging Sender ID', 'sync-fire'); ?></label>
            </th>
            <td>
                <input type="text" name="<?php echo SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID; ?>" id="<?php echo SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID; ?>" class="regular-text" value="<?php echo esc_attr(get_option(SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID, '')); ?>" />
                <p class="description"><?php _e('Your Firebase Messaging Sender ID.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="<?php echo SyncFire_Options::FIREBASE_APP_ID; ?>"><?php _e('App ID', 'sync-fire'); ?></label>
            </th>
            <td>
                <input type="text" name="<?php echo SyncFire_Options::FIREBASE_APP_ID; ?>" id="<?php echo SyncFire_Options::FIREBASE_APP_ID; ?>" class="regular-text" value="<?php echo esc_attr(get_option(SyncFire_Options::FIREBASE_APP_ID, '')); ?>" />
                <p class="description"><?php _e('Your Firebase App ID.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label for="<?php echo SyncFire_Options::FIREBASE_SERVICE_ACCOUNT; ?>"><?php _e('Service Account JSON', 'sync-fire'); ?></label>
            </th>
            <td>
                <textarea name="<?php echo SyncFire_Options::FIREBASE_SERVICE_ACCOUNT; ?>" id="<?php echo SyncFire_Options::FIREBASE_SERVICE_ACCOUNT; ?>" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option(SyncFire_Options::FIREBASE_SERVICE_ACCOUNT, '')); ?></textarea>
                <p class="description"><?php _e('Your Firebase Service Account JSON. This is used for secure server-to-server authentication with Firestore.', 'sync-fire'); ?></p>
                <p class="description"><strong><?php _e('Important:', 'sync-fire'); ?></strong> <?php _e('This contains sensitive information. Make sure your wp-config.php file has proper security settings.', 'sync-fire'); ?></p>
            </td>
        </tr>
    </table>
    </div>

    <div id="syncfire-debug-tools" class="syncfire-debug-section syncfire-collapsible-section" style="margin-top: 30px; padding: 15px; background: #f8f8f8; border: 1px solid #ddd;">
        <h3>調試工具</h3>
        <form method="post" action="<?php echo admin_url('admin.php?page=syncfire-settings'); ?>">
            <?php wp_nonce_field('syncfire_debug_action', 'syncfire_debug_nonce'); ?>
            <input type="hidden" name="action" value="syncfire_debug_insert_options">
            <p><input type="submit" name="syncfire_debug_insert" class="button button-secondary" value="手動插入測試選項"></p>
        </form>

        <?php
        // 處理手動插入選項的要求
        if (isset($_POST['syncfire_debug_insert']) && check_admin_referer('syncfire_debug_action', 'syncfire_debug_nonce')) {
            // 插入測試選項
            syncfire_update_option(SyncFire_Options::FIREBASE_API_KEY, 'test_api_key_' . time());
            syncfire_update_option(SyncFire_Options::FIREBASE_AUTH_DOMAIN, 'test-project.firebaseapp.com');
            syncfire_update_option(SyncFire_Options::FIREBASE_PROJECT_ID, 'test-project-' . time());

            echo '<div class="notice notice-success is-dismissible"><p>測試選項已插入。請刷新頁面查看結果。</p></div>';
        }
        ?>
    </div>
</div>
