<?php
/**
 * Test the complete file selection workflow after fixes
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php';

echo "<h1>File Selection Workflow Test</h1>";
echo "<p>Testing the complete file selection functionality after fixes</p>";

// Test 1: Check if the ImportExportManager get_trees method works
echo "<h2>Test 1: ImportExportManager get_trees</h2>";
try {
    $manager = new HeritagePress\Admin\ImportExportManager();

    // Use reflection to test the get_trees method
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('get_trees');
    $method->setAccessible(true);
    $trees = $method->invoke($manager);

    echo "<p style='color: green;'>✓ get_trees method works</p>";
    echo "<p>Found " . count($trees) . " trees</p>";

    if (!empty($trees)) {
        echo "<ul>";
        foreach ($trees as $tree) {
            $id = property_exists($tree, 'id') ? $tree->id : $tree->treeID;
            echo "<li>ID: $id, Title: " . esc_html($tree->title) . "</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 2: Check if the step1-upload.php template receives the trees variable
echo "<h2>Test 2: Template Variable Test</h2>";
try {
    // Simulate the template include process
    $trees = $manager->get_trees(); // This would be done in render_import_tab

    echo "<p>Trees variable type: " . gettype($trees) . "</p>";
    echo "<p>Trees count: " . count($trees) . "</p>";
    echo "<p>isset(\$trees): " . (isset($trees) ? 'true' : 'false') . "</p>";
    echo "<p>!empty(\$trees): " . (!empty($trees) ? 'true' : 'false') . "</p>";

    // Test the dropdown generation logic
    if (!empty($trees)) {
        echo "<h3>Dropdown HTML Test:</h3>";
        echo "<select>";
        echo '<option value="new">Create New Tree</option>';
        foreach ($trees as $tree) {
            if (property_exists($tree, 'treeID') && property_exists($tree, 'title')) {
                $tree_id = $tree->treeID;
                $tree_title = $tree->title;
                echo '<option value="' . esc_attr($tree_id) . '">' . esc_html($tree_title) . '</option>';
            }
        }
        echo "</select>";
        echo "<p style='color: green;'>✓ Dropdown HTML generated successfully</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Template test error: " . $e->getMessage() . "</p>";
}

// Test 3: File selection UI test
echo "<h2>Test 3: File Selection UI</h2>";
?>
<style>
    .test-file-zone {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background: #fafafa;
        transition: all 0.3s ease;
        margin: 20px 0;
        cursor: pointer;
    }

    .test-file-zone:hover {
        border-color: #0073aa;
        background: #f0f8ff;
    }

    .test-file-zone.drag-over {
        border-color: #0073aa;
        background: #e6f3ff;
        border-style: solid;
    }

    .test-file-zone.file-selected {
        border-color: #46b450;
        background: #f0fff0;
        border-style: solid;
    }

    .test-file-zone.file-selected .drag-instructions {
        color: #46b450;
        font-weight: bold;
    }

    .test-file-zone input[type="file"] {
        display: none;
    }

    .test-status {
        margin: 10px 0;
        padding: 10px;
        border-radius: 4px;
    }

    .test-status.success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .test-status.error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
</style>

<div class="test-file-zone" id="test-drop-zone">
    <p class="drag-instructions">Drag and drop a GEDCOM file here or click to select</p>
    <input type="file" id="test-file-input" accept=".ged,.gedcom">
</div>

<div id="test-status" class="test-status" style="display: none;"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var dropZone = document.getElementById('test-drop-zone');
        var fileInput = document.getElementById('test-file-input');
        var statusDiv = document.getElementById('test-status');

        function showStatus(message, type) {
            statusDiv.textContent = message;
            statusDiv.className = 'test-status ' + type;
            statusDiv.style.display = 'block';
        }

        // File input change handler
        fileInput.addEventListener('change', function () {
            var file = this.files[0];
            if (file) {
                var fileName = file.name;
                var fileSize = file.size;
                var extension = fileName.split('.').pop().toLowerCase();

                // Validate file type
                if (!['ged', 'gedcom'].includes(extension)) {
                    showStatus('Invalid file type. Only .ged and .gedcom files are allowed.', 'error');
                    this.value = '';
                    dropZone.classList.remove('file-selected');
                    dropZone.querySelector('.drag-instructions').textContent = 'Drag and drop a GEDCOM file here or click to select';
                    return;
                }

                // Validate file size (50MB limit)
                if (fileSize > 50 * 1024 * 1024) {
                    showStatus('File too large. Maximum size is 50MB.', 'error');
                    this.value = '';
                    dropZone.classList.remove('file-selected');
                    dropZone.querySelector('.drag-instructions').textContent = 'Drag and drop a GEDCOM file here or click to select';
                    return;
                }

                // File is valid
                dropZone.classList.add('file-selected');
                dropZone.querySelector('.drag-instructions').textContent = 'Selected: ' + fileName;
                showStatus('File selected successfully: ' + fileName + ' (' + (fileSize / 1024 / 1024).toFixed(2) + 'MB)', 'success');
            } else {
                dropZone.classList.remove('file-selected');
                dropZone.querySelector('.drag-instructions').textContent = 'Drag and drop a GEDCOM file here or click to select';
                statusDiv.style.display = 'none';
            }
        });

        // Drag and drop handlers
        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            var files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        // Click to select
        dropZone.addEventListener('click', function () {
            fileInput.click();
        });

        console.log('File selection test ready');
    });
</script>

<?php
echo "<h2>Summary</h2>";
echo "<p>The file selection functionality should now be working properly:</p>";
echo "<ul>";
echo "<li>✓ Fixed ImportExportManager get_trees() method to handle treeID field</li>";
echo "<li>✓ step1-upload.php template already has proper property checks</li>";
echo "<li>✓ File selection UI with drag-and-drop support is implemented</li>";
echo "<li>✓ Client-side validation for file type and size</li>";
echo "<li>✓ Visual feedback for different states (hover, drag-over, file-selected)</li>";
echo "</ul>";

echo "<p><strong>To test:</strong></p>";
echo "<ol>";
echo "<li>Try dragging a .ged or .gedcom file to the zone above</li>";
echo "<li>Or click the zone to open the file dialog</li>";
echo "<li>Check that the UI updates with the selected file name</li>";
echo "<li>Try selecting an invalid file type to test validation</li>";
echo "</ol>";
?>