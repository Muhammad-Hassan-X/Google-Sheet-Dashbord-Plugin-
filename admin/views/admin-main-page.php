<?php
// File: client-dashboard-system/admin/views/admin-main-page.php

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wrap cds-admin-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p><?php esc_html_e( 'Welcome to the Client Dashboard System. Use the submenus to configure settings or manage users and services.', 'client-dashboard-system' ); ?></p>
    
    <div class="cds-dashboard-widgets">
        <div class="cds-widget">
            <h2><?php esc_html_e( 'Total Users', 'client-dashboard-system' ); ?></h2>
            <?php
            $user_count = count_users();
            $total_users = $user_count['total_users'];
            // You might want to count only users with a specific role or meta key if needed
            // $client_users_query = new WP_User_Query(array(
            //     'meta_key' => 'cds_custom_id', // Count users who have been synced
            //     'meta_compare' => 'EXISTS',
            //     'count_total' => true,
            // ));
            // $total_client_users = $client_users_query->get_total();
            ?>
            <p class="cds-widget-metric"><?php echo esc_html( $total_users ); ?></p>
            <!-- <p class="cds-widget-metric"><?php // echo esc_html( $total_client_users ); ?> <?php // esc_html_e( 'Synced Clients', 'client-dashboard-system' ); ?></p> -->
            <a href="<?php echo esc_url(admin_url('users.php')); ?>" class="button button-secondary"><?php esc_html_e('View All Users', 'client-dashboard-system'); ?></a>
        </div>

        <div class="cds-widget">
             <h2><?php esc_html_e( 'Quick Links', 'client-dashboard-system' ); ?></h2>
            <ul>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=client-dashboard-system-settings')); ?>"><?php esc_html_e( 'Configure Settings', 'client-dashboard-system' ); ?></a></li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=client-dashboard-system-manage-users')); ?>"><?php esc_html_e( 'Manage Users & Services', 'client-dashboard-system' ); ?></a></li>
            </ul>
        </div>
    </div>


    <div id="cds-webhook-info" style="margin-top: 20px; padding: 15px; border: 1px solid #ccd0d4; background-color: #fff;">
        <h2><?php esc_html_e( 'Webhook Endpoint URL', 'client-dashboard-system' ); ?></h2>
        <p><?php esc_html_e( 'Configure your Google Apps Script to send POST requests to the following URL when a sheet is updated:', 'client-dashboard-system' ); ?></p>
        <p><strong><code><?php echo esc_url(get_rest_url(null, 'cds/v1/webhook')); ?></code></strong></p>
        <?php 
        $secret = get_option('cds_webhook_secret');
        $is_secret_validation_active = true; 
        // Simple check: If the bypass comment is present in the handler, assume it's bypassed for testing
        $handler_file_content = file_get_contents(CDS_PLUGIN_DIR . 'includes/class-cds-google-sheets-handler.php');
        if (strpos($handler_file_content, '// --- TEMPORARILY BYPASS SECRET KEY CHECK FOR TESTING ---') !== false &&
            strpos($handler_file_content, '/* $webhook_secret = get_option') !== false ) {
            $is_secret_validation_active = false;
        }


        if ( !$is_secret_validation_active ) {
             echo '<p style="color: orange;"><strong>' . __('Note:', 'client-dashboard-system') . '</strong> ' . __('Webhook secret key validation appears to be currently bypassed in the plugin code for testing purposes. Remember to re-enable it for production by removing the comment blocks in <code>includes/class-cds-google-sheets-handler.php</code> and ensuring the permission callback in <code>client-dashboard-system.php</code> is secure.', 'client-dashboard-system') . '</p>';
        } elseif (empty($secret)) {
             echo '<p style="color: red;"><strong>' . __('Warning:', 'client-dashboard-system') . '</strong> ' . __('Webhook Secret Key is not set. Please configure a strong secret key in the Settings page for security.', 'client-dashboard-system') . '</p>';
        } else {
             echo '<p>' . __('Ensure your Google Apps Script includes this Webhook Secret Key in an `X-Webhook-Secret` header if you enable secret key validation.', 'client-dashboard-system') . '</p>';
        }
        ?>
    </div>
    <style>
        .cds-admin-wrap .cds-dashboard-widgets { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
        .cds-admin-wrap .cds-widget { background: #fff; border: 1px solid #ccd0d4; padding: 20px; flex: 1; min-width: 250px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .cds-admin-wrap .cds-widget h2 { margin-top: 0; font-size: 1.2em; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;}
        .cds-admin-wrap .cds-widget-metric { font-size: 2.5em; font-weight: bold; color: #2271b1; margin: 0 0 15px 0; line-height: 1.2;}
        .cds-admin-wrap .cds-widget ul { list-style: disc; margin-left: 20px;}
    </style>

</div>