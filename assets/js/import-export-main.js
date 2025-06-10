/**
 * HeritagePress Import/Export Main JavaScript
 *
 * Loads and coordinates modular JavaScript files based on current page context
 */

(function ($) {
    'use strict';

    var HeritagePress_ImportExport = {
        /**
         * Initialize the module
         */
        init: function () {
            this.loadRequiredModules();
            this.initCommonFeatures();
        },

        /**
         * Load required modules based on current context
         */
        loadRequiredModules: function () {
            var currentTab = this.getCurrentTab();

            // Load date validation module for all tabs
            if (window.HeritagePress_DateValidation) {
                // Date validation is already loaded via separate file
            }

            // Load tab-specific modules
            switch (currentTab) {
                case 'import':
                    this.loadImportModule();
                    break;
                case 'export':
                    this.loadExportModule();
                    break;
                case 'settings':
                    this.loadSettingsModule();
                    break;
                case 'logs':
                    this.loadLogsModule();
                    break;
            }
        },

        /**
         * Load import module
         */
        loadImportModule: function () {
            if (window.HeritagePress_Import) {
                // Import module is already loaded
            }
        },

        /**
         * Load export module
         */
        loadExportModule: function () {
            if (window.HeritagePress_Export) {
                // Export module is already loaded
            }
        },

        /**
         * Load settings module
         */
        loadSettingsModule: function () {
            if (window.HeritagePress_Settings) {
                // Settings module is already loaded
            }
        },

        /**
         * Load logs module
         */
        loadLogsModule: function () {
            this.initLogsTab();
        },

        /**
         * Initialize common features
         */
        initCommonFeatures: function () {
            this.initMessages();
            this.initTabNavigation();
            this.initTooltips();
        },

        /**
         * Get the current tab from URL
         * 
         * @returns {string} The current tab name
         */
        getCurrentTab: function () {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tab') || 'import';
        },

        /**
         * Initialize message system
         */
        initMessages: function () {
            // Auto-dismiss success messages
            $('.notice-success.is-dismissible').each(function () {
                var $notice = $(this);
                setTimeout(function () {
                    $notice.fadeOut();
                }, 5000);
            });

            // Handle manual dismissal
            $(document).on('click', '.notice-dismiss', function () {
                $(this).closest('.notice').fadeOut();
            });
        },

        /**
         * Initialize tab navigation
         */
        initTabNavigation: function () {
            $('.nav-tab').on('click', function (e) {
                var href = $(this).attr('href');
                if (href && href.indexOf('#') === -1) {
                    // Allow normal navigation for real links
                    return true;
                }
                e.preventDefault();
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function () {
            // Simple tooltip implementation
            $('[title]').on('mouseenter', function () {
                var $element = $(this);
                var title = $element.attr('title');

                if (title && !$element.data('tooltip-active')) {
                    $element.data('tooltip-active', true);
                    $element.data('original-title', title);
                    $element.attr('title', '');

                    var $tooltip = $('<div class="hp-tooltip">' + title + '</div>');
                    $('body').append($tooltip);

                    var position = $element.offset();
                    $tooltip.css({
                        top: position.top - $tooltip.outerHeight() - 5,
                        left: position.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    }).fadeIn(200);

                    $element.data('tooltip', $tooltip);
                }
            }).on('mouseleave', function () {
                var $element = $(this);
                var $tooltip = $element.data('tooltip');

                if ($tooltip) {
                    $tooltip.fadeOut(200, function () {
                        $tooltip.remove();
                    });
                    $element.attr('title', $element.data('original-title'));
                    $element.removeData('tooltip tooltip-active original-title');
                }
            });
        },

        /**
         * Initialize logs tab functionality
         */
        initLogsTab: function () {
            // Log details toggle
            $('.hp-log-details-toggle').on('click', function (e) {
                e.preventDefault();
                $(this).closest('tr').next('.hp-log-details-row').toggle();
            });

            // Log filters
            $('#hp-log-filter-form').on('submit', function () {
                // This form performs a normal non-AJAX submission
                return true;
            });

            // Log export functionality
            $('.hp-export-logs').on('click', this.handleLogExport);

            // Log clear functionality
            $('.hp-clear-logs').on('click', this.handleLogClear);
        },

        /**
         * Handle log export
         */
        handleLogExport: function (e) {
            e.preventDefault();

            var format = $(this).data('format') || 'csv';
            var filters = $('#hp-log-filter-form').serialize();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_export_logs',
                    format: format,
                    filters: filters,
                    nonce: $('#hp_logs_nonce').val()
                },
                success: function (response) {
                    if (response.success) {
                        // Trigger download
                        window.location.href = response.data.download_url;
                        HeritagePress_ImportExport.showMessage('success',
                            (hp_i18n.logs_exported || 'Logs exported successfully') +
                            ' (' + response.data.count + ' entries)');
                    } else {
                        HeritagePress_ImportExport.showMessage('error',
                            response.data.message || (hp_i18n.export_failed || 'Export failed'));
                    }
                },
                error: function () {
                    HeritagePress_ImportExport.showMessage('error',
                        hp_i18n.ajax_error || 'An error occurred');
                }
            });
        },

        /**
         * Handle log clear
         */
        handleLogClear: function (e) {
            e.preventDefault();

            if (!confirm(hp_i18n.confirm_clear_logs || 'Are you sure you want to clear the logs?')) {
                return;
            }

            var filters = $('#hp-log-filter-form').serialize();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_clear_logs',
                    filters: filters,
                    nonce: $('#hp_logs_nonce').val()
                },
                success: function (response) {
                    if (response.success) {
                        HeritagePress_ImportExport.showMessage('success',
                            response.data.message || (hp_i18n.logs_cleared || 'Logs cleared successfully'));

                        // Reload page to show updated logs
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    } else {
                        HeritagePress_ImportExport.showMessage('error',
                            response.data.message || (hp_i18n.clear_failed || 'Failed to clear logs'));
                    }
                },
                error: function () {
                    HeritagePress_ImportExport.showMessage('error',
                        hp_i18n.ajax_error || 'An error occurred');
                }
            });
        },

        /**
         * Show message to user
         */
        showMessage: function (type, message) {
            // Remove existing messages
            $('.hp-message').remove();

            // Create new message
            var $message = $('<div class="hp-message notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

            // Add to page
            $('.wrap h1').after($message);

            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function () {
                    $message.fadeOut();
                }, 5000);
            }

            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 50
            }, 300);
        },

        /**
         * Utility: Format file size
         */
        formatFileSize: function (bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Utility: Format number
         */
        formatNumber: function (num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        /**
         * Utility: Escape HTML
         */
        escapeHtml: function (text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function (m) { return map[m]; });
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        // Check if we have required objects
        if (typeof ajaxurl === 'undefined') {
            console.error('HeritagePress: ajaxurl not defined');
            return;
        }

        // Initialize main functionality
        HeritagePress_ImportExport.init();
    });

    // Make available globally
    window.HeritagePress_ImportExport = HeritagePress_ImportExport;

})(jQuery);
