/**
 * HeritagePress Export JavaScript Module
 *
 * Handles export-specific functionality
 */

(function ($) {
    'use strict';

    var HeritagePress_Export = {
        progressInterval: null,

        /**
         * Initialize export functionality
         */
        init: function () {
            this.bindEvents();
            this.initFormElements();
        },

        /**
         * Bind export-specific events
         */
        bindEvents: function () {
            // Export form submission
            $('#hp-gedcom-export-form').on('submit', this.handleExport.bind(this));

            // Format change events
            $('input[name="export_format"]').on('change', this.handleFormatChange);

            // Person search for branch filtering
            $('#hp-branch-person').on('input', this.handlePersonSearch);
            $(document).on('click', '.hp-branch-person-result', this.handlePersonSelect);

            // Date range validation
            $('#hp-date-range-start, #hp-date-range-end').on('change', this.validateDateRange);

            // Advanced filters toggle
            $('.hp-export-filters details').on('toggle', this.handleFilterToggle);
        },

        /**
         * Initialize form elements
         */
        initFormElements: function () {
            // Trigger initial format change
            $('input[name="export_format"]:checked').trigger('change');

            // Initialize person search autocomplete
            this.initPersonSearch();
        },

        /**
         * Handle export format change
         */
        handleFormatChange: function () {
            var format = $(this).val();

            // Hide all format-specific options
            $('.hp-export-format-options').hide();

            // Show relevant format options
            $('#hp-export-options-' + format).fadeIn(300);

            // Update button text
            var buttonText = HeritagePress_Export.getButtonText(format);
            $('#submit').val(buttonText);
        },

        /**
         * Get button text for format
         */
        getButtonText: function (format) {
            switch (format) {
                case 'gedzip':
                    return hp_i18n.export_gedzip || 'Export GEDZIP';
                case 'json':
                    return hp_i18n.export_json || 'Export JSON';
                case 'gedcom':
                default:
                    return hp_i18n.export_gedcom || 'Export GEDCOM';
            }
        },

        /**
         * Handle export form submission
         */
        handleExport: function (e) {
            e.preventDefault();

            var $form = $(e.target);
            var $submitButton = $form.find('input[type="submit"]');
            var $formContainer = $('.hp-export-container form');
            var $progressContainer = $('#hp-export-progress-container');
            var formData = new FormData(e.target);

            // Validate form
            if (!this.validateExportForm($form)) {
                return;
            }

            // Get format for proper messaging
            var format = $('input[name="export_format"]:checked').val();

            // Add action
            formData.append('action', 'hp_export_gedcom');
            formData.append('hp_gedcom_export_nonce', $('#hp_gedcom_export_nonce').val());

            // Show processing state
            this.showProcessingState($submitButton, $formContainer, $progressContainer, format);

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.simulateExportProgress(response.data.export_key, format, () => {
                            this.showExportComplete(response.data, format, $submitButton, $formContainer, $progressContainer);
                        });
                    } else {
                        this.showExportError(response.data.message, format, $submitButton, $formContainer, $progressContainer);
                    }
                },
                error: () => {
                    this.showExportError(hp_i18n.ajax_error, format, $submitButton, $formContainer, $progressContainer);
                }
            });
        },

        /**
         * Validate export form
         */
        validateExportForm: function ($form) {
            // Check tree selection
            var treeId = $('#hp-export-tree').val();
            if (!treeId) {
                this.showMessage('error', hp_i18n.select_tree || 'Please select a tree to export');
                return false;
            }

            // Validate date range if used
            if ($('#hp-date-range-start').val() && $('#hp-date-range-end').val()) {
                if (!this.isValidDateRange()) {
                    this.showMessage('error', hp_i18n.date_range_error || 'End date must be after start date');
                    $('#hp-date-range-end').focus();
                    return false;
                }
            }

            return true;
        },

        /**
         * Check if date range is valid
         */
        isValidDateRange: function () {
            var startDate = new Date($('#hp-date-range-start').val());
            var endDate = new Date($('#hp-date-range-end').val());
            return startDate <= endDate;
        },

        /**
         * Show processing state
         */
        showProcessingState: function ($submitButton, $formContainer, $progressContainer, format) {
            // Update button text
            var buttonText = this.getProcessingButtonText(format);
            $submitButton.prop('disabled', true).val(buttonText);

            // Show progress container and hide form
            $formContainer.slideUp(400, () => {
                $progressContainer.slideDown(400);
                this.resetProgressIndicators();
            });
        },

        /**
         * Get processing button text
         */
        getProcessingButtonText: function (format) {
            switch (format) {
                case 'gedzip':
                    return hp_i18n.exporting_gedzip || 'Exporting GEDZIP...';
                case 'json':
                    return hp_i18n.exporting_json || 'Exporting JSON...';
                case 'gedcom':
                default:
                    return hp_i18n.exporting_gedcom || 'Exporting GEDCOM...';
            }
        },

        /**
         * Reset progress indicators
         */
        resetProgressIndicators: function () {
            $('.hp-progress-bar-inner').css('width', '0%');
            $('#hp-export-individuals, #hp-export-families, #hp-export-sources, #hp-export-media').text('0');
            $('#hp-export-operation').text(hp_i18n.preparing_export || 'Preparing export...');
            $('#hp-export-result').hide();
        },

        /**
         * Simulate export progress
         */
        simulateExportProgress: function (exportKey, format, callback) {
            var progress = 0;
            var operations = this.getOperationsForFormat(format);
            var currentStep = 0;

            var progressInterval = setInterval(() => {
                if (currentStep < operations.length) {
                    var operation = operations[currentStep];

                    // Update progress
                    $('.hp-progress-bar-inner').css('width', operation.percent + '%');
                    $('#hp-export-operation').text(operation.operation);

                    // Update stats
                    $('#hp-export-individuals').text(operation.individuals);
                    $('#hp-export-families').text(operation.families);
                    $('#hp-export-sources').text(operation.sources);
                    $('#hp-export-media').text(operation.media);

                    currentStep++;

                    // If complete
                    if (operation.percent >= 100) {
                        clearInterval(progressInterval);
                        callback();
                    }
                } else {
                    clearInterval(progressInterval);
                    callback();
                }
            }, 1500);
        },

        /**
         * Get operations for format
         */
        getOperationsForFormat: function (format) {
            switch (format) {
                case 'gedzip':
                    return [
                        { percent: 5, operation: 'Preparing data structures...', individuals: 0, families: 0, sources: 0, media: 0 },
                        { percent: 20, operation: 'Exporting individual records...', individuals: 120, families: 0, sources: 0, media: 0 },
                        { percent: 35, operation: 'Exporting families...', individuals: 250, families: 80, sources: 0, media: 0 },
                        { percent: 45, operation: 'Exporting source citations...', individuals: 250, families: 150, sources: 45, media: 0 },
                        { percent: 55, operation: 'Collecting media files...', individuals: 250, families: 150, sources: 95, media: 30 },
                        { percent: 75, operation: 'Processing media files...', individuals: 250, families: 150, sources: 95, media: 68 },
                        { percent: 85, operation: 'Generating GEDCOM file...', individuals: 250, families: 150, sources: 95, media: 68 },
                        { percent: 95, operation: 'Creating ZIP archive...', individuals: 250, families: 150, sources: 95, media: 68 },
                        { percent: 100, operation: 'Finalizing GEDZIP file...', individuals: 250, families: 150, sources: 95, media: 68 }
                    ];
                case 'json':
                    return [
                        { percent: 10, operation: 'Preparing data structures...', individuals: 0, families: 0, sources: 0, media: 0 },
                        { percent: 25, operation: 'Exporting individual records...', individuals: 120, families: 0, sources: 0, media: 0 },
                        { percent: 40, operation: 'Exporting families...', individuals: 250, families: 80, sources: 0, media: 0 },
                        { percent: 60, operation: 'Exporting source citations...', individuals: 250, families: 150, sources: 45, media: 0 },
                        { percent: 85, operation: 'Exporting media references...', individuals: 250, families: 150, sources: 95, media: 30 },
                        { percent: 100, operation: 'Finalizing JSON file...', individuals: 250, families: 150, sources: 95, media: 68 }
                    ];
                case 'gedcom':
                default:
                    return [
                        { percent: 10, operation: 'Preparing data structures...', individuals: 0, families: 0, sources: 0, media: 0 },
                        { percent: 25, operation: 'Processing individual records...', individuals: 120, families: 0, sources: 0, media: 0 },
                        { percent: 40, operation: 'Processing families...', individuals: 250, families: 80, sources: 0, media: 0 },
                        { percent: 55, operation: 'Processing source citations...', individuals: 250, families: 150, sources: 45, media: 0 },
                        { percent: 70, operation: 'Processing media metadata...', individuals: 250, families: 150, sources: 95, media: 30 },
                        { percent: 85, operation: 'Building JSON structure...', individuals: 250, families: 150, sources: 95, media: 68 },
                        { percent: 100, operation: 'Finalizing GEDCOM file...', individuals: 250, families: 150, sources: 95, media: 68 }
                    ];
            }
        },

        /**
         * Show export complete
         */
        showExportComplete: function (data, format, $submitButton, $formContainer, $progressContainer) {
            // Update download button
            $('#hp-export-download').attr('href', data.download_url);
            var downloadText = this.getDownloadText(format);
            $('#hp-export-download').text(downloadText);
            $('#hp-export-result').fadeIn();

            // Show success message
            this.showMessage('success', data.message);

            // Re-enable form after delay
            setTimeout(() => {
                $progressContainer.slideUp(400, () => {
                    $formContainer.slideDown(400);
                });

                var resetButtonText = this.getButtonText(format);
                $submitButton.prop('disabled', false).val(resetButtonText);
            }, 2000);
        },

        /**
         * Get download text for format
         */
        getDownloadText: function (format) {
            switch (format) {
                case 'gedzip':
                    return hp_i18n.download_gedzip || 'Download GEDZIP Archive';
                case 'json':
                    return hp_i18n.download_json || 'Download JSON File';
                case 'gedcom':
                default:
                    return hp_i18n.download_gedcom || 'Download GEDCOM File';
            }
        },

        /**
         * Show export error
         */
        showExportError: function (message, format, $submitButton, $formContainer, $progressContainer) {
            // Show form again
            $progressContainer.slideUp(400, () => {
                $formContainer.slideDown(400);
            });

            var resetButtonText = this.getButtonText(format);
            $submitButton.prop('disabled', false).val(resetButtonText);

            this.showMessage('error', message);
        },

        /**
         * Initialize person search
         */
        initPersonSearch: function () {
            var searchTimeout;

            $('#hp-branch-person').on('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performPersonSearch($(e.target).val());
                }, 500);
            });

            // Clear search when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('#hp-branch-person, .hp-branch-person-results').length) {
                    $('.hp-branch-person-results').remove();
                }
            });
        },

        /**
         * Handle person search
         */
        handlePersonSearch: function (e) {
            var $input = $(e.target);
            var query = $input.val().trim();

            // Clear person ID when text changes
            if (!$input.data('programmatic-change')) {
                $('#hp-branch-person-id').val('');
            }
            $input.data('programmatic-change', false);

            // Remove existing results
            $('.hp-branch-person-results').remove();

            if (query.length < 2) {
                return;
            }

            this.performPersonSearch(query);
        },

        /**
         * Perform person search
         */
        performPersonSearch: function (query) {
            var $input = $('#hp-branch-person');
            var treeId = $('#hp-export-tree').val();

            if (!treeId) {
                $input.after('<div class="hp-branch-person-results hp-branch-person-error">' +
                    (hp_i18n.select_tree_first || 'Please select a tree first') + '</div>');
                return;
            }

            // Show loading
            $input.after('<div class="hp-branch-person-search-loading">' +
                (hp_i18n.searching || 'Searching') + '...</div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_search_people',
                    nonce: $('#hp_gedcom_export_nonce').val(),
                    query: query,
                    tree_id: treeId
                },
                success: (response) => {
                    $('.hp-branch-person-search-loading').remove();

                    if (response.success && response.data.people.length > 0) {
                        this.showPersonSearchResults(response.data.people);
                    } else {
                        $input.after('<div class="hp-branch-person-results hp-branch-person-empty">' +
                            (hp_i18n.no_results || 'No people found') + '</div>');
                    }
                },
                error: () => {
                    $('.hp-branch-person-search-loading').remove();
                    $input.after('<div class="hp-branch-person-results hp-branch-person-error">' +
                        (hp_i18n.search_error || 'Search failed') + '</div>');
                }
            });
        },

        /**
         * Show person search results
         */
        showPersonSearchResults: function (people) {
            var $input = $('#hp-branch-person');
            var resultsHtml = '<div class="hp-branch-person-results">';

            people.forEach((person) => {
                var yearInfo = '';
                if (person.birth_year || person.death_year) {
                    yearInfo = ' (' + (person.birth_year || '?') + ' - ' + (person.death_year || '?') + ')';
                }

                resultsHtml += '<div class="hp-branch-person-result" data-id="' + person.id + '" data-name="' + person.name + '">' +
                    person.name + yearInfo + '</div>';
            });

            resultsHtml += '</div>';
            $input.after(resultsHtml);
        },

        /**
         * Handle person selection
         */
        handlePersonSelect: function (e) {
            var $result = $(e.target);
            var personId = $result.data('id');
            var personName = $result.data('name');

            // Set values
            $('#hp-branch-person').data('programmatic-change', true).val(personName);
            $('#hp-branch-person-id').val(personId);

            // Remove results
            $('.hp-branch-person-results').remove();

            // Enable branch options
            $('#hp-branch-generations, input[name="branch_direction"], #hp-include-spouses')
                .prop('disabled', false);
        },

        /**
         * Validate date range
         */
        validateDateRange: function () {
            var startDate = $('#hp-date-range-start').val();
            var endDate = $('#hp-date-range-end').val();

            // Remove existing error
            $('.hp-date-range-error').remove();

            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                $('#hp-date-range-end').after(
                    '<div class="hp-date-range-error" style="color: red; font-size: 12px; margin-top: 4px;">' +
                    (hp_i18n.date_range_error || 'End date must be after start date') +
                    '</div>'
                );
            }
        },

        /**
         * Handle filter toggle
         */
        handleFilterToggle: function () {
            if (this.open) {
                $(this).find('input:first').focus();
            }
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
                setTimeout(() => {
                    $message.fadeOut();
                }, 5000);
            }

            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 50
            }, 300);
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        HeritagePress_Export.init();
    });

    // Make available globally
    window.HeritagePress_Export = HeritagePress_Export;

})(jQuery);
