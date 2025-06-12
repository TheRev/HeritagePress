<?php
/**
 * Import tab template for Import/Export interface
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get current step or default to 1
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Ensure $trees variable is available for all steps
// This should be passed from ImportExportManager::render_import_tab()
if (!isset($trees)) {
    $trees = array(); // Fallback
}

// Handle different steps
switch ($step) {
    case 2:
        include 'step2-validation.php';
        break;
    case 3:
        include 'step3-import.php';
        break;
    case 4:
        include 'step4-results.php';
        break;
    default: // Step 1
        include 'step1-upload.php';
        break;
}
