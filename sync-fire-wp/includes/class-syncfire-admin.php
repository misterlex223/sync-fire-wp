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
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, SYNCFIRE_PLUGIN_URL . 'admin/css/syncfire-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script('syncfire-admin', plugin_dir_url(dirname(__FILE__)) . 'admin/js/syncfire-admin.js', array('jquery'), '1.0.0', true);
        
        // Add the AJAX URL and nonce to the script
        wp_localize_script('syncfire-admin', 'syncfire_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('syncfire_ajax_nonce'),
            'i18n' => array(
                'syncing' => __('Syncing...', 'sync-fire'),
                'sync_success' => __('Sync completed successfully!', 'sync-fire'),
                'sync_error' => __('Error during sync. Please check the logs.', 'sync-fire'),
                'testing_connection' => __('Testing connection...', 'sync-fire'),
                'connection_success' => __('Connection successful!', 'sync-fire'),
                'connection_error' => __('Connection failed. Please check your settings.', 'sync-fire')
            )
        ));
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
        
        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
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
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'syncfire_nonce' ) ) {
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
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'syncfire_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sync-fire' ) ) );
        }

        // Get the taxonomy to sync
        if ( ! isset( $_POST['taxonomy'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No taxonomy specified.', 'sync-fire' ) ) );
        }

        $taxonomy = sanitize_text_field( $_POST['taxonomy'] );
        
        // Get the taxonomy sync instance
        $taxonomy_sync = new SyncFire_Taxonomy_Sync();
        
        // Perform the sync
        $result = $taxonomy_sync->sync_taxonomy( $taxonomy );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => sprintf( __( 'Taxonomy %s has been successfully synced to Firestore.', 'sync-fire' ), $taxonomy ) ) );
        } else {
            wp_send_json_error( array( 'message' => sprintf( __( 'There was an error syncing taxonomy %s to Firestore. Please check your settings and try again.', 'sync-fire' ), $taxonomy ) ) );
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
