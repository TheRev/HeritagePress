<?php
/**
 * Import Step 4: Results
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get file key from URL
$file_key = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';

// Check if this is an error case
$has_error = isset($_GET['error']) && $_GET['error'] == '1';

if ($has_error) {
    // Show error state
    $import_results = array(
        'tree_id' => null,
        'tree_name' => 'Import Failed',
        'duration' => 0,
        'records_processed' => 0,
        'individuals' => 0,
        'families' => 0,
        'sources' => 0,
        'media' => 0,
        'notes' => 0,
        'repositories' => 0,
        'errors' => array(
            'Database schema error: Missing external_id columns in wp_hp_individuals and wp_hp_families tables',
            'Please run the database schema update to add the required external_id columns',
            'You can fix this by running: ALTER TABLE wp_hp_individuals ADD COLUMN external_id VARCHAR(50) NULL;',
            'And: ALTER TABLE wp_hp_families ADD COLUMN external_id VARCHAR(50) NULL;'
        ),
        'warnings' => array(),
    );
} else {
    // For demo purposes - this would be real data in production
    $import_results = array(
        'tree_id' => 1,
        'tree_name' => 'Smith Family Tree',
        'duration' => 65, // seconds
        'records_processed' => 462,
        'individuals' => 250,
        'families' => 85,
        'sources' => 43,
        'media' => 12,
        'notes' => 67,
        'repositories' => 5,
        'errors' => array(),
        'warnings' => array(
            'Could not import 3 media files due to invalid paths',
            'Found 5 individuals with incomplete birth information'
        ),
    );
}

?>
<div class="hp-import-container">
    <?php if ($has_error): ?>
        <h3><?php esc_html_e('Import Failed', 'heritagepress'); ?></h3>

        <div class="hp-notice hp-notice-error">
            <p>
                <span class="dashicons dashicons-no-alt"></span>
                <?php esc_html_e('The GEDCOM import encountered errors and could not complete.', 'heritagepress'); ?>
            </p>
        </div>
    <?php else: ?>
        <h3><?php esc_html_e('Import Complete', 'heritagepress'); ?></h3>

        <div class="hp-notice hp-notice-success">
            <p>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('The GEDCOM file was successfully imported.', 'heritagepress'); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="hp-import-summary">
        <h4><?php esc_html_e('Import Summary', 'heritagepress'); ?></h4>

        <table class="widefat hp-data-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Tree Name', 'heritagepress'); ?></th>
                    <td>
                        <strong><?php echo esc_html($import_results['tree_name']); ?></strong>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-trees&tree_id=' . $import_results['tree_id'])); ?>"
                            class="button button-small">
                            <?php esc_html_e('View Tree', 'heritagepress'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Import Duration', 'heritagepress'); ?></th>
                    <td>
                        <?php
                        $minutes = floor($import_results['duration'] / 60);
                        $seconds = $import_results['duration'] % 60;
                        printf(
                            esc_html__('%d minutes, %d seconds', 'heritagepress'),
                            $minutes,
                            $seconds
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Records Processed', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($import_results['records_processed']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Individuals', 'heritagepress'); ?></th>
                    <td>
                        <?php echo esc_html($import_results['individuals']); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-individuals&tree_id=' . $import_results['tree_id'])); ?>"
                            class="button button-small">
                            <?php esc_html_e('View Individuals', 'heritagepress'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Families', 'heritagepress'); ?></th>
                    <td>
                        <?php echo esc_html($import_results['families']); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-families&tree_id=' . $import_results['tree_id'])); ?>"
                            class="button button-small">
                            <?php esc_html_e('View Families', 'heritagepress'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Sources', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($import_results['sources']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Media Objects', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($import_results['media']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Notes', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($import_results['notes']); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Repositories', 'heritagepress'); ?></th>
                    <td><?php echo esc_html($import_results['repositories']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if (!empty($import_results['warnings'])): ?>
        <div class="hp-import-warnings">
            <h4><?php esc_html_e('Warnings', 'heritagepress'); ?></h4>

            <ul class="hp-warning-list">
                <?php foreach ($import_results['warnings'] as $warning): ?>
                    <li>
                        <span class="dashicons dashicons-warning"></span>
                        <?php echo esc_html($warning); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($import_results['errors'])): ?>
        <div class="hp-import-errors">
            <h4><?php esc_html_e('Errors', 'heritagepress'); ?></h4>

            <ul class="hp-error-list">
                <?php foreach ($import_results['errors'] as $error): ?>
                    <li>
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php echo esc_html($error); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <div class="hp-form-actions">
        <?php if ($has_error): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-importexport&tab=import&step=1')); ?>"
                class="button">
                <?php esc_html_e('Try Again', 'heritagepress'); ?>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-importexport&tab=logs')); ?>"
                class="button">
                <?php esc_html_e('View Error Log', 'heritagepress'); ?>
            </a>

            <a href="http://localhost/wordpress/add-external-id-columns.php" class="button button-primary" target="_blank">
                <?php esc_html_e('Fix Database Schema', 'heritagepress'); ?>
            </a>
        <?php else: ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-importexport&tab=import')); ?>"
                class="button">
                <?php esc_html_e('New Import', 'heritagepress'); ?>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-trees&tree_id=' . $import_results['tree_id'])); ?>"
                class="button button-primary">
                <?php esc_html_e('View Imported Tree', 'heritagepress'); ?>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress-importexport&tab=logs')); ?>"
                class="button">
                <?php esc_html_e('View Import Log', 'heritagepress'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>