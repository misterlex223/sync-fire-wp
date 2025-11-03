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
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-syncfire-options.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/syncfire-functions.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-syncfire-settings-test.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-syncfire-migration.php';
        require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-hooks.php';
    }

    /**
     * Register all hooks related to the plugin functionality.
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks() {
        // 初始化 SyncFire_Hooks 類，這將處理所有 admin JS/CSS 和 AJAX 請求
        new SyncFire_Hooks();

        // Admin hooks - only register settings, menu is handled by SyncFire_Admin class
        add_action('admin_init', array($this, 'register_settings'));

        // Initialize Google Maps API key integration with ACF
        syncfire_integrate_google_maps_api_key();

        // 清除快取並重新註冊設定
        add_action('admin_init', array($this, 'clear_options_cache'), 5);

        // 添加設定更新記錄鎖子
        add_action('pre_update_option_' . SyncFire_Options::FIREBASE_API_KEY, array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_' . SyncFire_Options::FIREBASE_AUTH_DOMAIN, array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_' . SyncFire_Options::FIREBASE_PROJECT_ID, array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_' . SyncFire_Options::FIREBASE_STORAGE_BUCKET, array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_' . SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID, array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_' . SyncFire_Options::FIREBASE_APP_ID, array($this, 'log_option_update'), 10, 3);
        add_action('pre_update_option_' . SyncFire_Options::FIREBASE_SERVICE_ACCOUNT, array($this, 'log_option_update'), 10, 3);

        // 添加設定更新後的鎖子
        add_action('updated_option', array($this, 'log_updated_option'), 10, 3);

        // 添加表單提交調試鎖子
        add_action('admin_init', array($this, 'debug_form_submission'));

        // 添加設定測試鎖子
        add_action('admin_init', 'syncfire_test_settings', 999);

        // 添加表單提交記錄鎖子
        add_action('admin_init', 'syncfire_log_form_submission');

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
        // Firebase settings
        syncfire_register_option(
            SyncFire_Options::FIREBASE_API_KEY,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        syncfire_register_option(
            SyncFire_Options::FIREBASE_AUTH_DOMAIN,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        syncfire_register_option(
            SyncFire_Options::FIREBASE_PROJECT_ID,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        syncfire_register_option(
            SyncFire_Options::FIREBASE_STORAGE_BUCKET,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        syncfire_register_option(
            SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        syncfire_register_option(
            SyncFire_Options::FIREBASE_APP_ID,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );

        // Service Account JSON 設定
        syncfire_register_option(
            SyncFire_Options::FIREBASE_SERVICE_ACCOUNT,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
                'default' => '',
            )
        );

        // Firestore emulator settings
        syncfire_register_option(
            SyncFire_Options::FIRESTORE_EMULATOR_ENABLED,
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'syncfire_sanitize_boolean',
                'default' => false,
            )
        );
        syncfire_register_option(
            SyncFire_Options::FIRESTORE_EMULATOR_HOST,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'localhost',
            )
        );
        syncfire_register_option(
            SyncFire_Options::FIRESTORE_EMULATOR_PORT,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '8080',
            )
        );

        // Taxonomy sync settings
        syncfire_register_option(
            SyncFire_Options::TAXONOMIES_TO_SYNC,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
                'default' => array(),
            )
        );
        syncfire_register_option(
            SyncFire_Options::TAXONOMY_ORDER_FIELD,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        syncfire_register_option(
            SyncFire_Options::TAXONOMY_SORT_ORDER,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'ASC',
            )
        );

        // Post type sync settings
        syncfire_register_option(
            SyncFire_Options::POST_TYPES_TO_SYNC,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
                'default' => array(),
            )
        );
        syncfire_register_option(
            SyncFire_Options::POST_TYPE_FIELDS,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
                'default' => array(),
            )
        );
        syncfire_register_option(
            SyncFire_Options::POST_TYPE_FIELD_MAPPING,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
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
        if (empty($_POST) || !isset($_POST['option_page'])) {
            return;
        }
        if ($_POST['option_page'] !== SyncFire_Options::GROUP) {
            return;
        }

        // Use the helper function to log form submission
        syncfire_log_form_submission($_POST);

        // Additional debug logging for specific option changes
        $this->log_option_changes();
    }

    /**
     * Log changes to options by comparing POST data with current option values
     *
     * @since 1.0.0
     * @return void
     */
    /**
     * Check if an array contains nested arrays
     *
     * @since 1.0.0
     * @param array $array The array to check
     * @return bool True if the array contains nested arrays, false otherwise
     */
    private function has_nested_array($array) {
        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $value) {
            if (is_array($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log changes to options by comparing POST data with current option values
     *
     * @since 1.0.0
     * @return void
     */
    private function log_option_changes() {
        $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/option_changes.log';
        $log_dir = dirname($log_file);

        // Ensure log directory exists
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $log_message = date('[Y-m-d H:i:s]') . " Option Changes\n";
        $changes_detected = false;

        // Get all option names from the Options class
        $all_options = SyncFire_Options::get_all_options();

        // Check each option for changes
        foreach ($all_options as $option_name) {
            if (isset($_POST[$option_name])) {
                $current_value = syncfire_get_option($option_name, '');
                $new_value = $_POST[$option_name];

                // Handle array values
                if (is_array($new_value)) {
                    // Handle nested arrays (like post type field mappings)
                    if ($this->has_nested_array($new_value)) {
                        $log_message .= "$option_name: Changed (contains nested array structure)\n";
                        $changes_detected = true;
                    } else {
                        $new_value_str = implode(', ', $new_value);
                        $current_value_str = is_array($current_value) ? implode(', ', $current_value) : $current_value;

                        if ($new_value_str !== $current_value_str) {
                            $log_message .= "$option_name: Changed from [$current_value_str] to [$new_value_str]\n";
                            $changes_detected = true;
                        }
                    }
                } else {
                    // For sensitive options, just log that it was changed, not the actual value
                    if (in_array($option_name, array(
                        SyncFire_Options::FIREBASE_API_KEY,
                        SyncFire_Options::FIREBASE_APP_ID,
                        SyncFire_Options::FIREBASE_SERVICE_ACCOUNT
                    ))) {
                        if ($new_value !== $current_value) {
                            $log_message .= "$option_name: Changed (sensitive value)\n";
                            $changes_detected = true;
                        }
                    } else {
                        // For non-sensitive options, log the actual values
                        if ($new_value !== $current_value) {
                            $log_message .= "$option_name: Changed from [$current_value] to [$new_value]\n";
                            $changes_detected = true;
                        }
                    }
                }
            }
        }

        // Only write to log if changes were detected
        if ($changes_detected) {
            $log_message .= "\n--------------------------------------------------\n";
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
        $options = SyncFire_Options::get_all_options();

        foreach ($options as $option) {
            // 強制重新讀取選項值
            $value = get_option($option, '');
            wp_cache_delete($option, 'options');
            $log_message .= "- 清除選項快取: {$option}\n";

            // 如果選項不存在，則初始化它
            if ($value === false || $value === '') {
                if (in_array($option, SyncFire_Options::get_array_options())) {
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
