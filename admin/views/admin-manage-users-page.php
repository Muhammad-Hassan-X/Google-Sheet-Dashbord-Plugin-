<?php
// File: client-dashboard-system/admin/views/admin-manage-users-page.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

// This page is intended for managing users and their service statuses.
// A full implementation would typically involve WP_List_Table for displaying users,
// search/filter capabilities, and forms for editing service details per user.

// For demonstration, let's show a simple list of users who have 'cds_custom_id'
// and a basic way to trigger an update for one of their services.

$client_users_query = new WP_User_Query(array(
    'meta_key' => 'cds_custom_id',
    'meta_compare' => 'EXISTS',
    'number' => 20, // Paginate this in a real implementation
    'orderby' => 'display_name'
));
$client_users = $client_users_query->get_results();

$all_possible_statuses = array( // Define your global service statuses
    "Requested" => __("Requested", 'client-dashboard-system'),
    "Information Received" => __("Information Received", 'client-dashboard-system'),
    "In Progress" => __("In Progress", 'client-dashboard-system'),
    "Request Sent" => __("Request Sent", 'client-dashboard-system'),
    "Missing docs" => __("Missing docs", 'client-dashboard-system'),
    "Request Denied" => __("Request Denied", 'client-dashboard-system'),
    "Request Completed" => __("Request Completed", 'client-dashboard-system'),
    "Done" => __("Done", 'client-dashboard-system')
);

?>
<div class="wrap cds-admin-wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p><?php esc_html_e( 'Manage client users and their service statuses. This is a basic interface for demonstration.', 'client-dashboard-system' ); ?></p>

    <div id="user-management-area" class="cds-widget">
        <h2><?php esc_html_e('Synced Client Users', 'client-dashboard-system'); ?></h2>
        <?php if ( ! empty( $client_users ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Name', 'client-dashboard-system'); ?></th>
                        <th scope="col"><?php esc_html_e('Email', 'client-dashboard-system'); ?></th>
                        <th scope="col"><?php esc_html_e('Client ID', 'client-dashboard-system'); ?></th>
                        <th scope="col" style="width: 40%;"><?php esc_html_e('Services & Statuses', 'client-dashboard-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $client_users as $user ) : 
                        $services_overview = get_user_meta($user->ID, 'cds_services_overview', true);
                        if (!is_array($services_overview)) $services_overview = [];
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" target="_blank">
                                    <?php echo esc_html( $user->display_name ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( $user->user_email ); ?></td>
                            <td><?php echo esc_html( get_user_meta($user->ID, 'cds_custom_id', true) ); ?></td>
                            <td>
                                <?php if (!empty($services_overview)) : ?>
                                    <ul class="cds-service-list">
                                        <?php foreach ($services_overview as $service_name => $current_status) : 
                                            $service_key_sanitized = sanitize_key($service_name);
                                            $details_for_service = get_user_meta($user->ID, 'cds_service_details_' . $service_key_sanitized, true);
                                            $missing_docs_for_service = get_user_meta($user->ID, 'cds_service_missing_docs_' . $service_key_sanitized, true);
                                            $denied_reason_for_service = get_user_meta($user->ID, 'cds_service_denied_reason_' . $service_key_sanitized, true);
                                        ?>
                                            <li class="cds-service-item">
                                                <strong><?php echo esc_html($service_name); ?>:</strong> 
                                                <span class="status-badge-admin status-<?php echo esc_attr(sanitize_title($current_status)); ?>">
                                                    <?php echo esc_html($current_status); ?>
                                                </span>
                                                <button class="button button-small cds-edit-service-status-btn" 
                                                        data-user-id="<?php echo esc_attr($user->ID); ?>"
                                                        data-service-name="<?php echo esc_attr($service_name); ?>"
                                                        data-current-status="<?php echo esc_attr($current_status); ?>"
                                                        data-details="<?php echo esc_attr($details_for_service); ?>"
                                                        data-missing-docs="<?php echo esc_attr($missing_docs_for_service); ?>"
                                                        data-denied-reason="<?php echo esc_attr($denied_reason_for_service); ?>">
                                                    <?php esc_html_e('Edit', 'client-dashboard-system'); ?>
                                                </button>
                                                <?php if(!empty($details_for_service)): ?>
                                                    <p class="cds-service-admin-details"><em><?php echo nl2br(esc_html($details_for_service)); ?></em></p>
                                                <?php endif; ?>
                                                 <?php if ($current_status === 'Missing docs' && !empty($missing_docs_for_service)): ?>
                                                    <p class="cds-service-admin-details cds-missing-docs"><strong><?php esc_html_e('Missing:', 'client-dashboard-system');?></strong> <?php echo nl2br(esc_html($missing_docs_for_service)); ?></p>
                                                <?php endif; ?>
                                                <?php if ($current_status === 'Request Denied' && !empty($denied_reason_for_service)): ?>
                                                    <p class="cds-service-admin-details cds-denied-reason"><strong><?php esc_html_e('Denied:', 'client-dashboard-system');?></strong> <?php echo nl2br(esc_html($denied_reason_for_service)); ?></p>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <?php esc_html_e('No services found for this user.', 'client-dashboard-system'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No client users found (users with a "cds_custom_id" meta field). Data needs to be synced from Google Sheets first.', 'client-dashboard-system' ); ?></p>
        <?php endif; ?>
    </div>

    <!-- Modal for Editing Service Status -->
    <div id="cds-edit-service-modal" style="display:none; position:fixed; top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;">
        <div style="background:#fff; padding:20px; width:500px; max-width:90%; margin:100px auto; border-radius:5px; box-shadow: 0 0 15px rgba(0,0,0,0.3);">
            <h3 id="cds-modal-title"><?php esc_html_e('Edit Service Status', 'client-dashboard-system'); ?></h3>
            <form id="cds-edit-service-form">
                <input type="hidden" id="cds-modal-user-id" name="user_id">
                <input type="hidden" id="cds-modal-service-name" name="service_name">
                <p>
                    <label for="cds-modal-new-status"><strong><?php esc_html_e('New Status:', 'client-dashboard-system'); ?></strong></label><br>
                    <select id="cds-modal-new-status" name="new_status" style="width:100%;">
                        <?php foreach($all_possible_statuses as $status_val => $status_label): ?>
                            <option value="<?php echo esc_attr($status_val); ?>"><?php echo esc_html($status_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p id="cds-modal-details-field-group">
                    <label for="cds-modal-details-text"><strong><?php esc_html_e('Details (e.g., Missing Docs List / Denial Reason / Other Info):', 'client-dashboard-system'); ?></strong></label><br>
                    <textarea id="cds-modal-details-text" name="details_text" rows="4" style="width:100%;"></textarea>
                </p>
                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Update Status', 'client-dashboard-system'); ?></button>
                    <button type="button" id="cds-modal-cancel" class="button button-secondary"><?php esc_html_e('Cancel', 'client-dashboard-system'); ?></button>
                </p>
                <div id="cds-modal-message" style="margin-top:10px;"></div>
            </form>
        </div>
    </div>

    <style>
        .cds-service-list { list-style: none; margin: 0; padding: 0; }
        .cds-service-item { padding: 5px 0; border-bottom: 1px dotted #eee; }
        .cds-service-item:last-child { border-bottom: none; }
        .status-badge-admin { padding: 2px 6px; border-radius: 10px; color: #fff; font-size: 0.8em; margin-left: 5px; }
        .status-request-received, .status-information-received, .status-pending, .status-pending-information { background-color: #6c757d; }
        .status-in-progress { background-color: #007bff; }
        .status-request-sent { background-color: #17a2b8; }
        .status-missing-docs { background-color: #ffc107; color: #212529 !important; }
        .status-request-denied { background-color: #dc3545; }
        .status-request-completed, .status-done { background-color: #28a745; }
        .cds-edit-service-status-btn { margin-left: 10px; vertical-align: middle; }
        .cds-service-admin-details { font-size: 0.9em; color: #555; margin-left: 10px; margin-top: 3px; }
        .cds-service-admin-details.cds-missing-docs { color: #856404; background-color: #fff3cd; padding: 3px; border-radius:3px; }
        .cds-service-admin-details.cds-denied-reason { color: #721c24; background-color: #f8d7da; padding: 3px; border-radius:3px; }
    </style>
</div>