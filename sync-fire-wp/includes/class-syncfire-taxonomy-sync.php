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
        $taxonomies_to_sync = get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, array());
        
        if (empty($taxonomies_to_sync)) {
            return true; // No taxonomies to sync
        }
        
        $success = true;
        $invalid_taxonomies = array();
        
        foreach ($taxonomies_to_sync as $taxonomy) {
            // Validate the taxonomy before syncing
            if (!$this->validate_taxonomy($taxonomy)) {
                error_log('SyncFire Taxonomy Sync: Skipping invalid taxonomy: ' . $taxonomy);
                $invalid_taxonomies[] = $taxonomy;
                continue;
            }
            
            $result = $this->sync_taxonomy($taxonomy);
            if (!$result) {
                $success = false;
            }
        }
        
        // Remove invalid taxonomies from the configuration
        if (!empty($invalid_taxonomies)) {
            $valid_taxonomies = array_diff($taxonomies_to_sync, $invalid_taxonomies);
            update_option(SyncFire_Options::TAXONOMIES_TO_SYNC, $valid_taxonomies);
            error_log('SyncFire Taxonomy Sync: Removed ' . count($invalid_taxonomies) . ' invalid taxonomies from configuration');
        }
        
        return $success;
    }

    /**
     * Validate if a taxonomy exists and is registered.
     * This also checks for ACF-created taxonomies.
     *
     * @since    1.0.0
     * @param    string    $taxonomy    The taxonomy to validate.
     * @return   boolean                True if valid, false otherwise.
     */
    private function validate_taxonomy($taxonomy) {
        // First check using WordPress core function
        $exists = taxonomy_exists($taxonomy);
        
        if ($exists) {
            error_log('SyncFire Taxonomy Sync: Taxonomy exists in WordPress: ' . $taxonomy);
            return true;
        }
        
        // If not found, check if it might be an ACF-created taxonomy
        if (function_exists('acf_get_taxonomies')) {
            $acf_taxonomies = acf_get_taxonomies();
            if (in_array($taxonomy, $acf_taxonomies)) {
                error_log('SyncFire Taxonomy Sync: Taxonomy exists in ACF: ' . $taxonomy);
                return true;
            }
        }
        
        // Try to get taxonomy object directly
        $taxonomy_obj = get_taxonomy($taxonomy);
        if ($taxonomy_obj !== false) {
            error_log('SyncFire Taxonomy Sync: Taxonomy object exists: ' . $taxonomy);
            return true;
        }
        
        error_log('SyncFire Taxonomy Sync: Taxonomy does not exist: ' . $taxonomy);
        return false;
    }
    
    /**
     * Sync a specific taxonomy to Firestore.
     *
     * @since    1.0.0
     * @param    string    $taxonomy    The taxonomy to sync.
     * @return   boolean                True on success, false on failure.
     */
    public function sync_taxonomy($taxonomy) {
        error_log('SyncFire Taxonomy Sync: Starting sync for taxonomy: ' . $taxonomy);
        
        // Get the taxonomy terms
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms)) {
            error_log('SyncFire Taxonomy Sync: Error getting terms for taxonomy ' . $taxonomy . ': ' . $terms->get_error_message());
            return false;
        }
        
        error_log('SyncFire Taxonomy Sync: Found ' . count($terms) . ' terms for taxonomy: ' . $taxonomy);
        
        // Get the order field and sort order
        $order_field = get_option(SyncFire_Options::TAXONOMY_ORDER_FIELD, 'name');
        $sort_order = get_option(SyncFire_Options::TAXONOMY_SORT_ORDER, 'ASC');
        error_log('SyncFire Taxonomy Sync: Using order field: ' . $order_field . ', sort order: ' . $sort_order);
        
        // Sort the terms
        if (!empty($terms)) {
            try {
                usort($terms, function($a, $b) use ($order_field, $sort_order) {
                    // Check if the property exists
                    if (!isset($a->$order_field) || !isset($b->$order_field)) {
                        error_log('SyncFire Taxonomy Sync: Order field ' . $order_field . ' not found in term object');
                        // Default to name if the field doesn't exist
                        $a_value = $a->name;
                        $b_value = $b->name;
                    } else {
                        $a_value = $a->$order_field;
                        $b_value = $b->$order_field;
                    }
                    
                    if ($sort_order === 'ASC') {
                        return strcmp($a_value, $b_value);
                    } else {
                        return strcmp($b_value, $a_value);
                    }
                });
                error_log('SyncFire Taxonomy Sync: Terms sorted successfully');
            } catch (\Exception $e) {
                error_log('SyncFire Taxonomy Sync: Error sorting terms: ' . $e->getMessage());
                // Continue without sorting if there's an error
            }
        }
        
        // Prepare the data for Firestore
        $taxonomy_data = array(
            'taxonomy' => $taxonomy,
            'terms' => array(),
        );
        
        foreach ($terms as $term) {
            $term_data = $this->prepare_term_data($term);
            $taxonomy_data['terms'][] = $term_data;
            error_log('SyncFire Taxonomy Sync: Prepared term: ' . $term->name . ' (ID: ' . $term->term_id . ')');
        }
        
        error_log('SyncFire Taxonomy Sync: Prepared taxonomy data for ' . $taxonomy . ' with ' . count($taxonomy_data['terms']) . ' terms');
        error_log('SyncFire Taxonomy Sync: Taxonomy data: ' . json_encode($taxonomy_data, JSON_PARTIAL_OUTPUT_ON_ERROR));
        
        // Sync to Firestore
        error_log('SyncFire Taxonomy Sync: Sending taxonomy data to Firestore for: ' . $taxonomy);
        $result = $this->firestore->save_taxonomy($taxonomy, $taxonomy_data);
        error_log('SyncFire Taxonomy Sync: Firestore save_taxonomy result: ' . ($result ? 'true' : 'false'));
        
        return $result;
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
        $taxonomies_to_sync = get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, array());
        
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
        $taxonomies_to_sync = get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, array());
        
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
                // Ensure meta value is properly formatted for Firestore
                $value = $values[0];
                
                // If the value is serialized, unserialize it
                if (is_serialized($value)) {
                    $value = maybe_unserialize($value);
                }
                
                // If the value is still a string but looks like JSON, try to decode it
                if (is_string($value) && $this->is_json($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }
                
                // Store the processed value
                $meta[$key] = $value;
            }
        }
        
        // Ensure meta is an associative array (map) for Firestore
        if (empty($meta)) {
            // If meta is empty, use an empty object instead of an empty array
            // to ensure Firestore treats it as a map
            $meta = new \stdClass();
        }
        
        return $meta;
    }
    
    /**
     * Check if a string is valid JSON.
     *
     * @since    1.0.0
     * @param    string    $string    The string to check.
     * @return   boolean              True if valid JSON, false otherwise.
     */
    private function is_json($string) {
        if (!is_string($string) || trim($string) === '') {
            return false;
        }
        
        // Check if the string starts with { or [ (common JSON indicators)
        $first_char = substr(trim($string), 0, 1);
        if ($first_char !== '{' && $first_char !== '[') {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Check if a newly registered taxonomy should be synced.
     *
     * @since    1.0.0
     * @param    string    $taxonomy     The taxonomy name.
     * @param    string    $object_type  Object type (e.g., 'post', 'page').
     * @param    array     $args         Taxonomy registration arguments.
     */
    public function maybe_sync_new_taxonomy($taxonomy, $object_type, $args) {
        // Get the taxonomies to sync
        $taxonomies_to_sync = get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, array());
        
        // Check if this taxonomy is configured for sync
        if (in_array($taxonomy, $taxonomies_to_sync)) {
            // Sync the taxonomy
            $this->sync_taxonomy($taxonomy);
        }
    }
}
