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

    private function __construct() {}

    public function send_status_change_email( $user_id, $service_name, $old_status, $new_status, $details = array() ) {
        $user_info = get_userdata( $user_id );
        if ( ! $user_info ) {
            error_log("CDS Notification: User not found for ID: " . $user_id);
            return; 
        }

        $to = $user_info->user_email;
        if (empty($to)) {
            error_log("CDS Notification: User ID $user_id has no email address. Cannot send notification for service '$service_name'.");
            return;
        }

        $subject = sprintf( __( 'Update on your service: %s', 'client-dashboard-system' ), $service_name );
        
        $message_body = sprintf(
            __( 'Hello %s,', 'client-dashboard-system' ),
            $user_info->first_name ?: $user_info->display_name
        ) . "\r\n\r\n";
        
        $message_body .= sprintf(
            __( 'The status of your service "%s" has been updated.', 'client-dashboard-system' ),
            esc_html( $service_name )
        ) . "\r\n";
        
        if ($old_status && $old_status !== $new_status) { 
             $message_body .= sprintf( __( 'Previous status: %s', 'client-dashboard-system' ), esc_html( $old_status ) ) . "\r\n";
        }
        $message_body .= sprintf( __( 'New status: %s', 'client-dashboard-system' ), esc_html( $new_status ) ) . "\r\n\r\n";

        $details_added_to_email = false;
        if ($new_status === 'Missing docs' && !empty($details['missing_docs'])) {
            $message_body .= __( 'Action Required - Missing documents:', 'client-dashboard-system' ) . "\r\n" . esc_html($details['missing_docs']) . "\r\n\r\n";
            $details_added_to_email = true;
        } elseif ($new_status === 'Request denied' && !empty($details['denied_reason'])) {
            $message_body .= __( 'Important - Reason for denial:', 'client-dashboard-system' ) . "\r\n" . esc_html($details['denied_reason']) . "\r\n\r\n";
            $details_added_to_email = true;
        } 
        
        // Add general details if they exist and haven't been covered by missing_docs or denied_reason specifically
        if (!empty($details['general_details'])) {
             $message_body .= __( 'Additional Details:', 'client-dashboard-system' ) . "\r\n" . esc_html(strip_tags(str_replace("<br />", "\n", $details['general_details']))) . "\r\n\r\n"; // Ensure newlines for text email
             $details_added_to_email = true;
        } elseif (empty($details_added_to_email) && !empty($details)) { // Fallback for other details not specifically handled
            $other_details_text = '';
            foreach($details as $key => $value){
                if (is_scalar($value) && !empty($value) && !in_array($key, ['missing_docs', 'denied_reason', 'general_details'])) {
                     $other_details_text .= esc_html(ucfirst(str_replace('_', ' ', $key))) . ": " . esc_html($value) . "\r\n";
                }
            }
            if (!empty($other_details_text)) {
                 $message_body .= __( 'Additional Information:', 'client-dashboard-system' ) . "\r\n" . $other_details_text . "\r\n";
            }
        }

        $dashboard_page_id = get_option('cds_dashboard_page_id');
        if ($dashboard_page_id && get_post_status($dashboard_page_id) === 'publish') {
            $message_body .= __( 'You can view your updated dashboard here:', 'client-dashboard-system' ) . "\r\n";
            $message_body .= get_permalink($dashboard_page_id) . "\r\n\r\n";
        } else {
            $message_body .= __( 'Please log in to our website to view updates.', 'client-dashboard-system' ) . "\r\n";
            $message_body .= home_url('/') . "\r\n\r\n";
        }
        
        $message_body .= sprintf( __( 'Thank you,', 'client-dashboard-system' ) ) . "\r\n";
        $message_body .= get_bloginfo( 'name' );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        $to = apply_filters('cds_status_change_email_to', $to, $user_id, $service_name, $new_status);
        $subject = apply_filters('cds_status_change_email_subject', $subject, $user_id, $service_name, $new_status);
        $message_body = apply_filters('cds_status_change_email_message', $message_body, $user_id, $service_name, $old_status, $new_status, $details);
        $headers = apply_filters('cds_status_change_email_headers', $headers, $user_id, $service_name, $new_status);

        if (wp_mail( $to, $subject, $message_body, $headers )) {
            error_log("CDS Notification: Email sent to $to for service '$service_name' status change to '$new_status' for user ID $user_id.");
        } else {
            error_log("CDS Notification: wp_mail() FAILED for user $user_id. To: $to, Subject: $subject. Check WordPress email configuration.");
        }
    }
}