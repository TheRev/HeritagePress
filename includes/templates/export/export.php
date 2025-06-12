<?php
/**
 * Export tab template for Import/Export interface
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="hp-export-container">
    <h3><?php esc_html_e('Export GEDCOM File', 'heritagepress'); ?></h3>

    <form method="post" id="hp-gedcom-export-form" class="hp-form">
        <?php wp_nonce_field('hp_gedcom_export', 'hp_gedcom_export_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="hp-export-tree"><?php esc_html_e('Select Tree', 'heritagepress'); ?></label>
                </th>
                <td>
                    <select name="tree_id" id="hp-export-tree" required>
                        <option value=""><?php esc_html_e('-- Select Tree --', 'heritagepress'); ?></option> <?php
                           // Display available trees passed from ImportExportManager
                           if (isset($trees) && !empty($trees)) {
                               foreach ($trees as $tree) {
                                   echo '<option value="' . esc_attr($tree->id) . '">';
                                   echo esc_html($tree->title);
                                   echo '</option>';
                               }
                           } else {
                               // Fallback: Get trees directly from database if not passed from ImportExportManager
                               global $wpdb;
                               if ($wpdb) {
                                   $fallback_trees = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_trees ORDER BY title ASC");

                                   if (!empty($fallback_trees)) {
                                       foreach ($fallback_trees as $tree) {
                                           if (isset($tree->id)) {
                                               echo '<option value="' . esc_attr($tree->id) . '">' . esc_html($tree->title) . '</option>';
                                           }
                                       }
                                   } else {
                                       echo '<option disabled>' . esc_html__('No trees available', 'heritagepress') . '</option>';
                                   }
                               } else {
                                   echo '<option disabled>' . esc_html__('Database connection error', 'heritagepress') . '</option>';
                               }
                           }
                           ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label
                        for="hp-export-gedcom-version"><?php esc_html_e('GEDCOM Version', 'heritagepress'); ?></label>
                </th>
                <td>
                    <select name="gedcom_version" id="hp-export-gedcom-version">
                        <option value="7.0"><?php esc_html_e('GEDCOM 7.0', 'heritagepress'); ?></option>
                        <option value="5.5.1" selected><?php esc_html_e('GEDCOM 5.5.1', 'heritagepress'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Export Format', 'heritagepress'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e('Export Format', 'heritagepress'); ?>
                        </legend>

                        <label for="hp-export-format-gedcom">
                            <input type="radio" name="export_format" id="hp-export-format-gedcom" value="gedcom"
                                checked>
                            <?php esc_html_e('Standard GEDCOM (.ged)', 'heritagepress'); ?>
                        </label><br>

                        <label for="hp-export-format-gedzip">
                            <input type="radio" name="export_format" id="hp-export-format-gedzip" value="gedzip">
                            <?php esc_html_e('GEDZIP (GEDCOM with media files)', 'heritagepress'); ?>
                        </label><br>

                        <label for="hp-export-format-json">
                            <input type="radio" name="export_format" id="hp-export-format-json" value="json">
                            <?php esc_html_e('JSON (for API compatibility)', 'heritagepress'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Privacy Options', 'heritagepress'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e('Privacy Options', 'heritagepress'); ?>
                        </legend>

                        <label for="hp-privacy-living">
                            <input type="checkbox" name="privacy_living" id="hp-privacy-living" value="1" checked>
                            <?php esc_html_e('Exclude details of living individuals', 'heritagepress'); ?>
                        </label><br>

                        <label for="hp-privacy-notes">
                            <input type="checkbox" name="privacy_notes" id="hp-privacy-notes" value="1">
                            <?php esc_html_e('Exclude private notes', 'heritagepress'); ?>
                        </label><br>

                        <label for="hp-privacy-media">
                            <input type="checkbox" name="privacy_media" id="hp-privacy-media" value="1">
                            <?php esc_html_e('Exclude private media items', 'heritagepress'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>

        <div class="hp-export-filters">
            <h4><?php esc_html_e('Advanced Filtering (Optional)', 'heritagepress'); ?></h4>

            <details>
                <summary><?php esc_html_e('Branch Selection', 'heritagepress'); ?></summary>
                <div class="hp-filter-content">
                    <!-- Branch selection options will be added here -->
                    <p class="description">
                        <?php esc_html_e('This feature will be available in a future update.', 'heritagepress'); ?>
                    </p>
                </div>
            </details>

            <details>
                <summary><?php esc_html_e('Date Range', 'heritagepress'); ?></summary>
                <div class="hp-filter-content">
                    <!-- Date range options will be added here -->
                    <p class="description">
                        <?php esc_html_e('This feature will be available in a future update.', 'heritagepress'); ?>
                    </p>
                </div>
            </details>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary"
                value="<?php esc_attr_e('Export GEDCOM', 'heritagepress'); ?>">
        </p>
    </form>
</div>