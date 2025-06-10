/**
 * HeritagePress GEDCOM Import Interface
 */
(function ($) {
    'use strict';

    const Import = {
        currentStage: 'upload',
        file: null,

        init() {
            this.bindEvents();
            this.setupDragDrop();
        },

        bindEvents() {
            $('#hp-file-input').on('change', (e) => this.handleFileSelect(e));
            $('#hp-import-form').on('submit', (e) => this.handleSubmit(e));
        },

        setupDragDrop() {
            const dropZone = $('.hp-drag-drop-zone');

            dropZone.on('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.addClass('dragover');
            });

            dropZone.on('dragleave', () => {
                dropZone.removeClass('dragover');
            });

            dropZone.on('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('dragover');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $('#hp-file-input')[0].files = files;
                    this.handleFileSelect({ target: { files } });
                }
            });
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file type
            if (!/\.(ged|gdz)$/i.test(file.name)) {
                this.showError('Invalid file type. Please select a GEDCOM (.ged) or GEDZIP (.gdz) file.');
                return;
            }

            // Validate file size
            if (file.size > HeritagePress.maxUploadSize) {
                this.showError(`File too large. Maximum size is ${Math.round(HeritagePress.maxUploadSize / 1024 / 1024)}MB.`);
                return;
            }

            this.file = file;
            this.validateFile();
        },

        validateFile() {
            const formData = new FormData();
            formData.append('action', 'heritagepress_validate_gedcom');
            formData.append('nonce', HeritagePress.nonce);
            formData.append('gedcom_file', this.file);

            this.showStatus('validating');

            $.ajax({
                url: HeritagePress.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showValidationResults(response.data);
                        this.goToStage('validate');
                    } else {
                        this.showError(response.data);
                    }
                },
                error: () => {
                    this.showError('Server error during validation');
                }
            });
        },

        handleImport() {
            const formData = new FormData();
            formData.append('action', 'heritagepress_import_gedcom');
            formData.append('nonce', HeritagePress.nonce);
            formData.append('gedcom_file', this.file);
            formData.append('overwrite_existing', $('#overwrite_existing').prop('checked'));
            formData.append('skip_media', $('#skip_media').prop('checked'));

            this.showStatus('importing');
            this.goToStage('import');
            this.updateProgress(0);

            $.ajax({
                url: HeritagePress.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.updateProgress(100);
                        this.showStatus('success');
                        this.showCompletionMessage(response.data);
                    } else {
                        this.showError(response.data);
                    }
                },
                error: () => {
                    this.showError('Server error during import');
                }
            });
        },

        showValidationResults(data) {
            const { stats, previewData } = data;
            let html = '<div class="hp-validation-summary">';

            // Show file statistics
            html += '<h3>File Contents</h3>';
            html += '<ul>';
            for (const [key, value] of Object.entries(stats)) {
                html += `<li><strong>${key}:</strong> ${value}</li>`;
            }
            html += '</ul>';

            // Show preview data if available
            if (previewData && previewData.length) {
                html += '<h3>Data Preview</h3>';
                html += '<div class="hp-preview-grid">';
                // Add preview grid content
                html += '</div>';
            }

            html += '<div class="hp-validation-actions">';
            html += '<button type="button" class="button button-primary" onclick="Import.handleImport()">Start Import</button>';
            html += '<button type="button" class="button" onclick="Import.goToStage(\'upload\')">Cancel</button>';
            html += '</div>';

            html += '</div>';

            $('.hp-validation-results').html(html);
        },

        showStatus(type) {
            const message = HeritagePress.strings[type] || type;
            $('.hp-progress-status').text(message);
        },

        showError(message) {
            const errorHtml = `<div class="notice notice-error"><p>${message}</p></div>`;
            $('.hp-validation-results, .hp-import-log').prepend(errorHtml);
        },

        updateProgress(percent) {
            $('.hp-progress-value').css('width', `${percent}%`);
        },

        goToStage(stage) {
            $('.hp-import-stage').addClass('hidden');
            $(`.hp-import-stage-${stage}`).removeClass('hidden');
            this.currentStage = stage;
        },

        showCompletionMessage(data) {
            const html = `
                <div class="notice notice-success">
                    <p>${data.message}</p>
                    <p><a href="admin.php?page=heritagepress&tree=${data.tree_id}" class="button">View Imported Tree</a></p>
                </div>
            `;
            $('.hp-import-log').html(html).removeClass('hidden');
        }
    };

    // Initialize on document ready
    $(document).ready(() => Import.init());

})(jQuery);
