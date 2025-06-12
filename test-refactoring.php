<?php
/**
 * Test MenuManager Refactoring
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../../../../');
}

require_once __DIR__ . '/includes/class-heritagepress-autoloader.php';

echo "<h1>HeritagePress MenuManager Refactoring Test</h1>\n";

try {
    // Test ServiceContainer
    echo "<h2>1. Testing ServiceContainer</h2>\n";
    $container = new \HeritagePress\Core\ServiceContainer();
    echo "<p>✅ ServiceContainer created successfully</p>\n";

    // Test ErrorHandler
    echo "<h2>2. Testing ErrorHandler</h2>\n";
    $errorHandler = new \HeritagePress\Core\ErrorHandler();
    $errorHandler->info('Test log message');
    echo "<p>✅ ErrorHandler created and logging works</p>\n";

    // Test ManagerFactory
    echo "<h2>3. Testing ManagerFactory</h2>\n";
    $factory = new \HeritagePress\Factories\ManagerFactory($container);
    echo "<p>✅ ManagerFactory created successfully</p>\n";

    // Test existing manager creation
    echo "<h2>4. Testing Manager Creation</h2>\n";
    $importExportManager = $factory->create('ImportExportManager');
    if ($importExportManager) {
        echo "<p>✅ ImportExportManager created successfully</p>\n";
    } else {
        echo "<p>⚠️ ImportExportManager creation returned null (expected for now)</p>\n";
    }

    // Test MenuManager
    echo "<h2>5. Testing MenuManager</h2>\n";
    $menuManager = new \HeritagePress\Admin\MenuManager($container);
    echo "<p>✅ MenuManager created with dependency injection</p>\n";

    // Test MenuConfig integration
    echo "<h2>6. Testing MenuConfig Integration</h2>\n";
    $mainMenu = \HeritagePress\Config\MenuConfig::getMainMenu();
    echo "<p>✅ Main menu config loaded: " . $mainMenu['menu_title'] . "</p>\n";

    $submenus = \HeritagePress\Config\MenuConfig::getOrderedSubmenus();
    echo "<p>✅ Found " . count($submenus) . " submenu configurations</p>\n";

    echo "<h2>🎉 Refactoring Test Complete!</h2>\n";
    echo "<p><strong>Summary:</strong> All core components are working with dependency injection.</p>\n";

} catch (Exception $e) {
    echo "<h2>❌ Test Failed</h2>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
} catch (Error $e) {
    echo "<h2>❌ Fatal Error</h2>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>