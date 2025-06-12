<?php
/**
 * Test file selection UI functionality specifically
 * This mimics the exact implementation from step1-upload.php
 */

// Set up WordPress context
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

if (!defined('ABSPATH')) {
    die('WordPress not found');
}

wp_enqueue_script('jquery');
?>
<!DOCTYPE html>
<html>

<head>
    <title>File Selection UI Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f1f1f1;
        }

        .test-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        /* Exact styles from step1-upload.php */
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
            padding: 8px 16px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .hp-drag-drop-zone .button:hover {
            background: #005a87;
        }

        .hp-drag-drop-zone .description {
            margin: 10px 0 0 0;
            color: #999;
            font-size: 14px;
        }

        .status-panel {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }

        .status-item {
            margin: 5px 0;
            padding: 8px 12px;
            border-radius: 4px;
        }

        .status-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .status-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .file-details {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
        }

        .file-details h4 {
            margin-top: 0;
            color: #333;
        }

        .file-details table {
            width: 100%;
            border-collapse: collapse;
        }

        .file-details td {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
        }

        .file-details td:first-child {
            font-weight: bold;
            width: 120px;
        }

        .test-button {
            background: #46b450;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 10px 10px 0;
        }

        .test-button:hover {
            background: #3ba54a;
        }

        .test-button.secondary {
            background: #666;
        }

        .test-button.secondary:hover {
            background: #555;
        }
    </style>
    <?php wp_head(); ?>
</head>

<body>
    <div class="test-container">
        <h1>File Selection UI Test</h1>
        <p>This test replicates the exact file selection functionality from step1-upload.php</p>
    </div>

    <div class="test-container">
        <h2>File Selection Area</h2>

        <form id="hp-gedcom-upload-form" enctype="multipart/form-data">
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
        </form>

        <button type="button" id="test-form-data" class="test-button">Test Form Data</button>
        <button type="button" id="clear-selection" class="test-button secondary">Clear Selection</button>
    </div>

    <div class="test-container">
        <h2>Status & Events</h2>
        <div id="status-display" class="status-panel">
            <div class="status-item status-info">Ready to test file selection</div>
        </div>
    </div>

    <div class="test-container" id="file-details-container" style="display: none;">
        <h2>Selected File Details</h2>
        <div id="file-details" class="file-details"></div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            console.log('File selection test initialized');

            function addStatus(message, type = 'info') {
                var timestamp = new Date().toLocaleTimeString();
                var statusHtml = '<div class="status-item status-' + type + '">' +
                    '<strong>[' + timestamp + ']</strong> ' + message + '</div>';
                $('#status-display').prepend(statusHtml);

                // Keep only last 10 status messages
                $('#status-display .status-item').slice(10).remove();

                console.log('[FILE TEST] ' + message);
            }

            function showFileDetails(file) {
                var detailsHtml = '<h4>File Information</h4>' +
                    '<table>' +
                    '<tr><td>Name:</td><td>' + file.name + '</td></tr>' +
                    '<tr><td>Size:</td><td>' + (file.size / 1024 / 1024).toFixed(2) + ' MB (' + file.size + ' bytes)</td></tr>' +
                    '<tr><td>Type:</td><td>' + (file.type || 'Unknown') + '</td></tr>' +
                    '<tr><td>Modified:</td><td>' + new Date(file.lastModified).toLocaleString() + '</td></tr>' +
                    '<tr><td>Extension:</td><td>' + file.name.split('.').pop().toLowerCase() + '</td></tr>' +
                    '</table>';

                $('#file-details').html(detailsHtml);
                $('#file-details-container').show();
            }

            function hideFileDetails() {
                $('#file-details-container').hide();
            }

            addStatus('Test initialized successfully', 'success');

            // EXACT FILE SELECTION HANDLER FROM step1-upload.php
            $('#hp-gedcom-file').on('change', function () {
                addStatus('File input change event triggered');

                var file = this.files[0];
                if (file) {
                    addStatus('File selected: ' + file.name);

                    var fileName = file.name;
                    var fileSize = file.size;
                    var maxSize = 50 * 1024 * 1024; // 50MB

                    // Validate file size
                    if (fileSize > maxSize) {
                        addStatus('File too large: ' + (fileSize / 1024 / 1024).toFixed(2) + 'MB > 50MB', 'error');
                        $(this).val('');
                        $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                        $('#hp-drop-zone').removeClass('file-selected');
                        hideFileDetails();
                        return;
                    }

                    // Validate file extension
                    var extension = fileName.split('.').pop().toLowerCase();
                    if (!['ged', 'gedcom'].includes(extension)) {
                        addStatus('Invalid file type: .' + extension + ' (only .ged and .gedcom allowed)', 'error');
                        $(this).val('');
                        $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                        $('#hp-drop-zone').removeClass('file-selected');
                        hideFileDetails();
                        return;
                    }

                    // Update UI with selected filename
                    $('#hp-drop-zone .drag-instructions').text('Selected: ' + fileName);
                    $('#hp-drop-zone').addClass('file-selected');
                    showFileDetails(file);
                    addStatus('File validation passed, UI updated', 'success');
                } else {
                    addStatus('No file selected (input cleared)');
                    // Reset to default text if no file selected
                    $('#hp-drop-zone .drag-instructions').text('Drag and drop a GEDCOM file here');
                    $('#hp-drop-zone').removeClass('file-selected');
                    hideFileDetails();
                }
            });

            // EXACT DRAG AND DROP FUNCTIONALITY FROM step1-upload.php
            var $dropZone = $('#hp-drop-zone');

            $dropZone.on('dragover dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
                addStatus('Drag over event');
            });

            $dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$(this).has(e.relatedTarget).length) {
                    $(this).removeClass('drag-over');
                    addStatus('Drag leave event');
                }
            });

            $dropZone.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
                addStatus('Drop event triggered');

                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    addStatus('Files dropped: ' + files.length + ' file(s)', 'success');
                    var fileInput = $('#hp-gedcom-file')[0];
                    fileInput.files = files;
                    $(fileInput).trigger('change'); // Trigger change event to update UI
                } else {
                    addStatus('No files in drop event', 'error');
                }
            });

            // Click to select file
            $dropZone.on('click', function () {
                addStatus('Drop zone clicked, opening file dialog');
                $('#hp-gedcom-file').click();
            });

            // Test form data functionality
            $('#test-form-data').on('click', function () {
                addStatus('Testing form data collection...');

                var form = $('#hp-gedcom-upload-form')[0];
                var formData = new FormData(form);
                var fileInput = $('#hp-gedcom-file')[0];

                if (fileInput.files.length > 0) {
                    var file = fileInput.files[0];
                    addStatus('✓ Form contains file: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + 'MB)', 'success');

                    // Check if file is in FormData
                    var hasFile = false;
                    for (var pair of formData.entries()) {
                        if (pair[1] instanceof File) {
                            addStatus('✓ FormData entry "' + pair[0] + '": ' + pair[1].name, 'success');
                            hasFile = true;
                        } else {
                            addStatus('FormData entry "' + pair[0] + '": ' + pair[1], 'info');
                        }
                    }

                    if (!hasFile) {
                        addStatus('✗ No file found in FormData!', 'error');
                    }
                } else {
                    addStatus('✗ No file selected in form', 'error');
                }
            });

            // Clear selection functionality
            $('#clear-selection').on('click', function () {
                addStatus('Clearing file selection...');
                $('#hp-gedcom-file').val('').trigger('change');
            });

            addStatus('All event handlers attached', 'success');
        });
    </script>

    <?php wp_footer(); ?>
</body>

</html>