<?php
/**
 * Plugin Name:       Client Dashboard System
 * Plugin URI:        https://example.com/client-dashboard-system
 * Description:       Integrates with Google Sheets to create users and display service statuses in a client dashboard.
 * Version:           0.1.3
 * Author:            Your Name
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
define( 'CDS_PLUGIN_VERSION', '0.1.3' );
define( 'CDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CDS_PLUGIN_FILE', __FILE__ );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
class Client_Dashboard_System {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
    }

    private function load_dependencies() {
        require_once CDS_PLUGIN_DIR . 'admin/class-cds-admin.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-google-sheets-handler.php';
        require_once CDS_PLUGIN_DIR . 'public/class-cds-public.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-user-manager.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-notifications.php';
        require_once CDS_PLUGIN_DIR . 'includes/class-cds-login-handler.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new CDS_Admin( $this->get_plugin_name(), $this->get_version() );
        add_action( 'admin_menu', array( $plugin_admin, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $plugin_admin, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
        // AJAX handler for updating service status (example)
        add_action( 'wp_ajax_cds_update_service_status', array( $plugin_admin, 'handle_update_service_status' ) );
    }

    private function define_public_hooks() {
        $plugin_public = new CDS_Public( $this->get_plugin_name(), $this->get_version() );
        $login_handler = CDS_Login_Handler::get_instance();
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        add_shortcode( 'client_dashboard', array( $plugin_public, 'render_client_dashboard_shortcode' ) );
        add_action('init', array($login_handler, 'handle_custom_login'));
        add_filter('authenticate', array($login_handler, 'authenticate_by_custom_id'), 20, 3);
        add_action('login_form', array($login_handler, 'custom_login_form_fields')); 
        add_action('wp_login_failed', array($login_handler, 'custom_login_failed'));
        add_filter('login_redirect', array($login_handler, 'custom_login_redirect'), 10, 3);
        add_action('login_enqueue_scripts', array($login_handler, 'enqueue_login_scripts')); 
    }

    public function register_webhook_endpoint() {
        register_rest_route( 'cds/v1', '/webhook', array(
            'methods' => WP_REST_Server::CREATABLE, // For POST requests
            'callback' => array( CDS_Google_Sheets_Handler::get_instance(), 'handle_webhook_data' ),
            'permission_callback' => '__return_true' // For testing: allows any request. Secure this for production!
        ) );
    }

    public function get_plugin_name() {
        return 'client-dashboard-system';
    }

    public function get_version() {
        return CDS_PLUGIN_VERSION;
    }
}

function run_client_dashboard_system() {
    $plugin = Client_Dashboard_System::get_instance();
}
run_client_dashboard_system();

function cds_activate_plugin() {
    if ( get_option( 'cds_webhook_secret' ) === false ) {
        add_option( 'cds_webhook_secret', wp_generate_password(32, false) );
    }
     if ( get_option( 'cds_dashboard_page_id' ) === false ) {
        add_option( 'cds_dashboard_page_id', '' );
    }
    flush_rewrite_rules();
}
register_activation_hook( CDS_PLUGIN_FILE, 'cds_activate_plugin' );

function cds_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook( CDS_PLUGIN_FILE, 'cds_deactivate_plugin' );