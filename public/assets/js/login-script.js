jQuery(document).ready(function($) {
    'use strict';

    // Find the relevant input fields on the login page
    const customIdInput = $('#user_custom_id');
    const usernameInput = $('#user_login');
    const passwordInput = $('#user_pass');

    // Check if our custom ID field exists on the page
    if (customIdInput.length > 0) {
        
        const checkCustomIdField = function() {
            // If the custom ID field has a value...
            if (customIdInput.val().trim() !== '') {
                // ...remove the 'required' attribute from the standard fields.
                usernameInput.removeAttr('required');
                passwordInput.removeAttr('required');
            } else {
                // If the custom ID field is empty, restore the 'required' attributes.
                usernameInput.attr('required', 'required');
                passwordInput.attr('required', 'required');
            }
        };

        // Run the check when the page loads
        checkCustomIdField();

        // Run the check again anytime the user types in the custom ID field
        customIdInput.on('input', checkCustomIdField);
    }
});