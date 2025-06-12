<?php
// Test if trees are being fetched and passed to import step 1
require_once('../../../wp-config.php');

echo "<h2>Testing Tree Dropdown Issue</h2>";

try {
    // 1. Test database connection directly
    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_trees';
    $trees_direct = $wpdb->get_results("SELECT * FROM $table_name ORDER BY title ASC");

    echo "<h3>1. Direct Database Query</h3>";
    echo "<p>Trees found: " . count($trees_direct) . "</p>";
    if (!empty($trees_direct)) {
        echo "<ul>";
        foreach ($trees_direct as $tree) {
            echo "<li>ID: {$tree->id}, Title: {$tree->title}</li>";
        }
        echo "</ul>";
    }

    // 2. Test DatabaseOperations trait
    require_once(__DIR__ . '/includes/Admin/DatabaseOperations.php');

    // Create a test class that uses the trait
    class TestDatabaseOps
    {
        use HeritagePress\Admin\DatabaseOperations;
        private $wpdb;

        public function __construct()
        {
            global $wpdb;
            $this->wpdb = $wpdb;
        }

        public function test_get_trees()
        {
            return $this->get_trees();
        }
    }

    $test = new TestDatabaseOps();
    $trees_trait = $test->test_get_trees();

    echo "<h3>2. DatabaseOperations Trait</h3>";
    echo "<p>Trees found: " . count($trees_trait) . "</p>";
    if (!empty($trees_trait)) {
        echo "<ul>";
        foreach ($trees_trait as $tree) {
            echo "<li>ID: {$tree->id}, Title: {$tree->title}</li>";
        }
        echo "</ul>";
    }

    // 3. Test ImportExportManager
    require_once(__DIR__ . '/includes/Admin/ImportExportManager.php');

    $manager = new HeritagePress\Admin\ImportExportManager();

    // Use reflection to access the private get_trees method
    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('get_trees');
    $method->setAccessible(true);
    $trees_manager = $method->invoke($manager);

    echo "<h3>3. ImportExportManager get_trees()</h3>";
    echo "<p>Trees found: " . count($trees_manager) . "</p>";
    if (!empty($trees_manager)) {
        echo "<ul>";
        foreach ($trees_manager as $tree) {
            echo "<li>ID: {$tree->id}, Title: {$tree->title}</li>";
        }
        echo "</ul>";
    }

    // 4. Test if the trees variable is available in the template context
    echo "<h3>4. Template Variable Test</h3>";

    // Simulate what render_import_tab does
    $trees = $trees_manager; // This is what should be passed to the template

    echo "<p>Variable \$trees set with " . count($trees) . " trees</p>";

    // Test the template code snippet
    if (isset($trees) && !empty($trees)) {
        echo "<p>✓ Template condition (isset(\$trees) && !empty(\$trees)) passes</p>";
        echo "<select>";
        echo '<option value="new">Create New Tree</option>';
        foreach ($trees as $tree) {
            if (isset($tree->id)) {
                echo '<option value="' . esc_attr($tree->id) . '">' . esc_html($tree->title) . '</option>';
            } else {
                echo "<p>⚠️ Tree missing ID property</p>";
            }
        }
        echo "</select>";
    } else {
        echo "<p>✗ Template condition fails - trees not available or empty</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>