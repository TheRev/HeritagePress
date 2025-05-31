<?php
/**
 * Autoloader class
 *
 * @since      1.0.0
 * @package HeritagePress
 */

namespace HeritagePress;

/**
 * Class Autoloader
 */
class Autoloader {
    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register([new self(), 'autoload']);
    }

    /**
     * Autoload a class
     */
    public function autoload($class) {
        if (strpos($class, 'HeritagePress\\') !== 0) {
            return;
        }

        $parts = explode('\\', $class);
        $path = HERITAGE_PRESS_PLUGIN_DIR . 'includes/HeritagePress';
        if (!empty($parts)) {
            array_shift($parts); // Remove HeritagePress
            $path .= '/' . implode('/', $parts) . '.php';
            
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
}
