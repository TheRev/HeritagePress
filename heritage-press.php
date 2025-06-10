<?php
/**
 * Plugin Name: HeritagePress
 * Plugin URI: https://heritagepress.com
 * Description: A comprehensive genealogy and family history management plugin for WordPress. Import GEDCOM files, manage family trees, and create beautiful genealogy websites.
 * Version: 1.0.0
 * Author: HeritagePress Team
 * Author URI: https://heritagepress.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: heritagepress
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package HeritagePress
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Generate a unique identifier for this request
if (!defined('HERITAGEPRESS_REQUEST_ID')) {
    define('HERITAGEPRESS_REQUEST_ID', uniqid('hp_', true));
}

// Prevent multiple loads of the plugin within the same request
if (defined('HERITAGEPRESS_LOADED')) {
    return;
}
define('HERITAGEPRESS_LOADED', true);

// Define plugin paths
if (!defined('HERITAGEPRESS_PLUGIN_DIR')) {
    define('HERITAGEPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('HERITAGEPRESS_PLUGIN_URL')) {
    define('HERITAGEPRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Load plugin class
require_once dirname(__FILE__) . '/includes/class-heritagepress.php';

// Initialize plugin
HeritagePress::init();
