// File: client-dashboard-system/admin/assets/js/admin-script.js

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('Client Dashboard System admin script loaded. Version ' + (typeof cds_admin_params !== 'undefined' ? cds_admin_params.version : 'N/A'));

        // Handle clicks on "Edit" service status buttons
        $('.cds-edit-service-status-btn').on('click', function() {
            const userId = $(this).data('user-id');
            const serviceName = $(this).data('service-name');
            const currentStatus = $(this).data('current-status');
            let detailsText = $(this).data('details') || ''; // General details
            const missingDocs = $(this).data('missing-docs') || '';
            const deniedReason = $(this).data('denied-reason') || '';

            $('#cds-modal-user-id').val(userId);
            $('#cds-modal-service-name').val(serviceName);
            $('#cds-modal-new-status').val(currentStatus);
            
            // Populate details text based on current status
            if (currentStatus === 'Missing docs' && missingDocs) {
                $('#cds-modal-details-text').val(missingDocs);
                $('#cds-modal-details-field-group label strong').text('Missing Documents List:');
            } else if (currentStatus === 'Request Denied' && deniedReason) {
                $('#cds-modal-details-text').val(deniedReason);
                $('#cds-modal-details-field-group label strong').text('Reason for Denial:');
            } else {
                 $('#cds-modal-details-text').val(detailsText); // Populate with general details if others not applicable
                 $('#cds-modal-details-field-group label strong').text('Details (e.g., Missing Docs List / Denial Reason / Other Info):');
            }
            
            $('#cds-modal-title').text('Edit Status for: ' + serviceName);
            $('#cds-modal-message').hide().removeClass('success error').empty();
            $('#cds-edit-service-modal').fadeIn();
        });

        // Handle status change in modal to update label for details field
        $('#cds-modal-new-status').on('change', function() {
            const selectedStatus = $(this).val();
            if (selectedStatus === 'Missing docs') {
                $('#cds-modal-details-field-group label strong').text('Missing Documents List:');
            } else if (selectedStatus === 'Request Denied') {
                $('#cds-modal-details-field-group label strong').text('Reason for Denial:');
            } else {
                 $('#cds-modal-details-field-group label strong').text('Details (e.g., Other Info):');
            }
        });


        // Handle modal form submission
        $('#cds-edit-service-form').on('submit', function(e) {
            e.preventDefault();
            $('#cds-modal-message').hide().removeClass('success error').empty();
            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            const originalButtonText = $submitButton.text();
            $submitButton.text('Updating...').prop('disabled', true);

            const formData = {
                action: 'cds_update_service_status',
                nonce: cds_admin_params.nonce,
                user_id: $('#cds-modal-user-id').val(),
                service_name: $('#cds-modal-service-name').val(),
                new_status: $('#cds-modal-new-status').val(),
                details_text: $('#cds-modal-details-text').val()
            };

            $.post(cds_admin_params.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        $('#cds-modal-message').text(response.data.message).addClass('success').show();
                        // Optionally, refresh the page or update the UI dynamically after a short delay
                        setTimeout(function() {
                            $('#cds-edit-service-modal').fadeOut();
                            location.reload(); // Simple refresh for now
                        }, 1500);
                    } else {
                        $('#cds-modal-message').text('Error: ' + (response.data.message || 'Unknown error')).addClass('error').show();
                    }
                })
                .fail(function() {
                    $('#cds-modal-message').text('AJAX request failed. Please try again.').addClass('error').show();
                })
                .always(function() {
                    $submitButton.text(originalButtonText).prop('disabled', false);
                });
        });

        // Close modal
        $('#cds-modal-cancel').on('click', function() {
            $('#cds-edit-service-modal').fadeOut();
        });
        $(document).on('click', function(e) { // Close if clicked outside modal content
            if ($(e.target).is('#cds-edit-service-modal')) {
                $('#cds-edit-service-modal').fadeOut();
            }
        });

    });

})(jQuery);