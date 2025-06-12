<?php
/**
 * Debug version of Import Step 1 to check available variables
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

echo "<h2>Debug: Available Variables in step1-upload.php</h2>";

echo "<h3>All Variables:</h3>";
echo "<pre>";
print_r(get_defined_vars());
echo "</pre>";

echo "<h3>Specific Variables Check:</h3>";
echo "<p>\$trees isset: " . (isset($trees) ? 'YES' : 'NO') . "</p>";
echo "<p>\$trees empty: " . (empty($trees) ? 'YES' : 'NO') . "</p>";

if (isset($trees)) {
    echo "<p>\$trees type: " . gettype($trees) . "</p>";
    echo "<p>\$trees count: " . (is_array($trees) || is_object($trees) ? count($trees) : 'N/A') . "</p>";

    if (!empty($trees)) {
        echo "<h4>Trees content:</h4>";
        echo "<pre>";
        print_r($trees);
        echo "</pre>";
    }
}

// Get available trees from the ImportExportManager (passed from render_import_tab)
// $trees variable is available from the include context
if (!isset($trees)) {
    $trees = array(); // Fallback if no trees are available
    echo "<p style='color: red;'>⚠️ No trees variable found, using empty fallback</p>";
}

?>
<div class="hp-import-container">
    <h3><?php esc_html_e('Step 1: Select GEDCOM File', 'heritagepress'); ?></h3>

    <form method="post" enctype="multipart/form-data" id="hp-gedcom-upload-form" class="hp-form">
        <?php wp_nonce_field('hp_gedcom_upload', 'hp_gedcom_nonce'); ?>

        <div class="hp-drag-drop-zone" id="hp-drop-zone">
            <p class="drag-instructions">
                <?php esc_html_e('Drag and drop a GEDCOM file here', 'heritagepress'); ?>
            </p>
            <p class="manual-upload">
                <?php esc_html_e('or', 'heritagepress'); ?>
            </p>
            <p>
                <input type="file" name="gedcom_file" id="hp-gedcom-file" accept=".ged,.gedcom">
                <label for="hp-gedcom-file" class="button">
                    <?php esc_html_e('Select File', 'heritagepress'); ?>
                </label>
            </p>
            <p class="description">
                <?php esc_html_e('Maximum file size: 32MB', 'heritagepress'); ?>
            </p>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="hp-gedcom-tree"><?php esc_html_e('Destination Tree', 'heritagepress'); ?></label>
                </th>
                <td>
                    <select name="tree_id" id="hp-gedcom-tree">
                        <option value="new"><?php esc_html_e('Create New Tree', 'heritagepress'); ?></option>
                        <?php
                        echo "<!-- Debug: trees check -->";
                        echo "<!-- isset: " . (isset($trees) ? 'yes' : 'no') . " -->";
                        echo "<!-- empty: " . (empty($trees) ? 'yes' : 'no') . " -->";
                        echo "<!-- count: " . (is_array($trees) ? count($trees) : 'not array') . " -->";

                        // Display available trees from database
                        if (isset($trees) && !empty($trees)) {
                            echo "<!-- Found trees, iterating -->";
                            foreach ($trees as $tree) {
                                // The database column is confirmed to be 'id'
                                if (isset($tree->id)) {
                                    echo '<option value="' . esc_attr($tree->id) . '">' . esc_html($tree->title) . '</option>';
                                } else {
                                    // Log for debugging if needed
                                    error_log('HeritagePress: Tree missing ID property: ' . print_r(get_object_vars($tree), true));
                                    echo "<!-- Tree missing ID: " . print_r(get_object_vars($tree), true) . " -->";
                                }
                            }
                        } else {
                            echo "<!-- No trees found or trees variable not set -->";
                        }
                        ?>
                    </select>

                    <div id="hp-new-tree-name" class="hp-conditional-field">
                        <label for="new_tree_name"><?php esc_html_e('New Tree Name:', 'heritagepress'); ?></label>
                        <input type="text" name="new_tree_name" id="new_tree_name"
                            placeholder="<?php esc_attr_e('My Family Tree', 'heritagepress'); ?>">
                    </div>
                </td>
            </tr>
        </table>

        <div class="hp-form-actions">
            <button type="submit" class="button button-primary" id="hp-validate-gedcom">
                <?php esc_html_e('Upload and Validate', 'heritagepress'); ?>
            </button>

            <div id="hp-upload-progress" class="hp-progress-container" style="display: none;">
                <div class="hp-progress-bar">
                    <div class="hp-progress-bar-inner"></div>
                </div>
                <div class="hp-progress-text">
                    <?php esc_html_e('Uploading...', 'heritagepress'); ?> <span class="hp-progress-percentage">0%</span>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- JavaScript is now handled by import-export.js -->