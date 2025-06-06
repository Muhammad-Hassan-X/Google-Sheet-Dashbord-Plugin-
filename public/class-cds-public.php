<?php
// File: client-dashboard-system/public/class-cds-public.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_Public {

    private $plugin_name;
    private $version;
    private $shortcode_has_run = false; // <-- ADD THIS PROPERTY

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'client_dashboard' ) ) {
            wp_enqueue_style( $this->plugin_name . '-public', CDS_PLUGIN_URL . 'public/assets/css/public-style.css', array(), $this->version, 'all' );
        }
    }

    public function enqueue_scripts() {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'client_dashboard' ) ) {
            wp_enqueue_script( $this->plugin_name . '-public', CDS_PLUGIN_URL . 'public/assets/js/public-script.js', array( 'jquery' ), $this->version, true ); 
            wp_localize_script($this->plugin_name . '-public', 'cds_public_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('cds_public_nonce') 
            ));
        }
    }

    public function render_client_dashboard_shortcode( $atts ) {
        // <-- START OF CHANGES TO PREVENT DUPLICATE RENDERING -->
        if ($this->shortcode_has_run) {
            return ''; // If it has already run on this page load, return an empty string.
        }
        $this->shortcode_has_run = true;
        // <-- END OF CHANGES -->
        
        if ( ! is_user_logged_in() ) {
            $dashboard_page_id = get_option('cds_dashboard_page_id');
            $redirect_url = ($dashboard_page_id && get_post_status($dashboard_page_id) === 'publish') ? get_permalink($dashboard_page_id) : home_url();
            $login_url = wp_login_url( $redirect_url ); 
            return '<div class="cds-login-prompt"><p>' . sprintf( __( 'You must be logged in to view your dashboard. <a href="%s">Login here</a>.', 'client-dashboard-system' ), esc_url( $login_url ) ) . '</p></div>';
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        ob_start();

        $template_path = CDS_PLUGIN_DIR . 'public/views/user-dashboard-template.php';
        if ( file_exists( $template_path ) ) {
            $template_data = array(
                'current_user' => $current_user,
                'user_id'      => $user_id,
                'user_meta'    => array(
                    'custom_id'     => get_user_meta( $user_id, 'cds_custom_id', true ),
                    'phone'         => get_user_meta( $user_id, 'billing_phone', true ), 
                    'address_city'  => get_user_meta( $user_id, 'cds_address_city', true ),
                    'address_street'=> get_user_meta( $user_id, 'cds_address_street', true ),
                    'address_number'=> get_user_meta( $user_id, 'cds_address_number', true ),
                    'address_apt'   => get_user_meta( $user_id, 'cds_address_apt', true ),
                    'address_floor' => get_user_meta( $user_id, 'cds_address_floor', true ),
                    'address_entrance' => get_user_meta( $user_id, 'cds_address_entrance', true ),
                    'full_address_display' => get_user_meta($user_id, 'cds_full_address_display', true),
                ),
                'services_overview' => get_user_meta( $user_id, 'cds_services_overview', true ), 
                'transactions'      => get_user_meta( $user_id, 'cds_transactions', true ), 
                'support_email'     => get_option('cds_support_email', ''),
                'support_whatsapp'  => get_option('cds_support_whatsapp', '')
            );
            
            $detailed_services = array();
            if (!empty($template_data['services_overview']) && is_array($template_data['services_overview'])) {
                foreach ($template_data['services_overview'] as $service_display_name => $status) {
                    $service_key = sanitize_key($service_display_name);
                    $detailed_services[$service_display_name] = array(
                        'status' => $status,
                        'missing_docs' => get_user_meta($user_id, 'cds_service_missing_docs_' . $service_key, true),
                        'denied_reason' => get_user_meta($user_id, 'cds_service_denied_reason_' . $service_key, true),
                        'general_details' => get_user_meta($user_id, 'cds_service_details_' . $service_key, true) 
                    );
                }
            }
            $template_data['detailed_services'] = $detailed_services;

            extract($template_data);

            include $template_path;
        } else {
            echo '<p class="cds-error">' . __( 'Error: User dashboard template not found.', 'client-dashboard-system' ) . '</p>';
        }

        return ob_get_clean();
    }
}
