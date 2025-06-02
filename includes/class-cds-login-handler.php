<?php
// File: client-dashboard-system/includes/class-cds-login-handler.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_Login_Handler {

    private static $instance;
    const CUSTOM_ID_META_KEY = 'cds_custom_id'; // Meta key for custom ID

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
     * Adds custom ID field to the WordPress login form.
     */
    public function custom_login_form_fields() {
        // Path to the login form template
        $template_path = CDS_PLUGIN_DIR . 'public/views/login-form-id-field-template.php';
        if (file_exists($template_path)) {
            load_template($template_path, false);
        } else {
            // Fallback basic field
            ?>
            <p>
                <label for="user_custom_id"><?php esc_html_e( 'Client ID', 'client-dashboard-system' ); ?><br />
                <input type="text" name="user_custom_id" id="user_custom_id" class="input" value="" size="20" /></label>
            </p>
            <?php
        }
    }


    /**
     * Authenticates a user by their custom ID.
     * The standard username/password fields will be ignored if custom ID is provided and valid.
     *
     * @param WP_User|WP_Error|null $user     WP_User object if authentication succeeded, WP_Error or null otherwise.
     * @param string                $username Username.
     * @param string                $password Password.
     * @return WP_User|WP_Error|null WP_User on success, WP_Error on failure, or null to pass to next auth method.
     */
    public function authenticate_by_custom_id( $user, $username, $password ) {
        // Check if our custom ID field is submitted and not empty
        if ( isset( $_POST['user_custom_id'] ) && ! empty( $_POST['user_custom_id'] ) ) {
            $custom_id = sanitize_text_field( $_POST['user_custom_id'] );

            // Query for the user by custom ID meta
            $user_query = new WP_User_Query( array(
                'meta_key'   => self::CUSTOM_ID_META_KEY,
                'meta_value' => $custom_id,
                'number'     => 1,
                'count_total' => false,
                'fields'     => 'ID', // Get only the ID
            ) );

            $found_users = $user_query->get_results();

            if ( ! empty( $found_users ) ) {
                $user_id = $found_users[0];
                // Remove other authentication hooks to prevent password check for this login
                remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
                remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
                // Return the user object
                return get_user_by( 'ID', $user_id );
            } else {
                // Custom ID not found, return an error to prevent standard login attempt if ID was the intended method.
                // Or, you could allow fallback to username/password if custom ID fails.
                // For "ID#-based login (simplified)", we assume ID is the primary method.
                return new WP_Error( 'cds_invalid_custom_id', __( '<strong>Error:</strong> Invalid Client ID.', 'client-dashboard-system' ) );
            }
        }
        // If custom ID field is not set or empty, let standard authentication proceed.
        return $user;
    }

    /**
     * Handles the custom login process if a specific action is set.
     * This is more for a completely custom login form, not just modifying wp-login.php.
     * For now, we're modifying wp-login.php, so this might not be strictly necessary
     * unless you build a separate login page/form.
     */
    public function handle_custom_login() {
        if ( isset( $_POST['cds_custom_login_nonce'] ) && wp_verify_nonce( $_POST['cds_custom_login_nonce'], 'cds_custom_login_action' ) ) {
            // This part would be for a fully custom login form submission
            // The `authenticate_by_custom_id` hook is usually sufficient for wp-login.php
        }
    }

     /**
     * Handles login failures, specifically for custom ID.
     *
     * @param string $username The username that failed.
     */
    public function custom_login_failed( $username ) {
        // If the login failed and it was due to our custom ID check,
        // we might want to redirect back with a specific error query arg.
        // The WP_Error returned by `authenticate_by_custom_id` should already display the message.
        // This function is more for logging or adding extra query args if needed.

        // Check if the referring page is the login page and if our custom ID was attempted
        if ( isset( $_POST['user_custom_id'] ) && !empty($_POST['user_custom_id']) ) {
            $referrer = $_SERVER['HTTP_REFERER'];
            // Prevent redirect loops or redirecting to non-login pages
            if ( !empty($referrer) && strpos($referrer, 'wp-login.php') !== false ) {
                // Add a query arg to indicate custom ID login failure
                // The WP_Error message should already be shown by WordPress.
                // This is if you want to add more specific handling or query args.
                // wp_redirect( add_query_arg( 'login_custom_id_failed', '1', $referrer ) );
                // exit;
            }
        }
    }


    /**
     * Redirects user to their dashboard after successful login.
     *
     * @param string  $redirect_to           The redirect destination URL.
     * @param string  $requested_redirect_to The requested redirect destination URL (if provided).
     * @param WP_User $user                  WP_User object if login was successful, WP_Error object otherwise.
     * @return string The new redirect destination URL.
     */
    public function custom_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
        // Check if login was successful and user object is valid
        if ( ! is_wp_error( $user ) && $user && $user->ID ) {
            // Check if this login was potentially via our custom ID method
            // (though this hook runs after successful auth regardless of method)
            // If you want this redirect *only* for custom ID logins, you'd need a flag.

            // Get the URL of the page where [client_dashboard] shortcode is placed.
            // Assume the page slug is 'client-dashboard'. Create this page in WP Admin.
            $dashboard_page = get_page_by_path( 'client-dashboard' );
            if ( $dashboard_page ) {
                return get_permalink( $dashboard_page->ID );
            } else {
                // Fallback to default behavior or home URL if dashboard page doesn't exist
                return $redirect_to; // Or home_url();
            }
        }
        return $redirect_to; // Return original redirect_to if login failed or no user
    }
}
