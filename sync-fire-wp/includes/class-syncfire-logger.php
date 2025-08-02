<?php
/**
 * The logging functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

/**
 * The logging functionality of the plugin.
 *
 * Handles logging and user feedback for synchronization operations.
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 * @author     SyncFire Team
 */
class SyncFire_Logger {

    /**
     * Log levels.
     */
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_SUCCESS = 'success';

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name = 'sync-fire', $version = '1.0.0') {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Log a message.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    string    $level      The log level.
     * @param    boolean   $display    Whether to display the message to the user.
     */
    public function log($message, $level = self::LOG_LEVEL_INFO, $display = false) {
        // Format the log message
        $log_message = sprintf('[%s] [%s] %s', date('Y-m-d H:i:s'), strtoupper($level), $message);
        
        // Log to WordPress error log
        error_log($log_message);
        
        // Store in the plugin's log
        $this->store_log($message, $level);
        
        // Display to the user if requested
        if ($display) {
            $this->add_admin_notice($message, $level);
        }
    }

    /**
     * Store a log entry in the database.
     *
     * @since    1.0.0
     * @param    string    $message    The message to log.
     * @param    string    $level      The log level.
     */
    private function store_log($message, $level) {
        // Get existing logs
        $logs = get_option('syncfire_logs', array());
        
        // Add new log entry
        $logs[] = array(
            'time' => current_time('mysql'),
            'message' => $message,
            'level' => $level
        );
        
        // Limit to 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        // Update logs
        update_option('syncfire_logs', $logs);
    }

    /**
     * Add an admin notice.
     *
     * @since    1.0.0
     * @param    string    $message    The message to display.
     * @param    string    $level      The notice level.
     */
    public function add_admin_notice($message, $level = self::LOG_LEVEL_INFO) {
        // Get existing notices
        $notices = get_option('syncfire_admin_notices', array());
        
        // Add new notice
        $notices[] = array(
            'message' => $message,
            'level' => $level,
            'time' => time()
        );
        
        // Update notices
        update_option('syncfire_admin_notices', $notices);
    }

    /**
     * Display admin notices.
     *
     * @since    1.0.0
     */
    public function display_admin_notices() {
        // Check if we're on a SyncFire admin page
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'syncfire') === false) {
            return;
        }
        
        // Get notices
        $notices = get_option('syncfire_admin_notices', array());
        
        // Display notices
        foreach ($notices as $key => $notice) {
            // Skip if older than 24 hours
            if (time() - $notice['time'] > 86400) {
                continue;
            }
            
            // Get notice class
            $class = 'notice';
            switch ($notice['level']) {
                case self::LOG_LEVEL_SUCCESS:
                    $class .= ' notice-success';
                    break;
                case self::LOG_LEVEL_ERROR:
                    $class .= ' notice-error';
                    break;
                case self::LOG_LEVEL_WARNING:
                    $class .= ' notice-warning';
                    break;
                default:
                    $class .= ' notice-info';
                    break;
            }
            
            // Display notice
            printf(
                '<div class="%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($class),
                esc_html($notice['message'])
            );
        }
        
        // Clear notices
        update_option('syncfire_admin_notices', array());
    }

    /**
     * Get logs.
     *
     * @since    1.0.0
     * @param    integer   $limit      The number of logs to get.
     * @param    string    $level      The log level to filter by.
     * @return   array                 The logs.
     */
    public function get_logs($limit = 20, $level = null) {
        // Get logs
        $logs = get_option('syncfire_logs', array());
        
        // Filter by level if specified
        if ($level !== null) {
            $logs = array_filter($logs, function($log) use ($level) {
                return $log['level'] === $level;
            });
        }
        
        // Sort by time (newest first)
        usort($logs, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        // Limit results
        if ($limit > 0) {
            $logs = array_slice($logs, 0, $limit);
        }
        
        return $logs;
    }

    /**
     * Clear logs.
     *
     * @since    1.0.0
     */
    public function clear_logs() {
        update_option('syncfire_logs', array());
    }
}
