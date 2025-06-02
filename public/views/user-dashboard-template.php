<?php
// File: client-dashboard-system/public/views/user-dashboard-template.php
/**
 * Template for the user dashboard.
 *
 * Available variables:
 * $current_user (WP_User object)
 * $user_id (int)
 * $user_meta (array) - Contains custom user meta like 'custom_id', 'phone', 'address_city', etc.
 * $services (array) - Associative array of [service_name => status]
 * $transactions (array) - Array of transaction arrays/objects
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! isset( $current_user, $user_id, $user_meta, $services, $transactions ) ) {
    echo '<p>' . __( 'Error: Dashboard data is not available.', 'client-dashboard-system' ) . '</p>';
    return;
}
?>
<div class="client-dashboard-system-wrap">
    <h2><?php printf( esc_html__( 'Welcome to Your Dashboard, %s', 'client-dashboard-system' ), esc_html( $current_user->display_name ) ); ?></h2>

    <div class="cds-section cds-user-info">
        <h3><?php esc_html_e( 'Your Information', 'client-dashboard-system' ); ?></h3>
        <p><strong><?php esc_html_e( 'Client ID:', 'client-dashboard-system' ); ?></strong> <?php echo esc_html( $user_meta['custom_id'] ?? 'N/A' ); ?></p>
        <p><strong><?php esc_html_e( 'Email:', 'client-dashboard-system' ); ?></strong> <?php echo esc_html( $current_user->user_email ); ?></p>
        <p><strong><?php esc_html_e( 'Phone:', 'client-dashboard-system' ); ?></strong> <?php echo esc_html( $user_meta['phone'] ?? 'N/A' ); ?></p>
        <h4><?php esc_html_e( 'Address:', 'client-dashboard-system' ); ?></h4>
        <p>
            <?php
            $address_parts = array(
                $user_meta['address_street'] ?? '',
                $user_meta['address_number'] ?? '',
                $user_meta['address_apt'] ? __('Apt', 'client-dashboard-system') . ' ' . $user_meta['address_apt'] : '',
                $user_meta['address_floor'] ? __('Floor', 'client-dashboard-system') . ' ' . $user_meta['address_floor'] : '',
                $user_meta['address_entrance'] ? __('Entrance', 'client-dashboard-system') . ' ' . $user_meta['address_entrance'] : '',
                $user_meta['address_city'] ?? ''
            );
            echo esc_html( implode(', ', array_filter($address_parts)) ?: 'N/A' );
            ?>
        </p>
    </div>

    <div class="cds-section cds-service-status">
        <h3><?php esc_html_e( 'Service Statuses', 'client-dashboard-system' ); ?></h3>
        <?php if ( ! empty( $services ) && is_array($services) ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Service Name', 'client-dashboard-system' ); ?></th>
                        <th><?php esc_html_e( 'Current Status', 'client-dashboard-system' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'client-dashboard-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $services as $service_name => $status ) : ?>
                        <tr>
                            <td><?php echo esc_html( $service_name ); ?></td>
                            <td><span class="status-badge status-<?php echo esc_attr(sanitize_title($status)); ?>"><?php echo esc_html( $status ); ?></span></td>
                            <td>
                                <?php
                                $details_text = '';
                                if ($status === 'Missing docs') {
                                    $missing_docs = get_user_meta($user_id, 'cds_service_missing_docs_' . sanitize_key($service_name), true);
                                    if ($missing_docs) {
                                        $details_text = __('Missing: ', 'client-dashboard-system') . nl2br(esc_html($missing_docs));
                                    }
                                } elseif ($status === 'Request denied') {
                                    $denied_reason = get_user_meta($user_id, 'cds_service_denied_reason_' . sanitize_key($service_name), true);
                                    if ($denied_reason) {
                                         $details_text = __('Reason: ', 'client-dashboard-system') . nl2br(esc_html($denied_reason));
                                    }
                                }
                                echo $details_text ?: '—';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No service information available at this time.', 'client-dashboard-system' ); ?></p>
        <?php endif; ?>
    </div>

    <div class="cds-section cds-transaction-history">
        <h3><?php esc_html_e( 'Transaction History', 'client-dashboard-system' ); ?></h3>
        <?php if ( ! empty( $transactions ) && is_array($transactions) ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Deal Name (Address)', 'client-dashboard-system' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'client-dashboard-system' ); ?></th>
                        </tr>
                </thead>
                <tbody>
                    <?php foreach ( $transactions as $transaction ) : ?>
                        <?php
                        // Ensure $transaction is an array
                        if (!is_array($transaction)) continue;

                        $deal_name = $transaction['deal_name'] ?? __('N/A', 'client-dashboard-system');
                        // If deal_name is not set, construct from parts
                        if ($deal_name === __('N/A', 'client-dashboard-system')) {
                             $addr_parts = array(
                                $transaction['street'] ?? '',
                                $transaction['number'] ?? '',
                                isset($transaction['apt']) && $transaction['apt'] ? __('Apt', 'client-dashboard-system') . ' ' . $transaction['apt'] : '',
                                isset($transaction['floor']) && $transaction['floor'] ? __('Floor', 'client-dashboard-system') . ' ' . $transaction['floor'] : '',
                                isset($transaction['entrance']) && $transaction['entrance'] ? __('Entrance', 'client-dashboard-system') . ' ' . $transaction['entrance'] : '',
                                $transaction['city'] ?? ''
                            );
                            $deal_name = implode(', ', array_filter($addr_parts));
                        }
                        $date = isset($transaction['timestamp']) ? date_i18n( get_option('date_format'), strtotime($transaction['timestamp']) ) : __('N/A', 'client-dashboard-system');
                        ?>
                        <tr>
                            <td><?php echo esc_html( $deal_name ); ?></td>
                            <td><?php echo esc_html( $date ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No transaction history available.', 'client-dashboard-system' ); ?></p>
        <?php endif; ?>
    </div>

    <div class="cds-section cds-contact-support">
        <h3><?php esc_html_e( 'Need Help?', 'client-dashboard-system' ); ?></h3>
        <p>
            <?php
            // You can make these configurable via admin settings
            $support_email = get_option('cds_support_email', 'support@example.com');
            $support_whatsapp = get_option('cds_support_whatsapp', ''); // e.g., +1234567890 or wa.me link

            echo sprintf(
                esc_html__( 'If you have any questions, please contact us via email at %s', 'client-dashboard-system' ),
                '<a href="mailto:' . esc_attr( $support_email ) . '">' . esc_html( $support_email ) . '</a>'
            );
            if ($support_whatsapp) {
                 // Check if it's a full wa.me link or just a number
                $whatsapp_link = (strpos($support_whatsapp, 'wa.me') !== false || strpos($support_whatsapp, 'api.whatsapp.com') !== false)
                                 ? $support_whatsapp
                                 : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $support_whatsapp); // Basic E.164 formatting attempt

                echo sprintf(
                    esc_html__( ' or on WhatsApp: %s', 'client-dashboard-system' ),
                    '<a href="' . esc_url( $whatsapp_link ) . '" target="_blank">' . esc_html__( 'Chat on WhatsApp', 'client-dashboard-system' ) . '</a>'
                );
            }
            echo '.';
            ?>
        </p>
    </div>
</div>

<style>
/* Basic styles for the dashboard - move to public-style.css for production */
.client-dashboard-system-wrap { font-family: sans-serif; max-width: 900px; margin: 20px auto; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; }
.cds-section { margin-bottom: 30px; padding: 15px; background-color: #fff; border: 1px solid #eee; border-radius: 4px; }
.cds-section h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.cds-section table { width: 100%; border-collapse: collapse; }
.cds-section th, .cds-section td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
.cds-section th { background-color: #f5f5f5; }
.status-badge { padding: 3px 8px; border-radius: 10px; color: #fff; font-size: 0.9em; }
.status-request-received { background-color: #6c757d; } /* Grey */
.status-in-progress { background-color: #007bff; } /* Blue */
.status-request-sent { background-color: #17a2b8; } /* Teal */
.status-missing-docs { background-color: #ffc107; color: #212529;} /* Yellow */
.status-request-denied { background-color: #dc3545; } /* Red */
.status-request-completed { background-color: #28a745; } /* Green */
.status-done { background-color: #20c997; } /* Another Green/Teal for finality */
</style>
