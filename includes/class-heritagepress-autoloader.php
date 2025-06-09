<?php
/**
 * HeritagePress Autoloader
 *
 * @package HeritagePress
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Class HeritagePress_Autoloader
 *
 * PSR-4 style autoloader for HeritagePress plugin classes
 */
class HeritagePress_Autoloader
{

    /**
     * Register autoloader
     */
    public static function register()
    {
        spl_autoload_register(array(new self(), 'autoload'));
    }

    /**
     * Autoload HeritagePress classes
     *
     * @param string $class_name Full class name to load.
     */
    public function autoload($class_name)
    {
        // Only handle our namespace
        if (strpos($class_name, 'HeritagePress\\') !== 0) {
            return;
        }

        // Remove namespace prefix
        $class_path = str_replace('HeritagePress\\', '', $class_name);

        // Convert namespace separator to directory separator
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path);

        // Build file path
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . $class_path . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

HeritagePress_Autoloader::register();
