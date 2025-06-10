<?php
/**
 * Settings tab template for Import/Export interface
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get saved settings
$import_settings = get_option('heritagepress_import_settings', array());
$export_settings = get_option('heritagepress_export_settings', array());
?>

<div class="hp-settings-container">
    <form method="post" id="hp-importexport-settings-form" class="hp-form">
        <?php wp_nonce_field('hp_importexport_settings', 'hp_settings_nonce'); ?>
        
        <div class="hp-settings-tabs">
            <button type="button" class="hp-settings-tab active" data-target="import">
                <?php esc_html_e('Import Settings', 'heritagepress'); ?>
            </button>
            <button type="button" class="hp-settings-tab" data-target="export">
                <?php esc_html_e('Export Settings', 'heritagepress'); ?>
            </button>
            <button type="button" class="hp-settings-tab" data-target="advanced">
                <?php esc_html_e('Advanced Settings', 'heritagepress'); ?>
            </button>
        </div>
        
        <!-- Import Settings -->
        <div class="hp-settings-content active" id="import-settings">
            <h3><?php esc_html_e('Default Import Settings', 'heritagepress'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hp-default-import-mode"><?php esc_html_e('Default Import Mode', 'heritagepress'); ?></label>
                    </th>
                    <td>
                        <select name="import_settings[default_mode]" id="hp-default-import-mode">
                            <option value="replace" <?php selected(isset($import_settings['default_mode']) ? $import_settings['default_mode'] : '', 'replace'); ?>>
                                <?php esc_html_e('Replace existing data', 'heritagepress'); ?>
                            </option>
                            <option value="add" <?php selected(isset($import_settings['default_mode']) ? $import_settings['default_mode'] : '', 'add'); ?>>
                                <?php esc_html_e('Add to existing data (skip duplicates)', 'heritagepress'); ?>
                            </option>
                            <option value="merge" <?php selected(isset($import_settings['default_mode']) ? $import_settings['default_mode'] : '', 'merge'); ?>>
                                <?php esc_html_e('Merge with existing data', 'heritagepress'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hp-default-media-import"><?php esc_html_e('Media Files', 'heritagepress'); ?></label>
                    </th>
                    <td>
                        <label for="hp-default-media-import">
                            <input type="checkbox" name="import_settings[import_media]" id="hp-default-media-import" value="1" 
                                <?php checked(isset($import_settings['import_media']) ? $import_settings['import_media'] : 0, 1); ?>>
                            <?php esc_html_e('Import media files by default', 'heritagepress'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hp-default-privacy"><?php esc_html_e('Privacy', 'heritagepress'); ?></label>
                    </th>
                    <td>
                        <label for="hp-default-privacy">
                            <input type="checkbox" name="import_settings[privacy_living]" id="hp-default-privacy" value="1" 
                                <?php checked(isset($import_settings['privacy_living']) ? $import_settings['privacy_living'] : 0, 1); ?>>
                            <?php esc_html_e('Mark living individuals as private by default', 'heritagepress'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Export Settings -->
        <div class="hp-settings-content" id="export-settings" style="display: none;">
            <h3><?php esc_html_e('Default Export Settings', 'heritagepress'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hp-default-gedcom-version"><?php esc_html_e('GEDCOM Version', 'heritagepress'); ?></label>
                    </th>
                    <td>
                        <select name="export_settings[default_version]" id="hp-default-gedcom-version">
                            <option value="7.0" <?php selected(isset($export_settings['default_version']) ? $export_settings['default_version'] : '', '7.0'); ?>>
                                <?php esc_html_e('GEDCOM 7.0', 'heritagepress'); ?>
                            </option>
                            <option value="5.5.1" <?php selected(isset($export_settings['default_version']) ? $export_settings['default_version'] : '', '5.5.1'); ?>>
                                <?php esc_html_e('GEDCOM 5.5.1', 'heritagepress'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hp-default-format"><?php esc_html_e('Export Format', 'heritagepress'); ?></label>
                    </th>
                    <td>
                        <select name="export_settings[default_format]" id="hp-default-format">
                            <option value="gedcom" <?php selected(isset($export_settings['default_format']) ? $export_settings['default_format'] : '', 'gedcom'); ?>>
                                <?php esc_html_e('Standard GEDCOM (.ged)', 'heritagepress'); ?>
                            </option>
                            <option value="gedzip" <?php selected(isset($export_settings['default_format']) ? $export_settings['default_format'] : '', 'gedzip'); ?>>
                                <?php esc_html_e('GEDZIP (GEDCOM with media files)', 'heritagepress'); ?>
                            </option>
                            <option value="json" <?php selected(isset($export_settings['default_format']) ? $export_settings['default_format'] : '', 'json'); ?>>
                                <?php esc_html_e('JSON (for API compatibility)', 'heritagepress'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('Default Privacy Settings', 'heritagepress'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e('Default Privacy Settings', 'heritagepress'); ?></legend>
                            
                            <label for="hp-default-privacy-living">
                                <input type="checkbox" name="export_settings[privacy_living]" id="hp-default-privacy-living" value="1" 
                                    <?php checked(isset($export_settings['privacy_living']) ? $export_settings['privacy_living'] : 0, 1); ?>>
                                <?php esc_html_e('Exclude details of living individuals', 'heritagepress'); ?>
                            </label><br>
                            
                            <label for="hp-default-privacy-notes">
                                <input type="checkbox" name="export_settings[privacy_notes]" id="hp-default-privacy-notes" value="1" 
                                    <?php checked(isset($export_settings['privacy_notes']) ? $export_settings['privacy_notes'] : 0, 1); ?>>
                                <?php esc_html_e('Exclude private notes', 'heritagepress'); ?>
                            </label><br>
                            
                            <label for="hp-default-privacy-media">
                                <input type="checkbox" name="export_settings[privacy_media]" id="hp-default-privacy-media" value="1" 
                                    <?php checked(isset($export_settings['privacy_media']) ? $export_settings['privacy_media'] : 0, 1); ?>>
                                <?php esc_html_e('Exclude private media items', 'heritagepress'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Advanced Settings -->
        <div class="hp-settings-content" id="advanced-settings" style="display: none;">
            <h3><?php esc_html_e('Advanced Settings', 'heritagepress'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hp-batch-size"><?php esc_html_e('Batch Size', 'heritagepress'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="advanced_settings[batch_size]" id="hp-batch-size" min="10" max="1000" step="10" 
                            value="<?php echo isset($import_settings['batch_size']) ? esc_attr($import_settings['batch_size']) : '100'; ?>">
                        <p class="description">
                            <?php esc_html_e('Number of records to process in a single batch during import/export. Lower values may improve reliability but decrease performance.', 'heritagepress'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hp-temp-directory"><?php esc_html_e('Temporary Directory', 'heritagepress'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="advanced_settings[temp_dir]" id="hp-temp-directory" 
                            value="<?php echo isset($import_settings['temp_dir']) ? esc_attr($import_settings['temp_dir']) : ''; ?>" 
                            placeholder="<?php echo esc_attr(wp_upload_dir()['basedir'] . '/heritagepress-temp'); ?>">
                        <p class="description">
                            <?php esc_html_e('Directory for temporary files during import/export. Leave blank for default.', 'heritagepress'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" 
                   value="<?php esc_attr_e('Save Settings', 'heritagepress'); ?>">
        </p>
    </form>
</div>
