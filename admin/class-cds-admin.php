<?php
// File: client-dashboard-system/admin/class-cds-admin.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Client Dashboard', 'client-dashboard-system' ), 
            __( 'Client Dashboard', 'client-dashboard-system' ), 
            'manage_options',                                   
            $this->plugin_name,                                 
            array( $this, 'display_plugin_admin_page' ),        
            'dashicons-groups',                                 
            26                                                  
        );

        add_submenu_page(
            $this->plugin_name,
            __( 'Settings', 'client-dashboard-system' ),
            __( 'Settings', 'client-dashboard-system' ),
            'manage_options',
            $this->plugin_name . '-settings',
            array( $this, 'display_settings_page' )
        );

        add_submenu_page(
            $this->plugin_name,
            __( 'Manage Users & Services', 'client-dashboard-system' ),
            __( 'Manage Users/Services', 'client-dashboard-system' ),
            'manage_options', 
            $this->plugin_name . '-manage-users',
            array( $this, 'display_manage_users_page' )
        );
    }

    public function display_plugin_admin_page() {
        require_once CDS_PLUGIN_DIR . 'admin/views/admin-main-page.php';
    }

    public function display_settings_page() {
        require_once CDS_PLUGIN_DIR . 'admin/views/admin-settings-page.php';
    }

    public function display_manage_users_page() {
        require_once CDS_PLUGIN_DIR . 'admin/views/admin-manage-users-page.php';
    }

    public function register_settings() {
        register_setting(
            'cds_settings_group', 
            'cds_webhook_secret', 
            array( 'sanitize_callback' => 'sanitize_text_field' ) 
        );
        
        register_setting(
            'cds_settings_group',
            'cds_dashboard_page_id',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint'
            )
        );

        register_setting(
            'cds_settings_group',
            'cds_support_email',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email'
            )
        );

        register_setting(
            'cds_settings_group',
            'cds_support_whatsapp',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field' 
            )
        );

        add_settings_section(
            'cds_general_settings_section', 
            __( 'General Settings', 'client-dashboard-system' ), 
            array( $this, 'settings_section_callback' ), 
            'cds_settings_page' 
        );

        add_settings_field(
            'cds_webhook_secret',
            __( 'Webhook Secret Key', 'client-dashboard-system' ),
            array( $this, 'webhook_secret_field_callback' ),
            'cds_settings_page',
            'cds_general_settings_section'
        );

        add_settings_field(
            'cds_dashboard_page_id',
            __( 'Client Dashboard Page', 'client-dashboard-system' ),
            array( $this, 'dashboard_page_field_callback' ),
            'cds_settings_page',
            'cds_general_settings_section'
        );
        
        add_settings_field(
            'cds_support_email',
            __( 'Support Email Address', 'client-dashboard-system' ),
            array( $this, 'support_email_field_callback' ),
            'cds_settings_page',
            'cds_general_settings_section'
        );

        add_settings_field(
            'cds_support_whatsapp',
            __( 'Support WhatsApp (Number or Link)', 'client-dashboard-system' ),
            array( $this, 'support_whatsapp_field_callback' ),
            'cds_settings_page',
            'cds_general_settings_section'
        );
    }

    public function settings_section_callback() {
        echo '<p>' . __( 'Configure settings for the Client Dashboard System.', 'client-dashboard-system' ) . '</p>';
        echo '<p>' . __( 'The Webhook URL for your Google Apps Script to send data to is:', 'client-dashboard-system' ) . ' <strong><code>' . esc_url(get_rest_url(null, 'cds/v1/webhook')) . '</code></strong></p>';
        echo '<p>' . __( 'The Webhook Secret Key is used to verify that incoming requests are genuinely from your Google Apps Script. Include this key in a header (e.g., X-Webhook-Secret) in your Apps Script fetch request if you re-enable secret key validation in the plugin.', 'client-dashboard-system' ) . '</p>';
    }

    public function webhook_secret_field_callback() {
        $secret = get_option( 'cds_webhook_secret' );
        printf(
            '<input type="text" id="cds_webhook_secret" name="cds_webhook_secret" value="%s" class="regular-text" />',
            isset( $secret ) ? esc_attr( $secret ) : ''
        );
         echo '<p class="description">' . __( 'A secret key to verify webhook requests. If empty, a default one might be used or generated. (Secret validation might be currently bypassed in the plugin for testing).', 'client-dashboard-system' ) . '</p>';
    }

    public function dashboard_page_field_callback() {
        $selected_page_id = get_option('cds_dashboard_page_id');
        wp_dropdown_pages(array(
            'name'              => 'cds_dashboard_page_id',
            'selected'          => $selected_page_id,
            'show_option_none'  => __('— Select a Page —', 'client-dashboard-system'),
            'option_none_value' => '0',
        ));
        echo '<p class="description">' . __('Select the page where you have placed the <code>[client_dashboard]</code> shortcode. Users will be redirected here after login.', 'client-dashboard-system') . '</p>';
    }
    
    public function support_email_field_callback() {
        $email = get_option('cds_support_email');
        printf(
            '<input type="email" id="cds_support_email" name="cds_support_email" value="%s" class="regular-text" />',
            esc_attr($email ?? '')
        );
        echo '<p class="description">' . __('Email address for client support, displayed on the dashboard.', 'client-dashboard-system') . '</p>';
    }

    public function support_whatsapp_field_callback() {
        $whatsapp = get_option('cds_support_whatsapp');
        printf(
            '<input type="text" id="cds_support_whatsapp" name="cds_support_whatsapp" value="%s" class="regular-text" placeholder="+1234567890 or https://wa.me/..." />',
            esc_attr($whatsapp ?? '')
        );
        echo '<p class="description">' . __('WhatsApp number or direct wa.me link for client support.', 'client-dashboard-system') . '</p>';
    }

    public function enqueue_styles( $hook_suffix ) {
        $allowed_hooks = array(
            'toplevel_page_client-dashboard-system',
            'client-dashboard_page_client-dashboard-system-settings',
            'client-dashboard_page_client-dashboard-system-manage-users'
        );
        if ( !in_array($hook_suffix, $allowed_hooks) ) {
            return;
        }
        wp_enqueue_style( $this->plugin_name . '-admin', CDS_PLUGIN_URL . 'admin/assets/css/admin-style.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts( $hook_suffix ) {
        $allowed_hooks = array(
            'toplevel_page_client-dashboard-system',
            'client-dashboard_page_client-dashboard-system-settings',
            'client-dashboard_page_client-dashboard-system-manage-users'
        );
        if ( !in_array($hook_suffix, $allowed_hooks) ) {
            return;
        }
        wp_enqueue_script( $this->plugin_name . '-admin', CDS_PLUGIN_URL . 'admin/assets/js/admin-script.js', array( 'jquery' ), $this->version, true ); // true for footer
        wp_localize_script($this->plugin_name . '-admin', 'cds_admin_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cds_admin_ajax_nonce'), // Specific nonce for admin AJAX
            'version'  => $this->version 
        ));
    }

    /**
     * Handles AJAX request to update service status.
     * This is a basic example. Needs more robust error handling and user capability checks.
     */
    public function handle_update_service_status() {
        check_ajax_referer('cds_admin_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) { // Or a more specific capability
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'client-dashboard-system')), 403);
            return;
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $service_name = isset($_POST['service_name']) ? sanitize_text_field(wp_unslash($_POST['service_name'])) : '';
        $new_status = isset($_POST['new_status']) ? sanitize_text_field(wp_unslash($_POST['new_status'])) : '';
        $details_text = isset($_POST['details_text']) ? sanitize_textarea_field(wp_unslash($_POST['details_text'])) : ''; // For missing docs or denial reason

        if (!$user_id || empty($service_name) || empty($new_status)) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'client-dashboard-system')), 400);
            return;
        }

        $user_manager = CDS_User_Manager::get_instance();
        $details_for_update = array();
        if ($new_status === 'Missing docs') {
            $details_for_update['missing_docs'] = $details_text;
        } elseif ($new_status === 'Request denied') {
            $details_for_update['denied_reason'] = $details_text;
        } else {
            // For other statuses, if you want to update a general details field
            $details_for_update['general_details'] = $details_text;
        }
        
        $result = $user_manager->update_service_status_manually($user_id, $service_name, $new_status, $details_for_update);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 500);
        } elseif ($result === true) {
            wp_send_json_success(array('message' => __('Service status updated successfully.', 'client-dashboard-system')));
        } else {
             wp_send_json_success(array('message' => __('Service status was already up to date.', 'client-dashboard-system'), 'no_change' => true));
        }
    }
}