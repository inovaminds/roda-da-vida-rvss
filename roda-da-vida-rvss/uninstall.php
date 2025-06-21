/**
 * Uninstall procedures for Roda da Vida by Simone Silvestrin (RVSS)
 * 
 * This file is used when the plugin is uninstalled (deleted) to clean up
 * any database tables, options, or other data created by the plugin.
 * 
 * @package   RVSS
 * @author    InovaMinds
 * @license   GPL v2 or later
 * @version   0.2.0
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin constants for uninstall
define('RVSS_VERSION', '0.2.0');
define('RVSS_PLUGIN_NAME', 'roda-da-vida-rvss');

/**
 * Cleanup database tables
 */
function rvss_drop_tables() {
    global $wpdb;
    
    // Drop custom tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rvss_usuarios");
    
    // Log uninstall if logger exists
    if (function_exists('error_log')) {
        error_log('RVSS: Tables removed during uninstall.');
    }
}

/**
 * Cleanup options
 */
function rvss_delete_options() {
    // Delete plugin options
    delete_option('rvss_email_feedback');
    delete_option('rvss_version');
    delete_option('rvss_db_version');
    delete_option('rvss_theme_settings');
    delete_option('rvss_social_sharing');
    
    // Delete transients
    delete_transient('rvss_admin_statistics');
    delete_transient('rvss_admin_cache');
    
    // Delete user meta for multisite
    delete_site_option('rvss_email_feedback');
    delete_site_option('rvss_version');
    delete_site_option('rvss_db_version');
    delete_site_option('rvss_theme_settings');
    delete_site_option('rvss_social_sharing');
}

/**
 * Cleanup user data
 */
function rvss_cleanup_user_meta() {
    global $wpdb;
    
    // Remove any user meta associated with the plugin
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'rvss_%'");
}

/**
 * Clean up uploaded files
 */
function rvss_cleanup_files() {
    // Path to upload directory
    $upload_dir = wp_upload_dir();
    $rvss_dir = $upload_dir['basedir'] . '/rvss-exports';
    
    // Check if directory exists
    if (is_dir($rvss_dir)) {
        // Clean up directory
        $files = glob($rvss_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Remove directory
        rmdir($rvss_dir);
    }
}

// Execute all cleanup functions
rvss_drop_tables();
rvss_delete_options();
rvss_cleanup_user_meta();
rvss_cleanup_files();