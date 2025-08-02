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
