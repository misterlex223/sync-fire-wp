<?php
/**
 * The post type synchronization functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

/**
 * The post type synchronization functionality of the plugin.
 *
 * Handles the synchronization of WordPress post types to Firestore.
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 * @author     SyncFire Team
 */
class SyncFire_Post_Type_Sync {

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
    }

    /**
     * Sync all post types that are configured for synchronization.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    public function sync_all_post_types() {
        // Get the post types to sync
        $post_types_to_sync = get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
        
        if (empty($post_types_to_sync)) {
            return true; // No post types to sync
        }
        
        $success = true;
        
        foreach ($post_types_to_sync as $post_type) {
            $result = $this->sync_post_type($post_type);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Sync a specific post type to Firestore.
     *
     * @since    1.0.0
     * @param    string    $post_type    The post type to sync.
     * @return   boolean                 True on success, false on failure.
     */
    public function sync_post_type($post_type) {
        // Get the posts
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        if (empty($posts)) {
            return true; // No posts to sync
        }
        
        $success = true;
        
        foreach ($posts as $post) {
            $result = $this->sync_single_post($post);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Sync a specific post to Firestore.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     * @return   boolean             True on success, false on failure.
     */
    public function sync_single_post($post) {
        // Get the post type
        $post_type = $post->post_type;
        
        // Check if this post type is configured for sync
        $post_types_to_sync = get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
        
        if (!in_array($post_type, $post_types_to_sync)) {
            return true; // This post type is not configured for sync
        }
        
        // Get the fields to sync for this post type
        $post_type_fields = get_option(SyncFire_Options::POST_TYPE_FIELDS, array());
        $fields_to_sync = isset($post_type_fields[$post_type]) ? $post_type_fields[$post_type] : array();
        
        if (empty($fields_to_sync)) {
            return true; // No fields to sync
        }
        
        // Get the field mapping for this post type
        $post_type_field_mapping = get_option(SyncFire_Options::POST_TYPE_FIELD_MAPPING, array());
        $field_mapping = isset($post_type_field_mapping[$post_type]) ? $post_type_field_mapping[$post_type] : array();
        
        // Prepare the data for Firestore
        $post_data = $this->prepare_post_data($post, $fields_to_sync, $field_mapping);
        
        // Sync to Firestore
        return $this->firestore->save_post($post_type, $post->ID, $post_data);
    }

    /**
     * Sync a post to Firestore when it's created or updated.
     *
     * @since    1.0.0
     * @param    int       $post_id    Post ID.
     * @param    WP_Post   $post       Post object.
     * @param    boolean   $update     Whether this is an existing post being updated or not.
     */
    public function sync_post($post_id, $post, $update) {
        // Don't sync revisions or auto-saves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Don't sync if the post is not published
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Check if this post type is configured for sync
        $post_types_to_sync = get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
        
        if (!in_array($post->post_type, $post_types_to_sync)) {
            return; // This post type is not configured for sync
        }
        
        // Sync the post
        $this->sync_single_post($post);
    }

    /**
     * Delete a post from Firestore when it's deleted from WordPress.
     *
     * @since    1.0.0
     * @param    int       $post_id    Post ID.
     */
    public function delete_post($post_id) {
        // Get the post
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        // Check if this post type is configured for sync
        $post_types_to_sync = get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
        
        if (!in_array($post->post_type, $post_types_to_sync)) {
            return; // This post type is not configured for sync
        }
        
        // Delete from Firestore
        $this->firestore->delete_post($post->post_type, $post_id);
    }

    /**
     * Handle post status changes.
     *
     * @since    1.0.0
     * @param    string    $new_status    The new post status.
     * @param    string    $old_status    The old post status.
     * @param    WP_Post   $post          The post object.
     */
    public function post_status_changed($new_status, $old_status, $post) {
        // Check if this post type is configured for sync
        $post_types_to_sync = get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
        
        if (!in_array($post->post_type, $post_types_to_sync)) {
            return; // This post type is not configured for sync
        }
        
        // If the post is being published, sync it
        if ($new_status === 'publish') {
            $this->sync_single_post($post);
        }
        
        // If the post is being unpublished, delete it from Firestore
        if ($old_status === 'publish' && $new_status !== 'publish') {
            $this->firestore->delete_post($post->post_type, $post->ID);
        }
    }

    /**
     * Sync post meta when it's updated, added, or deleted.
     *
     * @since    1.0.0
     * @param    int       $meta_id       ID of the metadata entry.
     * @param    int       $post_id       Post ID.
     * @param    string    $meta_key      Metadata key.
     * @param    mixed     $meta_value    Metadata value (only for updated or added).
     */
    public function sync_post_meta($meta_id, $post_id, $meta_key, $meta_value = null) {
        // Get the post
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        // Check if this post type is configured for sync
        $post_types_to_sync = get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
        
        if (!in_array($post->post_type, $post_types_to_sync)) {
            return; // This post type is not configured for sync
        }
        
        // Check if the post is published
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Get the fields to sync for this post type
        $post_type_fields = get_option(SyncFire_Options::POST_TYPE_FIELDS, array());
        $fields_to_sync = isset($post_type_fields[$post->post_type]) ? $post_type_fields[$post->post_type] : array();
        
        // Check if this meta key is in the fields to sync
        $meta_field = 'meta_' . $meta_key;
        
        if (in_array($meta_field, $fields_to_sync)) {
            // Sync the post
            $this->sync_single_post($post);
        }
    }

    /**
     * Sync featured image when it's updated or removed.
     *
     * @since    1.0.0
     * @param    int       $post_id       Post ID.
     * @param    int       $attachment_id Attachment ID (only for updated).
     */
    public function sync_featured_image($post_id, $attachment_id = null) {
        // Get the post
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        // Check if this post type is configured for sync
        $post_types_to_sync = get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
        
        if (!in_array($post->post_type, $post_types_to_sync)) {
            return; // This post type is not configured for sync
        }
        
        // Check if the post is published
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Get the fields to sync for this post type
        $post_type_fields = get_option(SyncFire_Options::POST_TYPE_FIELDS, array());
        $fields_to_sync = isset($post_type_fields[$post->post_type]) ? $post_type_fields[$post->post_type] : array();
        
        // Check if featured image is in the fields to sync
        if (in_array('featured_image', $fields_to_sync)) {
            // Sync the post
            $this->sync_single_post($post);
        }
    }

    /**
     * Prepare post data for Firestore.
     *
     * @since    1.0.0
     * @param    WP_Post    $post           The post object.
     * @param    array      $fields_to_sync The fields to sync.
     * @param    array      $field_mapping  The field mapping.
     * @return   array                      The prepared post data.
     */
    private function prepare_post_data($post, $fields_to_sync, $field_mapping) {
        $post_data = array();
        
        foreach ($fields_to_sync as $field) {
            // Get the field value
            $field_value = $this->get_post_field_value($post, $field);
            
            // Get the field name in Firestore
            $firestore_field = isset($field_mapping[$field]) ? $field_mapping[$field] : $field;
            
            // Add the field to the post data
            $post_data[$firestore_field] = $field_value;
        }
        
        return $post_data;
    }

    /**
     * Get the value of a post field.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     * @param    string     $field   The field name.
     * @return   mixed               The field value.
     */
    private function get_post_field_value($post, $field) {
        // Check if the field is a post property
        if (property_exists($post, $field)) {
            return $post->$field;
        }
        
        // Check if the field is post meta
        if (strpos($field, 'meta_') === 0) {
            $meta_key = substr($field, 5);
            return get_post_meta($post->ID, $meta_key, true);
        }
        
        // Check if the field is an ACF field
        if (strpos($field, 'acf_') === 0 && function_exists('get_field')) {
            $acf_key = substr($field, 4);
            return get_field($acf_key, $post->ID);
        }
        
        // Check if the field is a taxonomy (supports both tax_ and taxonomy_ prefixes)
        if (strpos($field, 'tax_') === 0 || strpos($field, 'taxonomy_') === 0) {
            $taxonomy = strpos($field, 'tax_') === 0 ? substr($field, 4) : substr($field, 9);
            $terms = get_the_terms($post->ID, $taxonomy);
            
            if (is_wp_error($terms) || empty($terms)) {
                return array();
            }
            
            return array_map(function($term) {
                return array(
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }, $terms);
        }
        
        // Check if the field is a featured image
        if ($field === 'featured_image') {
            $image_id = get_post_thumbnail_id($post->ID);
            
            if (!$image_id) {
                return null;
            }
            
            $image = wp_get_attachment_image_src($image_id, 'full');
            
            if (!$image) {
                return null;
            }
            
            return array(
                'id' => $image_id,
                'url' => $image[0],
                'width' => $image[1],
                'height' => $image[2],
            );
        }
        
        // Default to null
        return null;
    }
}
