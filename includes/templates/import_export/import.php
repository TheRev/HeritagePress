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

// Handle different steps
switch ($step) {
    case 2:
        include 'import/step2-validation.php';
        break;
    case 3:
        include 'import/step3-import.php';
        break;
    case 4:
        include 'import/step4-results.php';
        break;
    default: // Step 1
        include 'import/step1-upload.php';
        break;
}
