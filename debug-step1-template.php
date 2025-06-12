<?php
/**
 * Direct test of step1-upload.php template
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// WordPress constants for ABSPATH and others
define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

// Mock function if it doesn't exist
if (!function_exists('esc_html_e')) {
    function esc_html_e($text, $domain = 'default')
    {
        echo htmlspecialchars(__($text, $domain));
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars($text);
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars($text, ENT_QUOTES);
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default')
    {
        echo esc_attr(__($text, $domain));
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action, $name, $referer = true, $echo = true)
    {
        $field = '<input type="hidden" name="' . $name . '" value="test_nonce" />';
        if ($echo)
            echo $field;
        return $field;
    }
}

echo "<h1>Step 1 Template Test</h1>\n";

// Mock trees data
$trees = array(
    (object) array('id' => 1, 'title' => 'My Family Tree'),
    (object) array('id' => 2, 'title' => 'Test Tree'),
);

echo "<p>Trees available: " . count($trees) . "</p>\n";

// Include the step1 template
$template_file = __DIR__ . '/includes/templates/import/step1-upload.php';

if (file_exists($template_file)) {
    echo "<p style='color: green;'>✓ Template file found</p>\n";

    try {
        echo "<div style='border: 2px solid #000; padding: 20px; margin: 20px;'>\n";
        echo "<h2>Template Output:</h2>\n";
        include $template_file;
        echo "</div>\n";
        echo "<p style='color: green;'>✓ Template included successfully</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error including template: " . $e->getMessage() . "</p>\n";
    } catch (ParseError $e) {
        echo "<p style='color: red;'>✗ Parse error in template: " . $e->getMessage() . "</p>\n";
        echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>\n";
    } catch (Error $e) {
        echo "<p style='color: red;'>✗ Fatal error in template: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>✗ Template file not found: $template_file</p>\n";
}

echo "<p><strong>Test complete:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
