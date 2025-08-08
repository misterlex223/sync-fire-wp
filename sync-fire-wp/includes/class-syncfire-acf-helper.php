<?php
/**
 * ACF Helper functions for SyncFire
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACF Helper class for SyncFire
 *
 * This class contains helper functions for working with ACF fields
 *
 * @since      1.0.0
 * @package    SyncFire
 * @subpackage SyncFire/includes
 * @author     Your Name <email@example.com>
 */
class SyncFire_ACF_Helper {

    /**
     * Check if ACF plugin is active
     *
     * @return bool True if ACF is active, false otherwise
     */
    public static function is_acf_active() {
        return function_exists('acf_get_field_groups');
    }

    /**
     * Get ACF fields for a specific post type
     *
     * @param string $post_type The post type to get fields for
     * @return array Array of ACF fields
     */
    public static function get_acf_fields_for_post_type($post_type) {
        $fields = array();

        // Check if ACF is active
        if (!self::is_acf_active()) {
            return $fields;
        }

        // Get field groups for this post type
        $field_groups = acf_get_field_groups(array(
            'post_type' => $post_type
        ));

        // Loop through each field group
        foreach ($field_groups as $field_group) {
            // Get fields for this field group
            $group_fields = acf_get_fields($field_group);
            
            // Add each field to our array
            if (!empty($group_fields)) {
                foreach ($group_fields as $field) {
                    $fields[] = array(
                        'key' => $field['key'],
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'type' => $field['type']
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Get all ACF field groups
     *
     * @return array Array of ACF field groups
     */
    public static function get_all_acf_field_groups() {
        $field_groups = array();

        // Check if ACF is active
        if (!self::is_acf_active()) {
            return $field_groups;
        }

        // Get all field groups
        $field_groups = acf_get_field_groups();

        return $field_groups;
    }

    /**
     * Get ACF fields for all post types
     *
     * @return array Array of post types with their ACF fields
     */
    public static function get_acf_fields_for_all_post_types() {
        $post_types_fields = array();

        // Check if ACF is active
        if (!self::is_acf_active()) {
            return $post_types_fields;
        }

        // Get all post types
        $post_types = get_post_types(array('_builtin' => false), 'objects');
        $builtin_post_types = get_post_types(array('_builtin' => true, 'public' => true), 'objects');
        $post_types = array_merge($post_types, $builtin_post_types);

        // Loop through each post type
        foreach ($post_types as $post_type) {
            $post_type_name = $post_type->name;
            $post_types_fields[$post_type_name] = self::get_acf_fields_for_post_type($post_type_name);
        }

        return $post_types_fields;
    }
}
