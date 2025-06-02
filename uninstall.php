<?php
// File: client-dashboard-system/uninstall.php
/**
 * Uninstall script for Client Dashboard System
 *
 * This script is executed when the plugin is deleted from the WordPress admin.
 * It should remove all plugin data, such as options, custom tables, user meta, etc.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// --- Options ---
// Delete options stored by the plugin
delete_option( 'cds_google_api_key' );
delete_option( 'cds_webhook_secret' );
delete_option( 'cds_google_sheets_count' );
$sheet_count = get_option('cds_google_sheets_count', 5); // Get it one last time
for ($i = 1; $i <= $sheet_count; $i++) {
    delete_option( 'cds_google_sheet_url_' . $i );
}
delete_option( 'cds_support_email');
delete_option( 'cds_support_whatsapp');


// --- User Meta ---
// It's crucial to be careful here. Only delete meta specific to your plugin.
// Consider if admins might want to keep this data even after deleting the plugin.
// For a full cleanup:
/*
global $wpdb;
$meta_keys_to_delete = array(
    'cds_custom_id',
    'cds_address_city',
    'cds_address_street',
    'cds_address_number',
    'cds_address_apt',
    'cds_address_floor',
    'cds_address_entrance',
    'cds_full_address_display',
    'cds_transactions',
    'cds_services_overview'
    // Add all 'cds_service_status_%', 'cds_service_missing_docs_%', 'cds_service_denied_reason_%' keys
    // This would require querying for all users and then all meta keys starting with 'cds_service_'
);

// A more robust way for prefixed meta:
// $sql = "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'cds\_%'";
// $wpdb->query($sql);
// This is very broad. Be specific if possible.

// Example for specific keys:
foreach ($meta_keys_to_delete as $meta_key) {
    $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $meta_key ) );
}

// For dynamic service meta keys, it's more complex.
// You might need to loop through all users and then check their meta.
// Or, if you have a list of all possible service names (e.g., from a setting), loop through those.
// This part is often simplified or skipped in uninstall scripts due to complexity and performance,
// unless data privacy is a major concern and complete removal is mandated.
*/


// --- Custom Database Tables ---
// If your plugin created custom database tables, drop them here.
// global $wpdb;
// $table_name = $wpdb->prefix . 'my_custom_table';
// $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );


// --- Custom User Roles ---
// If your plugin added custom user roles:
// remove_role( 'client_user' );


// --- Scheduled Cron Events ---
// If your plugin scheduled cron events:
// $timestamp = wp_next_scheduled( 'cds_my_daily_cron_hook' );
// wp_unschedule_event( $timestamp, 'cds_my_daily_cron_hook' );


// --- Transients ---
// Delete any transients set by the plugin
// global $wpdb;
// $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_cds\_%' OR option_name LIKE '\_site\_transient\_cds\_%'";
// $wpdb->query($sql);


// Clear any caches if applicable (e.g., object cache)
// wp_cache_flush();

// Note: `flush_rewrite_rules()` is not typically needed on uninstall unless you are removing CPTs/taxonomies
// and want to ensure rules are cleaned up, but WordPress usually handles this.
