<?php
// File: client-dashboard-system/includes/class-cds-google-sheets-handler.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class CDS_Google_Sheets_Handler {

    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize any properties or hooks specific to this handler
    }

    /**
     * Handles incoming data from the Google Sheets webhook.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response|WP_Error The response object or error.
     */
    public function handle_webhook_data( WP_REST_Request $request ) {
        // 1. Verify the webhook (e.g., using a secret key)
        $webhook_secret = get_option( 'cds_webhook_secret' );
        $sent_secret = $request->get_header( 'x_webhook_secret' ); // Or whatever header you configure

        if ( empty($webhook_secret) || empty($sent_secret) || !hash_equals( $webhook_secret, $sent_secret ) ) {
            // Log this attempt for security auditing
            error_log('CDS Webhook: Invalid or missing secret. Request IP: ' . $_SERVER['REMOTE_ADDR']);
            return new WP_Error( 'cds_webhook_auth_failed', 'Authentication failed.', array( 'status' => 403 ) );
        }

        // 2. Get the data from the request
        $data = $request->get_json_params(); // If webhook sends JSON
        // $data = $request->get_body_params(); // If webhook sends form data

        if ( empty( $data ) ) {
            return new WP_Error( 'cds_webhook_no_data', 'No data received.', array( 'status' => 400 ) );
        }

        // 3. Process the data (this is where the core logic goes)
        //    - Identify if it's a new user or an update.
        //    - Extract common fields (name, email, ID, phone, address).
        //    - Extract any extra fields.
        //    - Call CDS_User_Manager to create or update the user.
        //    - Update service statuses.
        //    - Trigger notifications if status changes.

        // Example processing:
        // Assume $data is an array of rows, or a single row object
        // Each row needs to be processed.
        // For simplicity, let's assume $data is a single user record.
        
        // Log received data for debugging (remove in production or make conditional)
        // error_log('CDS Webhook Data Received: ' . print_r($data, true));

        try {
            $user_manager = CDS_User_Manager::get_instance();
            $result = $user_manager->process_sheet_data_entry( $data );

            if ( is_wp_error( $result ) ) {
                // Log the error
                error_log('CDS Webhook Processing Error: ' . $result->get_error_message());
                return $result; // Forward the WP_Error
            }

            // If successful, $result might contain info about created/updated user
            // CDS_Notifications::get_instance()->maybe_send_status_change_email(...);

            return new WP_REST_Response( array( 'success' => true, 'message' => 'Data processed successfully.', 'result' => $result ), 200 );

        } catch ( Exception $e ) {
            // Log the exception
            error_log('CDS Webhook Exception: ' . $e->getMessage());
            return new WP_Error( 'cds_webhook_exception', 'An error occurred: ' . $e->getMessage(), array( 'status' => 500 ) );
        }
    }

    /**
     * Fetches data from Google Sheets using the API (for periodic sync).
     * This is an alternative or supplement to webhooks.
     *
     * This function would require the Google API Client Library for PHP.
     * You'd need to handle OAuth2 or API Key authentication.
     *
     * For now, this is a placeholder.
     */
    public function sync_with_google_sheets_api() {
        // 0. Check if API key is set
        $api_key = get_option('cds_google_api_key');
        if (empty($api_key)) {
            // error_log('CDS Sync: Google API Key not set.');
            return new WP_Error('api_key_missing', 'Google API Key is not configured.');
        }

        // 1. Include Google API Client Library (if not using Composer)
        //    Make sure it's available. Best practice is to use Composer.
        //    Example: require_once CDS_PLUGIN_DIR . 'vendor/autoload.php';

        // 2. Authenticate with Google Sheets API.

        // 3. Loop through configured Google Sheet URLs.
        $sheet_count = get_option('cds_google_sheets_count', 5);
        for ($i = 1; $i <= $sheet_count; $i++) {
            $sheet_url = get_option('cds_google_sheet_url_' . $i);
            if (empty($sheet_url)) continue;

            // Parse Sheet ID and Range from URL
            // Example: https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit#gid=SHEET_GID
            // Or more simply, just ask for the Sheet ID and range in settings.

            // 4. Fetch data from each sheet.
            //    $service = new Google_Service_Sheets($client);
            //    $spreadsheetId = 'YOUR_SPREADSHEET_ID';
            //    $range = 'Sheet1!A1:Z'; // Adjust as needed
            //    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            //    $values = $response->getValues();

            // 5. Process the fetched $values (similar to webhook data processing).
            //    foreach ($values as $row) {
            //        $data_entry = $this->map_row_to_data_structure($row); // You'll need a mapping function
            //        CDS_User_Manager::get_instance()->process_sheet_data_entry($data_entry);
            //    }
        }
        // error_log('CDS Sync: Manual sync process placeholder executed.');
        return array('success' => true, 'message' => 'Manual sync placeholder executed. Implement actual API calls.');
    }

    /**
     * Helper function to map a sheet row (array) to a structured data array.
     * This depends heavily on your sheet structure.
     * Example:
     * $column_mapping = [
     * 0 => 'unique_id', // Column A in sheet
     * 1 => 'email',     // Column B
     * 2 => 'name',      // Column C
     * // ... etc.
     * ];
     */
    private function map_row_to_data_structure( $row_array ) {
        // Implement mapping based on your specific sheet column order
        // This is a very basic example, needs to be robust
        $data = array();
        // Define your expected column headers or indices
        // e.g., $data['id'] = $row_array[0]; $data['email'] = $row_array[1]; ...
        return $data;
    }
}
