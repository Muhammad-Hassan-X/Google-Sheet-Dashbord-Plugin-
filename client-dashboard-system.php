<?php
/**
 * Plugin Name:       Client Dashboard System
 * Plugin URI:        https://example.com/client-dashboard-system
 * Description:       Integrates with Google Sheets to create users and display service statuses in a client dashboard.
 * Version:           0.1.0
 * Author:            Muhammad Hassan
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       client-dashboard-system
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define Plugin Constants
 */
define( 'CDS_PLUGIN_VERSION', '0.1.0' );
define( 'CDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CDS_PLUGIN_FILE', __FILE__ );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
class Client_Dashboard_System {

    private static $instance;

    /**
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        // Hook for handling webhook requests
        add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        require_once CDS_PLUGIN_DIR . 'admin/class-cds-admin.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-google-sheets-handler.php';
        require_once CDS_PLUGIN_DIR . 'public/class-cds-public.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-user-manager.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-notifications.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-login-handler.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new CDS_Admin( $this->get_plugin_name(), $this->get_version() );

        add_action( 'admin_menu', array( $plugin_admin, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $plugin_admin, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

        // Add other admin hooks here, e.g., for handling form submissions from admin pages
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks() {
        $plugin_public = new CDS_Public( $this->get_plugin_name(), $this->get_version() );
        $login_handler = CDS_Login_Handler::get_instance();

        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        add_shortcode( 'client_dashboard', array( $plugin_public, 'render_client_dashboard_shortcode' ) );

        // Custom login hooks
        add_action('init', array($login_handler, 'handle_custom_login'));
        add_filter('authenticate', array($login_handler, 'authenticate_by_custom_id'), 20, 3);
        add_action('login_form', array($login_handler, 'custom_login_form_fields')); // To add ID field
        add_action('wp_login_failed', array($login_handler, 'custom_login_failed'));
        add_filter('login_redirect', array($login_handler, 'custom_login_redirect'), 10, 3);

    }

    /**
     * Register a custom REST API endpoint for webhooks.
     * Example: your-site.com/wp-json/cds/v1/webhook
     */
    public function register_webhook_endpoint() {
        register_rest_route( 'cds/v1', '/webhook', array(
            'methods' => 'POST', // Or 'GET' or WP_REST_Server::ALLMETHODS depending on webhook
            'callback' => array( CDS_Google_Sheets_Handler::get_instance(), 'handle_webhook_data' ),
            'permission_callback' => '__return_true' // IMPORTANT: Implement proper authentication/validation here
        ) );
    }


    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return 'client-dashboard-system';
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return CDS_PLUGIN_VERSION;
    }
}

/**
 * Begins execution of the plugin.
 */
function run_client_dashboard_system() {
    $plugin = Client_Dashboard_System::get_instance();
}
run_client_dashboard_system();

/**
 * Activation hook.
 * Create necessary options, tables, or roles.
 */
function cds_activate_plugin() {
    // Example: Set a default option
    if ( get_option( 'cds_google_sheets_webhook_url' ) === false ) {
        add_option( 'cds_google_sheets_webhook_url', '' );
    }
    // Example: Add a custom user role if needed (though typically not for this kind of plugin)
    // add_role('client_user', 'Client User', array('read' => true));

    // Flush rewrite rules if you've added custom post types or taxonomies (not in this initial setup)
    // flush_rewrite_rules();
}
register_activation_hook( CDS_PLUGIN_FILE, 'cds_activate_plugin' );

/**
 * Deactivation hook.
 * Clean up options, tables, or roles if necessary.
 */
function cds_deactivate_plugin() {
    // Example: Delete an option
    // delete_option( 'cds_google_sheets_webhook_url' );

    // Example: Remove custom role
    // remove_role('client_user');

    // Flush rewrite rules
    // flush_rewrite_rules();
}
register_deactivation_hook( CDS_PLUGIN_FILE, 'cds_deactivate_plugin' );

/**
 * Folder structure to create:
 *
 * client-dashboard-system/
 * | -- client-dashboard-system.php  (this file)
 * | -- admin/
 * |    | -- class-cds-admin.php
 * |    | -- assets/
 * |    |    | -- css/
 * |    |    |    | -- admin-style.css
 * |    |    | -- js/
 * |    |    |    | -- admin-script.js
 * |    | -- views/
 * |    |    | -- admin-settings-page.php
 * | -- includes/
 * |    | -- class-cds-google-sheets-handler.php
 * |    | -- class-cds-user-manager.php
 * |    | -- class-cds-notifications.php
 * |    | -- class-cds-login-handler.php
 * | -- public/
 * |    | -- class-cds-public.php
 * |    | -- assets/
 * |    |    | -- css/
 * |    |    |    | -- public-style.css
 * |    |    | -- js/
 * |    |    |    | -- public-script.js
 * |    | -- views/
 * |    |    | -- user-dashboard-template.php
 * |    |    | -- login-form-template.php (for ID based login)
 * | -- languages/
 * |    | -- client-dashboard-system.pot (for translations)
 * | -- uninstall.php (handles plugin removal)
 */
