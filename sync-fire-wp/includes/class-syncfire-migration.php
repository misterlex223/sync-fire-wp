<?php
/**
 * SyncFire Migration Helper Class
 *
 * This class provides migration utilities for SyncFire plugin options
 * to handle changes in option names or groups.
 *
 * @since      1.0.0
 * @package    SyncFire
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * SyncFire Migration Helper Class
 */
class SyncFire_Migration {
    
    /**
     * Option name mapping for migration
     * 
     * Maps old option names to new option names
     * Format: 'old_option_name' => 'new_option_name'
     *
     * @var array
     */
    private static $option_name_mapping = array(
        // Example: 'old_syncfire_api_key' => 'syncfire_firebase_api_key',
    );
    
    /**
     * Option group mapping for migration
     * 
     * Maps old option groups to new option groups
     * Format: 'old_group' => 'new_group'
     *
     * @var array
     */
    private static $option_group_mapping = array(
        // Example: 'old_syncfire_group' => 'syncfire_settings',
    );
    
    /**
     * Run migration for all options
     *
     * @return array Migration results
     */
    public static function run_migration() {
        $results = array(
            'migrated_options' => array(),
            'migrated_groups' => array(),
            'errors' => array(),
            'success' => true,
        );
        
        // Migrate option names
        foreach (self::$option_name_mapping as $old_name => $new_name) {
            $result = self::migrate_option($old_name, $new_name);
            if ($result['success']) {
                $results['migrated_options'][] = array(
                    'old_name' => $old_name,
                    'new_name' => $new_name,
                    'value_migrated' => $result['value_migrated'],
                );
            } else {
                $results['errors'][] = $result['error'];
                $results['success'] = false;
            }
        }
        
        // Migrate option groups
        foreach (self::$option_group_mapping as $old_group => $new_group) {
            $result = self::migrate_group($old_group, $new_group);
            if ($result['success']) {
                $results['migrated_groups'][] = array(
                    'old_group' => $old_group,
                    'new_group' => $new_group,
                    'options_migrated' => $result['options_migrated'],
                );
            } else {
                $results['errors'][] = $result['error'];
                $results['success'] = false;
            }
        }
        
        // Log migration results
        self::log_migration_results($results);
        
        return $results;
    }
    
    /**
     * Migrate a single option from old name to new name
     *
     * @param string $old_name Old option name
     * @param string $new_name New option name
     * @return array Migration result
     */
    public static function migrate_option($old_name, $new_name) {
        $result = array(
            'success' => false,
            'value_migrated' => false,
            'error' => '',
        );
        
        try {
            // Check if old option exists
            $old_value = get_option($old_name, null);
            if ($old_value === null) {
                $result['error'] = sprintf('Old option "%s" does not exist', $old_name);
                return $result;
            }
            
            // Check if new option already exists
            $new_value = get_option($new_name, null);
            if ($new_value !== null) {
                // New option already exists, don't overwrite
                $result['error'] = sprintf('New option "%s" already exists, not overwriting', $new_name);
                return $result;
            }
            
            // Migrate the option value
            $update_result = update_option($new_name, $old_value);
            if (!$update_result) {
                $result['error'] = sprintf('Failed to update new option "%s"', $new_name);
                return $result;
            }
            
            // Delete the old option
            delete_option($old_name);
            
            $result['success'] = true;
            $result['value_migrated'] = true;
        } catch (Exception $e) {
            $result['error'] = sprintf('Exception during migration: %s', $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Migrate all options from one group to another
     *
     * @param string $old_group Old option group
     * @param string $new_group New option group
     * @return array Migration result
     */
    public static function migrate_group($old_group, $new_group) {
        global $wpdb;
        
        $result = array(
            'success' => false,
            'options_migrated' => array(),
            'error' => '',
        );
        
        try {
            // Get all options in the old group
            $options = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s",
                    $old_group . '_%'
                )
            );
            
            if (empty($options)) {
                $result['error'] = sprintf('No options found in group "%s"', $old_group);
                return $result;
            }
            
            // Migrate each option
            foreach ($options as $option) {
                $old_name = $option->option_name;
                $new_name = str_replace($old_group . '_', $new_group . '_', $old_name);
                
                // Migrate the option
                $migrate_result = self::migrate_option($old_name, $new_name);
                if ($migrate_result['success']) {
                    $result['options_migrated'][] = array(
                        'old_name' => $old_name,
                        'new_name' => $new_name,
                    );
                }
            }
            
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = sprintf('Exception during group migration: %s', $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Log migration results to file
     *
     * @param array $results Migration results
     */
    private static function log_migration_results($results) {
        $log_file = SYNCFIRE_PLUGIN_DIR . 'logs/migration.log';
        $log_dir = dirname($log_file);
        
        // Ensure log directory exists
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_message = date('[Y-m-d H:i:s]') . " Migration Results\n";
        $log_message .= "Overall Success: " . ($results['success'] ? 'Yes' : 'No') . "\n\n";
        
        // Log migrated options
        $log_message .= "Migrated Options:\n";
        if (empty($results['migrated_options'])) {
            $log_message .= "None\n";
        } else {
            foreach ($results['migrated_options'] as $option) {
                $log_message .= sprintf(
                    "- %s -> %s (Value Migrated: %s)\n",
                    $option['old_name'],
                    $option['new_name'],
                    $option['value_migrated'] ? 'Yes' : 'No'
                );
            }
        }
        
        // Log migrated groups
        $log_message .= "\nMigrated Groups:\n";
        if (empty($results['migrated_groups'])) {
            $log_message .= "None\n";
        } else {
            foreach ($results['migrated_groups'] as $group) {
                $log_message .= sprintf(
                    "- %s -> %s (Options Migrated: %d)\n",
                    $group['old_group'],
                    $group['new_group'],
                    count($group['options_migrated'])
                );
                
                foreach ($group['options_migrated'] as $option) {
                    $log_message .= sprintf(
                        "  - %s -> %s\n",
                        $option['old_name'],
                        $option['new_name']
                    );
                }
            }
        }
        
        // Log errors
        $log_message .= "\nErrors:\n";
        if (empty($results['errors'])) {
            $log_message .= "None\n";
        } else {
            foreach ($results['errors'] as $error) {
                $log_message .= "- $error\n";
            }
        }
        
        $log_message .= "\n--------------------------------------------------\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
    
    /**
     * Add migration mapping for option name
     *
     * @param string $old_name Old option name
     * @param string $new_name New option name
     */
    public static function add_option_mapping($old_name, $new_name) {
        self::$option_name_mapping[$old_name] = $new_name;
    }
    
    /**
     * Add migration mapping for option group
     *
     * @param string $old_group Old option group
     * @param string $new_group New option group
     */
    public static function add_group_mapping($old_group, $new_group) {
        self::$option_group_mapping[$old_group] = $new_group;
    }
}

/**
 * Run migration if needed
 * 
 * This function checks if migration is needed and runs it
 */
function syncfire_maybe_run_migration() {
    // Check if migration has already been run
    $migration_version = get_option('syncfire_migration_version', '0');
    $current_version = '1.0'; // Update this when making changes to option structure
    
    if (version_compare($migration_version, $current_version, '<')) {
        // Run migration
        $results = SyncFire_Migration::run_migration();
        
        // Update migration version
        update_option('syncfire_migration_version', $current_version);
        
        // Show admin notice if migration failed
        if (!$results['success']) {
            add_action('admin_notices', 'syncfire_display_migration_notice');
        }
    }
}
add_action('admin_init', 'syncfire_maybe_run_migration', 5); // Run early

/**
 * Display admin notice for migration failures
 */
function syncfire_display_migration_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><strong><?php _e('SyncFire Migration Failed', 'sync-fire'); ?></strong></p>
        <p><?php _e('There were errors during the SyncFire settings migration. Please check the logs for details.', 'sync-fire'); ?></p>
        <p><?php printf(__('Log file: %s', 'sync-fire'), SYNCFIRE_PLUGIN_DIR . 'logs/migration.log'); ?></p>
    </div>
    <?php
}
