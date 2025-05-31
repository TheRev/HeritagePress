<?php
/**
 * Autoloader class for Heritage Press plugin
 *
 * @since      1.0.0
 * @package HeritagePress
 */

namespace HeritagePress\Core;

// Load WordPress compatibility helper if not in WordPress environment
if (!function_exists('wp_enqueue_script')) {
    require_once __DIR__ . '/wordpress-compatibility.php';
}

/**
 * Class Autoloader
 */
class Autoloader {
    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register([new self(), 'autoload']);
    }    /**
     * Autoload a class
     */
    public function autoload($class) {
        if (strpos($class, 'HeritagePress\\') === 0) {
            $parts = explode('\\', $class);
            array_shift($parts); // Remove HeritagePress

            $base_dir = dirname(__DIR__); // Corrected base_dir to point to the plugin root            // Handle HeritagePress namespaces
            if (count($parts) > 0) {
                $namespace = array_shift($parts);
                $filename = implode('', $parts) . '.php';
                
                // Try in HeritagePress directory first
                $path = $base_dir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'HeritagePress' . DIRECTORY_SEPARATOR . $namespace . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($path)) {
                    require_once $path;
                    return;
                }
                
                // Try in legacy directories
                $legacy_path = $base_dir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . strtolower($namespace) . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($legacy_path)) {
                    require_once $legacy_path;
                    return;
                }
            }

            // PSR-4 style path (e.g., includes/core/class-activator.php)
            // This needs to be adjusted based on actual directory structure for other HeritagePress sub-namespaces
            $current_path = $base_dir . DIRECTORY_SEPARATOR . 'includes';
            $filename_parts = [];
            $is_class_file = false;

            foreach ($parts as $part) {
                // Check if a directory exists for the part
                if (is_dir($current_path . DIRECTORY_SEPARATOR . strtolower($part))) {
                    $current_path .= DIRECTORY_SEPARATOR . strtolower($part);
                } else {
                    // Assume remaining parts form the class name
                    $filename_parts[] = $part;
                    $is_class_file = true; // Signal that we are now processing filename parts
                }
            }

            if ($is_class_file && !empty($filename_parts)) {
                $class_name_kebab = strtolower(implode('-', $filename_parts));
                $psr4_path = $current_path . DIRECTORY_SEPARATOR . 'class-' . $class_name_kebab . '.php';

                if (file_exists($psr4_path)) {
                    require_once $psr4_path;
                    return;
                }
            }

            // Legacy style path as fallback (e.g., includes/class-activator.php)
            $legacy_class_name = end($parts);
            $legacy_path = $base_dir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-' . strtolower($legacy_class_name) . '.php';
            if (file_exists($legacy_path)) {
                require_once $legacy_path;
                return;
            }        }
    }
}
