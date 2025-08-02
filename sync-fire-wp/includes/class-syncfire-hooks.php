<?php
/**
 * The hooks management functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

/**
 * The hooks management functionality of the plugin.
 *
 * Initializes and manages all WordPress hooks for real-time synchronization.
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 * @author     SyncFire Team
 */
class SyncFire_Hooks {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      SyncFire_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The taxonomy sync instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      SyncFire_Taxonomy_Sync    $taxonomy_sync    Handles taxonomy synchronization.
     */
    protected $taxonomy_sync;

    /**
     * The post type sync instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      SyncFire_Post_Type_Sync    $post_type_sync    Handles post type synchronization.
     */
    protected $post_type_sync;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->loader = new SyncFire_Loader();
        $this->taxonomy_sync = new SyncFire_Taxonomy_Sync();
        $this->post_type_sync = new SyncFire_Post_Type_Sync();
        
        $this->define_admin_hooks();
        $this->define_taxonomy_hooks();
        $this->define_post_type_hooks();
        
        $this->loader->run();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $admin = new SyncFire_Admin();
        
        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_syncfire_resync_all', $admin, 'resync_all');
        $this->loader->add_action('wp_ajax_syncfire_resync_taxonomy', $admin, 'resync_taxonomy');
        $this->loader->add_action('wp_ajax_syncfire_test_firebase_connection', $admin, 'test_firebase_connection');
    }

    /**
     * Register all of the hooks related to taxonomy synchronization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_taxonomy_hooks() {
        // Hook into taxonomy term creation
        $this->loader->add_action('created_term', $this->taxonomy_sync, 'sync_term', 10, 3);
        
        // Hook into taxonomy term update
        $this->loader->add_action('edited_term', $this->taxonomy_sync, 'sync_term', 10, 3);
        
        // Hook into taxonomy term deletion
        $this->loader->add_action('delete_term', $this->taxonomy_sync, 'delete_term', 10, 4);
        
        // Hook into taxonomy creation
        $this->loader->add_action('registered_taxonomy', $this->taxonomy_sync, 'maybe_sync_new_taxonomy', 10, 3);
    }

    /**
     * Register all of the hooks related to post type synchronization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_post_type_hooks() {
        // Hook into post creation and update
        $this->loader->add_action('save_post', $this->post_type_sync, 'sync_post', 10, 3);
        
        // Hook into post deletion
        $this->loader->add_action('before_delete_post', $this->post_type_sync, 'delete_post', 10, 1);
        
        // Hook into post status changes
        $this->loader->add_action('transition_post_status', $this->post_type_sync, 'post_status_changed', 10, 3);
        
        // Hook into post meta updates
        $this->loader->add_action('updated_post_meta', $this->post_type_sync, 'sync_post_meta', 10, 4);
        $this->loader->add_action('added_post_meta', $this->post_type_sync, 'sync_post_meta', 10, 4);
        $this->loader->add_action('deleted_post_meta', $this->post_type_sync, 'sync_post_meta', 10, 4);
        
        // Hook into featured image changes
        $this->loader->add_action('updated_post_thumbnail', $this->post_type_sync, 'sync_featured_image', 10, 2);
        $this->loader->add_action('removed_post_thumbnail', $this->post_type_sync, 'sync_featured_image', 10, 1);
    }
}
