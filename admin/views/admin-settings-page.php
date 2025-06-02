<?php
// File: client-dashboard-system/admin/views/admin-settings-page.php

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'cds_settings_group' ); // Output security fields for the registered setting group
        do_settings_sections( 'cds_settings_page' ); // Output the sections and fields for the settings page
        submit_button( __( 'Save Settings', 'client-dashboard-system' ) );
        ?>
    </form>
</div>
