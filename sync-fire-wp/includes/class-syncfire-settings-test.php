<?php
/**
 * SyncFire Settings Test Class
 *
 * This class provides testing and validation for SyncFire settings
 *
 * @since      1.0.0
 * @package    SyncFire
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * SyncFire Settings Test Class
 */
class SyncFire_Settings_Test {
    
    /**
     * Test settings registration
     *
     * @return array Test results with any issues found
     */
    public static function test_settings_registration() {
        global $wp_registered_settings;
        
        $results = array(
            'missing_options' => array(),
            'incorrect_group' => array(),
            'total_options' => count(SyncFire_Options::get_all_options()),
            'registered_options' => 0,
            'success' => true,
            'message' => '',
        );
        
        // Check that all options are correctly registered
        foreach (SyncFire_Options::get_all_options() as $option) {
            if (!isset($wp_registered_settings[$option])) {
                $results['missing_options'][] = $option;
                $results['success'] = false;
            } else {
                $results['registered_options']++;
                
                // Check if the option is registered with the correct group
                if (!isset($wp_registered_settings[$option]['group']) || 
                    $wp_registered_settings[$option]['group'] !== SyncFire_Options::GROUP) {
                    $results['incorrect_group'][] = $option;
                    $results['success'] = false;
                }
            }
        }
        
        // Generate result message
        if ($results['success']) {
            $results['message'] = sprintf(
                __('All %d options are correctly registered with the proper group.', 'sync-fire'),
                $results['total_options']
            );
        } else {
            $messages = array();
            
            if (!empty($results['missing_options'])) {
                $messages[] = sprintf(
                    __('Missing options: %s', 'sync-fire'),
                    implode(', ', $results['missing_options'])
                );
            }
            
            if (!empty($results['incorrect_group'])) {
                $messages[] = sprintf(
                    __('Options with incorrect group: %s', 'sync-fire'),
                    implode(', ', $results['incorrect_group'])
                );
            }
            
            $results['message'] = implode(' ', $messages);
        }
        
        return $results;
    }
    
    /**
     * Test form submission
     *
     * @return array Test results with any issues found
     */
    public static function test_form_submission() {
        $results = array(
            'form_action' => admin_url('options.php'),
            'option_page' => SyncFire_Options::GROUP,
            'success' => true,
            'message' => '',
        );
        
        // Check if the form action is correct
        if (empty($results['form_action'])) {
            $results['success'] = false;
            $results['message'] = __('Form action URL is empty.', 'sync-fire');
        }
        
        // Check if the option page is correct
        if ($results['option_page'] !== SyncFire_Options::GROUP) {
            $results['success'] = false;
            $results['message'] = sprintf(
                __('Option page is incorrect. Expected: %s, Got: %s', 'sync-fire'),
                SyncFire_Options::GROUP,
                $results['option_page']
            );
        }
        
        if ($results['success']) {
            $results['message'] = __('Form submission configuration is correct.', 'sync-fire');
        }
        
        return $results;
    }
    
    /**
     * Run all tests and log results
     *
     * @return array Combined test results
     */
    public static function run_all_tests() {
        $registration_results = self::test_settings_registration();
        $form_results = self::test_form_submission();
        
        $combined_results = array(
            'registration' => $registration_results,
            'form' => $form_results,
            'success' => $registration_results['success'] && $form_results['success'],
            'timestamp' => current_time('mysql'),
        );
        
        // Log the test results
        self::log_test_results($combined_results);
        
        return $combined_results;
    }
    
    /**
     * Log test results to file
     *
     * @param array $results Test results to log
     */
    private static function log_test_results($results) {
        $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/settings_tests.log';
        
        // Ensure log directory exists
        if (!file_exists(SYNCFIRE_PLUGIN_DIR . 'logs')) {
            mkdir(SYNCFIRE_PLUGIN_DIR . 'logs', 0755, true);
        }
        
        $log_message = date('[Y-m-d H:i:s]') . " Settings Tests Results\n";
        $log_message .= "Overall Success: " . ($results['success'] ? 'Yes' : 'No') . "\n\n";
        
        $log_message .= "Registration Test:\n";
        $log_message .= "- Success: " . ($results['registration']['success'] ? 'Yes' : 'No') . "\n";
        $log_message .= "- Total Options: " . $results['registration']['total_options'] . "\n";
        $log_message .= "- Registered Options: " . $results['registration']['registered_options'] . "\n";
        
        if (!empty($results['registration']['missing_options'])) {
            $log_message .= "- Missing Options: " . implode(', ', $results['registration']['missing_options']) . "\n";
        }
        
        if (!empty($results['registration']['incorrect_group'])) {
            $log_message .= "- Incorrect Group: " . implode(', ', $results['registration']['incorrect_group']) . "\n";
        }
        
        $log_message .= "\nForm Test:\n";
        $log_message .= "- Success: " . ($results['form']['success'] ? 'Yes' : 'No') . "\n";
        $log_message .= "- Form Action: " . $results['form']['form_action'] . "\n";
        $log_message .= "- Option Page: " . $results['form']['option_page'] . "\n";
        
        $log_message .= "\n--------------------------------------------------\n";
        
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// Run tests on admin_init with a very late priority to ensure all settings are registered
function syncfire_run_settings_tests() {
    // Only run tests in admin area
    if (!is_admin()) {
        return;
    }
    
    // Run tests and get results
    $test_results = SyncFire_Settings_Test::run_all_tests();
    
    // If there are issues, show admin notice
    if (!$test_results['success']) {
        add_action('admin_notices', 'syncfire_display_settings_test_notice');
    }
}
add_action('admin_init', 'syncfire_run_settings_tests', 9999);

/**
 * Display admin notice for settings test failures
 */
function syncfire_display_settings_test_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><strong><?php _e('SyncFire Settings Test Failed', 'sync-fire'); ?></strong></p>
        <p><?php _e('There are issues with the SyncFire settings configuration. Please check the logs for details.', 'sync-fire'); ?></p>
        <p><?php printf(__('Log file: %s', 'sync-fire'), SYNCFIRE_PLUGIN_DIR . 'logs/settings_tests.log'); ?></p>
    </div>
    <?php
}
