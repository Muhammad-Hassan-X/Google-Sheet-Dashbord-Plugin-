<?php
// File: client-dashboard-system/includes/class-cds-notifications.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_Notifications {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Constructor
    }

    /**
     * Sends an email notification to the user when a service status changes.
     *
     * @param int $user_id The WordPress User ID.
     * @param string $service_name The name of the service that changed.
     * @param string $old_status The previous status.
     * @param string $new_status The new status.
     */
    public function send_status_change_email( $user_id, $service_name, $old_status, $new_status ) {
        $user_info = get_userdata( $user_id );
        if ( ! $user_info ) {
            return; // User not found
        }

        $to = $user_info->user_email;
        $subject = sprintf( __( 'Update on your service: %s', 'client-dashboard-system' ), $service_name );
        
        $message_body = sprintf(
            __( 'Hello %s,', 'client-dashboard-system' ),
            $user_info->first_name ?: $user_info->display_name
        ) . "\r\n\r\n";
        
        $message_body .= sprintf(
            __( 'The status of your service "%s" has been updated.', 'client-dashboard-system' ),
            esc_html( $service_name )
        ) . "\r\n";
        
        if ($old_status) {
             $message_body .= sprintf( __( 'Previous status: %s', 'client-dashboard-system' ), esc_html( $old_status ) ) . "\r\n";
        }
        $message_body .= sprintf( __( 'New status: %s', 'client-dashboard-system' ), esc_html( $new_status ) ) . "\r\n\r\n";

        // Add details for specific statuses
        if ($new_status === 'Missing docs') {
            $missing_docs_list = get_user_meta($user_id, 'cds_service_missing_docs_' . sanitize_key($service_name), true);
            if ($missing_docs_list) {
                $message_body .= __( 'Missing documents:', 'client-dashboard-system' ) . "\r\n" . esc_html($missing_docs_list) . "\r\n\r\n";
            }
        } elseif ($new_status === 'Request denied') {
            $denied_reason = get_user_meta($user_id, 'cds_service_denied_reason_' . sanitize_key($service_name), true);
            if ($denied_reason) {
                $message_body .= __( 'Reason for denial:', 'client-dashboard-system' ) . "\r\n" . esc_html($denied_reason) . "\r\n\r\n";
            }
        }

        $message_body .= __( 'You can view your dashboard here:', 'client-dashboard-system' ) . "\r\n";
        // ToDo: Get the actual dashboard page URL. For now, assume it's a page with slug 'client-dashboard'
        $dashboard_page = get_page_by_path('client-dashboard'); // You'll need to create this page and put the shortcode on it
        if ($dashboard_page) {
            $message_body .= get_permalink($dashboard_page->ID) . "\r\n\r\n";
        } else {
            $message_body .= home_url('/') . "\r\n\r\n"; // Fallback to home URL
        }
        
        $message_body .= sprintf( __( 'Thank you,', 'client-dashboard-system' ) ) . "\r\n";
        $message_body .= get_bloginfo( 'name' );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        // Allow other plugins/themes to modify email parts
        $to = apply_filters('cds_status_change_email_to', $to, $user_id, $service_name, $new_status);
        $subject = apply_filters('cds_status_change_email_subject', $subject, $user_id, $service_name, $new_status);
        $message_body = apply_filters('cds_status_change_email_message', $message_body, $user_id, $service_name, $old_status, $new_status);
        $headers = apply_filters('cds_status_change_email_headers', $headers, $user_id, $service_name, $new_status);

        if ($to && $subject && $message_body) {
            wp_mail( $to, $subject, $message_body, $headers );
        } else {
            error_log("CDS Notification: Failed to send email for user $user_id. Missing to, subject, or body.");
        }
    }

    // No email for account creation as per spec.
    // public function send_account_creation_email( $user_id, $password ) {}
}
