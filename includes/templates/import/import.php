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

// DEBUG: Add template step routing debugging
error_log('import.php template - Step routing debug:');
error_log('  $_GET[step]: ' . (isset($_GET['step']) ? $_GET['step'] : 'NOT SET'));
error_log('  intval($_GET[step]): ' . (isset($_GET['step']) ? intval($_GET['step']) : 'N/A'));
error_log('  Final $step value: ' . $step);
error_log('  Current URL: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'UNKNOWN'));

// Ensure $trees variable is available for all steps
// This should be passed from ImportExportManager::render_import_tab()
if (!isset($trees)) {
    $trees = array(); // Fallback
}

// Handle different steps
switch ($step) {
    case 2:
        error_log('import.php - Including step2-validation.php');
        include 'step2-validation.php';
        break;
    case 3:
        error_log('import.php - Including step3-import.php');
        include 'step3-import.php';
        break;
    case 4:
        error_log('import.php - Including step4-results.php');
        include 'step4-results.php';
        break;
    default: // Step 1
        error_log('import.php - Including step1-upload.php (default case, step=' . $step . ')');
        include 'step1-upload.php';
        break;
}
