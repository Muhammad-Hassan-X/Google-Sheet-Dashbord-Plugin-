<?php
// File: client-dashboard-system/includes/class-cds-login-handler.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_Login_Handler {

    private static $instance;
    const CUSTOM_ID_META_KEY = 'cds_custom_id'; 

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function custom_login_form_fields() {
        $template_path = CDS_PLUGIN_DIR . 'public/views/login-form-id-field-template.php';
        if (file_exists($template_path)) {
            load_template($template_path, false);
        } else {
            echo '<p><label for="user_custom_id">' . esc_html__( 'Client ID', 'client-dashboard-system' ) . '<br /><input type="text" name="user_custom_id" id="user_custom_id" class="input" value="" size="20" autocapitalize="off" autocomplete="off" /></label></p>';
        }
    }
    public function enqueue_login_scripts() {
        wp_enqueue_script(
            'cds-login-script', // A unique handle for our script
            CDS_PLUGIN_URL . 'public/assets/js/login-script.js',
            array('jquery'),
            CDS_PLUGIN_VERSION,
            true // Load in footer
        );
    }

    public function authenticate_by_custom_id( $user, $username, $password ) {
        if ( isset( $_POST['user_custom_id'] ) && ! empty( trim($_POST['user_custom_id']) ) ) {
            $custom_id = sanitize_text_field( trim($_POST['user_custom_id']) );
            $user_found_by_id = CDS_User_Manager::get_instance()->get_user_by_custom_id( $custom_id );

            if ( $user_found_by_id ) {
                remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
                remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
                return $user_found_by_id; 
            } else {
                return new WP_Error( 'cds_invalid_custom_id', __( '<strong>ERROR</strong>: The Client ID you entered is not valid.', 'client-dashboard-system' ) );
            }
        }
        return $user;
    }

    public function handle_custom_login() {}

    public function custom_login_failed( $username_or_error_code ) {
        if ( isset( $_POST['user_custom_id'] ) && ! empty( trim( $_POST['user_custom_id'] ) ) ) {
             if ( empty( $_POST['log'] ) && empty( $_POST['pwd'] ) ) { 
                error_log("CDS Login: Custom ID login attempt failed for ID: " . sanitize_text_field($_POST['user_custom_id']));
             }
        }
    }

    public function custom_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
        if ( ! is_wp_error( $user ) && $user && $user->ID ) {
            if ( user_can( $user, 'manage_options' ) && $requested_redirect_to === admin_url() ) {
                return $redirect_to; 
            }

            $dashboard_page_id = get_option('cds_dashboard_page_id');
            if ( $dashboard_page_id && get_post_status($dashboard_page_id) === 'publish' ) {
                return get_permalink( $dashboard_page_id );
            } else {
                if (user_can($user, 'manage_options')) {
                    return admin_url();
                }
                return admin_url('profile.php'); 
            }
        }
        return $redirect_to; 
    }
}