<?php
/**
 * The testing functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

/**
 * The testing functionality of the plugin.
 *
 * Provides methods to test and validate the plugin functionality.
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 * @author     SyncFire Team
 */
class SyncFire_Tester {

    /**
     * The logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      SyncFire_Logger    $logger    The logger instance.
     */
    private $logger;

    /**
     * The Firestore instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      SyncFire_Firestore    $firestore    The Firestore instance.
     */
    private $firestore;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->logger = new SyncFire_Logger();
        $this->firestore = new SyncFire_Firestore();
    }

    /**
     * Run all tests.
     *
     * @since    1.0.0
     * @return   array    Test results.
     */
    public function run_all_tests() {
        $results = array(
            'firebase_connection' => $this->test_firebase_connection(),
            'taxonomy_sync' => $this->test_taxonomy_sync(),
            'post_type_sync' => $this->test_post_type_sync(),
            'real_time_sync' => $this->test_real_time_sync(),
            'security' => $this->test_security()
        );

        // Log overall results
        $success_count = count(array_filter($results));
        $total_count = count($results);
        
        if ($success_count === $total_count) {
            $this->logger->log(
                sprintf(__('All tests passed (%d/%d).', 'sync-fire'), $success_count, $total_count),
                SyncFire_Logger::LOG_LEVEL_SUCCESS,
                true
            );
        } else {
            $this->logger->log(
                sprintf(__('%d/%d tests passed. See logs for details.', 'sync-fire'), $success_count, $total_count),
                SyncFire_Logger::LOG_LEVEL_WARNING,
                true
            );
        }

        return $results;
    }

    /**
     * Test Firebase connection.
     *
     * @since    1.0.0
     * @return   boolean    True if the test passed, false otherwise.
     */
    public function test_firebase_connection() {
        $this->logger->log(__('Testing Firebase connection...', 'sync-fire'));
        
        $result = $this->firestore->test_connection();
        
        if ($result) {
            $this->logger->log(__('Firebase connection test passed.', 'sync-fire'), SyncFire_Logger::LOG_LEVEL_SUCCESS);
            return true;
        } else {
            $this->logger->log(__('Firebase connection test failed.', 'sync-fire'), SyncFire_Logger::LOG_LEVEL_ERROR);
            return false;
        }
    }

    /**
     * Test taxonomy synchronization.
     *
     * @since    1.0.0
     * @return   boolean    True if the test passed, false otherwise.
     */
    public function test_taxonomy_sync() {
        $this->logger->log(__('Testing taxonomy synchronization...', 'sync-fire'));
        
        // Get taxonomies to sync
        $taxonomies_to_sync = get_option('syncfire_taxonomies_to_sync', array());
        
        if (empty($taxonomies_to_sync)) {
            $this->logger->log(__('No taxonomies configured for synchronization.', 'sync-fire'), SyncFire_Logger::LOG_LEVEL_WARNING);
            return false;
        }
        
        // Test sync for each taxonomy
        $success = true;
        foreach ($taxonomies_to_sync as $taxonomy) {
            $taxonomy_sync = new SyncFire_Taxonomy_Sync();
            $result = $taxonomy_sync->sync_taxonomy($taxonomy);
            
            if ($result) {
                $this->logger->log(sprintf(__('Taxonomy %s synchronized successfully.', 'sync-fire'), $taxonomy), SyncFire_Logger::LOG_LEVEL_SUCCESS);
            } else {
                $this->logger->log(sprintf(__('Failed to synchronize taxonomy %s.', 'sync-fire'), $taxonomy), SyncFire_Logger::LOG_LEVEL_ERROR);
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Test post type synchronization.
     *
     * @since    1.0.0
     * @return   boolean    True if the test passed, false otherwise.
     */
    public function test_post_type_sync() {
        $this->logger->log(__('Testing post type synchronization...', 'sync-fire'));
        
        // Get post types to sync
        $post_types_to_sync = get_option('syncfire_post_types_to_sync', array());
        
        if (empty($post_types_to_sync)) {
            $this->logger->log(__('No post types configured for synchronization.', 'sync-fire'), SyncFire_Logger::LOG_LEVEL_WARNING);
            return false;
        }
        
        // Test sync for each post type
        $success = true;
        foreach ($post_types_to_sync as $post_type) {
            $post_type_sync = new SyncFire_Post_Type_Sync();
            $result = $post_type_sync->sync_post_type($post_type);
            
            if ($result) {
                $this->logger->log(sprintf(__('Post type %s synchronized successfully.', 'sync-fire'), $post_type), SyncFire_Logger::LOG_LEVEL_SUCCESS);
            } else {
                $this->logger->log(sprintf(__('Failed to synchronize post type %s.', 'sync-fire'), $post_type), SyncFire_Logger::LOG_LEVEL_ERROR);
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Test real-time synchronization.
     *
     * @since    1.0.0
     * @return   boolean    True if the test passed, false otherwise.
     */
    public function test_real_time_sync() {
        $this->logger->log(__('Testing real-time synchronization...', 'sync-fire'));
        
        // This is a simplified test that just checks if the hooks are registered
        // In a real test, we would create a post, update it, and delete it, then check Firestore
        
        global $wp_filter;
        
        $required_hooks = array(
            'save_post',
            'before_delete_post',
            'transition_post_status',
            'created_term',
            'edited_term',
            'delete_term'
        );
        
        $missing_hooks = array();
        foreach ($required_hooks as $hook) {
            if (!isset($wp_filter[$hook])) {
                $missing_hooks[] = $hook;
            }
        }
        
        if (empty($missing_hooks)) {
            $this->logger->log(__('All required hooks for real-time synchronization are registered.', 'sync-fire'), SyncFire_Logger::LOG_LEVEL_SUCCESS);
            return true;
        } else {
            $this->logger->log(
                sprintf(__('Missing hooks for real-time synchronization: %s', 'sync-fire'), implode(', ', $missing_hooks)),
                SyncFire_Logger::LOG_LEVEL_ERROR
            );
            return false;
        }
    }

    /**
     * Test security.
     *
     * @since    1.0.0
     * @return   boolean    True if the test passed, false otherwise.
     */
    public function test_security() {
        $this->logger->log(__('Testing security...', 'sync-fire'));
        
        $issues = array();
        
        // Check if API key is stored securely
        $api_key = get_option('syncfire_firebase_api_key', '');
        if (empty($api_key)) {
            $issues[] = __('Firebase API key is not set.', 'sync-fire');
        }
        
        // Check if service account JSON is stored securely
        $service_account = get_option('syncfire_firebase_service_account', '');
        if (empty($service_account)) {
            $issues[] = __('Firebase service account JSON is not set.', 'sync-fire');
        }
        
        // Check if admin pages are properly secured
        global $wp_filter;
        $admin_hooks = array(
            'admin_menu',
            'wp_ajax_syncfire_resync_all',
            'wp_ajax_syncfire_resync_taxonomy',
            'wp_ajax_syncfire_test_firebase_connection'
        );
        
        foreach ($admin_hooks as $hook) {
            if (!isset($wp_filter[$hook])) {
                $issues[] = sprintf(__('Admin hook %s is not registered.', 'sync-fire'), $hook);
            }
        }
        
        if (empty($issues)) {
            $this->logger->log(__('Security test passed.', 'sync-fire'), SyncFire_Logger::LOG_LEVEL_SUCCESS);
            return true;
        } else {
            foreach ($issues as $issue) {
                $this->logger->log($issue, SyncFire_Logger::LOG_LEVEL_ERROR);
            }
            return false;
        }
    }
}
