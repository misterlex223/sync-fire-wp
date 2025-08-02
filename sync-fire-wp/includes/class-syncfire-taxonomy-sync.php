<?php
/**
 * The taxonomy synchronization functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

/**
 * The taxonomy synchronization functionality of the plugin.
 *
 * Handles the synchronization of WordPress taxonomies to Firestore.
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 * @author     SyncFire Team
 */
class SyncFire_Taxonomy_Sync {

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
        $this->firestore = new SyncFire_Firestore();
        
        // Add hooks for real-time sync
        $this->add_taxonomy_hooks();
    }

    /**
     * Add hooks for real-time taxonomy synchronization.
     *
     * @since    1.0.0
     */
    private function add_taxonomy_hooks() {
        // Hook into taxonomy term creation
        add_action('created_term', array($this, 'sync_term'), 10, 3);
        
        // Hook into taxonomy term update
        add_action('edited_term', array($this, 'sync_term'), 10, 3);
        
        // Hook into taxonomy term deletion
        add_action('delete_term', array($this, 'delete_term'), 10, 4);
    }

    /**
     * Sync all taxonomies that are configured for synchronization.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    public function sync_all_taxonomies() {
        // Get the taxonomies to sync
        $taxonomies_to_sync = get_option('syncfire_taxonomies_to_sync', array());
        
        if (empty($taxonomies_to_sync)) {
            return true; // No taxonomies to sync
        }
        
        $success = true;
        
        foreach ($taxonomies_to_sync as $taxonomy) {
            $result = $this->sync_taxonomy($taxonomy);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Sync a specific taxonomy to Firestore.
     *
     * @since    1.0.0
     * @param    string    $taxonomy    The taxonomy to sync.
     * @return   boolean                True on success, false on failure.
     */
    public function sync_taxonomy($taxonomy) {
        // Get the taxonomy terms
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms)) {
            return false;
        }
        
        // Get the order field and sort order
        $order_field = get_option('syncfire_taxonomy_order_field', 'name');
        $sort_order = get_option('syncfire_taxonomy_sort_order', 'ASC');
        
        // Sort the terms
        usort($terms, function($a, $b) use ($order_field, $sort_order) {
            $a_value = $a->$order_field;
            $b_value = $b->$order_field;
            
            if ($sort_order === 'ASC') {
                return strcmp($a_value, $b_value);
            } else {
                return strcmp($b_value, $a_value);
            }
        });
        
        // Prepare the data for Firestore
        $taxonomy_data = array(
            'taxonomy' => $taxonomy,
            'terms' => array(),
        );
        
        foreach ($terms as $term) {
            $taxonomy_data['terms'][] = $this->prepare_term_data($term);
        }
        
        // Sync to Firestore
        return $this->firestore->save_taxonomy($taxonomy, $taxonomy_data);
    }

    /**
     * Sync a specific term to Firestore when it's created or updated.
     *
     * @since    1.0.0
     * @param    int       $term_id    Term ID.
     * @param    int       $tt_id      Term taxonomy ID.
     * @param    string    $taxonomy   Taxonomy slug.
     */
    public function sync_term($term_id, $tt_id, $taxonomy) {
        // Check if this taxonomy is configured for sync
        $taxonomies_to_sync = get_option('syncfire_taxonomies_to_sync', array());
        
        if (!in_array($taxonomy, $taxonomies_to_sync)) {
            return; // This taxonomy is not configured for sync
        }
        
        // Sync the entire taxonomy
        $this->sync_taxonomy($taxonomy);
    }

    /**
     * Delete a term from Firestore when it's deleted from WordPress.
     *
     * @since    1.0.0
     * @param    int       $term_id    Term ID.
     * @param    int       $tt_id      Term taxonomy ID.
     * @param    string    $taxonomy   Taxonomy slug.
     * @param    mixed     $deleted_term The deleted term object.
     */
    public function delete_term($term_id, $tt_id, $taxonomy, $deleted_term) {
        // Check if this taxonomy is configured for sync
        $taxonomies_to_sync = get_option('syncfire_taxonomies_to_sync', array());
        
        if (!in_array($taxonomy, $taxonomies_to_sync)) {
            return; // This taxonomy is not configured for sync
        }
        
        // Sync the entire taxonomy (which will exclude the deleted term)
        $this->sync_taxonomy($taxonomy);
    }

    /**
     * Prepare term data for Firestore.
     *
     * @since    1.0.0
     * @param    WP_Term    $term    The term object.
     * @return   array                The prepared term data.
     */
    private function prepare_term_data($term) {
        return array(
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'parent' => $term->parent,
            'count' => $term->count,
            'meta' => $this->get_term_meta($term->term_id),
        );
    }

    /**
     * Get term meta data.
     *
     * @since    1.0.0
     * @param    int       $term_id    Term ID.
     * @return   array                 The term meta data.
     */
    private function get_term_meta($term_id) {
        $meta = array();
        $meta_keys = get_term_meta($term_id);
        
        if (!empty($meta_keys)) {
            foreach ($meta_keys as $key => $values) {
                $meta[$key] = $values[0];
            }
        }
        
        return $meta;
    }
}
