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

    private function __construct() {}

    public function handle_webhook_data( WP_REST_Request $request ) {
        // --- TEMPORARILY BYPASS SECRET KEY CHECK FOR TESTING ---
        // Make sure 'permission_callback' => '__return_true' in register_rest_route for this to work.
        /*
        $webhook_secret = get_option( 'cds_webhook_secret' );
        $sent_secret = $request->get_header( 'x_webhook_secret' ); 

        if ( !empty($webhook_secret) && (empty($sent_secret) || !hash_equals( $webhook_secret, $sent_secret )) ) {
            error_log('CDS Webhook: Invalid or missing secret. Request IP: ' . $_SERVER['REMOTE_ADDR']);
            return new WP_Error( 'cds_webhook_auth_failed', 'Authentication failed.', array( 'status' => 403 ) );
        }
        */
        // --- END OF TEMPORARY BYPASS ---

        $all_request_data = $request->get_json_params(); 

        if ( empty( $all_request_data ) ) {
            error_log('CDS Webhook: No JSON data received or data is empty.');
            return new WP_Error( 'cds_webhook_no_data', 'No data received.', array( 'status' => 400 ) );
        }

        error_log('CDS Webhook: Full Request Data Received: ' . print_r($all_request_data, true));

        $rows_to_process = array();
        if (isset($all_request_data['data']) && is_array($all_request_data['data'])) {
            $rows_to_process = $all_request_data['data'];
            error_log('CDS Webhook: Processing "data" array with ' . count($rows_to_process) . ' items. Source: ' . ($all_request_data['source'] ?? 'N/A') . ', Sheet: ' . ($all_request_data['sheet_name'] ?? 'N/A'));
        } elseif (is_array($all_request_data) && isset($all_request_data[0]) && is_array($all_request_data[0]) && !isset($all_request_data[0]['data'])) {
            // If the top-level payload is directly an array of row objects (from async batching script)
            $rows_to_process = $all_request_data;
            error_log('CDS Webhook: Processing direct array of ' . count($rows_to_process) . ' row items.');
        } elseif (is_array($all_request_data) && !isset($all_request_data['data']) && !isset($all_request_data[0])) { 
            // If it's a single row object not wrapped in 'data' or an array
            $rows_to_process[] = $all_request_data;
            error_log('CDS Webhook: Processing single row object.');
        } else {
            error_log('CDS Webhook: Data received is not in expected array format or wrapped "data" array. Data: ' . print_r($all_request_data, true));
            return new WP_Error( 'cds_webhook_invalid_format', 'Data received is not in the expected array format.', array( 'status' => 400 ) );
        }

        if (empty($rows_to_process)) {
            error_log('CDS Webhook: No actual row data found to process after initial check.');
            return new WP_Error( 'cds_webhook_no_row_data', 'No row data to process.', array( 'status' => 400 ) );
        }

        $user_manager = CDS_User_Manager::get_instance();
        $results_summary = array();
        $processed_count = 0;
        $error_count = 0;
        $user_ids_affected = array();

        foreach ($rows_to_process as $index => $single_row_data) {
            if (!is_array($single_row_data) || empty($single_row_data)) {
                error_log("CDS Webhook: Skipping empty or invalid row data at batch index $index.");
                $results_summary[] = array('batch_row_index' => $index, 'status' => 'skipped', 'reason' => 'Empty or invalid row data in batch');
                continue;
            }
            try {
                $result = $user_manager->process_sheet_data_entry( $single_row_data ); 
                if ( is_wp_error( $result ) ) {
                    $error_message = $result->get_error_message();
                    error_log('CDS Webhook: Error processing sheet row (approx. batch index ' . $index . '): ' . $error_message . ' Data: ' . print_r($single_row_data, true));
                    $results_summary[] = array('batch_row_index' => $index, 'status' => 'error', 'message' => $error_message); // 'data' => $single_row_data - too verbose for summary
                    $error_count++;
                } else {
                    $user_id = $result; 
                    $user_ids_affected[] = $user_id;
                    $results_summary[] = array('batch_row_index' => $index, 'status' => 'success', 'user_id' => $user_id, 'message' => 'Processed successfully.');
                    $processed_count++;
                }
            } catch ( Exception $e ) {
                error_log('CDS Webhook: Exception processing sheet row (approx. batch index ' . $index . '): ' . $e->getMessage() . ' Data: ' . print_r($single_row_data, true));
                $results_summary[] = array('batch_row_index' => $index, 'status' => 'exception', 'message' => $e->getMessage());
                $error_count++;
            }
        }
        
        $final_message = sprintf('Batch processed. Rows in batch: %d. Successfully processed/updated users: %d. Errors encountered: %d.', count($rows_to_process), $processed_count, $error_count);
        error_log("CDS Webhook: " . $final_message);

        return new WP_REST_Response( array( 
            'success' => $error_count === 0, 
            'message' => $final_message,
            'processed_count' => $processed_count,
            'error_count' => $error_count,
            'user_ids_affected' => array_unique($user_ids_affected), // List of user IDs created/updated
            'details' => $results_summary // More detailed status per row in batch
        ), 200 );
    }
}