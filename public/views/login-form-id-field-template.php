<?php
// File: client-dashboard-system/public/views/login-form-id-field-template.php

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<p style="margin-bottom: 10px;">
    <label for="user_custom_id" style="display: block; margin-bottom: 3px; font-weight: bold;"><?php esc_html_e( 'Client ID', 'client-dashboard-system' ); ?></label>
    <input type="text" name="user_custom_id" id="user_custom_id" class="input" value="<?php echo esc_attr( wp_unslash( $_POST['user_custom_id'] ?? '' ) ); ?>" size="20" autocapitalize="off" autocomplete="off" />
</p>
<p style="font-size: 0.9em; color: #555; margin-top: -5px; margin-bottom: 15px;">
    <?php esc_html_e( 'If you have a Client ID, enter it here to log in.', 'client-dashboard-system' ); ?>
</p>