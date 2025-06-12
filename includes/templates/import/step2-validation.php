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

// Get real GEDCOM analysis instead of dummy data
$validation_result = null;
if (!empty($file_key)) {
    // Get the uploaded file path - use same path structure as ImportHandler  
    $upload_info = wp_upload_dir();
    $heritagepress_dir = $upload_info['basedir'] . '/heritagepress/gedcom/';
    $gedcom_file = $heritagepress_dir . $file_key . '.ged';

    if (file_exists($gedcom_file)) {
        // Load the ImportHandler to analyze the file
        $import_handler_path = dirname(dirname(dirname(__FILE__))) . '/Admin/ImportExport/ImportHandler.php';
        if (file_exists($import_handler_path)) {
            require_once($import_handler_path);
            $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();

            try {
                // Get real analysis data
                $analysis = $import_handler->analyze_gedcom_file($gedcom_file);

                if (!isset($analysis['error'])) {
                    $validation_result = $analysis;
                }
            } catch (Exception $e) {
                error_log('Error analyzing GEDCOM file: ' . $e->getMessage());
            }
        }
    }
}

// Fallback to dummy data if analysis fails
if ($validation_result === null) {
    $validation_result = array(
        'gedcom_version' => '5.5.1',
        'source_system' => 'Unknown',
        'encoding' => 'UTF-8',
        'has_errors' => false,
        'has_warnings' => true,
        'error_count' => 0,
        'warning_count' => 1,
        'individuals' => 0,
        'families' => 0,
        'sources' => 0,
        'media' => 0,
        'notes' => 0,
        'repositories' => 0,
        'events' => 0,
        'total_records' => 0,
        'date_range' => array('earliest' => null, 'latest' => null),
        'places' => array(),
        'surnames' => array(),
        'warnings' => array(
            array(
                'type' => 'warning',
                'message' => 'Could not analyze GEDCOM file - using upload data for import',
                'record_id' => '',
                'record_name' => '',
                'line' => 0
            )
        ),
        'errors' => array()
    );
}

// Add calculated fields
$validation_result['has_errors'] = !empty($validation_result['errors']);
$validation_result['has_warnings'] = !empty($validation_result['warnings']);
$validation_result['error_count'] = count($validation_result['errors']);
$validation_result['warning_count'] = count($validation_result['warnings']);

// Calculate total records if not set
if (!isset($validation_result['total_records'])) {
    $validation_result['total_records'] = $validation_result['individuals'] +
        $validation_result['families'] +
        $validation_result['sources'] +
        $validation_result['media'] +
        $validation_result['notes'] +
        $validation_result['repositories'];
}

// Prepare top surnames and places arrays
$top_surnames = !empty($validation_result['surnames']) ? array_keys($validation_result['surnames']) : array();
$top_places = !empty($validation_result['places']) ? array_keys($validation_result['places']) : array();
?>

<div class="heritagepress-admin-page">
    <div class="heritagepress-header">
        <h1><?php echo esc_html__('Import GEDCOM File', 'heritagepress'); ?></h1>
        <p class="description"><?php echo esc_html__('Step 2: Validation and Preview', 'heritagepress'); ?></p>
    </div>

    <div class="heritagepress-import-progress">
        <div class="progress-step completed">
            <span class="step-number">1</span>
            <span class="step-title"><?php echo esc_html__('Upload', 'heritagepress'); ?></span>
        </div>
        <div class="progress-step active">
            <span class="step-number">2</span>
            <span class="step-title"><?php echo esc_html__('Validation', 'heritagepress'); ?></span>
        </div>
        <div class="progress-step">
            <span class="step-number">3</span>
            <span class="step-title"><?php echo esc_html__('Import', 'heritagepress'); ?></span>
        </div>
    </div>

    <div class="heritagepress-card">
        <h2><?php echo esc_html__('GEDCOM File Analysis', 'heritagepress'); ?></h2>

        <div class="analysis-grid">
            <div class="analysis-section">
                <h3><?php echo esc_html__('File Information', 'heritagepress'); ?></h3>
                <div class="analysis-stats">
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('GEDCOM Version:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo esc_html($validation_result['gedcom_version']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Source System:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo esc_html($validation_result['source_system']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span
                            class="stat-label"><?php echo esc_html__('Character Encoding:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo esc_html($validation_result['encoding']); ?></span>
                    </div>
                    <?php if (!empty($validation_result['date_range']['earliest']) && !empty($validation_result['date_range']['latest'])): ?>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo esc_html__('Date Range:', 'heritagepress'); ?></span>
                            <span
                                class="stat-value"><?php echo esc_html($validation_result['date_range']['earliest'] . ' - ' . $validation_result['date_range']['latest']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="analysis-section">
                <h3><?php echo esc_html__('Record Counts', 'heritagepress'); ?></h3>
                <div class="analysis-stats">
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Individuals:', 'heritagepress'); ?></span>
                        <span
                            class="stat-value highlight"><?php echo number_format($validation_result['individuals']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Families:', 'heritagepress'); ?></span>
                        <span
                            class="stat-value highlight"><?php echo number_format($validation_result['families']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Sources:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo number_format($validation_result['sources']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Events:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo number_format($validation_result['events']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Media Objects:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo number_format($validation_result['media']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Notes:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo number_format($validation_result['notes']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo esc_html__('Repositories:', 'heritagepress'); ?></span>
                        <span class="stat-value"><?php echo number_format($validation_result['repositories']); ?></span>
                    </div>
                    <div class="stat-item total">
                        <span class="stat-label"><?php echo esc_html__('Total Records:', 'heritagepress'); ?></span>
                        <span
                            class="stat-value"><?php echo number_format($validation_result['total_records']); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($top_surnames)): ?>
                <div class="analysis-section">
                    <h3><?php echo esc_html__('Top Surnames', 'heritagepress'); ?></h3>
                    <div class="analysis-list">
                        <?php foreach (array_slice($top_surnames, 0, 5) as $surname): ?>
                            <div class="list-item">
                                <span class="item-name"><?php echo esc_html($surname); ?></span>
                                <span
                                    class="item-count"><?php echo number_format($validation_result['surnames'][$surname]); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($top_places)): ?>
                <div class="analysis-section">
                    <h3><?php echo esc_html__('Top Places', 'heritagepress'); ?></h3>
                    <div class="analysis-list">
                        <?php foreach (array_slice($top_places, 0, 5) as $place): ?>
                            <div class="list-item">
                                <span class="item-name"><?php echo esc_html($place); ?></span>
                                <span
                                    class="item-count"><?php echo number_format($validation_result['places'][$place]); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="heritagepress-card">
        <h2><?php echo esc_html__('Import Destination', 'heritagepress'); ?></h2>
        <div class="import-destination-info">
            <?php if ($tree_id === 'new'): ?>
                <div class="destination-item">
                    <span class="destination-label"><?php echo esc_html__('Destination:', 'heritagepress'); ?></span>
                    <span class="destination-value"><?php echo esc_html__('New Tree', 'heritagepress'); ?></span>
                </div>
                <div class="destination-item">
                    <span class="destination-label"><?php echo esc_html__('Tree Name:', 'heritagepress'); ?></span>
                    <span class="destination-value"><?php echo esc_html($new_tree_name); ?></span>
                </div>
            <?php else: ?>
                <div class="destination-item">
                    <span class="destination-label"><?php echo esc_html__('Destination:', 'heritagepress'); ?></span>
                    <span class="destination-value">
                        <?php
                        if (!empty($selected_tree_name)) {
                            echo esc_html($selected_tree_name);
                        } else {
                            echo esc_html__('Existing Tree (ID: ', 'heritagepress') . esc_html($tree_id) . ')';
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            <div class="destination-item">
                <span class="destination-label"><?php echo esc_html__('Import Method:', 'heritagepress'); ?></span>
                <span class="destination-value">
                    <?php
                    switch ($import_option) {
                        case 'replace':
                            echo esc_html__('Replace existing data', 'heritagepress');
                            break;
                        case 'merge':
                            echo esc_html__('Merge with existing data', 'heritagepress');
                            break;
                        case 'append':
                            echo esc_html__('Append to existing data', 'heritagepress');
                            break;
                        default:
                            echo esc_html($import_option);
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>

    <?php if ($validation_result['has_errors'] || $validation_result['has_warnings']): ?>
        <div class="heritagepress-card">
            <h2><?php echo esc_html__('Validation Issues', 'heritagepress'); ?></h2>

            <div class="validation-summary">
                <?php if ($validation_result['has_errors']): ?>
                    <div class="validation-stat error">
                        <span class="validation-icon">❌</span>
                        <span class="validation-text">
                            <?php printf(
                                _n('%d Error Found', '%d Errors Found', $validation_result['error_count'], 'heritagepress'),
                                $validation_result['error_count']
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($validation_result['has_warnings']): ?>
                    <div class="validation-stat warning">
                        <span class="validation-icon">⚠️</span>
                        <span class="validation-text">
                            <?php printf(
                                _n('%d Warning Found', '%d Warnings Found', $validation_result['warning_count'], 'heritagepress'),
                                $validation_result['warning_count']
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($validation_result['errors'])): ?>
                <div class="validation-issues errors">
                    <h3><?php echo esc_html__('Errors', 'heritagepress'); ?></h3>
                    <?php foreach ($validation_result['errors'] as $error): ?>
                        <div class="validation-issue error">
                            <div class="issue-header">
                                <span class="issue-type">Error</span>
                                <?php if (!empty($error['line'])): ?>
                                    <span class="issue-line">Line <?php echo esc_html($error['line']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="issue-message"><?php echo esc_html($error['message']); ?></div>
                            <?php if (!empty($error['record_name'])): ?>
                                <div class="issue-context">Record: <?php echo esc_html($error['record_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($validation_result['warnings'])): ?>
                <div class="validation-issues warnings">
                    <h3><?php echo esc_html__('Warnings', 'heritagepress'); ?></h3>
                    <?php foreach ($validation_result['warnings'] as $warning): ?>
                        <div class="validation-issue warning">
                            <div class="issue-header">
                                <span class="issue-type">Warning</span>
                                <?php if (!empty($warning['line'])): ?>
                                    <span class="issue-line">Line <?php echo esc_html($warning['line']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="issue-message"><?php echo esc_html($warning['message']); ?></div>
                            <?php if (!empty($warning['record_name'])): ?>
                                <div class="issue-context">Record: <?php echo esc_html($warning['record_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="heritagepress-card success">
            <h2><?php echo esc_html__('Validation Complete', 'heritagepress'); ?></h2>
            <div class="validation-success">
                <span class="validation-icon">✅</span>
                <span
                    class="validation-text"><?php echo esc_html__('No validation issues found. The GEDCOM file is ready for import.', 'heritagepress'); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="heritagepress-actions">
        <a href="<?php echo admin_url('admin.php?page=heritagepress-import'); ?>" class="button button-secondary">
            <?php echo esc_html__('← Back to Upload', 'heritagepress'); ?>
        </a> <?php if (!$validation_result['has_errors']): ?>
            <form method="post"
                action="<?php echo admin_url('admin.php?page=heritagepress-importexport&step=3&file=' . urlencode($file_key)); ?>"
                style="display: inline;">
                <?php wp_nonce_field('hp_gedcom_nonce', 'hp_gedcom_nonce'); ?>
                <input type="hidden" name="tree_id" value="<?php echo esc_attr($tree_id); ?>">
                <input type="hidden" name="new_tree_name" value="<?php echo esc_attr($new_tree_name); ?>">
                <input type="hidden" name="import_option" value="<?php echo esc_attr($import_option); ?>">
                <button type="submit" class="button button-primary">
                    <?php echo esc_html__('Continue to Import →', 'heritagepress'); ?>
                </button>
            </form>
        <?php else: ?>
            <p class="validation-block-message">
                <?php echo esc_html__('Please fix the errors above before continuing with the import.', 'heritagepress'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<style>
    .analysis-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .analysis-section {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    .analysis-section h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
        font-size: 16px;
    }

    .analysis-stats {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
    }

    .stat-item.total {
        border-top: 1px solid #ddd;
        padding-top: 10px;
        margin-top: 10px;
        font-weight: bold;
    }

    .stat-label {
        color: #666;
    }

    .stat-value {
        font-weight: bold;
        color: #333;
    }

    .stat-value.highlight {
        color: #0073aa;
        font-size: 1.1em;
    }

    .analysis-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }

    .list-item:last-child {
        border-bottom: none;
    }

    .item-name {
        color: #333;
    }

    .item-count {
        font-weight: bold;
        color: #0073aa;
        background: #e1f5fe;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 0.9em;
    }

    .validation-summary {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .validation-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        border-radius: 5px;
        font-weight: bold;
    }

    .validation-stat.error {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }

    .validation-stat.warning {
        background: #fff8e1;
        color: #f57c00;
        border: 1px solid #ffecb3;
    }

    .validation-issues {
        margin-bottom: 20px;
    }

    .validation-issues h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .validation-issue {
        margin-bottom: 15px;
        padding: 15px;
        border-radius: 5px;
        border-left: 4px solid;
    }

    .validation-issue.error {
        background: #ffebee;
        border-left-color: #c62828;
    }

    .validation-issue.warning {
        background: #fff8e1;
        border-left-color: #f57c00;
    }

    .issue-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .issue-type {
        font-weight: bold;
        text-transform: uppercase;
        font-size: 0.85em;
    }

    .issue-line {
        font-size: 0.9em;
        color: #666;
    }

    .issue-message {
        font-weight: bold;
        margin-bottom: 5px;
    }

    .issue-context {
        font-size: 0.9em;
        color: #666;
    }

    .validation-success {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 20px;
        background: #e8f5e8;
        border-radius: 5px;
        color: #2e7d32;
        font-weight: bold;
    }

    .validation-block-message {
        color: #c62828;
        font-weight: bold;
        margin-left: 10px;
    }

    .heritagepress-actions {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }

    .import-destination-info {
        padding: 15px;
        background: #f0f8ff;
        border-radius: 5px;
        border: 1px solid #007cba;
    }

    .destination-item {
        display: flex;
        margin-bottom: 10px;
        align-items: center;
    }

    .destination-item:last-child {
        margin-bottom: 0;
    }

    .destination-label {
        font-weight: 600;
        min-width: 140px;
        color: #555;
    }

    .destination-value {
        font-weight: 500;
        color: #333;
    }
</style>