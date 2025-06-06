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

    private function __construct() {}

    /**
     * Processes a single data entry (one row object) from the webhook.
     * The $data array keys are expected to be the actual header names from your Google Sheet.
     *
     * @param array $data The associative array of data for a single client.
     * @return int|WP_Error User ID on success, WP_Error on failure.
     */
    public function process_sheet_data_entry( $data ) {
        // --- 1. DEFINE MAPPING: Sheet Header Text -> Plugin's Internal Use ---
        // ** YOU MUST REVIEW AND UPDATE THESE HEADER NAMES TO MATCH YOUR SHEET EXACTLY **
        // These should be the literal text from the first row of your Google Sheet.
        
        // Essential for creating a WP user and for login
        // Try to use keys from your provided sample JSON, assuming they are the direct headers.
        $header_for_unique_client_id = 'Submission ID';         // From your sample: "Submission ID": "6239792998426489392" OR "unique id": "MZK6389"
                                                                // OR "מס' ת\"ז": 317745990. "Submission ID" is often good for forms.
        $header_for_client_email     = 'main user email';       // From your sample: "main user email": "deni3107@mail.ru"
        $header_for_client_fullname  = 'main user full name';   // From your sample: "main user full name": "אנה פרידמן"

        // Recommended for dashboard
        $header_for_client_phone     = 'main user phone number';// From your sample: "main user phone number": 545728867
                                                                // Fallback could be "מס טלפון נייד / CELL PHONE" or "My Phone"

        $header_for_address_city     = 'שם הישוב / CITY NAME';   // From your sample
        $header_for_address_street   = 'שם הרחוב / STREET NAME'; // From your sample
        $header_for_address_number   = 'מספר בית / BUILDING NUMBER';// From your sample
        $header_for_address_apt      = 'מספר דירה / APARTMENT NUMBER';// From your sample
        $header_for_address_floor    = 'קומה / FLOOR';            // From your sample
        $header_for_address_entrance = 'כניסה / ENTRANCE';        // From your sample
        $header_for_full_address     = 'כתובת מלאה';           // From your sample: "כתובת מלאה": "עמק יזרעאל 98 דירה 13, אשקלון"

        // For services
        $header_for_services_being_transferred = '?אילו מהחשבונות מעבירים'; // From your sample: "חשמל\nמים\nארנונה"
        // Headers for service specific details (examples from your sample)
        $header_for_arnona_property_id  = 'מספר נכס ארנונה /TAX PROPERTY ID';
        $header_for_water_property_id   = 'מספר נכס מים / WATER PROPERTY ID';
        $header_for_electricity_account = 'מספר חשבון חוזה חשמל';
        $header_for_gas_consumer_no     = 'מספר צרכן גז';
        // IMPORTANT: Your sheet needs columns for the STATUS of each of these services.
        // Example: $header_for_arnona_status = "סטטוס העברת ארנונה"; (Arnona Transfer Status) - ADD THIS TO YOUR SHEET AND HERE

        // --- 2. Extract Data Using Your Defined Header Keys ---
        $client_id_from_sheet = isset($data[$header_for_unique_client_id]) ? sanitize_text_field(trim($data[$header_for_unique_client_id])) : null;
        
        $email_from_sheet = null;
        if (isset($data[$header_for_client_email]) && !empty(trim($data[$header_for_client_email])) && is_email(trim($data[$header_for_client_email]))) {
            $email_from_sheet = sanitize_email(trim($data[$header_for_client_email]));
        } elseif (isset($data['דואר אלקטרוני / EMAIL']) && !empty(trim($data['דואר אלקטרוני / EMAIL'])) && is_email(trim($data['דואר אלקטרוני / EMAIL']))) {
            // Fallback if "main user email" is empty but "דואר אלקטרוני / EMAIL" is present
            $email_from_sheet = sanitize_email(trim($data['דואר אלקטרוני / EMAIL']));
        }


        if ( empty( $client_id_from_sheet ) ) {
            return new WP_Error( 'missing_client_id_php', "Required ID field (expected from sheet header '{$header_for_unique_client_id}') is missing or empty.", $data );
        }
        if ( empty( $email_from_sheet ) ) {
            return new WP_Error( 'missing_email_php', "Required Email field (expected from sheet header '{$header_for_client_email}' or fallback) is missing, empty, or invalid.", $data );
        }

        $full_name = isset($data[$header_for_client_fullname]) ? sanitize_text_field(trim($data[$header_for_client_fullname])) : '';
        if (empty($full_name) && isset($data['שם / FIRST NAME']) && isset($data['שם משפחה / LAST NAME'])) {
            $full_name = sanitize_text_field(trim($data['שם / FIRST NAME'])) . ' ' . sanitize_text_field(trim($data['שם משפחה / LAST NAME']));
        }
        $name_parts = explode(' ', trim($full_name), 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? $first_name; 

        $phone = isset($data[$header_for_client_phone]) ? sanitize_text_field(preg_replace('/[^0-9+]/', '', $data[$header_for_client_phone])) : '';
        if (empty($phone) && isset($data['מס טלפון נייד / CELL PHONE'])) {
            $phone = sanitize_text_field(preg_replace('/[^0-9+]/', '', $data['מס טלפון נייד / CELL PHONE']));
        }
         if (empty($phone) && isset($data['My Phone'])) { // Another fallback from sample
            $phone = sanitize_text_field(preg_replace('/[^0-9+]/', '', $data['My Phone']));
        }
        
        $address_city     = isset($data[$header_for_address_city]) ? sanitize_text_field(trim($data[$header_for_address_city])) : '';
        $address_street   = isset($data[$header_for_address_street]) ? sanitize_text_field(trim($data[$header_for_address_street])) : '';
        $address_number   = isset($data[$header_for_address_number]) ? sanitize_text_field(trim($data[$header_for_address_number])) : '';
        $address_apt      = isset($data[$header_for_address_apt]) ? sanitize_text_field(trim($data[$header_for_address_apt])) : '';
        $address_floor    = isset($data[$header_for_address_floor]) ? sanitize_text_field(trim($data[$header_for_address_floor])) : '';
        $address_entrance = isset($data[$header_for_address_entrance]) ? sanitize_text_field(trim($data[$header_for_address_entrance])) : '';
        
        $full_address_computed = trim(implode(', ', array_filter([$address_street, $address_number, $address_apt, $address_floor, $address_entrance, $address_city])));
        $full_address_from_sheet = isset($data[$header_for_full_address]) ? sanitize_text_field(trim($data[$header_for_full_address])) : '';
        $final_full_address_display = !empty($full_address_from_sheet) ? $full_address_from_sheet : $full_address_computed;


        // --- 3. Find or Create WordPress User ---
        $user = $this->get_user_by_custom_id( $client_id_from_sheet );
        if ( ! $user ) {
            $user_by_email = get_user_by( 'email', $email_from_sheet );
            if ($user_by_email) { // If user found by email, associate the custom ID with them
                update_user_meta($user_by_email->ID, 'cds_custom_id', $client_id_from_sheet);
                $user = $user_by_email;
                 error_log("CDS User Manager: Found existing user by email {$email_from_sheet} (ID: {$user_by_email->ID}), associating custom ID {$client_id_from_sheet}.");
            }
        }

        $user_data_array = array(
            'user_email' => $email_from_sheet,
            'user_login' => $email_from_sheet, 
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'display_name' => trim($full_name) ?: $email_from_sheet,
            'user_pass'  => null, // Let WP handle password for new users, don't change for existing
            'role'       => 'subscriber'
        );

        $wp_user_id = null;
        $user_existed = (bool) $user;

        if ( $user ) {
            $wp_user_id = $user->ID;
            unset($user_data_array['user_pass']); // Don't change password on update
            $user_data_array['ID'] = $wp_user_id;
            
            // Check if email is being changed and if the new email is already taken by another user
            if ($user->user_email !== $email_from_sheet) {
                $existing_user_with_new_email = get_user_by('email', $email_from_sheet);
                if ($existing_user_with_new_email && $existing_user_with_new_email->ID !== $wp_user_id) {
                    error_log("CDS User Manager: Cannot update email for user ID {$wp_user_id} to {$email_from_sheet} because it's already in use by user ID {$existing_user_with_new_email->ID}. Skipping email update for this user.");
                    unset($user_data_array['user_email']); // Don't attempt to update email
                    unset($user_data_array['user_login']); // Also don't update login if tied to email
                }
            }
            $result = wp_update_user( $user_data_array );
            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'user_update_failed_php', 'PHP: Failed to update user (ID: '.$wp_user_id.'): ' . $result->get_error_message(), $user_data_array );
            }
        } else {
            $user_data_array['user_pass'] = wp_generate_password(); // Set password for new users
            $result = wp_insert_user( $user_data_array );
            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'user_create_failed_php', 'PHP: Failed to create user: ' . $result->get_error_message(), $user_data_array );
            }
            $wp_user_id = $result;
        }

        // --- 4. Update Custom User Meta ---
        update_user_meta( $wp_user_id, 'cds_custom_id', $client_id_from_sheet );
        if ($phone) update_user_meta( $wp_user_id, 'billing_phone', $phone );
        
        if ($address_city) update_user_meta( $wp_user_id, 'cds_address_city', $address_city );
        if ($address_street) update_user_meta( $wp_user_id, 'cds_address_street', $address_street );
        if ($address_number) update_user_meta( $wp_user_id, 'cds_address_number', $address_number );
        if ($address_apt) update_user_meta( $wp_user_id, 'cds_address_apt', $address_apt );
        if ($address_floor) update_user_meta( $wp_user_id, 'cds_address_floor', $address_floor );
        if ($address_entrance) update_user_meta( $wp_user_id, 'cds_address_entrance', $address_entrance );
        if (!empty($final_full_address_display)) {
             update_user_meta( $wp_user_id, 'cds_full_address_display', $final_full_address_display );
        }

        // --- 5. Process Services and Transactions ---
        $current_transactions = get_user_meta($wp_user_id, 'cds_transactions', true);
        if (!is_array($current_transactions)) $current_transactions = array();
        $transaction_exists = false;
        if (!empty($final_full_address_display)) {
            foreach ($current_transactions as $existing_tx) {
                if (isset($existing_tx['deal_name']) && $existing_tx['deal_name'] === $final_full_address_display) {
                    $transaction_exists = true; break;
                }
            }
            if (!$transaction_exists) {
                $new_transaction = array(
                    'deal_name' => $final_full_address_display,
                    'city'      => $address_city, 'street'    => $address_street,
                    'number'    => $address_number, 'apt'       => $address_apt,
                    'floor'     => $address_floor, 'entrance'  => $address_entrance,
                    'timestamp' => isset($data['Submission Date']) ? sanitize_text_field($data['Submission Date']) : (isset($data['תאריך המעבר- Date']) ? sanitize_text_field($data['תאריך המעבר- Date']) : current_time('mysql'))
                );
                $current_transactions[] = $new_transaction;
                update_user_meta($wp_user_id, 'cds_transactions', $current_transactions);
            }
        }

        // Service Processing
        $active_services = get_user_meta($wp_user_id, 'cds_services_overview', true); // Preserve existing services not mentioned in this update
        if (!is_array($active_services)) $active_services = [];

        $services_string = isset($data[$header_for_services_being_transferred]) ? trim($data[$header_for_services_being_transferred]) : '';
        
        if (!empty($services_string)) {
            $service_lines = preg_split('/\r\n|\r|\n/', $services_string); // Split by newline
            
            foreach ($service_lines as $service_line_item) {
                $service_line_item = trim($service_line_item);
                if (empty($service_line_item)) continue;

                $service_name_display = ''; 
                $service_details_text = ''; 
                // ** CRITICAL: Determine the status for this service. **
                // This needs to come from your sheet. For now, we use a default.
                $service_status_from_sheet = "Information Received"; // Placeholder

                // Map sheet's service descriptions to consistent internal names
                if (strpos($service_line_item, 'חשמל') !== false) { // Electricity
                    $service_name_display = "Electricity Account Transfer";
                    if(isset($data[$header_for_electricity_account])) $service_details_text .= "Account Number: " . trim($data[$header_for_electricity_account]) . "\n";
                    if(isset($data['מספר מונה חשמל'])) $service_details_text .= "Meter Number: " . trim($data['מספר מונה חשמל']) . "\n";
                    // Example: if you add a "חשמל Status" column to your sheet:
                    // $header_for_electricity_status = "חשמל Status"; // Define this with other headers
                    // if(isset($data[$header_for_electricity_status])) $service_status_from_sheet = trim($data[$header_for_electricity_status]);
                } elseif (strpos($service_line_item, 'מים') !== false) { // Water
                    $service_name_display = "Water Account Transfer";
                    if(isset($data[$header_for_water_property_id])) $service_details_text .= "Property ID: " . trim($data[$header_for_water_property_id]) . "\n";
                    if(isset($data['מספר מונה מים'])) $service_details_text .= "Meter Number: " . trim($data['מספר מונה מים']) . "\n";
                    if(isset($data['מספר נפשות'])) $service_details_text .= "Persons: " . trim($data['מספר נפשות']) . "\n";
                } elseif (strpos($service_line_item, 'ארנונה') !== false) { // Property Tax
                    $service_name_display = "Property Tax Transfer";
                    if(isset($data[$header_for_arnona_property_id])) $service_details_text .= "Property ID: " . trim($data[$header_for_arnona_property_id]) . "\n";
                } elseif (strpos($service_line_item, 'גז') !== false) { // Gas
                     $service_name_display = "Gas Account Transfer";
                     if(isset($data[$header_for_gas_consumer_no])) $service_details_text .= "Consumer Number: " . trim($data[$header_for_gas_consumer_no]) . "\n";
                     if(isset($data['שם ספק הגז'])) $service_details_text .= "Supplier: " . trim($data['שם ספק הגז']) . "\n";
                } else {
                    $service_name_display = $service_line_item; // Use as is if not specifically mapped
                }


                if (!empty($service_name_display)) {
                    $service_key_sanitized = sanitize_key($service_name_display);
                    $status_meta_key = 'cds_service_status_' . $service_key_sanitized;
                    $details_meta_key = 'cds_service_details_' . $service_key_sanitized;
                    $missing_docs_meta_key = 'cds_service_missing_docs_' . $service_key_sanitized;
                    $denied_reason_meta_key = 'cds_service_denied_reason_' . $service_key_sanitized;


                    $old_status = get_user_meta($wp_user_id, $status_meta_key, true);
                    $current_status = sanitize_text_field(trim($service_status_from_sheet));

                    // If the status for this service type is "Missing docs" or "Request denied", 
                    // you need a way to get that specific text from your sheet.
                    // Example:
                    // $header_for_service_X_missing_docs = "Details for Missing Docs " . $service_name_display;
                    // $missing_docs_text = isset($data[$header_for_service_X_missing_docs]) ? sanitize_textarea_field($data[$header_for_service_X_missing_docs]) : '';
                    $missing_docs_text = ''; // Placeholder - populate this from $data if available
                    $denied_reason_text = ''; // Placeholder - populate this

                    if ($old_status !== $current_status || !$user_existed || !empty($missing_docs_text) || !empty($denied_reason_text) || get_user_meta($wp_user_id, $details_meta_key, true) !== trim($service_details_text)) {
                        update_user_meta( $wp_user_id, $status_meta_key, $current_status );
                        update_user_meta( $wp_user_id, $details_meta_key, trim($service_details_text) );

                        $notification_details_for_email = array();
                        if(!empty($service_details_text)) $notification_details_for_email['general_details'] = trim($service_details_text);

                        if ($current_status === 'Missing docs' && !empty($missing_docs_text)) {
                            update_user_meta( $wp_user_id, $missing_docs_meta_key, $missing_docs_text);
                            $notification_details_for_email['missing_docs'] = $missing_docs_text;
                        } else {
                            delete_user_meta( $wp_user_id, $missing_docs_meta_key); // Clear if status changed
                        }
                        if ($current_status === 'Request denied' && !empty($denied_reason_text)) {
                            update_user_meta( $wp_user_id, $denied_reason_meta_key, $denied_reason_text);
                            $notification_details_for_email['denied_reason'] = $denied_reason_text;
                        } else {
                             delete_user_meta( $wp_user_id, $denied_reason_meta_key);
                        }
                        
                        CDS_Notifications::get_instance()->send_status_change_email( $wp_user_id, $service_name_display, $old_status, $current_status, $notification_details_for_email );
                    }
                    $active_services[$service_name_display] = $current_status;
                }
            }
        }
        update_user_meta( $wp_user_id, 'cds_services_overview', $active_services );

        // Store other potentially useful data from your sample
        $other_fields_to_store = [
            'unique id' => 'cds_internal_unique_id', // Example: "unique id": "MZK6389"
            '?הזיקה לנכס' => 'cds_property_relation',
            '?אדם פרטי או תאגיד' => 'cds_client_type',
            'משלם (דייר) יחיד או שותפים' => 'cds_payer_type',
            'תאריך הכניסה לנכס לפי החוזה / MOVE IN DATE' => 'cds_move_in_date_contract',
            'תאריך סיום השכירות לפי החוזה / END OF LEASE DATE' => 'cds_lease_end_date_contract',
            'Length of Leas Calculation' => 'cds_lease_length_days',
            'מספר נפשות' => 'cds_number_of_persons',
            'בחירת חבילה: Products' => 'cds_package_products',
            // You can add more mappings here from your sample data to a cds_ prefixed meta key
        ];

        foreach($other_fields_to_store as $sheet_header_key => $meta_key) {
            if (isset($data[$sheet_header_key]) && !empty(trim((string)$data[$sheet_header_key]))) {
                update_user_meta($wp_user_id, $meta_key, sanitize_text_field(trim((string)$data[$sheet_header_key])));
            }
        }


        return $wp_user_id;
    }

    public function get_user_by_custom_id( $custom_id ) {
        if (empty($custom_id)) return false;
        $user_query = new WP_User_Query( array(
            'meta_key'   => 'cds_custom_id',
            'meta_value' => $custom_id,
            'number'     => 1,
            'count_total' => false,
            'fields'      => 'ID' 
        ) );
        $users = $user_query->get_results();
        if ($users) {
            return get_user_by('ID', $users[0]); 
        }
        return false;
    }

    public function update_service_status_manually( $user_id, $service_name, $new_status, $details = array() ) {
        $user = get_user_by('ID', $user_id);
        if ( !$user ) {
            return new WP_Error('invalid_user', 'User ID does not exist.');
        }
        if (empty($service_name) || empty($new_status)) {
            return new WP_Error('missing_params', 'Service name or new status is empty.');
        }

        $service_key_sanitized = sanitize_key($service_name);
        $status_meta_key = 'cds_service_status_' . $service_key_sanitized;
        
        $old_status = get_user_meta($user_id, $status_meta_key, true);
        $new_status_sanitized = sanitize_text_field($new_status);

        if ($old_status !== $new_status_sanitized || !empty($details)) { // Update if status changes OR if new details are provided
            update_user_meta($user_id, $status_meta_key, $new_status_sanitized);
            $notification_details_for_email = array();

            $missing_docs_text = isset($details['missing_docs']) ? sanitize_textarea_field($details['missing_docs']) : '';
            $denied_reason_text = isset($details['denied_reason']) ? sanitize_textarea_field($details['denied_reason']) : '';
            $general_details_text = isset($details['general_details']) ? sanitize_textarea_field($details['general_details']) : get_user_meta($user_id, 'cds_service_details_' . $service_key_sanitized, true); // Preserve old if not updated

            if ($new_status_sanitized === 'Missing docs' && !empty($missing_docs_text)) {
                update_user_meta($user_id, 'cds_service_missing_docs_' . $service_key_sanitized, $missing_docs_text);
                $notification_details_for_email['missing_docs'] = $missing_docs_text;
            } else {
                 // If status is no longer "Missing docs", clear the specific missing docs meta.
                 // But keep general details if they exist.
                delete_user_meta($user_id, 'cds_service_missing_docs_' . $service_key_sanitized);
            }

            if ($new_status_sanitized === 'Request denied' && !empty($denied_reason_text)) {
                update_user_meta($user_id, 'cds_service_denied_reason_' . $service_key_sanitized, $denied_reason_text);
                 $notification_details_for_email['denied_reason'] = $denied_reason_text;
            } else {
                delete_user_meta($user_id, 'cds_service_denied_reason_' . $service_key_sanitized);
            }
            
            // Update general details if provided, or preserve existing if only status changed
            if (!empty($details['general_details']) || ($old_status !== $new_status_sanitized && empty($details['general_details']) && !empty($general_details_text) ) ) {
                 update_user_meta($user_id, 'cds_service_details_' . $service_key_sanitized, $general_details_text);
                 if(!empty($general_details_text)) $notification_details_for_email['general_details'] = $general_details_text;
            }


            $services_overview = get_user_meta($user_id, 'cds_services_overview', true);
            if (!is_array($services_overview)) $services_overview = [];
            $services_overview[$service_name] = $new_status_sanitized;
            update_user_meta($user_id, 'cds_services_overview', $services_overview);

            // Only send notification if status actually changed.
            if ($old_status !== $new_status_sanitized) {
                CDS_Notifications::get_instance()->send_status_change_email( $user_id, $service_name, $old_status, $new_status_sanitized, $notification_details_for_email );
            }
            return true;
        }
        return false; 
    }
}