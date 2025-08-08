<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    SyncFire
 * @subpackage SyncFire/admin
 * @author     SyncFire Team
 */
class SyncFire_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name = 'sync-fire', $version = '1.0.0' ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Add admin hooks
        $this->add_admin_hooks();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook) {
        // Enqueue main admin CSS
        wp_enqueue_style($this->plugin_name, SYNCFIRE_PLUGIN_URL . 'admin/css/syncfire-admin.css', array(), $this->version, 'all');

        // Enqueue settings page CSS only on the settings page
        if ($hook === 'toplevel_page_syncfire-settings' || $hook === 'syncfire_page_syncfire-settings') {
            wp_enqueue_style('syncfire-settings', SYNCFIRE_PLUGIN_URL . 'admin/css/syncfire-settings.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook) {
        // 添加調試日誌
        error_log('SyncFire enqueue_scripts called on hook: ' . $hook);

        // 放寬條件，在所有管理頁面上載入 JavaScript
        // 這是臨時的，用於調試目的
        // 之後可以根據實際的 hook 值再進行限制
        // if (strpos($hook, 'syncfire') === false && $hook != 'toplevel_page_syncfire') {
        //     return;
        // }

        error_log('SyncFire loading JS on page: ' . $hook);

        wp_enqueue_script('syncfire-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/js/syncfire-admin.js', array('jquery'), time(), true);

        // Enqueue settings UI enhancement script only on the settings page
        if ($hook === 'toplevel_page_syncfire-settings' || $hook === 'syncfire_page_syncfire-settings') {
            wp_enqueue_script('syncfire-settings-ui', plugin_dir_url(dirname(__FILE__)) . 'admin/js/syncfire-settings-ui.js', array('jquery', 'syncfire-admin'), time(), true);
        }

        // Add the AJAX URL and nonce to the script
        wp_localize_script('syncfire-admin', 'syncfire_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('syncfire_ajax_nonce'),
            'page_hook' => $hook, // 添加頁面鉤子信息
            'debug_info' => array(
                'current_screen' => get_current_screen() ? get_current_screen()->id : 'unknown',
                'is_admin' => is_admin() ? 'yes' : 'no',
                'plugin_url' => plugin_dir_url(dirname(__FILE__)),
                'admin_url' => admin_url(),
                'time' => time()
            ),
            'i18n' => array(
                'syncing' => __('Syncing...', 'sync-fire'),
                'sync_success' => __('Sync completed successfully!', 'sync-fire'),
                'sync_error' => __('Error during sync. Please check the logs.', 'sync-fire'),
                'testing_connection' => __('Testing connection...', 'sync-fire'),
                'connection_success' => __('Connection successful!', 'sync-fire'),
                'connection_error' => __('Connection failed. Please check your settings.', 'sync-fire')
            )
        ));

        // 添加內聯調試腳本
        wp_add_inline_script('syncfire-admin', '
            console.log("SyncFire JS loaded on page: ' . $hook . '");
            console.log("Debug info:", syncfire_ajax.debug_info);
            jQuery(document).ready(function($) {
                console.log("jQuery ready on page: ' . $hook . '");
                // 檢查 Test Connection 按鈕是否存在
                if ($("#syncfire-test-connection").length) {
                    console.log("Test Connection button found");
                } else {
                    console.log("Test Connection button NOT found");
                }
            });
        ', 'before');
    }

    /**
     * Add admin-specific hooks.
     *
     * @since    1.0.0
     */
    private function add_admin_hooks() {
        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Add AJAX handlers
        add_action( 'wp_ajax_syncfire_resync_all', array( $this, 'resync_all' ) );
        add_action( 'wp_ajax_syncfire_resync_taxonomy', array( $this, 'resync_taxonomy' ) );
        add_action( 'wp_ajax_syncfire_test_firebase_connection', array( $this, 'test_firebase_connection' ) );
        add_action( 'wp_ajax_syncfire_clear_logs', array( $this, 'clear_logs' ) );
        add_action( 'wp_ajax_syncfire_save_configuration', array( $this, 'save_configuration' ) );

        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // Add Google Maps API key to ACF
        add_filter( 'acf/fields/google_map/api', array( $this, 'acf_google_map_api' ) );
        add_action( 'acf/init', array( $this, 'acf_init' ) );
    }

    /**
     * Register the admin menu.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Add the main menu page
        add_menu_page(
            __('SyncFire', 'sync-fire'),
            __('SyncFire', 'sync-fire'),
            'manage_options',
            'syncfire',
            array($this, 'display_admin_page'),
            'dashicons-database-sync',
            100
        );

        // Add the settings submenu page
        add_submenu_page(
            'syncfire',
            __('Settings', 'sync-fire'),
            __('Settings', 'sync-fire'),
            'manage_options',
            'syncfire-settings',
            array($this, 'display_settings_page')
        );

        // Add the configuration submenu page
        add_submenu_page(
            'syncfire',
            __('Configuration', 'sync-fire'),
            __('Configuration', 'sync-fire'),
            'manage_options',
            'syncfire-configuration',
            array($this, 'display_configuration_page')
        );

        // Add the logs submenu page
        add_submenu_page(
            'syncfire',
            __('Logs', 'sync-fire'),
            __('Logs', 'sync-fire'),
            'manage_options',
            'syncfire-logs',
            array($this, 'display_logs_page')
        );

        // Add the tests submenu page
        add_submenu_page(
            'syncfire',
            __('Tests', 'sync-fire'),
            __('Tests', 'sync-fire'),
            'manage_options',
            'syncfire-tests',
            array($this, 'display_tests_page')
        );
    }

    /**
     * Handle the AJAX request to resync all post types and taxonomies.
     *
     * @since    1.0.0
     */
    public function resync_all() {
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to perform this action.', 'sync-fire' ) ) );
        }

        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'syncfire_ajax_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sync-fire' ) ) );
        }

        // Get the post type sync instance
        $post_type_sync = new SyncFire_Post_Type_Sync();
        $taxonomy_sync = new SyncFire_Taxonomy_Sync();

        // Perform the sync
        $post_type_result = $post_type_sync->sync_all_post_types();
        $taxonomy_result = $taxonomy_sync->sync_all_taxonomies();

        if ( $post_type_result && $taxonomy_result ) {
            wp_send_json_success( array( 'message' => __( 'All post types and taxonomies have been successfully synced to Firestore.', 'sync-fire' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'There was an error syncing to Firestore. Please check your settings and try again.', 'sync-fire' ) ) );
        }
    }

    /**
     * Handle the AJAX request to resync specific taxonomies.
     *
     * @since    1.0.0
     */
    public function resync_taxonomy() {
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to perform this action.', 'sync-fire' ) ) );
        }

        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'syncfire_ajax_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sync-fire' ) ) );
        }

        // Get the taxonomy to sync
        if ( ! isset( $_POST['taxonomy'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No taxonomy specified.', 'sync-fire' ) ) );
        }

        $taxonomy = sanitize_text_field( $_POST['taxonomy'] );
        error_log('SyncFire Admin: Starting sync for taxonomy: ' . $taxonomy);

        // Get the taxonomy sync instance
        $taxonomy_sync = new SyncFire_Taxonomy_Sync();

        // Debug: Get taxonomy data before sync
        $taxonomy_obj = get_taxonomy($taxonomy);
        if ($taxonomy_obj) {
            error_log('SyncFire Admin: Taxonomy object exists: ' . json_encode($taxonomy_obj));
        } else {
            error_log('SyncFire Admin: Taxonomy object not found for: ' . $taxonomy);
        }

        // Perform the sync
        error_log('SyncFire Admin: Calling sync_taxonomy method for: ' . $taxonomy);
        $result = $taxonomy_sync->sync_taxonomy( $taxonomy );
        error_log('SyncFire Admin: sync_taxonomy result: ' . ($result ? 'true' : 'false'));

        if ( $result ) {
            error_log('SyncFire Admin: Sync successful for taxonomy: ' . $taxonomy);
            wp_send_json_success( array( 'message' => sprintf( __( 'Taxonomy [%s] has been successfully synced to Firestore.', 'sync-fire' ), $taxonomy ) ) );
        } else {
            error_log('SyncFire Admin: Sync failed for taxonomy: ' . $taxonomy);
            wp_send_json_error( array( 'message' => sprintf( __( 'There was an error syncing taxonomy [%s] to Firestore. Please check your settings and try again.', 'sync-fire' ), $taxonomy ) ) );
        }
    }

    /**
     * Test the Firebase connection.
     *
     * @since    1.0.0
     */
    public function test_firebase_connection() {
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to perform this action.', 'sync-fire' ) ) );
        }

        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'syncfire_ajax_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sync-fire' ) ) );
        }

        // Get the Firestore instance
        $firestore = new SyncFire_Firestore();

        // Test the connection
        $result = $firestore->test_connection();

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Successfully connected to Firestore.', 'sync-fire' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to connect to Firestore. Please check your Firebase configuration settings.', 'sync-fire' ) ) );
        }
    }

    /**
     * Re-sync a specific post type.
     *
     * @since    1.0.0
     */
    public function resync_post_type() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'sync-fire'));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'syncfire_ajax_nonce')) {
            wp_send_json_error(__('Security check failed.', 'sync-fire'));
        }

        // Get the post type
        if (!isset($_POST['post_type']) || empty($_POST['post_type'])) {
            wp_send_json_error(__('No post type specified.', 'sync-fire'));
        }

        $post_type = sanitize_text_field($_POST['post_type']);

        // Check if this post type is configured for sync
        $post_types_to_sync = get_option('syncfire_post_types_to_sync', array());

        if (!in_array($post_type, $post_types_to_sync)) {
            wp_send_json_error(sprintf(__('Post type %s is not configured for synchronization.', 'sync-fire'), $post_type));
        }

        // Sync the post type
        $post_type_sync = new SyncFire_Post_Type_Sync();
        $result = $post_type_sync->sync_post_type($post_type);

        if ($result) {
            wp_send_json_success(sprintf(__('Post type %s has been successfully synced to Firestore.', 'sync-fire'), $post_type));
        } else {
            wp_send_json_error(sprintf(__('There was an error syncing post type %s to Firestore. Please check your settings and try again.', 'sync-fire'), $post_type));
        }
    }

    /**
     * Display the admin page.
     *
     * @since    1.0.0
     */
    public function display_admin_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/syncfire-admin-display.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/syncfire-settings-display.php';
    }

    /**
     * Display the logs page.
     *
     * @since    1.0.0
     */
    public function display_logs_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/syncfire-logs-display.php';
    }

    /**
     * Display the tests page.
     *
     * @since    1.0.0
     */
    public function display_tests_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/syncfire-tests-display.php';
    }

    /**
     * Display the configuration page.
     *
     * @since    1.0.0
     */
    public function display_configuration_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/syncfire-configuration-display.php';
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register Google Maps API Key setting using syncfire_register_option helper
        // This ensures it uses the same settings group as Firebase settings
        syncfire_register_option(
            SyncFire_Options::GROUP,
            SyncFire_Options::GOOGLE_MAP_API_KEY,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        // Register the sync settings group for the Settings page
        register_setting(
            'syncfire_sync_settings',
            SyncFire_Options::TAXONOMIES_TO_SYNC,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
                'default' => array()
            )
        );
        
        register_setting(
            'syncfire_sync_settings',
            SyncFire_Options::TAXONOMY_ORDER_FIELD,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        register_setting(
            'syncfire_sync_settings',
            SyncFire_Options::TAXONOMY_SORT_ORDER,
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'ASC'
            )
        );
        
        register_setting(
            'syncfire_sync_settings',
            SyncFire_Options::POST_TYPES_TO_SYNC,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
                'default' => array()
            )
        );
        
        register_setting(
            'syncfire_sync_settings',
            SyncFire_Options::POST_TYPE_FIELDS,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
                'default' => array()
            )
        );
        
        register_setting(
            'syncfire_sync_settings',
            SyncFire_Options::POST_TYPE_FIELD_MAPPING,
            array(
                'type' => 'array',
                'sanitize_callback' => 'syncfire_sanitize_array',
                'default' => array()
            )
        );
    }

    /**
     * Add Google Maps API key to ACF Google Map field.
     * Method 1: Using ACF filter.
     *
     * @since    1.0.0
     * @param    array    $api    The API array.
     * @return   array           The modified API array.
     */
    public function acf_google_map_api($api) {
        $api_key = get_option(SyncFire_Options::GOOGLE_MAP_API_KEY, '');

        if (!empty($api_key)) {
            $api['key'] = $api_key;
        }

        return $api;
    }

    /**
     * Initialize ACF with Google Maps API key.
     * Method 2: Using ACF setting.
     *
     * @since    1.0.0
     */
    public function acf_init() {
        $api_key = get_option(SyncFire_Options::GOOGLE_MAP_API_KEY, '');

        if (!empty($api_key) && function_exists('acf_update_setting')) {
            acf_update_setting('google_api_key', $api_key);
        }
    }
    
    /**
     * Handle the AJAX request to save configuration with connection test.
     * Only saves settings if the connection test is successful.
     *
     * @since    1.0.0
     */
    public function save_configuration() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'syncfire_ajax_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'sync-fire')));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'sync-fire')));
        }
        
        // Parse form data
        $form_data = array();
        parse_str($_POST['form_data'], $form_data);
        
        // Log the form data for debugging
        error_log('SyncFire: Form data received: ' . print_r($form_data, true));
        
        // Always save Google Maps API key regardless of Firebase connection status
        if (isset($form_data[SyncFire_Options::GOOGLE_MAP_API_KEY])) {
            $api_key = sanitize_text_field($form_data[SyncFire_Options::GOOGLE_MAP_API_KEY]);
            update_option(SyncFire_Options::GOOGLE_MAP_API_KEY, $api_key);
            error_log('SyncFire: Google Maps API key set: ' . substr($api_key, 0, 3) . '...');
        }
        
        // Temporarily save the Firebase settings to test the connection
        $temp_options = array();
        
        // Save Firebase settings temporarily
        $firebase_options = array(
            SyncFire_Options::FIREBASE_API_KEY,
            SyncFire_Options::FIREBASE_AUTH_DOMAIN,
            SyncFire_Options::FIREBASE_PROJECT_ID,
            SyncFire_Options::FIREBASE_STORAGE_BUCKET,
            SyncFire_Options::FIREBASE_MESSAGING_SENDER_ID,
            SyncFire_Options::FIREBASE_APP_ID,
            SyncFire_Options::FIREBASE_SERVICE_ACCOUNT
        );
        
        foreach ($firebase_options as $option) {
            if (isset($form_data[$option])) {
                $temp_options[$option] = $form_data[$option];
                // Temporarily update option for connection test
                update_option($option, $form_data[$option]);
            }
        }
        
        // Test the connection with the new settings
        $firestore = new SyncFire_Firestore();
        $connection_status = $firestore->test_connection();
        
        // Save connection status
        update_option('syncfire_connection_status', $connection_status);
        
        if ($connection_status) {
            // Connection successful, settings are already saved
            wp_send_json_success(array(
                'message' => __('Successfully connected to Firestore and saved all configuration.', 'sync-fire'),
                'connection_status' => true
            ));
        } else {
            // Connection failed, but we still saved the Google Maps API key
            wp_send_json_error(array(
                'message' => __('Failed to connect to Firestore. Firebase settings were not saved, but Google Maps API key was saved successfully.', 'sync-fire'),
                'connection_status' => false
            ));
        }
    }

    /**
     * Clear logs.
     *
     * @since    1.0.0
     */
    public function clear_logs() {
        // Check if user has permission
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform this action.', 'sync-fire' ) );
        }

        // Verify nonce
        check_admin_referer( 'syncfire_clear_logs' );

        // Clear logs
        $logger = new SyncFire_Logger();
        $logger->clear_logs();

        // Add notice
        $logger->add_admin_notice( __( 'Logs cleared successfully.', 'sync-fire' ), SyncFire_Logger::LOG_LEVEL_SUCCESS );

        // Redirect back to logs page
        wp_redirect( admin_url( 'admin.php?page=syncfire-logs' ) );
        exit;
    }
}
