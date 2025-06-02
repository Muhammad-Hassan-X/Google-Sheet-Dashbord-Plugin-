// File: client-dashboard-system/admin/assets/js/admin-script.js
// JavaScript for the plugin's admin pages

(function($) {
    'use strict';

    $(document).ready(function() {
        // Example: Confirm before a destructive action
        // $('.delete-something-button').on('click', function(e) {
        //     if (!confirm('Are you sure you want to delete this?')) {
        //         e.preventDefault();
        //     }
        // });

        // Example: AJAX call for manual sync (if implemented)
        // $('input[name="cds_manual_sync"]').on('click', function(e) {
        //     e.preventDefault();
        //     var $button = $(this);
        //     var $originalText = $button.val();
        //     $button.val('Syncing...').prop('disabled', true);

        //     $.ajax({
        //         url: cds_admin_ajax.ajax_url,
        //         type: 'POST',
        //         data: {
        //             action: 'cds_manual_sync_action', // This needs to be registered with add_action('wp_ajax_cds_manual_sync_action', ...)
        //             nonce: cds_admin_ajax.nonce
        //         },
        //         success: function(response) {
        //             if (response.success) {
        //                 alert('Sync completed successfully! ' + (response.data.message || ''));
        //                 // Optionally refresh part of the page or redirect
        //             } else {
        //                 alert('Sync failed: ' + (response.data.message || 'Unknown error.'));
        //             }
        //         },
        //         error: function(xhr, status, error) {
        //             alert('AJAX Error: ' + error);
        //         },
        //         complete: function() {
        //             $button.val($originalText).prop('disabled', false);
        //         }
        //     });
        // });

        // Handle dynamic sheet URL fields based on count
        var sheetCountInput = $('#cds_google_sheets_count');
        if (sheetCountInput.length) {
            // If the count changes, suggest saving and reloading for fields to update.
            // A more complex JS solution could dynamically add/remove fields,
            // but WordPress Settings API typically handles this on page reload after save.
            sheetCountInput.on('change', function() {
                var currentVal = $(this).val();
                var originalVal = $(this).data('original-val');
                if (typeof originalVal === 'undefined') {
                    $(this).data('original-val', currentVal);
                } else if (originalVal !== currentVal) {
                     if (!$('#cds-sheet-count-notice').length) {
                        $(this).after('<p id="cds-sheet-count-notice" class="notice notice-warning inline" style="margin-left:10px; padding:5px;">' + 'Please save settings for the sheet URL fields to update.' + '</p>');
                    }
                }
            });
        }


        // More admin-specific JS can go here
        // For example, for managing users on the "Manage Users/Services" page:
        // - AJAX calls to update service statuses
        // - AJAX calls to create/update users
        // - Implementing filters for the user list

        console.log('Client Dashboard System admin script loaded.');
    });

})(jQuery);