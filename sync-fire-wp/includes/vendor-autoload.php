<?php
/**
 * Composer autoloader for Firebase PHP SDK
 *
 * @since      1.0.0
 *
 * @package    SyncFire
 * @subpackage SyncFire/includes
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Load Composer autoloader if it exists
$composer_autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
    return true;
} else {
    // Log error if autoloader doesn't exist
    error_log('SyncFire: Composer autoloader not found. Please run composer install.');
    return false;
}
