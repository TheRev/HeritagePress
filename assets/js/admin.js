// Admin JavaScript for HeritagePress
jQuery(document).ready(function ($) {
    'use strict';

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Handle AJAX requests
    $('.heritagepress-ajax-action').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var action = $button.data('action');

        $.ajax({
            url: heritagePressAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: action,
                nonce: heritagePressAdmin.nonce
            },
            beforeSend: function () {
                $button.prop('disabled', true);
            },
            success: function (response) {
                if (response.success) {
                    // Handle success
                } else {
                    // Handle error
                }
            },
            error: function (xhr, status, error) {
                console.error('HeritagePress Ajax Error:', error);
            },
            complete: function () {
                $button.prop('disabled', false);
            }
        });
    });
});
