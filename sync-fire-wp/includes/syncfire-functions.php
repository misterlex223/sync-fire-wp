<?php
/**
 * Helper functions for SyncFire plugin
 *
 * @since      1.0.0
 * @package    SyncFire
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Integrate Google Maps API key with Advanced Custom Fields
 *
 * This function implements both methods from ACF documentation:
 * 1. Using the acf/fields/google_map/api filter
 * 2. Using the acf_update_setting function on acf/init hook
 *
 * @since 1.0.0
 * @return void
 */
function syncfire_integrate_google_maps_api_key() {
    add_action('acf/init', 'syncfire_acf_init_google_api_key');
}

/**
 * Set Google Maps API key on ACF init
 *
 * @since 1.0.0
 * @return void
 */
function syncfire_acf_init_google_api_key() {
    $api_key = get_option(SyncFire_Options::GOOGLE_MAP_API_KEY, '');
    error_log('SyncFire: Google Maps API key set: ' . $api_key);
    if (!empty($api_key) && function_exists('acf_update_setting')) {
        acf_update_setting('google_api_key', $api_key);
    }
}

/**
 * Register plugin option
 *
 * @param string $option_name Option name constant
 * @param array  $args Option arguments
 */
function syncfire_register_option($option_name, $args) {
    register_setting(SyncFire_Options::GROUP, $option_name, $args);
}

/**
 * Get plugin option value
 *
 * @param string $option_name Option name constant
 * @param mixed  $default Default value
 * @return mixed Option value
 */
function syncfire_get_option($option_name, $default = '') {
    return get_option($option_name, $default);
}

/**
 * Update plugin option value
 *
 * @param string $option_name Option name constant
 * @param mixed  $value Option value
 * @return bool Whether the update was successful
 */
function syncfire_update_option($option_name, $value) {
    return update_option($option_name, $value);
}

/**
 * Delete plugin option
 *
 * @param string $option_name Option name constant
 * @return bool Whether the deletion was successful
 */
function syncfire_delete_option($option_name) {
    return delete_option($option_name);
}

/**
 * Sanitize array values
 *
 * @param mixed $input Input to sanitize
 * @return array Sanitized array
 */
function syncfire_sanitize_array($input) {
    // If not an array, convert to an empty array
    if (!is_array($input)) {
        return array();
    }

    // Sanitize each element in the array
    $sanitized_input = array();
    foreach ($input as $key => $value) {
        if (is_array($value)) {
            $sanitized_input[$key] = syncfire_sanitize_array($value);
        } else {
            $sanitized_input[$key] = sanitize_text_field($value);
        }
    }

    return $sanitized_input;
}

/**
 * Sanitize boolean values
 *
 * @param mixed $input Input to sanitize
 * @return bool Sanitized boolean
 */
function syncfire_sanitize_boolean($input) {
    return (bool) $input;
}

/**
 * Log form submission data
 */
function syncfire_log_form_submission() {
    if (empty($_POST) || !isset($_POST['option_page']) || $_POST['option_page'] !== SyncFire_Options::GROUP) {
        return;
    }

    $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/form_submission.log';

    // Ensure log directory exists
    if (!file_exists(SYNCFIRE_PLUGIN_DIR . 'logs')) {
        mkdir(SYNCFIRE_PLUGIN_DIR . 'logs', 0755, true);
    }

    $log_message = date('[Y-m-d H:i:s]') . " Form submission data\n";
    $log_message .= "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    $log_message .= "option_page: " . $_POST['option_page'] . "\n";

    // Log all option values
    foreach (SyncFire_Options::get_all_options() as $option) {
        $log_message .= "$option: " . (isset($_POST[$option]) ? (is_array($_POST[$option]) ? json_encode($_POST[$option]) : $_POST[$option]) : 'N/A') . "\n";
    }

    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Test settings registration and form generation
 */
function syncfire_test_settings() {
    // Get all registered settings
    global $wp_registered_settings;

    // Check that all options are correctly registered
    $missing_options = [];
    foreach (SyncFire_Options::get_all_options() as $option) {
        if (!isset($wp_registered_settings[$option])) {
            $missing_options[] = $option;
        }
    }

    if (!empty($missing_options)) {
        error_log('SyncFire: Missing options: ' . implode(', ', $missing_options));
    }
}

/**
 * Check if emulator mode is enabled
 *
 * @since    1.1.0
 * @return   boolean    True if emulator mode is enabled, false otherwise
 */
function syncfire_is_emulator_enabled() {
    return (bool) get_option(SyncFire_Options::FIRESTORE_EMULATOR_ENABLED, false);
}

/**
 * Get emulator configuration
 *
 * @since    1.1.0
 * @return   array      Array containing emulator configuration
 */
function syncfire_get_emulator_config() {
    return [
        'enabled' => (bool) get_option(SyncFire_Options::FIRESTORE_EMULATOR_ENABLED, false),
        'host' => get_option(SyncFire_Options::FIRESTORE_EMULATOR_HOST, 'localhost'),
        'port' => get_option(SyncFire_Options::FIRESTORE_EMULATOR_PORT, '8080')
    ];
}

/**
 * Test emulator connection
 *
 * @since    1.1.0
 * @return   boolean    True on success, false on failure
 */
function syncfire_test_emulator_connection() {
    if (!syncfire_is_emulator_enabled()) {
        error_log('SyncFire: Emulator is not enabled');
        return false;
    }

    try {
        // Load the Firestore class to test the connection
        if (!class_exists('SyncFire_Firestore')) {
            require_once SYNCFIRE_PLUGIN_DIR . 'includes/class-syncfire-firestore.php';
        }

        $firestore = new SyncFire_Firestore();
        $result = $firestore->test_connection();

        if ($result) {
            error_log('SyncFire: Successfully connected to Firestore emulator');
        } else {
            error_log('SyncFire: Failed to connect to Firestore emulator');
        }

        return $result;
    } catch (\Exception $e) {
        error_log('SyncFire: Error during emulator connection test: ' . $e->getMessage());
        return false;
    }
}
