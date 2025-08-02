<?php
/**
 * Plugin Name: SyncFire
 * Plugin URI: https://example.com/sync-fire
 * Description: WordPress plugin that synchronizes specified taxonomies and post types with Google Firestore.
 * Version: 1.0.0
 * Author: SyncFire Team
 * Author URI: https://example.com
 * Text Domain: sync-fire
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SyncFire
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SYNCFIRE_VERSION', '1.0.0');
define('SYNCFIRE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SYNCFIRE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SYNCFIRE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main SyncFire Plugin Class
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
class SyncFire {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;

    /**
     * Initialize the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        // Load dependencies
        $this->load_dependencies();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Get the singleton instance of this class.
     *
     * @since 1.0.0
     * @return SyncFire
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since 1.0.0
     * @return void
     */
    private function load_dependencies() {
        // Include core files
        require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-loader.php';
        require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-admin.php';
        require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-taxonomy-sync.php';
        require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-post-type-sync.php';
        require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-firestore.php';
    }

    /**
     * Register all hooks related to the plugin functionality.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // 清除快取並重新註冊設定
        add_action('admin_init', array($this, 'clear_options_cache'), 5);

        // 添加設定更新記錄鎖子
        add_action('pre_update_option_syncfire_firebase_api_key', array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_syncfire_firebase_auth_domain', array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_syncfire_firebase_project_id', array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_syncfire_firebase_storage_bucket', array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_syncfire_firebase_messaging_sender_id', array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_syncfire_firebase_app_id', array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_syncfire_firebase_service_account', array($this, 'log_option_update'), 10, 3);

        // 添加設定更新後的鎖子
        add_action('updated_option', array($this, 'log_updated_option'), 10, 3);

        // 添加表單提交調試鎖子
        add_action('admin_init', array($this, 'debug_form_submission'));

        // Plugin activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Add admin menu items.
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __('SyncFire', 'sync-fire'),
            __('SyncFire', 'sync-fire'),
            'manage_options',
            'syncfire',
            array($this, 'display_admin_page'),
            'dashicons-database-sync',
            100
        );

        add_submenu_page(
            'syncfire',
            __('Settings', 'sync-fire'),
            __('Settings', 'sync-fire'),
            'manage_options',
            'syncfire-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Register plugin settings with WordPress Settings API.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_settings() {
        // 所有設定統一使用一個群組 'syncfire_settings'

        // Firebase settings
        register_setting(
            'syncfire_settings',
            'syncfire_firebase_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_firebase_auth_domain',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_firebase_project_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_firebase_storage_bucket',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_firebase_messaging_sender_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_firebase_app_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );

        // Service Account JSON 設定
        register_setting(
            'syncfire_settings',
            'syncfire_firebase_service_account',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => '',
            )
        );

        // Taxonomy sync settings
        register_setting(
            'syncfire_settings',
            'syncfire_taxonomies_to_sync',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_array'),
                'default' => array(),
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_taxonomy_order_field',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_taxonomy_sort_order',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'ASC',
            )
        );

        // Post type sync settings
        register_setting(
            'syncfire_settings',
            'syncfire_post_types_to_sync',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_array'),
                'default' => array(),
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_post_type_fields',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_array'),
                'default' => array(),
            )
        );
        register_setting(
            'syncfire_settings',
            'syncfire_post_type_field_mapping',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_array'),
                'default' => array(),
            )
        );
    }

    /**
     * Display the main admin page.
     *
     * @since 1.0.0
     * @return void
     */
    public function display_admin_page() {
        require_once SYNCFIRE_PLUGIN_DIR . 'admin/partials/syncfire-admin-display.php';
    }

    /**
     * Display the settings page.
     *
     * @since 1.0.0
     * @return void
     */
    public function display_settings_page() {
        require_once SYNCFIRE_PLUGIN_DIR . 'admin/partials/syncfire-settings-display.php';
    }

    /**
     * Plugin activation.
     *
     * @since 1.0.0
     * @return void
     */
    public function activate() {
        // Create necessary database tables or options
        add_option('syncfire_version', SYNCFIRE_VERSION);
    }

    /**
     * Plugin deactivation.
     *
     * @since 1.0.0
     * @return void
     */
    public function deactivate() {
        // Cleanup if needed
    }

    /**
     * 清理和驗證陣列類型的設定值
     *
     * @since 1.0.0
     * @param mixed $input 要清理的輸入值
     * @return array 清理後的陣列
     */
    public function sanitize_array($input) {
        // 如果輸入不是陣列，則將其轉換為陣列
        if (!is_array($input)) {
            if (empty($input)) {
                return array();
            }
            return array($input);
        }

        // 清理陣列中的每個值
        $sanitized_input = array();
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized_input[$key] = $this->sanitize_array($value);
            } else {
                $sanitized_input[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized_input;
    }

    /**
     * 記錄選項更新前的資訊
     *
     * @since 1.0.0
     * @param mixed $value 新的選項值
     * @param mixed $old_value 舊的選項值
     * @param string $option 選項名稱
     * @return mixed 原始值（不修改）
     */
    public function log_option_update($value, $old_value, $option) {
        // 建立日誌檔案
        $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/options_update.log';

        // 確保日誌目錄存在
        if (!file_exists(SYNCFIRE_PLUGIN_DIR . 'logs')) {
            mkdir(SYNCFIRE_PLUGIN_DIR . 'logs', 0755, true);
        }

        // 記錄更新資訊
        $log_message = date('[Y-m-d H:i:s]') . " 嘗試更新選項: {$option}\n";
        $log_message .= "舊值: " . (is_array($old_value) ? json_encode($old_value) : $old_value) . "\n";
        $log_message .= "新值: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        $log_message .= "\n";

        // 寫入日誌
        file_put_contents($log_file, $log_message, FILE_APPEND);

        // 返回原始值，不修改
        return $value;
    }

    /**
     * 記錄選項更新後的資訊
     *
     * @since 1.0.0
     * @param string $option 已更新的選項名稱
     * @param mixed $old_value 舊的選項值
     * @param mixed $value 新的選項值
     */
    public function log_updated_option($option, $old_value, $value) {
        // 只記錄 SyncFire 相關的選項
        if (strpos($option, 'syncfire_') !== 0) {
            return;
        }

        // 建立日誌檔案
        $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/options_updated.log';

        // 確保日誌目錄存在
        if (!file_exists(SYNCFIRE_PLUGIN_DIR . 'logs')) {
            mkdir(SYNCFIRE_PLUGIN_DIR . 'logs', 0755, true);
        }

        // 記錄更新資訊
        $log_message = date('[Y-m-d H:i:s]') . " 選項已更新: {$option}\n";
        $log_message .= "舊值: " . (is_array($old_value) ? json_encode($old_value) : $old_value) . "\n";
        $log_message .= "新值: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        $log_message .= "\n";

        // 寫入日誌
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    /**
     * 清除選項快取並重新註冊設定
     *
     * @since 1.0.0
     * @return void
     */
    /**
     * 調試表單提交數據
     *
     * @since 1.0.0
     * @return void
     */
    public function debug_form_submission() {
        // 只在表單提交後執行
        if (!empty($_POST)) {
            // 建立日誌檔案
            $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/form_submission.log';

            // 確保日誌目錄存在
            if (!file_exists(SYNCFIRE_PLUGIN_DIR . 'logs')) {
                mkdir(SYNCFIRE_PLUGIN_DIR . 'logs', 0755, true);
            }

            // 記錄表單提交的數據
            $log_message = date('[Y-m-d H:i:s]') . " 表單提交數據\n";
            $log_message .= "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
            $log_message .= "HTTP_REFERER: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'N/A') . "\n";
            $log_message .= "POST 數據:\n" . print_r($_POST, true) . "\n";

            // 檢查是否為設定表單提交
            if (isset($_POST['option_page']) && $_POST['option_page'] === 'syncfire_settings') {
                $log_message .= "\n設定表單提交詳細資訊:\n";
                $log_message .= "option_page: " . $_POST['option_page'] . "\n";
                $log_message .= "action: " . (isset($_POST['action']) ? $_POST['action'] : 'N/A') . "\n";

                // 檢查是否包含 Firebase 設定欄位
                $firebase_fields = array(
                    'syncfire_firebase_api_key',
                    'syncfire_firebase_auth_domain',
                    'syncfire_firebase_project_id',
                    'syncfire_firebase_storage_bucket',
                    'syncfire_firebase_messaging_sender_id',
                    'syncfire_firebase_app_id',
                    'syncfire_firebase_service_account'
                );

                foreach ($firebase_fields as $field) {
                    $log_message .= "$field: " . (isset($_POST[$field]) ? $_POST[$field] : 'N/A') . "\n";
                }
            }

            // 寫入日誌
            file_put_contents($log_file, $log_message, FILE_APPEND);
        }
    }

    public function clear_options_cache() {
        // 在記錄檔中記錄清除快取的動作
        $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/options_cache_clear.log';

        // 確保日誌目錄存在
        if (!file_exists(SYNCFIRE_PLUGIN_DIR . 'logs')) {
            mkdir(SYNCFIRE_PLUGIN_DIR . 'logs', 0755, true);
        }

        // 記錄清除快取的動作
        $log_message = date('[Y-m-d H:i:s]') . " 清除選項快取\n";

        // 強制重新讀取已註冊的選項
        $options = array(
            'syncfire_firebase_api_key',
            'syncfire_firebase_auth_domain',
            'syncfire_firebase_project_id',
            'syncfire_firebase_storage_bucket',
            'syncfire_firebase_messaging_sender_id',
            'syncfire_firebase_app_id',
            'syncfire_firebase_service_account',
            'syncfire_taxonomies_to_sync',
            'syncfire_taxonomy_order_field',
            'syncfire_taxonomy_sort_order',
            'syncfire_post_types_to_sync',
            'syncfire_post_type_fields',
            'syncfire_post_type_field_mapping'
        );

        foreach ($options as $option) {
            // 強制重新讀取選項值
            $value = get_option($option, '');
            wp_cache_delete($option, 'options');
            $log_message .= "- 清除選項快取: {$option}\n";

            // 如果選項不存在，則初始化它
            if ($value === false || $value === '') {
                if (strpos($option, '_array') !== false ||
                    in_array($option, array('syncfire_taxonomies_to_sync', 'syncfire_post_types_to_sync', 'syncfire_post_type_fields', 'syncfire_post_type_field_mapping'))) {
                    update_option($option, array());
                    $log_message .= "  - 初始化陣列選項: {$option}\n";
                } else {
                    update_option($option, '');
                    $log_message .= "  - 初始化字串選項: {$option}\n";
                }
            }
        }

        // 寫入日誌
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// Initialize the plugin
function run_syncfire() {
    return SyncFire::get_instance();
}

// Run the plugin
run_syncfire();
