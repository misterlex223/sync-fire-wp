<?php
/**
 * Provide a admin area view for the plugin configuration
 *
 * This file is used to markup the admin-facing configuration aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/admin/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Ensure the options class is loaded
if (!class_exists('SyncFire_Options')) {
    require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-options.php';
}

// Load the Firestore class for connection testing
if (!class_exists('SyncFire_Firestore')) {
    require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-firestore.php';
}

// Check connection status
$firestore = new SyncFire_Firestore();
$connection_status = $firestore->test_connection();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="syncfire-admin-container">
        <div class="syncfire-admin-header">
            <h2><?php _e('SyncFire Configuration', 'sync-fire'); ?></h2>
            <p><?php _e('Configure your Firebase and other API settings.', 'sync-fire'); ?></p>
        </div>

        <div class="syncfire-admin-tabs">
            <nav class="nav-tab-wrapper">
                <a href="?page=syncfire" class="nav-tab"><?php _e('Dashboard', 'sync-fire'); ?></a>
                <a href="?page=syncfire-settings" class="nav-tab"><?php _e('Settings', 'sync-fire'); ?></a>
                <a href="?page=syncfire-configuration" class="nav-tab nav-tab-active"><?php _e('Configuration', 'sync-fire'); ?></a>
            </nav>

            <div class="tab-content">
                <div class="syncfire-settings">
                    <form method="post" id="syncfire-configuration-form">
                        <?php
                        // Use the same settings group as the Firebase settings
                        settings_fields(SyncFire_Options::GROUP);
                        do_settings_sections('syncfire_configuration');

                        // Include the Firebase Configuration template
                        include(SYNCFIRE_PLUGIN_DIR . 'admin/partials/syncfire-firebase-config-display.php');

                        // Google Maps API Configuration Section
                        ?>
                        <div id="syncfire-google-maps-config" class="syncfire-settings-section syncfire-collapsible-section">
                            <h3><?php _e('Google Maps API Configuration', 'sync-fire'); ?></h3>
                            <div class="syncfire-section-content">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="syncfire_google_maps_api_key"><?php _e('Google Maps API Key', 'sync-fire'); ?></label>
                                        </th>
                                        <td>
                                            <input type="password" name="<?php echo SyncFire_Options::GOOGLE_MAP_API_KEY; ?>" id="<?php echo SyncFire_Options::GOOGLE_MAP_API_KEY; ?>" class="regular-text syncfire-api-key-field" value="<?php echo esc_attr(get_option(SyncFire_Options::GOOGLE_MAP_API_KEY, '')); ?>" />
                                            <p class="description"><?php _e('Your Google Maps API Key for ACF Google Maps field.', 'sync-fire'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="submit">
                            <button type="button" id="syncfire-save-configuration" class="button button-primary"><?php _e('Save Configuration', 'sync-fire'); ?></button>
                            <span id="syncfire-save-result" style="margin-left: 10px; display: none;"></span>
                        </div>

                        <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            // Debug: Log all form fields on page load
                            console.log('Form fields on page load:');
                            $('#syncfire-configuration-form input').each(function() {
                                console.log($(this).attr('name') + ': ' + $(this).val());
                            });

                            $('#syncfire-save-configuration').on('click', function() {
                                var $button = $(this);
                                var $result = $('#syncfire-save-result');

                                // Disable button and show loading
                                $button.prop('disabled', true);
                                $result.html('<?php _e('Testing connection and saving...', 'sync-fire'); ?>').show();

                                // Ensure Google Maps API Key is included
                                var apiKeyField = $('#<?php echo SyncFire_Options::GOOGLE_MAP_API_KEY; ?>');

                                // Collect form data
                                var formData = $('#syncfire-configuration-form').serialize();

                                // Debug form data
                                console.log('Form data:', formData);

                                // Manually add Google Maps API Key if not in form data
                                if (apiKeyField.length > 0 && formData.indexOf('<?php echo SyncFire_Options::GOOGLE_MAP_API_KEY; ?>=') === -1) {
                                    formData += '&<?php echo SyncFire_Options::GOOGLE_MAP_API_KEY; ?>=' + encodeURIComponent(apiKeyField.val());
                                }

                                // Send AJAX request
                                $.ajax({
                                    url: syncfire_ajax.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'syncfire_save_configuration',
                                        nonce: syncfire_ajax.nonce,
                                        form_data: formData
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $result.html('<span style="color: green;">' + response.data.message + '</span>');
                                        } else {
                                            $result.html('<span style="color: red;">' + response.data.message + '</span>');
                                        }
                                    },
                                    error: function() {
                                        $result.html('<span style="color: red;"><?php _e('An error occurred while saving settings.', 'sync-fire'); ?></span>');
                                    },
                                    complete: function() {
                                        $button.prop('disabled', false);
                                    }
                                });
                            });
                        });
                        </script>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
