<?php
// File: client-dashboard-system/uninstall.php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$options_to_delete = array(
    'cds_webhook_secret',
    'cds_dashboard_page_id',
    'cds_support_email',
    'cds_support_whatsapp',
);

foreach ($options_to_delete as $option_name) {
    delete_option($option_name);
}
// Consider user meta deletion with caution. It's destructive.
// global $wpdb;
// $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'cds\_%'");
