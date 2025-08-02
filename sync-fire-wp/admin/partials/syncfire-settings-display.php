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
                    ?>


                    <!-- 顯示表單提交的目標 -->
                    <div class="notice notice-info is-dismissible">
                        <p><strong>表單提交信息</strong><br>
                        表單將提交到: <?php echo admin_url('options.php'); ?><br>
                        </p>
                    </div>

                    <form method="post" action="<?php echo admin_url('options.php'); ?>" class="syncfire-settings-form">
                        <?php
                        // 顯示設定欄位的隱藏輸入 - 使用常數定義的群組
                        settings_fields(SyncFire_Options::GROUP);

                        // 手動添加隱藏欄位，確保選項頁面正確
                        echo '<input type="hidden" name="option_page" value="syncfire_settings" />';

                        // 顯示隱藏欄位的值
                        $nonce = wp_create_nonce('syncfire_settings-options');
                        echo '<div class="notice notice-info is-dismissible"><p>';
                        echo '<strong>隱藏欄位值</strong><br>';
                        echo 'Nonce: ' . $nonce . '<br>';
                        echo 'Action: ' . 'update' . '<br>';
                        echo 'Option page: ' . SyncFire_Options::GROUP . '<br>';
                        echo '</p></div>';
                        ?>

                        <div class="syncfire-settings-section">
                            <h3><?php _e('Firebase Configuration', 'sync-fire'); ?></h3>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo SyncFire_Options::FIREBASE_API_KEY; ?>"><?php _e('API Key', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <input type="password" name="syncfire_firebase_api_key" id="syncfire_firebase_api_key" class="regular-text" value="<?php echo esc_attr(get_option('syncfire_firebase_api_key', '')); ?>" />
                                        <p class="description"><?php _e('Your Firebase API Key.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo SyncFire_Options::FIREBASE_AUTH_DOMAIN; ?>"><?php _e('Auth Domain', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="syncfire_firebase_auth_domain" id="syncfire_firebase_auth_domain" class="regular-text" value="<?php echo esc_attr(get_option('syncfire_firebase_auth_domain', '')); ?>" />
                                        <p class="description"><?php _e('Your Firebase Auth Domain.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo SyncFire_Options::FIREBASE_PROJECT_ID; ?>"><?php _e('Project ID', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="syncfire_firebase_project_id" id="syncfire_firebase_project_id" class="regular-text" value="<?php echo esc_attr(get_option('syncfire_firebase_project_id', '')); ?>" />
                                        <p class="description"><?php _e('Your Firebase Project ID.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo SyncFire_Options::FIREBASE_STORAGE_BUCKET; ?>"><?php _e('Storage Bucket', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="<?php echo SyncFire_Options::FIREBASE_STORAGE_BUCKET; ?>" id="<?php echo SyncFire_Options::FIREBASE_STORAGE_BUCKET; ?>" value="<?php echo esc_attr(syncfire_get_option(SyncFire_Options::FIREBASE_STORAGE_BUCKET, '')); ?>" class="regular-text" />
                                        <p class="description"><?php _e('Your Firebase Storage Bucket.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID; ?>"><?php _e('Messaging Sender ID', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="<?php echo SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID; ?>" id="<?php echo SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID; ?>" value="<?php echo esc_attr(syncfire_get_option(SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID, '')); ?>" class="regular-text" />
                                        <p class="description"><?php _e('Your Firebase Messaging Sender ID.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo SyncFire_Options::FIREBASE_APP_ID; ?>"><?php _e('App ID', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="<?php echo SyncFire_Options::FIREBASE_APP_ID; ?>" id="<?php echo SyncFire_Options::FIREBASE_APP_ID; ?>" value="<?php echo esc_attr(syncfire_get_option(SyncFire_Options::FIREBASE_APP_ID, '')); ?>" class="regular-text" />
                                        <p class="description"><?php _e('Your Firebase App ID.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr valign="top">
                    <th scope="row">
                        <label for="<?php echo SyncFire_Options::FIREBASE_SERVICE_ACCOUNT; ?>"><?php _e('Service Account JSON', 'sync-fire'); ?></label>
                    </th>
                    <td>
                        <textarea name="<?php echo SyncFire_Options::FIREBASE_SERVICE_ACCOUNT; ?>" id="<?php echo SyncFire_Options::FIREBASE_SERVICE_ACCOUNT; ?>" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(syncfire_get_option(SyncFire_Options::FIREBASE_SERVICE_ACCOUNT, '')); ?></textarea>
                        <p class="description"><?php _e('Your Firebase Service Account JSON. This is used for secure server-to-server authentication with Firestore.', 'sync-fire'); ?></p>
                        <p class="description"><strong><?php _e('Important:', 'sync-fire'); ?></strong> <?php _e('This contains sensitive information. Make sure your wp-config.php file has proper security settings.', 'sync-fire'); ?></p>
                    </td>
                </tr>
                            </table>
                        </div>

                        <div class="syncfire-settings-section">
                            <h3><?php _e('Taxonomy Synchronization', 'sync-fire'); ?></h3>

                            <?php
                            settings_fields('syncfire_taxonomy_settings');
                            ?>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label><?php _e('Taxonomies to Sync', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        $taxonomies = get_taxonomies(array('public' => true), 'objects');
                                        $taxonomies_to_sync = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, array());

                                        foreach ($taxonomies as $taxonomy) {
                                            $checked = in_array($taxonomy->name, $taxonomies_to_sync) ? 'checked' : '';
                                            ?>
                                            <label>
                                                <input type="checkbox" name="<?php echo SyncFire_Options::TAXONOMIES_TO_SYNC; ?>[]" value="<?php echo esc_attr($taxonomy->name); ?>" <?php checked($checked); ?> />
                                                <?php echo esc_html($taxonomy->labels->name); ?>
                                            </label><br>
                                            <?php
                                        }
                                        ?>
                                        <p class="description"><?php _e('Select the taxonomies you want to synchronize with Firestore.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="syncfire_taxonomy_order_field"><?php _e('Order Field', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <select name="syncfire_taxonomy_order_field" id="syncfire_taxonomy_order_field">
                                            <option value="name" <?php selected(get_option('syncfire_taxonomy_order_field', 'name'), 'name'); ?>><?php _e('Name', 'sync-fire'); ?></option>
                                            <option value="slug" <?php selected(get_option('syncfire_taxonomy_order_field', 'name'), 'slug'); ?>><?php _e('Slug', 'sync-fire'); ?></option>
                                            <option value="description" <?php selected(get_option('syncfire_taxonomy_order_field', 'name'), 'description'); ?>><?php _e('Description', 'sync-fire'); ?></option>
                                        </select>
                                        <p class="description"><?php _e('Select the field to use for ordering the taxonomy terms.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="syncfire_taxonomy_sort_order"><?php _e('Sort Order', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <select name="syncfire_taxonomy_sort_order" id="syncfire_taxonomy_sort_order">
                                            <option value="ASC" <?php selected(get_option('syncfire_taxonomy_sort_order', 'ASC'), 'ASC'); ?>><?php _e('Ascending', 'sync-fire'); ?></option>
                                            <option value="DESC" <?php selected(get_option('syncfire_taxonomy_sort_order', 'ASC'), 'DESC'); ?>><?php _e('Descending', 'sync-fire'); ?></option>
                                        </select>
                                        <p class="description"><?php _e('Select the sort order for the taxonomy terms.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="syncfire-settings-section">
                            <h3><?php _e('Post Type Synchronization', 'sync-fire'); ?></h3>

                            <?php
                            settings_fields('syncfire_settings');
                            ?>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label><?php _e('Post Types to Sync', 'sync-fire'); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        $post_types = get_post_types(array('public' => true), 'objects');
                                        $post_types_to_sync = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
                                        // Ensure $selected_post_types is always an array
                                        if (!is_array($selected_post_types)) {
                                            $selected_post_types = array();
                                        }

                                        foreach ($post_types as $post_type) {
                                            // Skip attachments
                                            if ($post_type->name === 'attachment') {
                                                continue;
                                            }

                                            $checked = in_array($post_type->name, $post_types_to_sync) ? 'checked' : '';
                                            ?>
                                            <label>
                                                <input type="checkbox" name="<?php echo SyncFire_Options::POST_TYPES_TO_SYNC; ?>[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked($checked); ?> class="syncfire-post-type-checkbox" data-post-type="<?php echo esc_attr($post_type->name); ?>" />
                                                <?php echo esc_html($post_type->labels->name); ?>
                                            </label><br>
                                            <?php
                                        }
                                        ?>
                                        <p class="description"><?php _e('Select the post types you want to synchronize with Firestore.', 'sync-fire'); ?></p>
                                    </td>
                                </tr>
                            </table>

                            <?php
                            // For each selected post type, show field selection and mapping
                            $post_type_fields = syncfire_get_option(SyncFire_Options::POST_TYPE_FIELDS, array());
                            if (!is_array($post_type_fields)) {
                                $post_type_fields = array();
                            }
                            $post_type_field_mapping = syncfire_get_option(SyncFire_Options::POST_TYPE_FIELD_MAPPING, array());
                            if (!is_array($post_type_field_mapping)) {
                                $post_type_field_mapping = array();
                            }

                            foreach ($post_types_to_sync as $post_type) {
                                $post_type_obj = get_post_type_object($post_type);

                                if (!$post_type_obj) {
                                    continue;
                                }

                                $fields = isset($post_type_fields[$post_type]) ? $post_type_fields[$post_type] : array();
                                $field_mapping = isset($post_type_field_mapping[$post_type]) ? $post_type_field_mapping[$post_type] : array();
                                ?>
                                <div class="syncfire-post-type-fields" id="syncfire-post-type-fields-<?php echo esc_attr($post_type); ?>">
                                    <h4><?php echo esc_html($post_type_obj->labels->name); ?> <?php _e('Fields', 'sync-fire'); ?></h4>

                                    <table class="form-table">
                                        <tr>
                                            <th scope="row">
                                                <label><?php _e('Fields to Sync', 'sync-fire'); ?></label>
                                            </th>
                                            <td>
                                                <?php
                                                // Common post fields
                                                $common_fields = array(
                                                    'ID' => __('ID', 'sync-fire'),
                                                    'post_title' => __('Title', 'sync-fire'),
                                                    'post_content' => __('Content', 'sync-fire'),
                                                    'post_excerpt' => __('Excerpt', 'sync-fire'),
                                                    'post_name' => __('Slug', 'sync-fire'),
                                                    'post_date' => __('Date', 'sync-fire'),
                                                    'post_modified' => __('Modified Date', 'sync-fire'),
                                                    'post_author' => __('Author', 'sync-fire'),
                                                    'post_status' => __('Status', 'sync-fire'),
                                                    'featured_image' => __('Featured Image', 'sync-fire'),
                                                );

                                                foreach ($common_fields as $field => $label) {
                                                    $checked = in_array($field, $fields) ? 'checked' : '';
                                                    ?>
                                                    <label>
                                                        <input type="checkbox" name="syncfire_post_type_fields[<?php echo esc_attr($post_type); ?>][]" value="<?php echo esc_attr($field); ?>" <?php echo $checked; ?> class="syncfire-field-checkbox" data-post-type="<?php echo esc_attr($post_type); ?>" data-field="<?php echo esc_attr($field); ?>" />
                                                        <?php echo esc_html($label); ?>
                                                    </label><br>
                                                    <?php
                                                }

                                                // Taxonomies associated with this post type
                                                $taxonomies = get_object_taxonomies($post_type, 'objects');

                                                if (!empty($taxonomies)) {
                                                    echo '<h5>' . __('Taxonomies', 'sync-fire') . '</h5>';

                                                    foreach ($taxonomies as $taxonomy) {
                                                        $field = 'tax_' . $taxonomy->name;
                                                        $checked = in_array($field, $fields) ? 'checked' : '';
                                                        ?>
                                                        <label>
                                                            <input type="checkbox" name="syncfire_post_type_fields[<?php echo esc_attr($post_type); ?>][]" value="<?php echo esc_attr($field); ?>" <?php echo $checked; ?> class="syncfire-field-checkbox" data-post-type="<?php echo esc_attr($post_type); ?>" data-field="<?php echo esc_attr($field); ?>" />
                                                            <?php echo esc_html($taxonomy->labels->name); ?>
                                                        </label><br>
                                                        <?php
                                                    }
                                                }

                                                // Custom fields (meta)
                                                // In a real implementation, you would get the custom fields for this post type
                                                // For simplicity, we'll just add a note
                                                ?>
                                                <h5><?php _e('Custom Fields', 'sync-fire'); ?></h5>
                                                <p><?php _e('Custom fields will be detected automatically when posts are synchronized.', 'sync-fire'); ?></p>
                                            </td>
                                        </tr>
                                    </table>

                                    <h4><?php _e('Field Mapping', 'sync-fire'); ?></h4>
                                    <p><?php _e('Map WordPress fields to Firestore document fields.', 'sync-fire'); ?></p>

                                    <table class="form-table syncfire-field-mapping" id="syncfire-field-mapping-<?php echo esc_attr($post_type); ?>">
                                        <tr>
                                            <th><?php _e('WordPress Field', 'sync-fire'); ?></th>
                                            <th><?php _e('Firestore Field', 'sync-fire'); ?></th>
                                        </tr>
                                        <?php
                                        foreach ($fields as $field) {
                                            $firestore_field = isset($field_mapping[$field]) ? $field_mapping[$field] : $field;
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html($field); ?></td>
                                                <td>
                                                    <input type="text" name="<?php echo SyncFire_Options::POST_TYPE_FIELD_MAPPING; ?>[<?php echo esc_attr($post_type); ?>][<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($firestore_field); ?>" class="regular-text" />
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </table>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                        <?php
                        // 顯示表單提交按鈕並添加調試信息
                        submit_button(__('Save Settings', 'sync-fire'));

                        // 添加一個手動導入選項的按鈕，用於測試
                        ?>
                    </form>

                    <div class="syncfire-debug-section" style="margin-top: 30px; padding: 15px; background: #f8f8f8; border: 1px solid #ddd;">
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
            </div>
        </div>
    </div>
</div>
