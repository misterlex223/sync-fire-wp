/**
 * All of the JavaScript for your admin-specific functionality should be
 * included in this file.
 */
(function($) {
    'use strict';

    /**
     * Initialize the admin JavaScript
     */
    function init() {
        // Test Firebase connection
        $('#syncfire-test-connection').on('click', function(e) {
            e.preventDefault();
            testFirebaseConnection();
        });

        // Sync all post types
        $('#syncfire-sync-all').on('click', function(e) {
            e.preventDefault();
            syncAll();
        });

        // Sync selected taxonomy
        $('#syncfire-sync-taxonomy').on('click', function(e) {
            e.preventDefault();
            syncTaxonomy();
        });

        // Post type checkbox change
        $('.syncfire-post-type-checkbox').on('change', function() {
            const postType = $(this).data('post-type');
            togglePostTypeFields(postType, $(this).is(':checked'));
        });

        // Field checkbox change
        $('.syncfire-field-checkbox').on('change', function() {
            const postType = $(this).data('post-type');
            const field = $(this).data('field');
            toggleFieldMapping(postType, field, $(this).is(':checked'));
        });

        // Initialize post type fields visibility
        $('.syncfire-post-type-checkbox').each(function() {
            const postType = $(this).data('post-type');
            togglePostTypeFields(postType, $(this).is(':checked'));
        });
    }

    /**
     * Test the Firebase connection
     */
    function testFirebaseConnection() {
        const data = {
            action: 'syncfire_test_firebase_connection',
            nonce: syncfire_data.nonce
        };

        showSyncStatus('Testing Firebase connection...', 'info');

        $.post(syncfire_data.ajax_url, data, function(response) {
            if (response.success) {
                showSyncStatus(response.data.message, 'success');
            } else {
                showSyncStatus(response.data.message, 'error');
            }
        }).fail(function() {
            showSyncStatus('An error occurred while testing the connection.', 'error');
        });
    }

    /**
     * Sync all post types and taxonomies
     */
    function syncAll() {
        const data = {
            action: 'syncfire_resync_all',
            nonce: syncfire_data.nonce
        };

        showSyncStatus('Syncing all post types and taxonomies...', 'info');

        $.post(syncfire_data.ajax_url, data, function(response) {
            if (response.success) {
                showSyncStatus(response.data.message, 'success');
            } else {
                showSyncStatus(response.data.message, 'error');
            }
        }).fail(function() {
            showSyncStatus('An error occurred during synchronization.', 'error');
        });
    }

    /**
     * Sync a specific taxonomy
     */
    function syncTaxonomy() {
        const taxonomy = $('#syncfire-taxonomy-select').val();

        if (!taxonomy) {
            showSyncStatus('No taxonomy selected.', 'error');
            return;
        }

        const data = {
            action: 'syncfire_resync_taxonomy',
            nonce: syncfire_data.nonce,
            taxonomy: taxonomy
        };

        showSyncStatus(`Syncing taxonomy ${taxonomy}...`, 'info');

        $.post(syncfire_data.ajax_url, data, function(response) {
            if (response.success) {
                showSyncStatus(response.data.message, 'success');
            } else {
                showSyncStatus(response.data.message, 'error');
            }
        }).fail(function() {
            showSyncStatus('An error occurred during taxonomy synchronization.', 'error');
        });
    }

    /**
     * Toggle post type fields visibility
     */
    function togglePostTypeFields(postType, isChecked) {
        const fieldsContainer = $(`#syncfire-post-type-fields-${postType}`);
        
        if (isChecked) {
            fieldsContainer.show();
        } else {
            fieldsContainer.hide();
        }
    }

    /**
     * Toggle field mapping visibility
     */
    function toggleFieldMapping(postType, field, isChecked) {
        const mappingTable = $(`#syncfire-field-mapping-${postType}`);
        const fieldRow = mappingTable.find(`tr:contains("${field}")`);
        
        if (isChecked) {
            // If the row doesn't exist, add it
            if (fieldRow.length === 0) {
                const newRow = $('<tr></tr>');
                newRow.append(`<td>${field}</td>`);
                newRow.append(`<td><input type="text" name="syncfire_post_type_field_mapping[${postType}][${field}]" value="${field}" class="regular-text" /></td>`);
                mappingTable.append(newRow);
            } else {
                fieldRow.show();
            }
        } else {
            fieldRow.hide();
        }
    }

    /**
     * Show sync status message
     */
    function showSyncStatus(message, type) {
        const statusContainer = $('#syncfire-sync-status');
        let statusClass = '';
        
        switch (type) {
            case 'success':
                statusClass = 'notice-success';
                break;
            case 'error':
                statusClass = 'notice-error';
                break;
            case 'info':
            default:
                statusClass = 'notice-info';
                break;
        }
        
        statusContainer.html(`<div class="notice ${statusClass} inline"><p>${message}</p></div>`);
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);
