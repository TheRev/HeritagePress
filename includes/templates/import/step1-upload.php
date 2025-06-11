<?php
/**
 * Import Step 1: File upload form
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get available trees if we have any
$trees = array(); // TODO: Get trees from your tree management system

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
                        <?php foreach ($trees as $tree_id => $tree_name): ?>
                            <option value="<?php echo esc_attr($tree_id); ?>">
                                <?php echo esc_html($tree_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="hp-new-tree-name" class="hp-conditional-field">
                        <label for="new_tree_name"><?php esc_html_e('New Tree Name:', 'heritagepress'); ?></label>
                        <input type="text" name="new_tree_name" id="new_tree_name"
                            placeholder="<?php esc_attr_e('My Family Tree', 'heritagepress'); ?>">
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e('Import Options', 'heritagepress'); ?>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e('Import Options', 'heritagepress'); ?>
                        </legend>

                        <label>
                            <input type="radio" name="import_option" value="replace" checked>
                            <?php esc_html_e('Replace existing data (recommended for new trees)', 'heritagepress'); ?>
                        </label><br>

                        <label>
                            <input type="radio" name="import_option" value="add">
                            <?php esc_html_e('Add to existing data (skip duplicates)', 'heritagepress'); ?>
                        </label><br>

                        <label>
                            <input type="radio" name="import_option" value="merge">
                            <?php esc_html_e('Merge with existing data (update duplicates)', 'heritagepress'); ?>
                        </label>
                    </fieldset>
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