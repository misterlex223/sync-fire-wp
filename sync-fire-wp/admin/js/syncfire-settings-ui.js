/**
 * SyncFire Settings UI Enhancement
 *
 * Handles the dynamic UI behavior for the SyncFire settings page
 */
(function($) {
    'use strict';

    // Store connection status
    let isFirebaseConnected = false;

    /**
     * Initialize the settings UI enhancements
     */
    function init() {
        console.log('SyncFire Settings UI Init');

        // Check initial connection status
        checkConnectionStatus();

        // Setup collapsible sections
        setupCollapsibleSections();

        // Override the test connection function
        overrideTestConnection();
    }

    /**
     * Check the initial connection status
     */
    function checkConnectionStatus() {
        const connectionStatus = $('#syncfire-connection-status-value').val();
        isFirebaseConnected = connectionStatus === 'connected';

        // Update UI based on connection status
        updateUIBasedOnConnection();
    }

    /**
     * Setup collapsible sections
     */
    function setupCollapsibleSections() {
        // Add toggle icons to section headers
        $('.syncfire-collapsible-section h3').append('<span class="dashicons dashicons-arrow-up-alt2 syncfire-toggle-icon"></span>');

        // Handle section toggle
        $('.syncfire-collapsible-section h3').on('click', function() {
            const section = $(this).closest('.syncfire-collapsible-section');
            const content = section.find('.syncfire-section-content');
            const icon = $(this).find('.syncfire-toggle-icon');

            // Toggle only this specific section's content
            content.slideToggle(300);

            // Toggle only this specific section's icon
            if (icon.hasClass('dashicons-arrow-down-alt2')) {
                icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            } else {
                icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            }
        });

        // Initially collapse Firebase Configuration if connected
        if (isFirebaseConnected) {
            const firebaseSection = $('#syncfire-firebase-config');
            firebaseSection.find('.syncfire-section-content').hide();
            firebaseSection.find('.syncfire-toggle-icon')
                .removeClass('dashicons-arrow-up-alt2')
                .addClass('dashicons-arrow-down-alt2');
        }
    }

    /**
     * Override the test connection function
     */
    function overrideTestConnection() {
        // Store the original function
        const originalTestFunction = window.testFirebaseConnection;

        // Override with our enhanced version
        window.testFirebaseConnection = function() {
            // Call the original function
            originalTestFunction();

            // Add a delay to wait for the response
            setTimeout(function() {
                // Check if the success message is displayed
                const successMessage = $('#syncfire-sync-status .notice-success');
                if (successMessage.length > 0) {
                    isFirebaseConnected = true;
                    $('#syncfire-connection-status-value').val('connected');
                    updateUIBasedOnConnection();
                }
            }, 1000);
        };
    }

    /**
     * Update UI based on connection status
     */
    function updateUIBasedOnConnection() {
        if (isFirebaseConnected) {
            // Show sync sections
            $('#syncfire-taxonomy-sync, #syncfire-post-type-sync').show();

            // Collapse Firebase config
            const firebaseSection = $('#syncfire-firebase-config');
            const content = firebaseSection.find('.syncfire-section-content');
            const icon = firebaseSection.find('.syncfire-toggle-icon');

            content.slideUp(300);
            icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');

            // Move Firebase config and debug tools to bottom
            $('#syncfire-firebase-config, #syncfire-debug-tools').appendTo('.syncfire-settings-form');
        } else {
            // Hide sync sections
            $('#syncfire-taxonomy-sync, #syncfire-post-type-sync').hide();
        }
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);
