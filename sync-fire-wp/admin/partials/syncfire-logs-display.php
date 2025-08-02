<?php
/**
 * Provide a admin area view for the logs
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get logs
$logger = new SyncFire_Logger();
$logs = $logger->get_logs(50);

// Get filter
$filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : '';

// Filter logs if needed
if (!empty($filter)) {
    $logs = array_filter($logs, function($log) use ($filter) {
        return $log['level'] === $filter;
    });
}
?>

<div class="wrap syncfire-admin">
    <h1><?php _e('SyncFire - Logs', 'sync-fire'); ?></h1>
    <p><?php _e('View logs of synchronization operations between WordPress and Firestore.', 'sync-fire'); ?></p>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=syncfire" class="nav-tab"><?php _e('Dashboard', 'sync-fire'); ?></a>
        <a href="?page=syncfire-settings" class="nav-tab"><?php _e('Settings', 'sync-fire'); ?></a>
        <a href="?page=syncfire-logs" class="nav-tab nav-tab-active"><?php _e('Logs', 'sync-fire'); ?></a>
    </h2>
    
    <div class="syncfire-card">
        <h2><?php _e('Synchronization Logs', 'sync-fire'); ?></h2>
        
        <div class="syncfire-filters">
            <form method="get">
                <input type="hidden" name="page" value="syncfire-logs">
                <select name="filter">
                    <option value="" <?php selected($filter, ''); ?>><?php _e('All Levels', 'sync-fire'); ?></option>
                    <option value="info" <?php selected($filter, 'info'); ?>><?php _e('Info', 'sync-fire'); ?></option>
                    <option value="warning" <?php selected($filter, 'warning'); ?>><?php _e('Warning', 'sync-fire'); ?></option>
                    <option value="error" <?php selected($filter, 'error'); ?>><?php _e('Error', 'sync-fire'); ?></option>
                    <option value="success" <?php selected($filter, 'success'); ?>><?php _e('Success', 'sync-fire'); ?></option>
                </select>
                <button type="submit" class="button"><?php _e('Filter', 'sync-fire'); ?></button>
                
                <?php if (!empty($logs)): ?>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=syncfire_clear_logs'), 'syncfire_clear_logs'); ?>" class="button button-secondary" onclick="return confirm('<?php _e('Are you sure you want to clear all logs?', 'sync-fire'); ?>');"><?php _e('Clear Logs', 'sync-fire'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if (empty($logs)): ?>
            <p><?php _e('No logs found.', 'sync-fire'); ?></p>
        <?php else: ?>
            <table class="widefat syncfire-logs-table">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'sync-fire'); ?></th>
                        <th><?php _e('Level', 'sync-fire'); ?></th>
                        <th><?php _e('Message', 'sync-fire'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr class="syncfire-log-<?php echo esc_attr($log['level']); ?>">
                            <td><?php echo esc_html($log['time']); ?></td>
                            <td>
                                <span class="syncfire-log-level syncfire-log-level-<?php echo esc_attr($log['level']); ?>">
                                    <?php echo esc_html(ucfirst($log['level'])); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($log['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
