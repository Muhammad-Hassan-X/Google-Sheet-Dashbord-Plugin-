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

    /**
     * Add admin menu page.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Client Dashboard', 'client-dashboard-system' ), // Page Title
            __( 'Client Dashboard', 'client-dashboard-system' ), // Menu Title
            'manage_options',                                   // Capability
            $this->plugin_name,                                 // Menu Slug
            array( $this, 'display_plugin_admin_page' ),        // Callback function
            'dashicons-groups',                                 // Icon URL
            26                                                  // Position
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
            'manage_options', // Or a more specific capability
            $this->plugin_name . '-manage-users',
            array( $this, 'display_manage_users_page' )
        );
    }

    /**
     * Display the main admin page (overview or dashboard for the plugin).
     */
    public function display_plugin_admin_page() {
        // For now, redirect to settings, or create a dedicated overview page.
        // For simplicity, let's make this an alias for settings for now or a simple welcome.
        require_once CDS_PLUGIN_DIR . 'admin/views/admin-main-page.php';
    }

    /**
     * Display the settings page.
     */
    public function display_settings_page() {
        require_once CDS_PLUGIN_DIR . 'admin/views/admin-settings-page.php';
    }

    /**
     * Display the manage users and services page.
     */
    public function display_manage_users_page() {
        require_once CDS_PLUGIN_DIR . 'admin/views/admin-manage-users-page.php';
    }


    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // Register a setting for Google Sheets API Key (if using direct API calls)
        register_setting(
            'cds_settings_group', // Option group
            'cds_google_api_key', // Option name
            array( 'sanitize_callback' => 'sanitize_text_field' ) // Sanitization callback
        );

        // Register a setting for Webhook Secret (to validate incoming webhooks)
        register_setting(
            'cds_settings_group',
            'cds_webhook_secret',
            array( 'sanitize_callback' => 'sanitize_text_field' )
        );

        // Register a setting for the number of Google Sheets
        register_setting(
            'cds_settings_group',
            'cds_google_sheets_count',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint', // Ensures it's a positive integer
                'default' => 5
            )
        );

        // Dynamically register settings for each Google Sheet URL
        $sheet_count = get_option('cds_google_sheets_count', 5);
        for ($i = 1; $i <= $sheet_count; $i++) {
            register_setting(
                'cds_settings_group',
                'cds_google_sheet_url_' . $i,
                array(
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw' // Sanitizes URL
                )
            );
        }


        // Add settings section
        add_settings_section(
            'cds_general_settings_section', // ID
            __( 'Google Sheets Integration Settings', 'client-dashboard-system' ), // Title
            array( $this, 'settings_section_callback' ), // Callback
            'cds_settings_page' // Page on which to show this section (slug of settings page)
        );

        // Add settings fields
        add_settings_field(
            'cds_google_api_key',
            __( 'Google API Key (Optional)', 'client-dashboard-system' ),
            array( $this, 'api_key_field_callback' ),
            'cds_settings_page',
            'cds_general_settings_section'
        );

        add_settings_field(
            'cds_webhook_secret',
            __( 'Webhook Secret Key', 'client-dashboard-system' ),
            array( $this, 'webhook_secret_field_callback' ),
            'cds_settings_page',
            'cds_general_settings_section'
        );
        
        add_settings_field(
            'cds_google_sheets_count',
            __( 'Number of Google Sheets', 'client-dashboard-system' ),
            array( $this, 'google_sheets_count_field_callback' ),
            'cds_settings_page',
            'cds_general_settings_section'
        );

        for ($i = 1; $i <= $sheet_count; $i++) {
            add_settings_field(
                'cds_google_sheet_url_' . $i,
                sprintf(__( 'Google Sheet URL %d', 'client-dashboard-system' ), $i),
                array( $this, 'google_sheet_url_field_callback' ),
                'cds_settings_page',
                'cds_general_settings_section',
                array( 'sheet_number' => $i ) // Pass sheet number to callback
            );
        }
    }

    public function settings_section_callback() {
        echo '<p>' . __( 'Configure settings for Google Sheets integration. If using webhooks, ensure your Google Apps Script is configured to send data to:', 'client-dashboard-system' ) . ' <code>' . esc_url(get_rest_url(null, 'cds/v1/webhook')) . '</code></p>';
        echo '<p>' . __( 'The Webhook Secret Key is used to verify that incoming requests are genuinely from your Google Apps Script. Include this key in a header (e.g., X-Webhook-Secret) in your Apps Script fetch request.', 'client-dashboard-system' ) . '</p>';
    }

    public function api_key_field_callback() {
        $api_key = get_option( 'cds_google_api_key' );
        printf(
            '<input type="text" id="cds_google_api_key" name="cds_google_api_key" value="%s" class="regular-text" />',
            isset( $api_key ) ? esc_attr( $api_key ) : ''
        );
        echo '<p class="description">' . __( 'Enter your Google API Key if you plan to fetch data directly (e.g., for periodic sync). Not required if solely using webhooks.', 'client-dashboard-system' ) . '</p>';
    }

    public function webhook_secret_field_callback() {
        $secret = get_option( 'cds_webhook_secret' );
        printf(
            '<input type="text" id="cds_webhook_secret" name="cds_webhook_secret" value="%s" class="regular-text" />',
            isset( $secret ) ? esc_attr( $secret ) : ''
        );
         echo '<p class="description">' . __( 'A secret key to verify webhook requests. Keep this confidential.', 'client-dashboard-system' ) . '</p>';
    }
    
    public function google_sheets_count_field_callback() {
        $count = get_option('cds_google_sheets_count', 5);
        printf(
            '<input type="number" id="cds_google_sheets_count" name="cds_google_sheets_count" value="%d" class="small-text" min="1" max="20" />',
            esc_attr($count)
        );
        echo '<p class="description">' . __('Enter the number of Google Sheets you will be integrating (1-20). Save changes for the URL fields below to update.', 'client-dashboard-system') . '</p>';
    }

    public function google_sheet_url_field_callback($args) {
        $sheet_number = $args['sheet_number'];
        $option_name = 'cds_google_sheet_url_' . $sheet_number;
        $url = get_option($option_name);
        printf(
            '<input type="url" id="%s" name="%s" value="%s" class="regular-text" placeholder="https://docs.google.com/spreadsheets/d/.../edit" />',
            esc_attr($option_name),
            esc_attr($option_name),
            isset($url) ? esc_url($url) : ''
        );
    }


    /**
     * Enqueue admin-specific stylesheets.
     */
    public function enqueue_styles( $hook_suffix ) {
        // Only load on our plugin's admin pages
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

    /**
     * Enqueue admin-specific JavaScript.
     */
    public function enqueue_scripts( $hook_suffix ) {
         // Only load on our plugin's admin pages
        $allowed_hooks = array(
            'toplevel_page_client-dashboard-system',
            'client-dashboard_page_client-dashboard-system-settings',
            'client-dashboard_page_client-dashboard-system-manage-users'
        );
        if ( !in_array($hook_suffix, $allowed_hooks) ) {
            return;
        }
        wp_enqueue_script( $this->plugin_name . '-admin', CDS_PLUGIN_URL . 'admin/assets/js/admin-script.js', array( 'jquery' ), $this->version, false );
        // Localize script if you need to pass PHP variables to JS
        wp_localize_script($this->plugin_name . '-admin', 'cds_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cds_admin_nonce')
        ));
    }
}
