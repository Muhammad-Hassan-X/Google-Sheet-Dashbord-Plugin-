<?php
// File: client-dashboard-system/admin/views/admin-main-page.php

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p><?php esc_html_e( 'Welcome to the Client Dashboard System. Use the submenus to configure settings or manage users and services.', 'client-dashboard-system' ); ?></p>
    
    <h2><?php esc_html_e( 'Quick Links', 'client-dashboard-system' ); ?></h2>
    <ul>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=client-dashboard-system-settings')); ?>"><?php esc_html_e( 'Configure Settings', 'client-dashboard-system' ); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=client-dashboard-system-manage-users')); ?>"><?php esc_html_e( 'Manage Users & Services', 'client-dashboard-system' ); ?></a></li>
    </ul>

    <h2><?php esc_html_e( 'Webhook Endpoint URL', 'client-dashboard-system' ); ?></h2>
    <p><?php esc_html_e( 'Configure your Google Apps Script to send POST requests to the following URL when a sheet is updated:', 'client-dashboard-system' ); ?></p>
    <p><strong><code><?php echo esc_url(get_rest_url(null, 'cds/v1/webhook')); ?></code></strong></p>
    <p><?php esc_html_e( 'Remember to include the Webhook Secret Key (configured in Settings) in a request header (e.g., X-Webhook-Secret) for verification.', 'client-dashboard-system' ); ?></p>

</div>
