/**
 * HeritagePress Import/Export JavaScript
 *
 * Handles the frontend interactivity for the import/export interface
 */

(function ($) {
    'use strict';

    var HeritagePress_ImportExport = {
        /**
         * Initialize the module
         */
        init: function () {
            this.bindEvents();

            // Initialize the current tab functionality
            var currentTab = this.getCurrentTab();
            this.initTabFunctionality(currentTab);

            // Initialize date validation
            this.initDateValidation();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function () {
            // Export form submission
            $('#hp-gedcom-export-form').on('submit', this.handleExport);
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
         * Initialize tab-specific functionality
         * 
         * @param {string} tab The current tab name
         */
        initTabFunctionality: function (tab) {
            switch (tab) {
                case 'export':
                    this.initExportTab();
                    break;
                case 'import':
                    this.initImportTab();
                    break;
                case 'settings':
                    this.initSettingsTab();
                    break;
                case 'logs':
                    this.initLogsTab();
                    break;
            }
        },

        /**
         * Initialize the export tab functionality
         */
        initExportTab: function () {
            // Show/hide format-specific options
            $('input[name="export_format"]').on('change', function () {
                var format = $(this).val();
                $('.hp-export-format-options').hide();
                $('#hp-export-options-' + format).fadeIn(300);

                // Update the export button text based on format
                var buttonText = '';
                switch (format) {
                    case 'gedzip':
                        buttonText = hp_i18n.export_gedzip || 'Export GEDZIP';
                        break;
                    case 'json':
                        buttonText = hp_i18n.export_json || 'Export JSON';
                        break;
                    case 'gedcom':
                    default:
                        buttonText = hp_i18n.export_gedcom || 'Export GEDCOM';
                        break;
                }
                $('#submit').val(buttonText);
            }).filter(':checked').trigger('change');

            // Toggle advanced filter sections
            $('.hp-export-filters details').on('toggle', function () {
                if (this.open) {
                    $(this).find('input:first').focus();
                }
            });

            // Date range validation
            $('#hp-date-range-start, #hp-date-range-end').on('change', function () {
                var startDate = $('#hp-date-range-start').val();
                var endDate = $('#hp-date-range-end').val();

                if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                    $('.hp-date-range-error').remove();
                    $('#hp-date-range-end').after(
                        '<div class="hp-date-range-error" style="color: red; font-size: 12px; margin-top: 4px;">' +
                        (hp_i18n.date_range_error || 'End date must be after start date') +
                        '</div>'
                    );
                } else {
                    $('.hp-date-range-error').remove();
                }
            });
            // Person search for branch selection with typeahead
            var searchTimeout = null;
            var minSearchLength = 2;

            $('#hp-branch-person').on('keyup focus', function () {
                var query = $(this).val().trim();
                var $input = $(this);

                // Clear previous search timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                // Clear results if input is empty
                if (query.length === 0) {
                    $('#hp-branch-person-id').val('');
                    $('.hp-branch-person-results, .hp-branch-person-search-loading').remove();
                    return;
                }

                // Don't search if query is too short
                if (query.length < minSearchLength) {
                    return;
                }

                // Wait a bit before searching to avoid too many requests
                searchTimeout = setTimeout(function () {
                    // Check if already searching
                    if ($input.data('searching')) return;
                    $input.data('searching', true);

                    // Remove old results
                    $('.hp-branch-person-results').remove();

                    // Show loading indicator
                    if (!$('.hp-branch-person-search-loading').length) {
                        $input.after('<div class="hp-branch-person-search-loading">' +
                            (hp_i18n.searching || 'Searching') + '...</div>');
                    }

                    // Get the current tree ID
                    var treeId = $('#hp-export-tree').val();
                    if (!treeId) {
                        $('.hp-branch-person-search-loading').remove();
                        $input.after('<div class="hp-branch-person-results hp-branch-person-error">' +
                            (hp_i18n.select_tree_first || 'Please select a tree first') + '</div>');
                        $input.data('searching', false);
                        return;
                    }

                    // Make AJAX request to search for people
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'hp_search_people',
                            nonce: $('#hp_gedcom_export_nonce').val(),
                            query: query,
                            tree_id: treeId
                        },
                        success: function (response) {
                            $('.hp-branch-person-search-loading').remove();

                            if (response.success && response.data.people && response.data.people.length > 0) {
                                // Create results dropdown
                                var $results = $('<div class="hp-branch-person-results"></div>');

                                // Add each person to results
                                $.each(response.data.people, function (i, person) {
                                    var lifespan = '';
                                    if (person.birth_year || person.death_year) {
                                        lifespan = ' (' + (person.birth_year || '?') + '-' +
                                            (person.death_year || '') + ')';
                                    }

                                    $results.append(
                                        $('<div class="hp-branch-person-result"></div>')
                                            .attr('data-id', person.id)
                                            .text(person.name + lifespan)
                                    );
                                });

                                // Add to DOM
                                $input.after($results);

                            } else {
                                // Show no results message
                                $input.after('<div class="hp-branch-person-results hp-branch-person-no-results">' +
                                    (hp_i18n.no_people_found || 'No people found matching your search') + '</div>');
                            }

                            $input.data('searching', false);
                        },
                        error: function () {
                            $('.hp-branch-person-search-loading').remove();
                            $input.after('<div class="hp-branch-person-results hp-branch-person-error">' +
                                (hp_i18n.search_error || 'Error searching for people') + '</div>');
                            $input.data('searching', false);
                        }
                    });
                }, 500); // Wait 500ms before searching
            });

            // Handle person selection from search results
            $(document).on('click', '.hp-branch-person-result', function () {
                var personId = $(this).data('id');
                var personName = $(this).text();

                $('#hp-branch-person').val(personName);
                $('#hp-branch-person-id').val(personId);
                $('.hp-branch-person-results').remove();

                // Enable branch selection options
                $('#hp-branch-generations, input[name="branch_direction"], #hp-include-spouses')
                    .prop('disabled', false);
            });

            // Handle typing in person search to clear ID when text is changed
            $('#hp-branch-person').on('input', function () {
                // If user is actively changing the text, clear the ID
                if (!$(this).data('programmatic-change')) {
                    $('#hp-branch-person-id').val('');

                    // Disable branch options if no person selected
                    if ($(this).val().trim() === '') {
                        $('#hp-branch-generations, input[name="branch_direction"], #hp-include-spouses')
                            .prop('disabled', true);
                    }
                }
                $(this).data('programmatic-change', false);
            });

            // Handle clicking outside of the person search
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#hp-branch-person, .hp-branch-person-results').length) {
                    $('.hp-branch-person-results, .hp-branch-person-search-loading').remove();
                }
            });

            // Export form submission
            $('#hp-gedcom-export-form').on('submit', this.handleExport);
        },        /**
         * Handle the export form submission
         * 
         * @param {Event} e The submit event
         */
        handleExport: function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var $formContainer = $('.hp-export-container form');
            var $progressContainer = $('#hp-export-progress-container');
            var formData = new FormData(this);

            // Get format for proper messaging
            var format = $('input[name="export_format"]:checked').val();
            var formatName = format.toUpperCase();

            // Validate form fields
            var treeId = $('#hp-export-tree').val();
            if (!treeId) {
                HeritagePress_ImportExport.showMessage('error', hp_i18n.select_tree || 'Please select a tree to export');
                return;
            }

            // Validate date range if used
            if ($('#hp-date-range-start').val() && $('#hp-date-range-end').val()) {
                var startDate = new Date($('#hp-date-range-start').val());
                var endDate = new Date($('#hp-date-range-end').val());

                if (startDate > endDate) {
                    HeritagePress_ImportExport.showMessage('error', hp_i18n.date_range_error || 'End date must be after start date');
                    $('#hp-date-range-end').focus();
                    return;
                }
            }

            // Add action
            formData.append('action', 'hp_export_gedcom');
            formData.append('hp_gedcom_export_nonce', $('#hp_gedcom_export_nonce').val());

            // Disable button and show exporting state
            var buttonText = '';
            switch (format) {
                case 'gedzip':
                    buttonText = hp_i18n.exporting_gedzip || 'Exporting GEDZIP...';
                    break;
                case 'json':
                    buttonText = hp_i18n.exporting_json || 'Exporting JSON...';
                    break;
                case 'gedcom':
                default:
                    buttonText = hp_i18n.exporting_gedcom || 'Exporting GEDCOM...';
                    break;
            }
            $submitButton.prop('disabled', true).val(buttonText);

            // Show progress container and hide form
            $formContainer.slideUp(400, function () {
                $progressContainer.slideDown(400);

                // Reset progress indicators
                $('.hp-progress-bar-inner').css('width', '0%');
                $('#hp-export-individuals, #hp-export-families, #hp-export-sources, #hp-export-media').text('0');
                $('#hp-export-operation').text(hp_i18n.preparing_export || 'Preparing export...');
                $('#hp-export-result').hide();
            });

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        // Simulate export progress (in a real implementation, this would be a separate progress checking mechanism)
                        HeritagePress_ImportExport.simulateExportProgress(response.data.export_key, format, function () {
                            // Update download button with format-specific text
                            $('#hp-export-download').attr('href', response.data.download_url);
                            var downloadText = '';
                            switch (format) {
                                case 'gedzip':
                                    downloadText = hp_i18n.download_gedzip || 'Download GEDZIP Archive';
                                    break;
                                case 'json':
                                    downloadText = hp_i18n.download_json || 'Download JSON File';
                                    break;
                                case 'gedcom':
                                default:
                                    downloadText = hp_i18n.download_gedcom || 'Download GEDCOM File';
                                    break;
                            }
                            $('#hp-export-download').text(downloadText);
                            $('#hp-export-result').fadeIn();

                            // Show success message
                            HeritagePress_ImportExport.showMessage('success', response.data.message);

                            // Re-enable form submission after a delay
                            setTimeout(function () {
                                var resetButtonText = '';
                                switch (format) {
                                    case 'gedzip':
                                        resetButtonText = hp_i18n.export_gedzip || 'Export GEDZIP';
                                        break;
                                    case 'json':
                                        resetButtonText = hp_i18n.export_json || 'Export JSON';
                                        break;
                                    case 'gedcom':
                                    default:
                                        resetButtonText = hp_i18n.export_gedcom || 'Export GEDCOM';
                                        break;
                                }
                                $submitButton.prop('disabled', false).val(resetButtonText);
                            }, 2000);
                        });
                    } else {
                        // Show form again if there's an error
                        $progressContainer.slideUp(400, function () {
                            $formContainer.slideDown(400);
                        });

                        var resetButtonText = '';
                        switch (format) {
                            case 'gedzip':
                                resetButtonText = hp_i18n.export_gedzip || 'Export GEDZIP';
                                break;
                            case 'json':
                                resetButtonText = hp_i18n.export_json || 'Export JSON';
                                break;
                            case 'gedcom':
                            default:
                                resetButtonText = hp_i18n.export_gedcom || 'Export GEDCOM';
                                break;
                        }
                        $submitButton.prop('disabled', false).val(resetButtonText);

                        HeritagePress_ImportExport.showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    // Show form again
                    $progressContainer.slideUp(400, function () {
                        $formContainer.slideDown(400);
                    });

                    var resetButtonText = '';
                    switch (format) {
                        case 'gedzip':
                            resetButtonText = hp_i18n.export_gedzip || 'Export GEDZIP';
                            break;
                        case 'json':
                            resetButtonText = hp_i18n.export_json || 'Export JSON';
                            break;
                        case 'gedcom':
                        default:
                            resetButtonText = hp_i18n.export_gedcom || 'Export GEDCOM';
                            break;
                    }
                    $submitButton.prop('disabled', false).val(resetButtonText);

                    HeritagePress_ImportExport.showMessage('error', hp_i18n.ajax_error || 'A server error occurred. Please try again later.');
                }
            });
        },        /**
         * Simulate export progress for demo purposes
         * In a real implementation, this would check progress via AJAX
         * 
         * @param {string} exportKey The unique export key
         * @param {string} format The export format (gedcom, gedzip, json)
         * @param {function} callback Function to call when complete
         */
        simulateExportProgress: function (exportKey, format, callback) {
            var progress = 0;
            var operations;

            // Define format-specific operations
            switch (format) {
                case 'gedzip':
                    operations = [
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
                    break;

                case 'json':
                    operations = [
                        { percent: 10, operation: 'Preparing data structures...', individuals: 0, families: 0, sources: 0, media: 0 },
                        { percent: 25, operation: 'Processing individual records...', individuals: 120, families: 0, sources: 0, media: 0 },
                        { percent: 40, operation: 'Processing families...', individuals: 250, families: 80, sources: 0, media: 0 },
                        { percent: 55, operation: 'Processing source citations...', individuals: 250, families: 150, sources: 45, media: 0 },
                        { percent: 70, operation: 'Processing media metadata...', individuals: 250, families: 150, sources: 95, media: 30 },
                        { percent: 85, operation: 'Building JSON structure...', individuals: 250, families: 150, sources: 95, media: 68 },
                        { percent: 100, operation: 'Finalizing JSON file...', individuals: 250, families: 150, sources: 95, media: 68 }
                    ];
                    break;

                case 'gedcom':
                default:
                    operations = [
                        { percent: 10, operation: 'Preparing data structures...', individuals: 0, families: 0, sources: 0, media: 0 },
                        { percent: 25, operation: 'Exporting individual records...', individuals: 120, families: 0, sources: 0, media: 0 },
                        { percent: 40, operation: 'Exporting families...', individuals: 250, families: 80, sources: 0, media: 0 },
                        { percent: 60, operation: 'Exporting source citations...', individuals: 250, families: 150, sources: 45, media: 0 },
                        { percent: 85, operation: 'Exporting media references...', individuals: 250, families: 150, sources: 95, media: 30 },
                        { percent: 100, operation: 'Finalizing GEDCOM file...', individuals: 250, families: 150, sources: 95, media: 68 }
                    ];
                    break;
            }

            // Check if we have branch filtering
            if ($('#hp-branch-person-id').val()) {
                // Insert a step at the beginning for branch calculation
                operations.unshift({
                    percent: 5,
                    operation: 'Analyzing family relationships...',
                    individuals: 0,
                    families: 0,
                    sources: 0,
                    media: 0
                });

                // Adjust all other percentages
                for (var i = 1; i < operations.length; i++) {
                    operations[i].percent = Math.min(100, Math.round(operations[i].percent * 0.95 + 5));
                }
            }

            // Check if we have date range filtering
            if ($('#hp-date-range-start').val() || $('#hp-date-range-end').val()) {
                // Insert or modify a step for date filtering
                if (operations[0].operation.indexOf('Analyzing') === 0) {
                    operations[0].operation = 'Analyzing relationships and dates...';
                } else {
                    operations.unshift({
                        percent: 5,
                        operation: 'Filtering events by date range...',
                        individuals: 0,
                        families: 0,
                        sources: 0,
                        media: 0
                    });

                    // Adjust all other percentages
                    for (var i = 1; i < operations.length; i++) {
                        operations[i].percent = Math.min(100, Math.round(operations[i].percent * 0.95 + 5));
                    }
                }
            }

            var currentStep = 0;
            var intervalId = setInterval(function () {
                if (currentStep < operations.length) {
                    var op = operations[currentStep];

                    // Update progress bar
                    $('.hp-progress-bar-inner').animate({ width: op.percent + '%' }, 500);

                    // Update operation text
                    $('#hp-export-operation').text(op.operation);

                    // Update stats
                    $('#hp-export-individuals').text(op.individuals);
                    $('#hp-export-families').text(op.families);
                    $('#hp-export-sources').text(op.sources);
                    $('#hp-export-media').text(op.media);

                    currentStep++;
                } else {
                    // We're done
                    clearInterval(intervalId);
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            }, 1000);
        },

        /**
         * Initialize the import tab functionality
         */
        initImportTab: function () {
            // This depends on which step we're on
            var urlParams = new URLSearchParams(window.location.search);
            var step = parseInt(urlParams.get('step') || '1', 10);

            switch (step) {
                case 1:
                    this.initImportStep1();
                    break;
                case 2:
                    this.initImportStep2();
                    break;
                case 3:
                    this.initImportStep3();
                    break;
                case 4:
                    this.initImportStep4();
                    break;
            }
        },

        /**
         * Initialize settings tab functionality
         */        initSettingsTab: function () {
            // Settings form submit handler
            $('#hp-importexport-settings-form').on('submit', this.handleSettingsSave);

            // Tab navigation within settings
            $('.hp-settings-tab').on('click', function () {
                var target = $(this).data('target');

                // Update active tab
                $('.hp-settings-tab').removeClass('active');
                $(this).addClass('active');

                // Show target content, hide others
                $('.hp-settings-content').hide();
                $('#' + target + '-settings').show();
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
        },

        /**
         * Initialize import step 1 (file upload)
         */
        initImportStep1: function () {
            // Tree selection change
            $('#hp-gedcom-tree').on('change', function () {
                if ($(this).val() === 'new') {
                    $('#hp-new-tree-name').show();
                } else {
                    $('#hp-new-tree-name').hide();
                }
            }).trigger('change');

            // Drag and drop handling
            var $dropZone = $('#hp-drop-zone');

            $dropZone.on('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            $dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });

            $dropZone.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $('#hp-gedcom-file')[0].files = files;
                    var fileName = files[0].name;
                    $(this).find('.drag-instructions').text(fileName);
                }
            });

            // Form submit
            $('#hp-gedcom-upload-form').on('submit', this.handleGedcomUpload);
        },

        /**
         * Handle GEDCOM file upload
         * 
         * @param {Event} e The submit event
         */
        handleGedcomUpload: function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitButton = $form.find('#hp-validate-gedcom');
            var formData = new FormData(this);

            // Add action
            formData.append('action', 'hp_upload_gedcom');

            // Validate form
            var $fileInput = $form.find('#hp-gedcom-file');
            if ($fileInput[0].files.length === 0) {
                HeritagePress_ImportExport.showMessage('error', hp_i18n.no_file);
                return;
            }

            var $treeSelect = $form.find('#hp-gedcom-tree');
            if ($treeSelect.val() === 'new') {
                var $newTreeName = $form.find('#new_tree_name');
                if ($newTreeName.val().trim() === '') {
                    HeritagePress_ImportExport.showMessage('error', hp_i18n.tree_name_required);
                    $newTreeName.focus();
                    return;
                }
            }

            // Disable button and show progress
            $submitButton.prop('disabled', true);
            $('#hp-upload-progress').show();

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function () {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function (e) {
                        if (e.lengthComputable) {
                            var percent = Math.round((e.loaded / e.total) * 100);
                            $('.hp-progress-bar-inner').css('width', percent + '%');
                            $('.hp-progress-percentage').text(percent + '%');
                        }
                    });
                    return xhr;
                },
                success: function (response) {
                    if (response.success) {
                        // Redirect to step 2
                        window.location.href = hp_admin_url + 'admin.php?page=heritagepress-importexport&tab=import&step=2&file=' + response.data.file_key;
                    } else {
                        // Show error and reset form
                        $submitButton.prop('disabled', false);
                        $('#hp-upload-progress').hide();
                        HeritagePress_ImportExport.showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    $submitButton.prop('disabled', false);
                    $('#hp-upload-progress').hide();
                    HeritagePress_ImportExport.showMessage('error', hp_i18n.ajax_error);
                }
            });
        },

        /**
         * Initialize import step 2 (validation)
         */
        initImportStep2: function () {
            // No special initialization needed for step 2
        },

        /**
         * Initialize import step 3 (processing)
         */
        initImportStep3: function () {
            // Start checking progress immediately
            this.startProgressCheck();
        },

        /**
         * Start checking import progress
         */
        startProgressCheck: function () {
            var urlParams = new URLSearchParams(window.location.search);
            var fileKey = urlParams.get('file');

            if (!fileKey) return;

            // Store reference to this for use in callbacks
            var self = this;

            // Check progress initially and then set interval
            self.checkImportProgress(fileKey);

            // Set interval for progress checks
            self.progressInterval = setInterval(function () {
                self.checkImportProgress(fileKey);
            }, 2000);
        },

        /**
         * Check import progress
         * 
         * @param {string} fileKey The file key
         */
        checkImportProgress: function (fileKey) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_import_progress',
                    nonce: $('#hp-gedcom-nonce').val(),
                    file_key: fileKey
                },
                success: function (response) {
                    if (response.success) {
                        // Update progress display
                        var percent = response.data.percent;
                        $('#hp-progress-percentage').text(percent + '%');
                        $('.hp-progress-bar-inner').css('width', percent + '%');

                        $('#hp-current-operation').text(response.data.operation);
                        $('#hp-current-detail').text(response.data.detail);

                        // Update stats
                        $('#hp-stat-processed').text(response.data.stats.processed);
                        $('#hp-stat-individuals').text(response.data.stats.individuals);
                        $('#hp-stat-families').text(response.data.stats.families);
                        $('#hp-stat-sources').text(response.data.stats.sources);
                        $('#hp-stat-media').text(response.data.stats.media);
                        $('#hp-stat-notes').text(response.data.stats.notes);

                        // If complete, go to results page
                        if (response.data.completed) {
                            clearInterval(HeritagePress_ImportExport.progressInterval);
                            setTimeout(function () {
                                window.location.href = hp_admin_url + 'admin.php?page=heritagepress-importexport&tab=import&step=4&file=' + fileKey;
                            }, 1000);
                        }
                    }
                },
                error: function () {
                    $('#hp-current-detail').text(hp_i18n.checking_progress_error);
                }
            });
        },

        /**
         * Initialize import step 4 (results)
         */
        initImportStep4: function () {
            // No special initialization needed for step 4
        },

        /**
         * Show a message to the user
         * 
         * @param {string} type The message type (success, error, warning)
         * @param {string} message The message text
         */
        showMessage: function (type, message) {
            var $container = $('.hp-messages');

            // Create container if it doesn't exist
            if ($container.length === 0) {
                $container = $('<div class="hp-messages"></div>');
                $('.heritagepress-import-export h1').after($container);
            }

            // Create message element
            var $message = $('<div class="hp-notice hp-notice-' + type + '"><p>' + message + '</p></div>');

            // Add to container
            $container.html($message);

            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function () {
                    $message.fadeOut(500, function () {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        /**
         * Handle settings save
         * 
         * @param {Event} e The submit event
         */        handleSettingsSave: function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var formData = new FormData(this);

            // Add action
            formData.append('action', 'hp_save_import_export_settings');
            formData.append('hp_settings_nonce', $('#hp_settings_nonce').val());

            // Disable button
            $submitButton.prop('disabled', true);
            $submitButton.val(hp_i18n.saving || 'Saving...');

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $submitButton.prop('disabled', false);
                    $submitButton.val(hp_i18n.save_settings || 'Save Settings');

                    if (response.success) {
                        HeritagePress_ImportExport.showMessage('success', response.data.message);
                    } else {
                        HeritagePress_ImportExport.showMessage('error', response.data.message || hp_i18n.save_failed);
                    }
                },
                error: function () {
                    $submitButton.prop('disabled', false);
                    $submitButton.val(hp_i18n.save_settings || 'Save Settings');
                    HeritagePress_ImportExport.showMessage('error', hp_i18n.ajax_error || 'An error occurred during the request. Please try again.');
                }
            });
        },

        /**
         * Initialize date validation functionality
         */
        initDateValidation: function () {
            // Add date validation to date input fields
            $(document).on('blur', '.hp-date-input', this.validateDateField);

            // Add date conversion button functionality
            $(document).on('click', '.hp-convert-date', this.convertDate);
        },

        /**
         * Validate a date field using DateConverter
         * 
         * @param {Event} e The blur event
         */
        validateDateField: function (e) {
            var $field = $(this);
            var dateString = $field.val().trim();

            if (!dateString) {
                $field.removeClass('hp-date-valid hp-date-invalid');
                return;
            }

            // Remove existing validation indicators
            $field.removeClass('hp-date-valid hp-date-invalid');
            $field.next('.hp-date-validation').remove();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_validate_date',
                    date_string: dateString,
                    nonce: $('#hp_admin_nonce').val()
                },
                success: function (response) {
                    if (response.success) {
                        $field.addClass('hp-date-valid');

                        var validationInfo = '<div class="hp-date-validation hp-date-success">';
                        validationInfo += '<strong>Parsed:</strong> ' + (response.data.formatted || 'N/A') + '<br>';
                        validationInfo += '<strong>Calendar:</strong> ' + response.data.calendar + '<br>';
                        if (response.data.modifier) {
                            validationInfo += '<strong>Modifier:</strong> ' + response.data.modifier + '<br>';
                        }
                        if (response.data.is_range) {
                            validationInfo += '<strong>Range:</strong> Yes<br>';
                        }
                        validationInfo += '</div>';

                        $field.after(validationInfo);
                    } else {
                        $field.addClass('hp-date-invalid');
                        $field.after('<div class="hp-date-validation hp-date-error">Invalid date format</div>');
                    }
                },
                error: function () {
                    $field.addClass('hp-date-invalid');
                    $field.after('<div class="hp-date-validation hp-date-error">Validation failed</div>');
                }
            });
        },

        /**
         * Convert date to different formats
         * 
         * @param {Event} e The click event
         */
        convertDate: function (e) {
            e.preventDefault();

            var $button = $(this);
            var $field = $button.prev('.hp-date-input');
            var dateString = $field.val().trim();

            if (!dateString) {
                alert('Please enter a date first');
                return;
            }

            $button.prop('disabled', true).text('Converting...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hp_convert_date',
                    date_string: dateString,
                    target_format: 'standard',
                    nonce: $('#hp_admin_nonce').val()
                },
                success: function (response) {
                    if (response.success) {
                        var conversions = response.data;
                        var output = '<div class="hp-date-conversions">';
                        output += '<h4>Date Conversions:</h4>';
                        output += '<p><strong>Original:</strong> ' + conversions.original + '</p>';
                        output += '<p><strong>Standard:</strong> ' + (conversions.standard || 'N/A') + '</p>';
                        output += '<p><strong>Calendar:</strong> ' + conversions.calendar + '</p>';
                        if (conversions.julian_day) {
                            output += '<p><strong>Julian Day:</strong> ' + conversions.julian_day + '</p>';
                        }
                        output += '</div>';

                        $field.after(output);
                    } else {
                        alert('Date conversion failed: ' + response.data.message);
                    }
                },
                error: function () {
                    alert('Date conversion request failed');
                },
                complete: function () {
                    $button.prop('disabled', false).text('Convert Date');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        // Check if we have required objects
        if (typeof ajaxurl === 'undefined') {
            console.error('HeritagePress: ajaxurl not defined');
            return;
        }

        // Initialize
        HeritagePress_ImportExport.init();
    });

})(jQuery);
