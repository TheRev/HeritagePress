/**
 * HeritagePress Import JavaScript Module
 *
 * Handles import-specific functionality
 */

(function ($) {
    'use strict';

    var HeritagePress_Import = {
        progressInterval: null,

        /**
         * Initialize import functionality
         */
        init: function () {
            this.bindEvents();
            this.initCurrentStep();
        },

        /**
         * Bind import-specific events
         */
        bindEvents: function () {
            // Import form submission
            $('#hp-gedcom-upload-form').on('submit', this.handleGedcomUpload.bind(this));

            // Tree selection change
            $('#hp-gedcom-tree').on('change', this.handleTreeSelection);

            // Drag and drop
            this.initDragAndDrop();
        },

        /**
         * Initialize current import step
         */
        initCurrentStep: function () {
            var urlParams = new URLSearchParams(window.location.search);
            var step = urlParams.get('step') || '1';

            switch (step) {
                case '1':
                    this.initStep1();
                    break;
                case '2':
                    this.initStep2();
                    break;
                case '3':
                    this.initStep3();
                    break;
                case '4':
                    this.initStep4();
                    break;
            }
        },

        /**
         * Initialize step 1 (file upload)
         */
        initStep1: function () {
            // File selection validation
            $('#hp-gedcom-file').on('change', function () {
                var file = this.files[0];
                if (file) {
                    var fileName = file.name;
                    var fileSize = file.size;
                    var maxSize = 50 * 1024 * 1024; // 50MB

                    // Update drop zone text with filename
                    $('#hp-drop-zone .drag-instructions').text(fileName);                    // Validate file size
                    if (fileSize > maxSize) {
                        alert(hp_vars.hp_i18n.file_too_large || 'File is too large. Maximum size is 50MB.');
                        $(this).val('');
                        $('#hp-drop-zone .drag-instructions').text(hp_vars.hp_i18n.drag_drop_text || 'Drag and drop your GEDCOM file here, or click to select');
                        return;
                    }

                    // Validate file extension
                    var extension = fileName.split('.').pop().toLowerCase();
                    if (!['ged', 'gedcom'].includes(extension)) {
                        alert(hp_vars.hp_i18n.invalid_file_type || 'Invalid file type. Only .ged and .gedcom files are allowed.');
                        $(this).val('');
                        $('#hp-drop-zone .drag-instructions').text(hp_vars.hp_i18n.drag_drop_text || 'Drag and drop your GEDCOM file here, or click to select');
                        return;
                    }
                }
            });
        },

        /**
         * Initialize step 2 (validation)
         */
        initStep2: function () {
            // Validation results are already displayed by PHP
            // Add any interactive elements here if needed
        },

        /**
         * Initialize step 3 (processing)
         */
        initStep3: function () {
            this.startProgressCheck();
        },

        /**
         * Initialize step 4 (results)
         */
        initStep4: function () {
            // Results are displayed by PHP
            // Add any cleanup or final actions here
        },

        /**
         * Handle tree selection change
         */
        handleTreeSelection: function () {
            if ($(this).val() === 'new') {
                $('#hp-new-tree-name').slideDown();
                $('#new_tree_name').focus();
            } else {
                $('#hp-new-tree-name').slideUp();
            }
        },

        /**
         * Initialize drag and drop functionality
         */
        initDragAndDrop: function () {
            var $dropZone = $('#hp-drop-zone');

            $dropZone.on('dragover dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            $dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                // Only remove class if we're actually leaving the drop zone
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
                    $(fileInput).trigger('change');
                }
            });

            // Click to select
            $dropZone.on('click', function () {
                $('#hp-gedcom-file').click();
            });
        },

        /**
         * Handle GEDCOM file upload
         */
        handleGedcomUpload: function (e) {
            e.preventDefault();

            var $form = $(e.target);
            var $submitButton = $form.find('#hp-validate-gedcom');
            var formData = new FormData(e.target);

            // Add action
            formData.append('action', 'hp_upload_gedcom');

            // Validate form
            if (!this.validateUploadForm($form)) {
                return;
            }

            // Disable button and show progress
            $submitButton.prop('disabled', true);
            $('#hp-upload-progress').show();            // Send AJAX request
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
                    if (response.success) {
                        // Redirect to step 2
                        var redirectUrl = hp_vars.hp_admin_url + 'admin.php?page=heritagepress-importexport&tab=import&step=2&file=' + response.data.file_key;
                        window.location.href = redirectUrl;
                    } else {
                        HeritagePress_Import.handleUploadError(response.data.message);
                        $submitButton.prop('disabled', false);
                        $('#hp-upload-progress').hide();
                    }
                },
                error: function () {
                    HeritagePress_Import.handleUploadError(hp_vars.hp_i18n.ajax_error);
                    $submitButton.prop('disabled', false);
                    $('#hp-upload-progress').hide();
                }
            });
        },        /**
         * Validate upload form
         */
        validateUploadForm: function ($form) {
            var $fileInput = $form.find('#hp-gedcom-file');
            if ($fileInput[0].files.length === 0) {
                this.showMessage('error', hp_vars.hp_i18n.no_file || 'Please select a GEDCOM file to upload.');
                return false;
            }

            var $treeSelect = $form.find('#hp-gedcom-tree');
            if ($treeSelect.val() === 'new') {
                var $newTreeName = $form.find('#new_tree_name');
                if ($newTreeName.val().trim() === '') {
                    this.showMessage('error', hp_vars.hp_i18n.tree_name_required || 'Please enter a name for the new tree.');
                    $newTreeName.focus();
                    return false;
                }
            }

            return true;
        },

        /**
         * Start checking import progress
         */
        startProgressCheck: function () {
            var urlParams = new URLSearchParams(window.location.search);
            var fileKey = urlParams.get('file');

            if (!fileKey) {
                console.error('No file key found for progress tracking');
                return;
            }

            // Check progress initially and then set interval
            this.checkImportProgress(fileKey);

            // Set interval for progress checks
            this.progressInterval = setInterval(() => {
                this.checkImportProgress(fileKey);
            }, 2000);
        },

        /**
         * Check import progress
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
                success: (response) => {
                    if (response.success) {
                        this.updateProgressDisplay(response.data);

                        // If complete, redirect to results page
                        if (response.data.completed) {
                            clearInterval(this.progressInterval);
                            setTimeout(() => {
                                var redirectUrl = hp_admin_url + 'admin.php?page=heritagepress-importexport&tab=import&step=4&file=' + fileKey;
                                window.location.href = redirectUrl;
                            }, 1000);
                        }
                    } else {
                        this.handleProgressError(response.data.message);
                    }
                },
                error: () => {
                    this.handleProgressError(hp_i18n.checking_progress_error || 'Error checking progress');
                }
            });
        },

        /**
         * Update progress display
         */
        updateProgressDisplay: function (progressData) {
            // Update progress bar
            var percent = progressData.percent || 0;
            $('#hp-progress-percentage').text(percent + '%');
            $('.hp-progress-bar-inner').css('width', percent + '%');

            // Update operation text
            $('#hp-current-operation').text(progressData.operation || '');
            $('#hp-current-detail').text(progressData.detail || '');

            // Update statistics
            if (progressData.stats) {
                $('#hp-stat-processed').text(progressData.stats.processed || 0);
                $('#hp-stat-individuals').text(progressData.stats.individuals || 0);
                $('#hp-stat-families').text(progressData.stats.families || 0);
                $('#hp-stat-sources').text(progressData.stats.sources || 0);
                $('#hp-stat-media').text(progressData.stats.media || 0);
                $('#hp-stat-notes').text(progressData.stats.notes || 0);
            }
        },

        /**
         * Handle upload error
         */
        handleUploadError: function (message) {
            this.showMessage('error', message || hp_i18n.upload_failed);
        },

        /**
         * Handle progress error
         */
        handleProgressError: function (message) {
            $('#hp-current-detail').text(message);
            // Don't stop checking - the import might still be running
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
        },

        /**
         * Cleanup function
         */
        cleanup: function () {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        HeritagePress_Import.init();
    });

    // Cleanup on page unload
    $(window).on('beforeunload', () => {
        HeritagePress_Import.cleanup();
    });

    // Make available globally
    window.HeritagePress_Import = HeritagePress_Import;

})(jQuery);
