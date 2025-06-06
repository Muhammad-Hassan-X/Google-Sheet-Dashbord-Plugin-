<?php
// File: client-dashboard-system/public/views/user-dashboard-template.php

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="client-dashboard-system-wrap">
    <h2><?php printf( esc_html__( 'Welcome to Your Dashboard, %s', 'client-dashboard-system' ), esc_html( $current_user->display_name ) ); ?></h2>

    <div class="cds-section cds-user-info">
        <h3><?php esc_html_e( 'Your Information', 'client-dashboard-system' ); ?></h3>
        <p><strong><?php esc_html_e( 'Client ID:', 'client-dashboard-system' ); ?></strong> <?php echo esc_html( $user_meta['custom_id'] ?? 'N/A' ); ?></p>
        <p><strong><?php esc_html_e( 'Email:', 'client-dashboard-system' ); ?></strong> <?php echo esc_html( $current_user->user_email ); ?></p>
        <p><strong><?php esc_html_e( 'Phone:', 'client-dashboard-system' ); ?></strong> <?php echo esc_html( $user_meta['phone'] ?? 'N/A' ); ?></p>
        
        <?php if (!empty($user_meta['full_address_display'])) : ?>
        <h4><?php esc_html_e( 'Primary Address:', 'client-dashboard-system' ); ?></h4>
        <p><?php echo esc_html($user_meta['full_address_display']); ?></p>
        <?php elseif (!empty($user_meta['address_city']) || !empty($user_meta['address_street'])) : ?>
        <h4><?php esc_html_e( 'Primary Address:', 'client-dashboard-system' ); ?></h4>
        <p>
            <?php
            $address_parts = array_filter([
                $user_meta['address_street'] ?? '',
                $user_meta['address_number'] ?? '',
                ($user_meta['address_apt'] ?? '') ? __('Apt', 'client-dashboard-system') . ' ' . $user_meta['address_apt'] : '',
                ($user_meta['address_floor'] ?? '') ? __('Floor', 'client-dashboard-system') . ' ' . $user_meta['address_floor'] : '',
                ($user_meta['address_entrance'] ?? '') ? __('Entrance', 'client-dashboard-system') . ' ' . $user_meta['address_entrance'] : '',
                $user_meta['address_city'] ?? ''
            ]);
            echo esc_html( implode(', ', $address_parts) ?: 'N/A' );
            ?>
        </p>
        <?php endif; ?>
    </div>

    <div class="cds-section cds-service-status">
        <h3><?php esc_html_e( 'Service Statuses', 'client-dashboard-system' ); ?></h3>
        <?php if ( ! empty( $detailed_services ) && is_array($detailed_services) ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Service Name', 'client-dashboard-system' ); ?></th>
                        <th><?php esc_html_e( 'Current Status', 'client-dashboard-system' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'client-dashboard-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $detailed_services as $service_display_name => $service_data ) : ?>
                        <tr>
                            <td><?php echo esc_html( $service_display_name ); ?></td>
                            <td><span class="status-badge status-<?php echo esc_attr(sanitize_title($service_data['status'])); ?>"><?php echo esc_html( $service_data['status'] ); ?></span></td>
                            <td>
                                <?php
                                $details_output = '';
                                if ($service_data['status'] === 'Missing docs' && !empty($service_data['missing_docs'])) {
                                    $details_output = '<strong>' . __('Missing: ', 'client-dashboard-system') . '</strong><br>' . nl2br(esc_html($service_data['missing_docs']));
                                } elseif ($service_data['status'] === 'Request denied' && !empty($service_data['denied_reason'])) {
                                     $details_output = '<strong>' . __('Reason: ', 'client-dashboard-system') . '</strong><br>' . nl2br(esc_html($service_data['denied_reason']));
                                } elseif (!empty($service_data['general_details'])) {
                                    $details_output = nl2br(esc_html($service_data['general_details']));
                                }
                                echo $details_output ?: '—'; 
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'No active service information available at this time.', 'client-dashboard-system' ); ?></p>
        <?php endif; ?>
    </div>

    <div class="cds-section cds-transaction-history">
        <h3><?php esc_html_e( 'Transaction History (Properties)', 'client-dashboard-system' ); ?></h3>
        <?php if ( ! empty( $transactions ) && is_array($transactions) ) : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Property Address (Deal Name)', 'client-dashboard-system' ); ?></th>
                        <th><?php esc_html_e( 'Date Recorded', 'client-dashboard-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $transactions as $transaction ) : ?>
                        <?php
                        if (!is_array($transaction)) continue; 
                        $deal_name = $transaction['deal_name'] ?? __('N/A', 'client-dashboard-system');
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

    <?php if (!empty($support_email) || !empty($support_whatsapp)): ?>
    <div class="cds-section cds-contact-support">
        <h3><?php esc_html_e( 'Need Help?', 'client-dashboard-system' ); ?></h3>
        <p>
            <?php
            $contact_messages = [];
            if ($support_email) {
                $contact_messages[] = sprintf(
                    esc_html__( 'contact us via email at %s', 'client-dashboard-system' ),
                    '<a href="mailto:' . esc_attr( $support_email ) . '">' . esc_html( $support_email ) . '</a>'
                );
            }
            if ($support_whatsapp) {
                $whatsapp_link = (strpos($support_whatsapp, 'wa.me') !== false || strpos($support_whatsapp, 'api.whatsapp.com') !== false)
                                 ? $support_whatsapp
                                 : 'https://wa.me/' . preg_replace('/[^0-9+]/', '', $support_whatsapp); 

                $contact_messages[] = sprintf(
                    esc_html__( '%1$s on WhatsApp: %2$s', 'client-dashboard-system' ),
                    (count($contact_messages) > 0 ? esc_html__('or', 'client-dashboard-system') : ''), 
                    '<a href="' . esc_url( $whatsapp_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Chat on WhatsApp', 'client-dashboard-system' ) . '</a>'
                );
            }
            echo esc_html__('If you have any questions, please ', 'client-dashboard-system') . implode(' ', $contact_messages) . '.';
            ?>
        </p>
    </div>
    <?php endif; ?>
</div>