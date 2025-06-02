<?php
// File: client-dashboard-system/public/class-cds-public.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_Public {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Enqueue public-facing stylesheets.
     */
    public function enqueue_styles() {
        // Only enqueue if the shortcode is present or on specific pages.
        // For simplicity, let's assume it might be needed generally if a user is logged in
        // or on pages that could contain the shortcode.
        // A more optimized way is to check `has_shortcode()` in a later hook if possible.
        wp_enqueue_style( $this->plugin_name, CDS_PLUGIN_URL . 'public/assets/css/public-style.css', array(), $this->version, 'all' );
    }

    /**
     * Enqueue public-facing JavaScript.
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, CDS_PLUGIN_URL . 'public/assets/js/public-script.js', array( 'jquery' ), $this->version, false );
         // Localize script if you need to pass PHP variables to JS, e.g., for AJAX calls from dashboard
        wp_localize_script($this->plugin_name, 'cds_public_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cds_public_nonce') // For actions initiated from dashboard
        ));
    }

    /**
     * Renders the [client_dashboard] shortcode.
     * Displays user information, service statuses, and transaction history.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the shortcode.
     */
    public function render_client_dashboard_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            // User is not logged in, show login message or form.
            // You can redirect to wp-login.php or include a custom login form.
            // For ID-based login, wp-login.php is modified by CDS_Login_Handler.
            $login_url = wp_login_url( get_permalink() ); // Redirect back to current page after login
            return '<p>' . sprintf( __( 'You must be logged in to view your dashboard. <a href="%s">Login here</a>.', 'client-dashboard-system' ), esc_url( $login_url ) ) . '</p>';
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        // Start output buffering
        ob_start();

        // Load the template file for the dashboard
        $template_path = CDS_PLUGIN_DIR . 'public/views/user-dashboard-template.php';
        if ( file_exists( $template_path ) ) {
            // Pass data to the template
            $template_data = array(
                'current_user' => $current_user,
                'user_id'      => $user_id,
                'user_meta'    => array(
                    'custom_id'     => get_user_meta( $user_id, 'cds_custom_id', true ),
                    'phone'         => get_user_meta( $user_id, 'billing_phone', true ), // or 'cds_phone'
                    'address_city'  => get_user_meta( $user_id, 'cds_address_city', true ),
                    'address_street'=> get_user_meta( $user_id, 'cds_address_street', true ),
                    'address_number'=> get_user_meta( $user_id, 'cds_address_number', true ),
                    'address_apt'   => get_user_meta( $user_id, 'cds_address_apt', true ),
                    'address_floor' => get_user_meta( $user_id, 'cds_address_floor', true ),
                    'address_entrance' => get_user_meta( $user_id, 'cds_address_entrance', true ),
                    'full_address_display' => get_user_meta($user_id, 'cds_full_address_display', true),
                ),
                'services'     => get_user_meta( $user_id, 'cds_services_overview', true ), // Array of [service_name => status]
                'transactions' => get_user_meta( $user_id, 'cds_transactions', true ), // Array of transaction data
                // You'll need to fetch specific service details like missing docs/denial reasons here too
            );
            
            // Make $template_data available to the included file
            extract($template_data);

            include $template_path;
        } else {
            echo '<p>' . __( 'Error: User dashboard template not found.', 'client-dashboard-system' ) . '</p>';
        }

        // Return the buffered content
        return ob_get_clean();
    }
}
