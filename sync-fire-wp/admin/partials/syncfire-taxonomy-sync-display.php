<?php
/**
 * Displays the Taxonomy Synchronization section of the SyncFire settings page.
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

<!-- Taxonomy Synchronization Section -->
<div id="syncfire-taxonomy-sync" class="syncfire-settings-section syncfire-collapsible-section" <?php echo !$connection_status ? 'style="display:none;"' : ''; ?>>
    <h3><?php _e('Taxonomy Synchronization', 'sync-fire'); ?></h3>
    <div class="syncfire-section-content">

    <?php
    settings_fields(SyncFire_Options::GROUP);
    ?>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label><?php _e('Taxonomies to Sync', 'sync-fire'); ?></label>
            </th>
            <td>
                <?php
                $taxonomies = get_taxonomies(array('public' => true), 'objects');
                $taxonomies_to_sync = syncfire_get_option(SyncFire_Options::TAXONOMIES_TO_SYNC, array());

                foreach ($taxonomies as $taxonomy) {
                    $checked = in_array($taxonomy->name, $taxonomies_to_sync) ? 'checked' : '';
                    ?>
                    <label>
                        <input type="checkbox" name="<?php echo SyncFire_Options::TAXONOMIES_TO_SYNC; ?>[]" value="<?php echo esc_attr($taxonomy->name); ?>" <?php echo $checked; ?> />
                        <?php echo esc_html($taxonomy->labels->name); ?>
                    </label><br>
                    <?php
                }
                ?>
                <p class="description"><?php _e('Select the taxonomies you want to synchronize with Firestore.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="syncfire_taxonomy_order_field"><?php _e('Order Field', 'sync-fire'); ?></label>
            </th>
            <td>
                <select name="syncfire_taxonomy_order_field" id="syncfire_taxonomy_order_field">
                    <option value="name" <?php selected(get_option('syncfire_taxonomy_order_field', 'name'), 'name'); ?>><?php _e('Name', 'sync-fire'); ?></option>
                    <option value="slug" <?php selected(get_option('syncfire_taxonomy_order_field', 'name'), 'slug'); ?>><?php _e('Slug', 'sync-fire'); ?></option>
                    <option value="description" <?php selected(get_option('syncfire_taxonomy_order_field', 'name'), 'description'); ?>><?php _e('Description', 'sync-fire'); ?></option>
                </select>
                <p class="description"><?php _e('Select the field to use for ordering the taxonomy terms.', 'sync-fire'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="syncfire_taxonomy_sort_order"><?php _e('Sort Order', 'sync-fire'); ?></label>
            </th>
            <td>
                <select name="syncfire_taxonomy_sort_order" id="syncfire_taxonomy_sort_order">
                    <option value="ASC" <?php selected(get_option('syncfire_taxonomy_sort_order', 'ASC'), 'ASC'); ?>><?php _e('Ascending', 'sync-fire'); ?></option>
                    <option value="DESC" <?php selected(get_option('syncfire_taxonomy_sort_order', 'ASC'), 'DESC'); ?>><?php _e('Descending', 'sync-fire'); ?></option>
                </select>
                <p class="description"><?php _e('Select the sort order for the taxonomy terms.', 'sync-fire'); ?></p>
            </td>
        </tr>
    </table>
    </div>
</div>
