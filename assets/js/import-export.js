/**
 * HeritagePress Import/Export Main JavaScript
 * 
 * This file serves as the main entry point for import/export functionality
 * and coordinates between various modules.
 */

(function ($) {
    'use strict';

    // Global HeritagePress object
    window.HeritagePress = window.HeritagePress || {};
    /**
   * Main HeritagePress Import/Export handler
   */
    window.HeritagePress.ImportExport = {        /**
         * Initialize the import/export functionality
         */
        init: function () {
            console.log('HeritagePress ImportExport init called');

            // Initialize based on current tab
            this.initCurrentTab();

            // Initialize common functionality
            this.initCommon();
        },        /**
         * Initialize functionality based on current tab
         */        initCurrentTab: function () {
            var urlParams = new URLSearchParams(window.location.search);
            var tab = urlParams.get('tab') || 'import';

            console.log('Current tab detected:', tab);
            console.log('URL search params:', window.location.search);

            switch (tab) {
                case 'tables':
                    console.log('Initializing tables tab');
                    this.initTables();
                    break;
                case 'import':
                    console.log('Initializing import tab');
                    this.initImport();
                    break;
                case 'export':
                    console.log('Initializing export tab');
                    this.initExport();
                    break;
                case 'settings':
                    console.log('Initializing settings tab');
                    this.initSettings();
                    break;
                case 'logs':
                    console.log('Initializing logs tab');
                    this.initLogs();
                    break;
                default:
                    console.log('Unknown tab, defaulting to import');
                    this.initImport();
                    break;
            }
        },/**
         * Initialize tables functionality
         */
        initTables: function () {
            console.log('Initializing tables functionality');

            // Bulk table management
            this.initBulkTableActions();

            // Individual table management is handled in the template's inline JavaScript
        },

        /**
         * Initialize bulk table actions
         */
        initBulkTableActions: function () {
            // Rebuild tables
            $('#hp-rebuild-tables').on('click', function () {
                if (confirm(hp_i18n.confirm_rebuild_tables || 'Are you sure you want to rebuild all tables?')) {
                    HeritagePress.ImportExport.performBulkTableAction('hp_rebuild_tables', 'Rebuilding tables...');
                }
            });

            // Optimize tables
            $('#hp-optimize-tables').on('click', function () {
                if (confirm(hp_i18n.confirm_optimize_tables || 'Are you sure you want to optimize all tables?')) {
                    HeritagePress.ImportExport.performBulkTableAction('hp_optimize_tables', 'Optimizing tables...');
                }
            });

            // Clear all tables
            $('#hp-clear-all-tables').on('click', function () {
                if (confirm(hp_i18n.confirm_clear_all_tables || 'Are you sure you want to clear all table data?')) {
                    HeritagePress.ImportExport.performBulkTableAction('hp_clear_all_tables', 'Clearing table data...');
                }
            });

            // Delete all tables
            $('#hp-delete-all-tables').on('click', function () {
                if (confirm(hp_i18n.confirm_delete_all_tables || 'Are you sure you want to delete all tables? This cannot be undone!')) {
                    HeritagePress.ImportExport.performBulkTableAction('hp_delete_all_tables', 'Deleting tables...');
                }
            });
        },

        /**
         * Perform bulk table action
         */
        performBulkTableAction: function (action, loadingText) {
            var button = $('#' + action.replace('hp_', 'hp-'));
            var originalText = button.html();

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> ' + loadingText);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: $('#hp_table_nonce').val() || wp.ajax.settings.nonce
                },
                success: function (response) {
                    button.prop('disabled', false).html(originalText);

                    if (response.success) {
                        HeritagePress.ImportExport.showMessage('success', response.data.message);
                        // Refresh page after successful operation
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    } else {
                        HeritagePress.ImportExport.showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    button.prop('disabled', false).html(originalText);
                    HeritagePress.ImportExport.showMessage('error', 'An error occurred while performing the operation.');
                }
            });
        },

        /**
         * Initialize common functionality
         */
        initCommon: function () {
            // Tab switching
            $('.nav-tab').on('click', function (e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url) {
                    window.location.href = url;
                }
            });            // Form validation helpers
            this.initFormValidation();
        },

        /**
         * Initialize import functionality
         */
        initImport: function () {
            console.log('Initializing import functionality');

            // File upload handling
            this.initFileUpload();

            // Tree selection
            this.initTreeSelection();

            // Drag and drop
            this.initDragAndDrop();

            // Progress monitoring
            this.initProgressMonitoring();
        },

        /**
         * Initialize export functionality
         */
        initExport: function () {
            // Export form handling
            $('#hp-export-form').on('submit', this.handleExportSubmit.bind(this));
        },

        /**
         * Initialize settings functionality
         */
        initSettings: function () {
            // Settings form handling
            $('#hp-settings-form').on('submit', this.handleSettingsSubmit.bind(this));
        },        /**
         * Initialize logs functionality
         */
        initLogs: function () {
            console.log('=== LOGS TAB INITIALIZATION START ===');
            console.log('Logs tab initialization called');

            // Log filtering and pagination
            this.initLogFilters();

            // Log details toggle functionality
            this.initLogDetailsToggle();

            console.log('=== LOGS TAB INITIALIZATION COMPLETE ===');
        },

        /**
         * Initialize file upload functionality
         */
        initFileUpload: function () {
            var self = this;

            $('#hp-gedcom-upload-form').on('submit', function (e) {
                e.preventDefault();
                self.handleGedcomUpload(e);
            });

            // File input change handler
            $('#hp-gedcom-file').on('change', function () {
                self.handleFileSelection(this);
            });
        },

        /**
         * Handle file selection
         */
        handleFileSelection: function (input) {
            var file = input.files[0];
            if (!file) return;

            var fileName = file.name;
            var fileSize = file.size;
            var maxSize = 50 * 1024 * 1024; // 50MB

            // Update UI with filename
            $('#hp-drop-zone .drag-instructions').text(fileName);

            // Validate file size
            if (fileSize > maxSize) {
                alert(hp_vars.hp_i18n.file_too_large || 'File is too large. Maximum size is 50MB.');
                $(input).val('');
                $('#hp-drop-zone .drag-instructions').text(hp_vars.hp_i18n.drag_drop_text || 'Drag and drop your GEDCOM file here');
                return;
            }

            // Validate file extension
            var extension = fileName.split('.').pop().toLowerCase();
            if (!['ged', 'gedcom'].includes(extension)) {
                alert(hp_vars.hp_i18n.invalid_file_type || 'Invalid file type. Only .ged and .gedcom files are allowed.');
                $(input).val('');
                $('#hp-drop-zone .drag-instructions').text(hp_vars.hp_i18n.drag_drop_text || 'Drag and drop your GEDCOM file here');
                return;
            }
        },

        /**
         * Initialize tree selection functionality
         */
        initTreeSelection: function () {
            $('#hp-gedcom-tree').on('change', function () {
                if ($(this).val() === 'new') {
                    $('#hp-new-tree-name').slideDown();
                    $('#new_tree_name').focus();
                } else {
                    $('#hp-new-tree-name').slideUp();
                }
            }).trigger('change');
        },

        /**
         * Initialize drag and drop functionality
         */
        initDragAndDrop: function () {
            var self = this;
            var $dropZone = $('#hp-drop-zone');

            $dropZone.on('dragover dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            $dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$(this).has(e.relatedTarget).length) {
                    $(this).removeClass('drag-over');
                }
            });

            $dropZone.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    var fileInput = $('#hp-gedcom-file')[0];
                    fileInput.files = files;
                    self.handleFileSelection(fileInput);
                }
            });

            // Click to select
            $dropZone.on('click', function () {
                $('#hp-gedcom-file').click();
            });
        },        /**
         * Handle GEDCOM file upload
         */
        handleGedcomUpload: function (e) {
            var $form = $(e.target);
            var $submitButton = $form.find('#hp-validate-gedcom');
            var formData = new FormData(e.target);

            // Add action
            formData.append('action', 'hp_upload_gedcom');

            // Ensure nonce is included (get from form or hp_vars)
            var nonceValue = $form.find('input[name="hp_gedcom_nonce"]').val();
            if (nonceValue) {
                formData.set('hp_gedcom_nonce', nonceValue);
            } else if (hp_vars.nonce) {
                formData.set('hp_gedcom_nonce', hp_vars.nonce);
            }

            // Debug: Log what we're sending
            console.log('Form data being sent:');
            for (var pair of formData.entries()) {
                console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
            }

            // Validate form
            if (!this.validateUploadForm($form)) {
                return;
            }

            // Disable button and show progress
            $submitButton.prop('disabled', true);
            $('#hp-upload-progress').show();

            // Send AJAX request
            $.ajax({
                url: hp_vars.ajaxurl,
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
                }, success: function (response) {
                    console.log('AJAX Response:', response);
                    if (response.success) {                        // Redirect to step 2
                        var redirectUrl = hp_vars.hp_admin_url + 'admin.php?page=heritagepress-import-export&tab=import&step=2&file=' + encodeURIComponent(response.data.file_key);
                        window.location.href = redirectUrl;
                    } else {
                        console.error('Upload failed:', response.data);
                        alert(response.data.message || hp_vars.hp_i18n.upload_failed || 'Upload failed. Please try again.');
                        $submitButton.prop('disabled', false);
                        $('#hp-upload-progress').hide();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', { xhr: xhr, status: status, error: error });
                    console.error('Response Text:', xhr.responseText);
                    alert(hp_vars.hp_i18n.upload_failed || 'Upload failed. Please try again.');
                    $submitButton.prop('disabled', false);
                    $('#hp-upload-progress').hide();
                }
            });
        },

        /**
         * Validate upload form
         */
        validateUploadForm: function ($form) {
            var fileInput = $form.find('#hp-gedcom-file')[0];

            if (!fileInput.files || fileInput.files.length === 0) {
                alert(hp_vars.hp_i18n.no_file || 'Please select a GEDCOM file to upload.');
                return false;
            }

            var treeSelect = $form.find('#hp-gedcom-tree').val();
            if (treeSelect === 'new') {
                var treeName = $form.find('#new_tree_name').val().trim();
                if (!treeName) {
                    alert(hp_vars.hp_i18n.tree_name_required || 'Please enter a name for the new tree.');
                    $form.find('#new_tree_name').focus();
                    return false;
                }
            }

            return true;
        },

        /**
         * Initialize progress monitoring
         */
        initProgressMonitoring: function () {
            var urlParams = new URLSearchParams(window.location.search);
            var step = urlParams.get('step');

            if (step === '3') {
                this.startProgressPolling();
            }
        },

        /**
         * Start progress polling for import process
         */
        startProgressPolling: function () {
            var self = this;
            var pollInterval = setInterval(function () {
                $.ajax({
                    url: hp_vars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hp_get_import_progress',
                        nonce: hp_vars.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            var progress = response.data;
                            self.updateProgressUI(progress);

                            if (progress.completed) {
                                clearInterval(pollInterval);
                                self.handleImportComplete(progress);
                            }
                        }
                    },
                    error: function () {
                        clearInterval(pollInterval);
                        console.error('Failed to get import progress');
                    }
                });
            }, 2000); // Poll every 2 seconds
        },

        /**
         * Update progress UI
         */
        updateProgressUI: function (progress) {
            if (progress.percentage !== undefined) {
                $('.hp-progress-bar-inner').css('width', progress.percentage + '%');
                $('.hp-progress-percentage').text(progress.percentage + '%');
            }

            if (progress.status) {
                $('.hp-progress-text').text(progress.status);
            }
        },

        /**
         * Handle import completion
         */        handleImportComplete: function (progress) {
            if (progress.success) {
                // Redirect to results page
                var redirectUrl = hp_vars.hp_admin_url + 'admin.php?page=heritagepress-import-export&tab=import&step=4&file=' + encodeURIComponent(progress.file_key);
                window.location.href = redirectUrl;
            } else {
                alert(progress.message || 'Import failed. Please check the logs for details.');
            }
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function () {
            // Add any common form validation here
        },        /**
         * Initialize log filters
         */
        initLogFilters: function () {
            // Log filter form submission
            $('#hp-log-filter-form').on('submit', function () {
                // This form performs a normal non-AJAX submission
                return true;
            });
        },

        /**
         * Initialize log details toggle functionality
         */
        initLogDetailsToggle: function () {
            console.log('Initializing log details toggle');

            // Log details toggle
            $(document).on('click', '.hp-log-details-toggle', function (e) {
                e.preventDefault();
                console.log('Log details toggle clicked');

                var $toggle = $(this);
                var $detailsRow = $toggle.closest('tr').next('.hp-log-details-row');

                if ($detailsRow.length) {
                    $detailsRow.toggle();

                    // Update toggle text
                    if ($detailsRow.is(':visible')) {
                        $toggle.text('Hide Details');
                    } else {
                        $toggle.text('View Details');
                    }
                } else {
                    console.warn('Details row not found for log entry');
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
         * Handle export form submission
         */
        handleExportSubmit: function (e) {
            e.preventDefault();
            // Add export handling here
        },

        /**
         * Handle settings form submission
         */
        handleSettingsSubmit: function (e) {
            e.preventDefault();            // Add settings handling here
        }
    };    // Initialize when document is ready
    $(document).ready(function () {
        // Check if hp_vars is defined
        if (typeof hp_vars === 'undefined') {
            console.error('HeritagePress: hp_vars is not defined. Asset localization may have failed.');
            return;
        }

        console.log('HeritagePress Import/Export JavaScript loaded');
        console.log('hp_vars:', hp_vars);
        console.log('AJAX URL:', hp_vars.ajaxurl);
        console.log('Nonce:', hp_vars.nonce);

        HeritagePress.ImportExport.init();
    });

})(jQuery);