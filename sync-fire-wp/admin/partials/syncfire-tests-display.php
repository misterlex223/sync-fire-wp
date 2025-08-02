<?php
/**
 * Provide an admin area view for the tests
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

// Check if tests were run
$test_results = null;
if (isset($_POST['syncfire_run_tests']) && check_admin_referer('syncfire_run_tests')) {
    $tester = new SyncFire_Tester();
    $test_results = $tester->run_all_tests();
}
?>

<div class="wrap syncfire-admin">
    <h1><?php _e('SyncFire - Tests', 'sync-fire'); ?></h1>
    <p><?php _e('Run tests to validate the functionality of the SyncFire plugin.', 'sync-fire'); ?></p>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=syncfire" class="nav-tab"><?php _e('Dashboard', 'sync-fire'); ?></a>
        <a href="?page=syncfire-settings" class="nav-tab"><?php _e('Settings', 'sync-fire'); ?></a>
        <a href="?page=syncfire-logs" class="nav-tab"><?php _e('Logs', 'sync-fire'); ?></a>
        <a href="?page=syncfire-tests" class="nav-tab nav-tab-active"><?php _e('Tests', 'sync-fire'); ?></a>
    </h2>
    
    <div class="syncfire-card">
        <h2><?php _e('Run Tests', 'sync-fire'); ?></h2>
        <p><?php _e('Click the button below to run all tests and validate the functionality of the SyncFire plugin.', 'sync-fire'); ?></p>
        
        <form method="post">
            <?php wp_nonce_field('syncfire_run_tests'); ?>
            <p>
                <input type="submit" name="syncfire_run_tests" class="button button-primary" value="<?php _e('Run All Tests', 'sync-fire'); ?>" />
            </p>
        </form>
    </div>
    
    <?php if ($test_results): ?>
    <div class="syncfire-card">
        <h2><?php _e('Test Results', 'sync-fire'); ?></h2>
        
        <table class="widefat syncfire-tests-table">
            <thead>
                <tr>
                    <th><?php _e('Test', 'sync-fire'); ?></th>
                    <th><?php _e('Result', 'sync-fire'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($test_results as $test => $result): ?>
                    <tr>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $test))); ?></td>
                        <td>
                            <?php if ($result): ?>
                                <span class="syncfire-test-result syncfire-test-passed"><?php _e('Passed', 'sync-fire'); ?></span>
                            <?php else: ?>
                                <span class="syncfire-test-result syncfire-test-failed"><?php _e('Failed', 'sync-fire'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p><?php _e('See the Logs page for detailed test results and any error messages.', 'sync-fire'); ?></p>
    </div>
    <?php endif; ?>
</div>
