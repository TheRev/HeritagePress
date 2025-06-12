<?php
/**
 * Debug file selection functionality in Step 1
 * Direct test page to check if file selection UI works properly
 */

// Set up WordPress context if not already loaded
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = [
        __DIR__ . '/../../../../wp-load.php',
        __DIR__ . '/../../../../../wp-load.php',
        __DIR__ . '/../../../../../../wp-load.php'
    ];

    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// Only run if we're in WordPress context
if (!defined('ABSPATH')) {
    die('WordPress not found');
}

// Add admin styles and scripts
wp_enqueue_script('jquery');
?>
<!DOCTYPE html>
<html>

<head>
    <title>File Selection Debug Test</title>
    <style>
        /* Copy styles from step1-upload.php */
        .hp-drag-drop-zone {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            background: #fafafa;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .hp-drag-drop-zone:hover {
            border-color: #0073aa;
            background: #f0f8ff;
        }

        .hp-drag-drop-zone.drag-over {
            border-color: #0073aa;
            background: #e6f3ff;
            border-style: solid;
        }

        .hp-drag-drop-zone.file-selected {
            border-color: #46b450;
            background: #f0fff0;
            border-style: solid;
        }

        .hp-drag-drop-zone.file-selected .drag-instructions {
            color: #46b450;
            font-weight: bold;
        }

        .hp-drag-drop-zone .drag-instructions {
            font-size: 16px;
            margin: 0 0 10px 0;
            color: #666;
        }

        .hp-drag-drop-zone .manual-upload {
            margin: 10px 0;
            color: #999;
            font-style: italic;
        }

        .hp-drag-drop-zone input[type="file"] {
            display: none;
        }

        .hp-drag-drop-zone .button {
            margin: 10px 0;
            padding: 8px 12px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .hp-drag-drop-zone .description {
            margin: 10px 0 0 0;
            color: #999;
            font-size: 14px;
        }

        .debug-output {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .debug-output h3 {
            margin-top: 0;
        }

        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .status.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
    <?php wp_head(); ?>
</head>

<body>
    <div style="max-width: 800px; margin: 20px auto; padding: 20px;">
        <h1>HeritagePress File Selection Debug Test</h1>

        <div class="debug-output">
            <h3>Current Status</h3>
            <div id="status-messages"></div>
        </div>

        <form id="test-form" enctype="multipart/form-data">
            <div class="hp-drag-drop-zone" id="hp-drop-zone">
                <p class="drag-instructions">
                    Drag and drop a GEDCOM file here
                </p>
                <p class="manual-upload">
                    or
                </p>
                <p>
                    <input type="file" name="gedcom_file" id="hp-gedcom-file" accept=".ged,.gedcom">
                    <label for="hp-gedcom-file" class="button">
                        Select File
                    </label>
                </p>
                <p class="description">
                    Maximum file size: 50MB
                </p>
            </div>

            <button type="button" id="test-submit" class="button">Test Form Data</button>
        </form>

        <div class="debug-output">
            <h3>File Information</h3>
            <div id="file-info">No file selected</div>
        </div>

        <div class="debug-output">
            <h3>Event Log</h3>
            <div id="event-log"></div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            console.log('File selection debug test started');

            function logEvent(message, type = 'info') {
                var timestamp = new Date().toLocaleTimeString();
                var logEntry = '<div class="status ' + type + '">[' + timestamp + '] ' + message + '</div>';
                $('#event-log').prepend(logEntry);
                console.log('[FILE DEBUG] ' + message);
            }

            function updateStatus(message, type = 'info') {
                $('#status-messages').html('<div class="status ' + type + '">' + message + '</div>');
            }

            logEvent('Debug test initialized', 'success');
            updateStatus('Ready to test file selection', 'info');

            // File selection change handler
            $('#hp-gedcom-file').on('change', function () {
                logEvent('File input change event triggered');

                var file = this.files[0];
                if (file) {
                    logEvent('File selected: ' + file.name + ' (' + file.size + ' bytes)');

                    var fileName = file.name;
                    var fileSize = file.size;
                    var maxSize = 50 * 1024 * 1024; // 50MB

                    // Update file info display
                    $('#file-info').html(
                        '<strong>File:</strong> ' + fileName + '<br>' +
                        '<strong>Size:</strong> ' + (fileSize / 1024 / 1024).toFixed(2) + ' MB<br>' +
                        '<strong>Type:</strong> ' + file.type + '<br>' +
                        '<strong>Last Modified:</strong> ' + new Date(file.lastModified).toLocaleString()
                    );

                    // Validate file size
                    if (fileSize > maxSize) {
                        logEvent('File too large: ' + (fileSize / 1024 / 1024).toFixed(2) + 'MB > 50MB', 'error');
                        updateStatus('File is too large. Maximum size is 50MB.', 'error');
                        $(this).val('');
                        $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                        $('#hp-drop-zone').removeClass('file-selected');
                        return;
                    }

                    // Validate file extension
                    var extension = fileName.split('.').pop().toLowerCase();
                    if (!['ged', 'gedcom'].includes(extension)) {
                        logEvent('Invalid file type: ' + extension, 'error');
                        updateStatus('Invalid file type. Only .ged and .gedcom files are allowed.', 'error');
                        $(this).val('');
                        $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                        $('#hp-drop-zone').removeClass('file-selected');
                        return;
                    }

                    // Update UI with selected filename
                    $('#hp-drop-zone .drag-instructions').text('Selected: ' + fileName);
                    $('#hp-drop-zone').addClass('file-selected');
                    logEvent('File validation passed, UI updated', 'success');
                    updateStatus('File selected successfully: ' + fileName, 'success');
                } else {
                    logEvent('No file selected (input cleared)');
                    // Reset to default text if no file selected
                    $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                    $('#hp-drop-zone').removeClass('file-selected');
                    $('#file-info').text('No file selected');
                    updateStatus('No file selected', 'info');
                }
            });

            // Drag and drop functionality
            var $dropZone = $('#hp-drop-zone');

            $dropZone.on('dragover dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
                logEvent('Drag over event');
            });

            $dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$(this).has(e.relatedTarget).length) {
                    $(this).removeClass('drag-over');
                    logEvent('Drag leave event');
                }
            });

            $dropZone.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
                logEvent('Drop event triggered');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    logEvent('Files dropped: ' + files.length + ' file(s)');
                    var fileInput = $('#hp-gedcom-file')[0];
                    fileInput.files = files;
                    $(fileInput).trigger('change'); // Trigger change event to update UI
                } else {
                    logEvent('No files in drop event', 'error');
                }
            });

            // Click to select file
            $dropZone.on('click', function () {
                logEvent('Drop zone clicked, opening file dialog');
                $('#hp-gedcom-file').click();
            });

            // Test form data
            $('#test-submit').on('click', function () {
                logEvent('Testing form data collection');

                var formData = new FormData($('#test-form')[0]);
                var fileInput = $('#hp-gedcom-file')[0];

                if (fileInput.files.length > 0) {
                    var file = fileInput.files[0];
                    logEvent('Form data contains file: ' + file.name, 'success');
                    updateStatus('Form ready to submit with file: ' + file.name, 'success');
                } else {
                    logEvent('No file in form data', 'error');
                    updateStatus('No file selected for form submission', 'error');
                }

                // Log all form data entries
                for (var pair of formData.entries()) {
                    logEvent('Form data: ' + pair[0] + ' = ' + (pair[1] instanceof File ? 'File: ' + pair[1].name : pair[1]));
                }
            });

            logEvent('All event handlers attached', 'success');
        });
    </script>

    <?php wp_footer(); ?>
</body>

</html>