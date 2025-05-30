/* Media Import Progress Handler */
jQuery(document).ready(function($) {
    let importProgress = {
        importId: null,
        updateInterval: null,
        startTime: null,
        isPaused: false,

        init: function(importId) {
            this.importId = importId;
            this.startTime = new Date();
            this.bindEvents();
            this.startProgressUpdates();
        },

        bindEvents: function() {
            $('.pause-import').on('click', () => this.pauseImport());
            $('.resume-import').on('click', () => this.resumeImport());
            $('.cancel-import').on('click', () => this.cancelImport());
        },

        startProgressUpdates: function() {
            this.updateProgress();
            this.updateInterval = setInterval(() => this.updateProgress(), 2000);
        },

        updateProgress: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',                data: {
                    action: 'heritage_press_media_import_progress',
                    import_id: this.importId,
                    nonce: heritage_press_import.nonce
                },
                success: (response) => this.handleProgressUpdate(response)
            });
        },

        handleProgressUpdate: function(data) {
            if (!data.success) {
                this.showError('Failed to get progress update');
                return;
            }

            const progress = data.data;

            // Update progress bar
            $('.progress-fill').css('width', progress.percent_complete + '%');
            $('.progress-text').text(progress.percent_complete + '%');
            
            // Update stats
            $('.files-processed').text(progress.processed_files);
            $('.files-total').text(progress.total_files);

            // Show import details
            $('.progress-details').removeClass('hidden');
            $('.import-start-time').text(this.startTime.toLocaleString());
            $('.import-status').text(this.getStatusText(progress.status));

            // Calculate and show processing rate
            const elapsedSeconds = (new Date() - this.startTime) / 1000;
            const rate = progress.processed_files / elapsedSeconds;
            $('.processing-rate').text(rate.toFixed(2));

            // Calculate and show estimated time remaining
            const remainingFiles = progress.total_files - progress.processed_files;
            const estimatedSeconds = remainingFiles / rate;
            $('.time-remaining').text(this.formatTimeRemaining(estimatedSeconds));

            // Handle failed files
            if (progress.failed_files.length > 0) {
                this.showFailedFiles(progress.failed_files);
            }

            // Handle completion
            if (progress.status === 'completed') {
                this.handleCompletion();
            }
        },

        showFailedFiles: function(failedFiles) {
            const $tbody = $('.error-list tbody').empty();
            
            failedFiles.forEach(file => {
                $tbody.append(`
                    <tr>
                        <td>${file.file}</td>
                        <td>${file.error}</td>
                        <td>
                            <button type="button" class="button retry-file" data-file="${file.file}">
                                Retry
                            </button>
                        </td>
                    </tr>
                `);
            });

            $('.error-list').removeClass('hidden');
        },

        getStatusText: function(status) {
            const statusMap = {
                'not_started': 'Not Started',
                'in_progress': 'In Progress',
                'completed': 'Completed',
                'has_errors': 'Has Errors',
                'paused': 'Paused',
                'cancelled': 'Cancelled'
            };
            return statusMap[status] || status;
        },

        formatTimeRemaining: function(seconds) {
            if (!isFinite(seconds)) return 'Calculating...';
            
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            
            const parts = [];
            if (hours > 0) parts.push(`${hours}h`);
            if (minutes > 0) parts.push(`${minutes}m`);
            parts.push(`${remainingSeconds}s`);
            
            return parts.join(' ');
        },

        pauseImport: function() {
            this.isPaused = true;
            clearInterval(this.updateInterval);
            $('.pause-import').addClass('hidden');
            $('.resume-import').removeClass('hidden');

            $.ajax({
                url: ajaxurl,                type: 'POST',
                data: {
                    action: 'heritage_press_pause_media_import',
                    import_id: this.importId,
                    nonce: heritage_press_import.nonce
                }
            });
        },

        resumeImport: function() {
            this.isPaused = false;
            this.startProgressUpdates();
            $('.resume-import').addClass('hidden');
            $('.pause-import').removeClass('hidden');

            $.ajax({                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'heritage_press_resume_media_import',
                    import_id: this.importId,
                    nonce: heritage_press_import.nonce
                }
            });
        },

        cancelImport: function() {
            if (!confirm('Are you sure you want to cancel this import? This cannot be undone.')) {
                return;
            }

            clearInterval(this.updateInterval);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',                data: {
                    action: 'heritage_press_cancel_media_import',
                    import_id: this.importId,
                    nonce: heritage_press_import.nonce
                },
                success: () => window.location.reload()
            });
        },

        handleCompletion: function() {
            clearInterval(this.updateInterval);
            
            // Show completion message
            const $message = $('<div>')
                .addClass('notice notice-success')
                .html(`
                    <p>
                        <strong>Import Complete!</strong>
                        Successfully imported ${$('.files-processed').text()} files.
                        ${$('.error-list').is(':visible') ? 'Some files had errors.' : ''}
                    </p>
                `);
            
            $('.heritage-press-progress-container').prepend($message);
            
            // Hide action buttons
            $('.progress-actions button').addClass('hidden');
        },

        showError: function(message) {
            const $error = $('<div>')
                .addClass('notice notice-error')
                .html(`<p>${message}</p>`);
            
            $('.heritage-press-progress-container').prepend($error);
        }
    };    // Initialize if we have an import ID
    if (typeof heritage_press_import !== 'undefined' && heritage_press_import.import_id) {
        importProgress.init(heritage_press_import.import_id);
    }
});
