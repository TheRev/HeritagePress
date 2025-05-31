<?php

namespace HeritagePress\Tests;

// Define WordPress functions before anything else
require_once __DIR__ . '/Mocks/wp-functions.php';

/**
 * Bootstrap file for testing
 */

// Load composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load and register plugin autoloader
$base_dir = dirname(__DIR__);
require_once $base_dir . '/includes/class-autoloader.php';

// Add all necessary includes paths
set_include_path(get_include_path() . PATH_SEPARATOR . $base_dir . '/includes');

// Explicitly require the query builder for tests
require_once $base_dir . '/includes/database/class-query-builder.php';

// Manually require classes needed for testing
require_once $base_dir . '/includes/models/interface-model.php';
require_once $base_dir . '/includes/database/class-database-manager.php';
require_once $base_dir . '/includes/core/class-audit-log-observer.php';
require_once $base_dir . '/includes/repositories/class-family-repository.php';
require_once $base_dir . '/includes/models/class-family-model.php';

// Register the custom autoloader to handle other classes
\HeritagePress\Core\Autoloader::register();

// Mock WordPress functions and classes
require_once __DIR__ . '/Mocks/MockWP.php';
require_once __DIR__ . '/Mocks/MockWPDB.php';

// Load test base classes
require_once __DIR__ . '/HeritageTestCase.php';
require_once __DIR__ . '/Unit/UnitTestCase.php';
require_once __DIR__ . '/Integration/IntegrationTestCase.php';
require_once __DIR__ . '/Functional/FunctionalTestCase.php';

// Define WordPress constants
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
if (!defined('OBJECT_K')) {
    define('OBJECT_K', 'OBJECT_K');
}
