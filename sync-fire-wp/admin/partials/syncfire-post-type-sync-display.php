<?php
/**
 * Displays the Post Type Synchronization section of the SyncFire settings page.
 *
 * @link       https://www.example.com
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Ensure we have the connection_status variable
if (!isset($connection_status)) {
    $connection_status = false;
}
?>

<!-- Post Type Synchronization Section -->
<div id="syncfire-post-type-sync" class="syncfire-settings-section syncfire-collapsible-section" <?php echo !$connection_status ? 'style="display:none;"' : ''; ?>>
    <h3><?php _e('Post Type Synchronization', 'sync-fire'); ?></h3>
    <div class="syncfire-section-content">

    <?php
    settings_fields(SyncFire_Options::GROUP);
    ?>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label><?php _e('Post Types to Sync', 'sync-fire'); ?></label>
            </th>
            <td>
                <?php
                // Get all public post types, including those created by ACF
                $post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
                // Also include built-in post types like 'post' and 'page'
                $builtin_post_types = get_post_types(array('public' => true, '_builtin' => true), 'objects');
                $post_types = array_merge($post_types, $builtin_post_types);
                $post_types_to_sync = syncfire_get_option(SyncFire_Options::POST_TYPES_TO_SYNC, array());
                // Ensure $post_types_to_sync is always an array
                if (!is_array($post_types_to_sync)) {
                    $post_types_to_sync = array();
                }

                foreach ($post_types as $post_type) {
                    // Skip attachments
                    if ($post_type->name === 'attachment') {
                        continue;
                    }

                    $checked = in_array($post_type->name, $post_types_to_sync) ? 'checked' : '';
                    ?>
                    <label>
                        <input type="checkbox" name="<?php echo SyncFire_Options::POST_TYPES_TO_SYNC; ?>[]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo $checked; ?> class="syncfire-post-type-checkbox" data-post-type="<?php echo esc_attr($post_type->name); ?>" />
                        <?php echo esc_html($post_type->labels->name); ?>
                    </label><br>
                    <?php
                }
                ?>
                <p class="description"><?php _e('Select the post types you want to synchronize with Firestore.', 'sync-fire'); ?></p>
            </td>
        </tr>
    </table>

    <?php
    // For each selected post type, show field selection and mapping
    $post_type_fields = syncfire_get_option(SyncFire_Options::POST_TYPE_FIELDS, array());
    if (!is_array($post_type_fields)) {
        $post_type_fields = array();
    }
    $post_type_field_mapping = syncfire_get_option(SyncFire_Options::POST_TYPE_FIELD_MAPPING, array());
    if (!is_array($post_type_field_mapping)) {
        $post_type_field_mapping = array();
    }

    foreach ($post_types_to_sync as $post_type) {
        $post_type_obj = get_post_type_object($post_type);

        if (!$post_type_obj) {
            continue;
        }

        $fields = isset($post_type_fields[$post_type]) ? $post_type_fields[$post_type] : array();
        $field_mapping = isset($post_type_field_mapping[$post_type]) ? $post_type_field_mapping[$post_type] : array();
        ?>
        <div class="syncfire-post-type-fields" id="syncfire-post-type-fields-<?php echo esc_attr($post_type); ?>">
            <h4><?php echo esc_html($post_type_obj->labels->name); ?> <?php _e('Fields', 'sync-fire'); ?></h4>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Fields to Sync', 'sync-fire'); ?></label>
                    </th>
                    <td>
                        <?php
                        // Common post fields
                        $common_fields = array(
                            'ID' => __('ID', 'sync-fire'),
                            'post_title' => __('Title', 'sync-fire'),
                            'post_content' => __('Content', 'sync-fire'),
                            'post_excerpt' => __('Excerpt', 'sync-fire'),
                            'post_name' => __('Slug', 'sync-fire'),
                            'post_date' => __('Date', 'sync-fire'),
                            'post_modified' => __('Modified Date', 'sync-fire'),
                            'post_author' => __('Author', 'sync-fire'),
                            'post_status' => __('Status', 'sync-fire'),
                            'featured_image' => __('Featured Image', 'sync-fire'),
                        );

                        foreach ($common_fields as $field => $label) {
                            $checked = in_array($field, $fields) ? 'checked' : '';
                            ?>
                            <label>
                                <input type="checkbox" name="syncfire_post_type_fields[<?php echo esc_attr($post_type); ?>][]" value="<?php echo esc_attr($field); ?>" <?php echo $checked; ?> class="syncfire-field-checkbox" data-post-type="<?php echo esc_attr($post_type); ?>" data-field="<?php echo esc_attr($field); ?>" />
                                <?php echo esc_html($label); ?>
                            </label><br>
                            <?php
                        }

                        // Get taxonomies for this post type
                        $taxonomies = get_object_taxonomies($post_type, 'objects');
                        if (!empty($taxonomies)) {
                            ?>
                            <h5><?php _e('Taxonomies', 'sync-fire'); ?></h5>
                            <?php
                            foreach ($taxonomies as $taxonomy) {
                                $field = 'taxonomy_' . $taxonomy->name;
                                $checked = in_array($field, $fields) ? 'checked' : '';
                                ?>
                                <label>
                                    <input type="checkbox" name="syncfire_post_type_fields[<?php echo esc_attr($post_type); ?>][]" value="<?php echo esc_attr($field); ?>" <?php echo $checked; ?> class="syncfire-field-checkbox" data-post-type="<?php echo esc_attr($post_type); ?>" data-field="<?php echo esc_attr($field); ?>" />
                                    <?php echo esc_html($taxonomy->labels->name); ?>
                                </label><br>
                                <?php
                            }
                        }

                        // Custom fields (meta)
                        ?>
                        <h5><?php _e('Custom Fields', 'sync-fire'); ?></h5>
                        <?php
                        // Check if ACF is active and get ACF fields for this post type
                        if (class_exists('SyncFire_ACF_Helper') && SyncFire_ACF_Helper::is_acf_active()) {
                            $acf_fields = SyncFire_ACF_Helper::get_acf_fields_for_post_type($post_type);

                            if (!empty($acf_fields)) {
                                echo '<div class="syncfire-acf-fields">';
                                foreach ($acf_fields as $acf_field) {
                                    $field = 'acf_' . $acf_field['name'];
                                    $checked = in_array($field, $fields) ? 'checked' : '';
                                    ?>
                                    <label>
                                        <input type="checkbox" name="syncfire_post_type_fields[<?php echo esc_attr($post_type); ?>][]" value="<?php echo esc_attr($field); ?>" <?php echo $checked; ?> class="syncfire-field-checkbox" data-post-type="<?php echo esc_attr($post_type); ?>" data-field="<?php echo esc_attr($field); ?>" />
                                        <?php echo esc_html($acf_field['label']); ?> (<?php echo esc_html($acf_field['name']); ?>)
                                    </label><br>
                                    <?php
                                }
                                echo '</div>';
                            } else {
                                echo '<p>' . __('No ACF fields found for this post type.', 'sync-fire') . '</p>';
                            }
                        } else {
                            echo '<p>' . __('ACF plugin is not active or no fields are defined. Custom fields will be detected automatically when posts are synchronized.', 'sync-fire') . '</p>';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <h4><?php _e('Field Mapping', 'sync-fire'); ?></h4>
            <p><?php _e('Map WordPress fields to Firestore document fields.', 'sync-fire'); ?></p>

            <table class="form-table syncfire-field-mapping" id="syncfire-field-mapping-<?php echo esc_attr($post_type); ?>">
                <tr>
                    <th><?php _e('WordPress Field', 'sync-fire'); ?></th>
                    <th><?php _e('Firestore Field', 'sync-fire'); ?></th>
                </tr>
                <?php
                foreach ($fields as $field) {
                    $firestore_field = isset($field_mapping[$field]) ? $field_mapping[$field] : $field;
                    ?>
                    <tr>
                        <td><?php echo esc_html($field); ?></td>
                        <td>
                            <input type="text" name="<?php echo SyncFire_Options::POST_TYPE_FIELD_MAPPING; ?>[<?php echo esc_attr($post_type); ?>][<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($firestore_field); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>
        <?php
    }
    ?>
    </div>
</div>
