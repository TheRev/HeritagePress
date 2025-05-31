<?php
/**
 * WordPress Heritage Press Complete Cleanup Script
 * Fixes nested folder structure and database orphans
 * 
 * Upload to WordPress root and access via browser
 * URL: http://yoursite.com/heritage-press-complete-cleanup.php
 */

// Security checks
if (!file_exists('./wp-config.php')) {
    die('Error: This script must be placed in your WordPress root directory (same folder as wp-config.php)');
}

require_once('./wp-config.php');
require_once('./wp-includes/wp-db.php');

// Initialize WordPress database
$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);

echo '<h1>Heritage Press Complete Cleanup</h1>';
echo '<style>body{font-family:Arial,sans-serif;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>';

$cleanup_steps = [];

// Step 1: Check current plugin folder structure
echo '<h2>Step 1: Checking Plugin Folder Structure</h2>';

$plugin_dir = WP_CONTENT_DIR . '/plugins/';
$heritage_folders = [];

if (is_dir($plugin_dir)) {
    $folders = scandir($plugin_dir);
    foreach ($folders as $folder) {
        if (strpos($folder, 'heritage-press') === 0 && is_dir($plugin_dir . $folder)) {
            $heritage_folders[] = $folder;
        }
    }
}

if (empty($heritage_folders)) {
    echo '<p class="info">✓ No Heritage Press plugin folders found.</p>';
} else {
    echo '<p class="error">Found Heritage Press folders:</p>';
    echo '<ul>';
    foreach ($heritage_folders as $folder) {
        echo '<li>' . $folder . '</li>';
        
        // Check for nested structure
        $main_file = $plugin_dir . $folder . '/heritage-press.php';
        $nested_file = $plugin_dir . $folder . '/heritage-press/heritage-press.php';
        
        if (file_exists($nested_file) && !file_exists($main_file)) {
            echo '<li style="color:red;">→ NESTED STRUCTURE DETECTED in ' . $folder . '</li>';
            $cleanup_steps[] = 'fix_nested_' . $folder;
        } elseif (file_exists($main_file)) {
            echo '<li style="color:green;">→ Correct structure in ' . $folder . '</li>';
        } else {
            echo '<li style="color:orange;">→ No main file found in ' . $folder . '</li>';
            $cleanup_steps[] = 'remove_' . $folder;
        }
    }
    echo '</ul>';
}

// Step 2: Database cleanup
echo '<h2>Step 2: Database Cleanup</h2>';

// Get active plugins
$active_plugins = get_option('active_plugins', array());
$heritage_plugins = array_filter($active_plugins, function($plugin) {
    return strpos($plugin, 'heritage-press') !== false;
});

if (!empty($heritage_plugins)) {
    echo '<p class="error">Found Heritage Press in active plugins:</p>';
    echo '<ul>';
    foreach ($heritage_plugins as $plugin) {
        echo '<li>' . $plugin . '</li>';
    }
    echo '</ul>';
    $cleanup_steps[] = 'remove_active_plugins';
} else {
    echo '<p class="info">✓ No Heritage Press plugins in active list.</p>';
}

// Check for plugin data in options table
$heritage_options = $wpdb->get_results(
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%heritage%' OR option_name LIKE '%heritage_press%'"
);

if (!empty($heritage_options)) {
    echo '<p class="error">Found Heritage Press database options:</p>';
    echo '<ul>';
    foreach ($heritage_options as $option) {
        echo '<li>' . $option->option_name . '</li>';
    }
    echo '</ul>';
    $cleanup_steps[] = 'remove_options';
} else {
    echo '<p class="info">✓ No Heritage Press options in database.</p>';
}

// Step 3: Perform cleanup if requested
if (isset($_GET['cleanup']) && $_GET['cleanup'] === 'yes') {
    echo '<h2>Step 3: Performing Cleanup</h2>';
    
    // Remove from active plugins
    if (in_array('remove_active_plugins', $cleanup_steps)) {
        $new_active = array_filter($active_plugins, function($plugin) {
            return strpos($plugin, 'heritage-press') === false;
        });
        update_option('active_plugins', $new_active);
        echo '<p class="success">✓ Removed Heritage Press from active plugins list</p>';
    }
    
    // Remove database options
    if (in_array('remove_options', $cleanup_steps)) {
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%heritage%' OR option_name LIKE '%heritage_press%'"
        );
        echo '<p class="success">✓ Removed Heritage Press database options</p>';
    }
    
    // Fix nested folder structures
    foreach ($heritage_folders as $folder) {
        if (in_array('fix_nested_' . $folder, $cleanup_steps)) {
            $source_dir = $plugin_dir . $folder . '/heritage-press/';
            $target_dir = $plugin_dir . $folder . '/';
            
            if (is_dir($source_dir)) {
                // Move files from nested structure to correct location
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($source_dir),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $relative_path = str_replace($source_dir, '', $file->getPathname());
                        $target_file = $target_dir . $relative_path;
                        
                        // Create directory if needed
                        $target_file_dir = dirname($target_file);
                        if (!is_dir($target_file_dir)) {
                            mkdir($target_file_dir, 0755, true);
                        }
                        
                        // Move file
                        if (copy($file->getPathname(), $target_file)) {
                            unlink($file->getPathname());
                        }
                    }
                }
                
                // Remove empty nested directory
                rmdir($source_dir);
                echo '<p class="success">✓ Fixed nested structure in ' . $folder . '</p>';
            }
        }
        
        // Remove broken folders
        if (in_array('remove_' . $folder, $cleanup_steps)) {
            $folder_path = $plugin_dir . $folder;
            if (is_dir($folder_path)) {
                // Remove directory recursively
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($folder_path),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        unlink($file->getPathname());
                    } elseif ($file->isDir()) {
                        rmdir($file->getPathname());
                    }
                }
                rmdir($folder_path);
                echo '<p class="success">✓ Removed broken folder: ' . $folder . '</p>';
            }
        }
    }
    
    echo '<h3 class="success">Cleanup Complete!</h3>';
    echo '<p>You can now:</p>';
    echo '<ol>';
    echo '<li>Go to WordPress Admin → Plugins</li>';
    echo '<li>Upload the new heritage-press.zip file</li>';
    echo '<li>Activate the plugin</li>';
    echo '</ol>';
    echo '<p><strong>Don\'t forget to delete this cleanup script!</strong></p>';
    
} else {
    // Show cleanup options
    if (!empty($cleanup_steps)) {
        echo '<h2>Step 3: Cleanup Required</h2>';
        echo '<p class="error">Issues found that need cleanup:</p>';
        echo '<ul>';
        foreach ($cleanup_steps as $step) {
            if (strpos($step, 'fix_nested_') === 0) {
                echo '<li>Fix nested folder structure in ' . str_replace('fix_nested_', '', $step) . '</li>';
            } elseif (strpos($step, 'remove_') === 0) {
                echo '<li>Remove broken folder: ' . str_replace('remove_', '', $step) . '</li>';
            } elseif ($step === 'remove_active_plugins') {
                echo '<li>Remove Heritage Press from active plugins list</li>';
            } elseif ($step === 'remove_options') {
                echo '<li>Remove Heritage Press database options</li>';
            }
        }
        echo '</ul>';
        
        echo '<p><strong><a href="?cleanup=yes" style="background:red;color:white;padding:10px;text-decoration:none;">PERFORM CLEANUP</a></strong></p>';
        echo '<p><em>Warning: This will remove all Heritage Press plugin data. Make sure you have a backup!</em></p>';
    } else {
        echo '<h2>Step 3: No Cleanup Needed</h2>';
        echo '<p class="success">✓ No Heritage Press issues detected. You can safely install the plugin.</p>';
    }
}

?>
