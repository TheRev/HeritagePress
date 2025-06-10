// Get current step or default to 1
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Handle different steps
switch ($step) {
    case 2:
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/import_export/import/step2-validation.php';
        break;
    case 3:
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/import_export/import/step3-import.php';
        break;
    case 4:
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/import_export/import/step4-results.php';
        break;
    default: // Step 1
        include HERITAGEPRESS_PLUGIN_DIR . 'includes/templates/import_export/import/step1-upload.php';
        break;
}