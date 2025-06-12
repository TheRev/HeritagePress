<?php
// Debug import page issue
echo "<!-- Debug: PHP is working -->\n";

// Check if ABSPATH is defined
if (!defined('ABSPATH')) {
    echo "<!-- Debug: ABSPATH not defined -->\n";
    exit;
}

echo "<!-- Debug: ABSPATH is defined -->\n";

// Check if trees variable exists and output it
if (isset($trees)) {
    echo "<!-- Debug: \$trees variable exists, count: " . count($trees) . " -->\n";
} else {
    echo "<!-- Debug: \$trees variable does not exist -->\n";
    $trees = array(); // Fallback
}

echo "<!-- Debug: About to show HTML -->\n";
?>
<h1>Test Import Page</h1>
<p>This is a test to see if the import page is working.</p>
<?php if (!empty($trees)): ?>
    <ul>
        <?php foreach ($trees as $tree): ?>
            <li><?php echo esc_html($tree->title ?? 'Unknown'); ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No trees found.</p>
<?php endif; ?>