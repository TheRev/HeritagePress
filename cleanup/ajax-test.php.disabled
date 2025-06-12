<?php
/**
 * Quick AJAX Test
 * Add this to functions.php temporarily or create as a mu-plugin
 */

// Simple test AJAX handler
add_action('wp_ajax_test_ajax', 'test_ajax_handler');
add_action('wp_ajax_nopriv_test_ajax', 'test_ajax_handler');

function test_ajax_handler()
{
    error_log('Test AJAX handler called');
    wp_send_json_success(array('message' => 'AJAX is working!'));
}

// Test if our HeritagePress handler is registered
add_action('init', function () {
    error_log('WordPress init - checking AJAX handlers');

    // Force load our plugin's main class to ensure handlers are registered
    if (class_exists('\HeritagePress\Admin\ImportExport\ImportHandler')) {
        error_log('ImportHandler class exists');

        // Try to instantiate it
        try {
            $handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
            error_log('ImportHandler instantiated successfully');
        } catch (Exception $e) {
            error_log('Error instantiating ImportHandler: ' . $e->getMessage());
        }
    } else {
        error_log('ImportHandler class does not exist');
    }

    // Check if action is registered
    global $wp_filter;
    if (isset($wp_filter['wp_ajax_hp_upload_gedcom'])) {
        error_log('wp_ajax_hp_upload_gedcom is registered');
    } else {
        error_log('wp_ajax_hp_upload_gedcom is NOT registered');
    }
});
?>