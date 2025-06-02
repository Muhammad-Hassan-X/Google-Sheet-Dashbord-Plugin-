<?php
// File: client-dashboard-system/includes/class-cds-user-manager.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_User_Manager {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooks related to user profile updates if needed
        // add_action( 'personal_options_update', array( $this, 'save_custom_user_fields' ) );
        // add_action( 'edit_user_profile_update', array( $this, 'save_custom_user_fields' ) );
        // add_action( 'show_user_profile', array( $this, 'display_custom_user_fields' ) );
        // add_action( 'edit_user_profile', array( $this, 'display_custom_user_fields' ) );
    }

    /**
     * Processes a single data entry from Google Sheets (either via webhook or API sync).
     * Creates or updates a WordPress user and their associated service/transaction data.
     *
     * @param array $data The data for a single user/transaction from the sheet.
     * Expected keys: 'id', 'email', 'name', 'phone', 'address_city',
     * 'address_street', 'address_number', 'address_apt',
     * 'address_floor', 'address_entrance',
     * 'service_name_1', 'service_status_1', ...
     * @return int|WP_Error User ID on success, WP_Error on failure.
     */
    public function process_sheet_data_entry( $data ) {
        // Validate required fields (ID and email are crucial)
        if ( empty( $data['id'] ) || empty( $data['email'] ) ) {
            return new WP_Error( 'missing_required_fields', 'User ID or Email is missing from sheet data.', $data );
        }

        $user_id_custom = sanitize_text_field( $data['id'] ); // This is the custom ID from sheet
        $email = sanitize_email( $data['email'] );
        $name_parts = explode(' ', sanitize_text_field( $data['name'] ?? '' ), 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        $phone = sanitize_text_field( $data['phone'] ?? '' );

        // Address components
        $address_city = sanitize_text_field( $data['address_city'] ?? '' );
        $address_street = sanitize_text_field( $data['address_street'] ?? '' );
        $address_number = sanitize_text_field( $data['address_number'] ?? '' );
        // ... other address fields

        $full_address_string = trim(sprintf('%s, %s %s, %s', $address_street, $address_number, $data['address_apt'] ?? '', $address_city));


        // Try to find user by custom ID meta field first
        $user = $this->get_user_by_custom_id( $user_id_custom );

        if ( ! $user ) {
            // If not found by custom ID, try by email
            $user = get_user_by( 'email', $email );
        }

        $user_data = array(
            'user_email' => $email,
            'user_login' => $email, // Or generate a unique username based on ID/email
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'display_name' => trim("$first_name $last_name"),
            'user_pass'  => wp_generate_password(), // Generate a random password
            'role'       => 'subscriber' // Or your custom client role
        );

        $wp_user_id = null;

        if ( $user ) {
            // User exists, update them
            $wp_user_id = $user->ID;
            $user_data['ID'] = $wp_user_id;
            $result = wp_update_user( $user_data );
            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'user_update_failed', 'Failed to update user: ' . $result->get_error_message(), $data );
            }
        } else {
            // User does not exist, create them
            $result = wp_insert_user( $user_data );
            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'user_create_failed', 'Failed to create user: ' . $result->get_error_message(), $data );
            }
            $wp_user_id = $result;
            // No email notification for account creation as per spec
        }

        // Update/add custom user meta
        update_user_meta( $wp_user_id, 'cds_custom_id', $user_id_custom );
        if ($phone) update_user_meta( $wp_user_id, 'billing_phone', $phone ); // Standard WC field, or use custom
        
        // Store address components. You might want to group them in an array meta.
        update_user_meta( $wp_user_id, 'cds_address_city', $address_city );
        update_user_meta( $wp_user_id, 'cds_address_street', $address_street );
        update_user_meta( $wp_user_id, 'cds_address_number', $address_number );
        update_user_meta( $wp_user_id, 'cds_address_apt', sanitize_text_field( $data['address_apt'] ?? '' ) );
        update_user_meta( $wp_user_id, 'cds_address_floor', sanitize_text_field( $data['address_floor'] ?? '' ) );
        update_user_meta( $wp_user_id, 'cds_address_entrance', sanitize_text_field( $data['address_entrance'] ?? '' ) );
        update_user_meta( $wp_user_id, 'cds_full_address_display', $full_address_string);


        // Process services and transactions
        // Each address is a transaction. Services are associated with users.
        // This logic needs to be more robust. How are services linked if a user has multiple transactions/addresses?
        // For now, let's assume services are general to the user, and transactions are separate.

        // Store/Update Transaction (based on address)
        // A transaction could be a custom post type or custom table entry linked to the user.
        // For simplicity, let's store transactions as user meta for now, assuming one main transaction per user from a sheet row.
        // This needs refinement if a user can have multiple distinct transactions from different sheet rows over time.
        $transaction_data = array(
            'deal_name' => $full_address_string, // Address is deal name
            'city'      => $address_city,
            'street'    => $address_street,
            'number'    => $address_number,
            'apt'       => sanitize_text_field( $data['address_apt'] ?? '' ),
            'floor'     => sanitize_text_field( $data['address_floor'] ?? '' ),
            'entrance'  => sanitize_text_field( $data['address_entrance'] ?? '' ),
            'timestamp' => current_time('mysql')
        );
        // This simplistic model adds the transaction to a list of transactions for the user.
        $transactions = get_user_meta($wp_user_id, 'cds_transactions', true);
        if (!is_array($transactions)) {
            $transactions = array();
        }
        // To avoid duplicates if the same address data comes again, we might need an ID for the transaction itself.
        // For now, just append. Consider how to update existing transactions.
        $transactions[] = $transaction_data;
        update_user_meta($wp_user_id, 'cds_transactions', $transactions);


        // Process service statuses
        // Assuming service data comes in pairs like 'service_name_1', 'service_status_1', 'service_name_2', 'service_status_2'
        $services = array();
        $i = 1;
        while ( isset( $data['service_name_' . $i] ) && isset( $data['service_status_' . $i] ) ) {
            $service_name = sanitize_text_field( $data['service_name_' . $i] );
            $current_status = sanitize_text_field( $data['service_status_' . $i] );
            
            $old_status_meta_key = 'cds_service_status_' . sanitize_key($service_name);
            $old_status = get_user_meta($wp_user_id, $old_status_meta_key, true);

            if ($old_status !== $current_status) {
                update_user_meta( $wp_user_id, $old_status_meta_key, $current_status );
                // Store missing docs or denied reasons if provided
                if ($current_status === 'Missing docs' && isset($data['service_missing_docs_' . $i])) {
                    update_user_meta( $wp_user_id, 'cds_service_missing_docs_' . sanitize_key($service_name), sanitize_textarea_field($data['service_missing_docs_' . $i]) );
                }
                if ($current_status === 'Request denied' && isset($data['service_denied_reason_' . $i])) {
                     update_user_meta( $wp_user_id, 'cds_service_denied_reason_' . sanitize_key($service_name), sanitize_textarea_field($data['service_denied_reason_' . $i]) );
                }

                // Trigger notification
                CDS_Notifications::get_instance()->send_status_change_email( $wp_user_id, $service_name, $old_status, $current_status );
            }
            $services[$service_name] = $current_status;
            $i++;
        }
        // Optionally, store all services in a single meta field as an array
        update_user_meta( $wp_user_id, 'cds_services_overview', $services );


        return $wp_user_id;
    }

    /**
     * Retrieves a WordPress user object by their custom ID stored in user meta.
     *
     * @param string $custom_id The custom ID to search for.
     * @return WP_User|false WP_User object if found, false otherwise.
     */
    public function get_user_by_custom_id( $custom_id ) {
        $user_query = new WP_User_Query( array(
            'meta_key'   => 'cds_custom_id',
            'meta_value' => $custom_id,
            'number'     => 1,
            'count_total' => false
        ) );
        $users = $user_query->get_results();
        return $users ? $users[0] : false;
    }

    /**
     * Manually updates a service status for a given user.
     * Called from the admin dashboard.
     *
     * @param int $user_id The WordPress User ID.
     * @param string $service_name The name of the service.
     * @param string $new_status The new status.
     * @param array $details Optional details (e.g., missing_docs_list, denied_reason_list).
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function update_service_status_manually( $user_id, $service_name, $new_status, $details = array() ) {
        if ( ! get_user_by('ID', $user_id) ) {
            return new WP_Error('invalid_user', 'User ID does not exist.');
        }
        if (empty($service_name) || empty($new_status)) {
            return new WP_Error('missing_params', 'Service name or new status is empty.');
        }

        $service_key = sanitize_key($service_name);
        $status_meta_key = 'cds_service_status_' . $service_key;
        $old_status = get_user_meta($user_id, $status_meta_key, true);

        if ($old_status !== $new_status) {
            update_user_meta($user_id, $status_meta_key, $new_status);

            // Update details like missing docs or denied reasons
            if ($new_status === 'Missing docs' && !empty($details['missing_docs'])) {
                update_user_meta($user_id, 'cds_service_missing_docs_' . $service_key, sanitize_textarea_field($details['missing_docs']));
            } else {
                delete_user_meta($user_id, 'cds_service_missing_docs_' . $service_key); // Clear if not applicable
            }

            if ($new_status === 'Request denied' && !empty($details['denied_reason'])) {
                update_user_meta($user_id, 'cds_service_denied_reason_' . $service_key, sanitize_textarea_field($details['denied_reason']));
            } else {
                delete_user_meta($user_id, 'cds_service_denied_reason_' . $service_key); // Clear if not applicable
            }
            
            // Update the overview meta
            $services_overview = get_user_meta($user_id, 'cds_services_overview', true);
            if (!is_array($services_overview)) $services_overview = [];
            $services_overview[$service_name] = $new_status;
            update_user_meta($user_id, 'cds_services_overview', $services_overview);

            CDS_Notifications::get_instance()->send_status_change_email( $user_id, $service_name, $old_status, $new_status );
            return true;
        }
        return false; // Status was already the same
    }

    // Add functions for admin to manually create/update users if needed.
    // These would take form data, sanitize it, and call wp_insert_user or wp_update_user,
    // along with updating all the custom meta fields.
}
