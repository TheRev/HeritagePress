<?php
/**
 * Import Step 2: GEDCOM Validation
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get file key from URL
$file_key = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';

// Get import options from step 1
$tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : 'new';
$new_tree_name = isset($_POST['new_tree_name']) ? sanitize_text_field($_POST['new_tree_name']) : '';
$import_option = isset($_POST['import_option']) ? sanitize_text_field($_POST['import_option']) : 'replace';

// For demo purposes - this would be replaced with actual validation results
$validation_result = array(
    'gedcom_version' => '5.5.1',
    'has_errors' => false,
    'has_warnings' => true,
    'error_count' => 0,
    'warning_count' => 3,
    'individuals' => 250,
    'families' => 85,
    'sources' => 43,
    'media' => 12,
    'notes' => 67,
    'repositories' => 5,
    'warnings' => array(
        array(
            'type' => 'warning',
            'message' => 'Missing birth date for individual',
            'record_id' => 'I0023',
            'record_name' => 'John Smith',
            'line' => 342
        ),
        array(
            'type' => 'warning',
            'message' => 'Missing birth date for individual',
            'record_id' => 'I0045',
            'record_name' => 'Mary Johnson',
            'line' => 673
        ),
        array(
            'type' => 'warning',
            'message' => 'Potentially invalid date format',
            'record_id' => 'I0078',
            'record_name' => 'William Brown',
            'line' => 1204
        ),
    ),
);

?>
<div class="hp-import-container">
    <h3><?php esc_html_e('Step 2: Validate GEDCOM File', 'heritagepress'); ?></h3>
    
    <div class="hp-validation-summary">
        <h4><?php esc_html_e('GEDCOM Summary', 'heritagepress'); ?></h4>
        <table class="widefat hp-data-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e('GEDCOM Version', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($validation_result['gedcom_version']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Individuals', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($validation_result['individuals']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Families', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($validation_result['families']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Sources', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($validation_result['sources']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Media Objects', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($validation_result['media']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Notes', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($validation_result['notes']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Repositories', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($validation_result['repositories']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Validation Status', 'heritagepress'); ?></th>
                    <td>
                        <?php if ($validation_result['has_errors']): ?>
                            <span class="hp-validation-error">
                                <?php echo sprintf(
                                    esc_html(_n('%d error', '%d errors', $validation_result['error_count'], 'heritagepress')), 
                                    $validation_result['error_count']
                                ); ?>
                            </span>
                        <?php else: ?>
                            <span class="hp-validation-success">
                                <?php esc_html_e('No errors', 'heritagepress'); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($validation_result['has_warnings']): ?>
                            <span class="hp-validation-warning">
                                <?php echo sprintf(
                                    esc_html(_n('%d warning', '%d warnings', $validation_result['warning_count'], 'heritagepress')), 
                                    $validation_result['warning_count']
                                ); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <?php if ($validation_result['has_warnings'] || $validation_result['has_errors']): ?>
        <div class="hp-validation-issues">
            <h4><?php esc_html_e('Validation Issues', 'heritagepress'); ?></h4>
            
            <table class="widefat hp-data-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Type', 'heritagepress'); ?></th>
                        <th><?php esc_html_e('Record', 'heritagepress'); ?></th>
                        <th><?php esc_html_e('Message', 'heritagepress'); ?></th>
                        <th><?php esc_html_e('Line', 'heritagepress'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($validation_result['warnings'] as $issue): ?>
                        <tr class="hp-validation-<?php echo esc_attr($issue['type']); ?>-row">
                            <td><?php echo esc_html(ucfirst($issue['type'])); ?></td>
                            <td><?php echo esc_html($issue['record_id'] . ' - ' . $issue['record_name']); ?></td>
                            <td><?php echo esc_html($issue['message']); ?></td>
                            <td><?php echo esc_html($issue['line']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <form method="post" id="hp-gedcom-import-form" class="hp-form" action="<?php echo esc_url(admin_url('admin.php?page=heritagepress-importexport&tab=import&step=3&file=' . $file_key)); ?>">
        <?php wp_nonce_field('hp_gedcom_import', 'hp_gedcom_import_nonce'); ?>
        
        <input type="hidden" name="tree_id" value="<?php echo esc_attr($tree_id); ?>">
        <input type="hidden" name="new_tree_name" value="<?php echo esc_attr($new_tree_name); ?>">
        <input type="hidden" name="import_option" value="<?php echo esc_attr($import_option); ?>">
        
        <h4><?php esc_html_e('Additional Import Options', 'heritagepress'); ?></h4>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php esc_html_e('Media Files', 'heritagepress'); ?>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e('Media Files', 'heritagepress'); ?></legend>
                        
                        <label>
                            <input type="checkbox" name="import_media" value="1" checked>
                            <?php esc_html_e('Import media files referenced in the GEDCOM', 'heritagepress'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Attempts to locate and import all media files referenced in the GEDCOM file.', 'heritagepress'); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e('Privacy', 'heritagepress'); ?>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e('Privacy', 'heritagepress'); ?></legend>
                        
                        <label>
                            <input type="checkbox" name="privacy_living" value="1" checked>
                            <?php esc_html_e('Mark living individuals as private', 'heritagepress'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Individuals without death dates and born less than 100 years ago will be marked private.', 'heritagepress'); ?>
                        </p>
                        
                        <label>
                            <input type="checkbox" name="privacy_notes" value="1">
                            <?php esc_html_e('Import private notes', 'heritagepress'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Import notes marked as private in the GEDCOM file.', 'heritagepress'); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <div class="hp-form-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-importexport&tab=import')); ?>" class="button">
                <?php esc_html_e('Back', 'heritagepress'); ?>
            </a>
            
            <button type="submit" class="button button-primary" id="hp-start-import" 
                <?php echo ($validation_result['has_errors']) ? 'disabled' : ''; ?>>
                <?php esc_html_e('Start Import', 'heritagepress'); ?>
            </button>
            
            <?php if ($validation_result['has_errors']): ?>
                <p class="hp-validation-error">
                    <?php esc_html_e('Please fix the errors before proceeding with the import.', 'heritagepress'); ?>
                </p>
            <?php endif; ?>
        </div>
    </form>
</div>
