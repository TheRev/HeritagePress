<?php
/**
 * Proof Argument Detail View
 * 
 * Displays detailed view of a proof argument with all supporting evidence,
 * reasoning, conclusion, and quality assessment.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$argument_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$argument = null;
$research_question = null;
$evidence_analyses = [];

if ($argument_id) {
    global $wpdb;
    
    // Get argument details
    $argument = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}hp_proof_arguments WHERE id = %d",
        $argument_id
    ));
    
    if ($argument) {
        // Get related research question
        $research_question = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hp_research_questions WHERE id = %d",
            $argument->research_question_id
        ));
        
        // Get evidence analyses
        $evidence_ids = json_decode($argument->evidence_analysis_ids, true) ?: [];
        if (!empty($evidence_ids)) {
            $placeholders = implode(',', array_fill(0, count($evidence_ids), '%d'));
            $evidence_analyses = $wpdb->get_results($wpdb->prepare(
                "SELECT ea.*, is_stmt.statement_text, is_stmt.source_citation
                 FROM {$wpdb->prefix}hp_evidence_analysis ea
                 LEFT JOIN {$wpdb->prefix}hp_information_statements is_stmt ON ea.information_statement_id = is_stmt.id
                 WHERE ea.id IN ($placeholders)
                 ORDER BY ea.confidence_score DESC",
                ...$evidence_ids
            ));
        }
    }
}

if (!$argument) {
    wp_die(__('Proof argument not found.', 'heritage-press'));
}

// Parse JSON fields
$argument_data = json_decode($argument->argument_data, true) ?: [];
$metadata = json_decode($argument->metadata, true) ?: [];

// Calculate overall quality metrics
$total_evidence = count($evidence_analyses);
$average_confidence = 0;
$confidence_distribution = ['high' => 0, 'medium' => 0, 'low' => 0];

if ($total_evidence > 0) {
    $total_score = 0;
    foreach ($evidence_analyses as $evidence) {
        $total_score += $evidence->confidence_score;
        $confidence_distribution[strtolower($evidence->confidence_level)]++;
    }
    $average_confidence = round($total_score / $total_evidence);
}

// Determine proof strength
$proof_strength = 'insufficient';
if ($average_confidence >= 80) {
    $proof_strength = 'strong';
} elseif ($average_confidence >= 60) {
    $proof_strength = 'moderate';
} elseif ($average_confidence >= 40) {
    $proof_strength = 'weak';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Proof Argument Details', 'heritage-press'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments'); ?>" class="page-title-action">
        <?php _e('← Back to Proof Arguments', 'heritage-press'); ?>
    </a>
    
    <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=edit&id=' . $argument_id); ?>" class="page-title-action">
        <?php _e('Edit Argument', 'heritage-press'); ?>
    </a>
    
    <hr class="wp-header-end">

    <div class="heritage-admin-content">
        <div class="heritage-detail-container">
            <!-- Main Content -->
            <div class="heritage-detail-main">
                <!-- Research Question -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h2><?php _e('Research Question', 'heritage-press'); ?></h2>
                        <div class="heritage-proof-badges">
                            <span class="heritage-proof-standard heritage-standard-<?php echo esc_attr($argument->proof_standard); ?>">
                                <?php 
                                $standard_labels = [
                                    'preponderance' => __('Preponderance', 'heritage-press'),
                                    'clear_and_convincing' => __('Clear & Convincing', 'heritage-press'),
                                    'beyond_reasonable_doubt' => __('Beyond Doubt', 'heritage-press')
                                ];
                                echo esc_html($standard_labels[$argument->proof_standard] ?? ucwords(str_replace('_', ' ', $argument->proof_standard)));
                                ?>
                            </span>
                            <span class="heritage-status-badge heritage-status-<?php echo esc_attr($argument->status); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $argument->status))); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="heritage-card-body">
                        <?php if ($research_question): ?>
                        <div class="heritage-question-display">
                            <h3><?php echo esc_html($research_question->question_text); ?></h3>
                            <?php if (!empty($research_question->category)): ?>
                            <div class="heritage-question-meta">
                                <span class="heritage-category-tag">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $research_question->category))); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Evidence Overview -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Evidence Summary', 'heritage-press'); ?></h3>
                        <div class="heritage-proof-strength heritage-strength-<?php echo esc_attr($proof_strength); ?>">
                            <?php echo esc_html(ucfirst($proof_strength)); ?> Proof
                        </div>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-evidence-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo esc_html($total_evidence); ?></div>
                                <div class="stat-label"><?php _e('Evidence Analyses', 'heritage-press'); ?></div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-number"><?php echo esc_html($average_confidence); ?>/100</div>
                                <div class="stat-label"><?php _e('Average Quality', 'heritage-press'); ?></div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-number"><?php echo esc_html($confidence_distribution['high']); ?></div>
                                <div class="stat-label"><?php _e('High Confidence', 'heritage-press'); ?></div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-number"><?php echo esc_html($confidence_distribution['medium']); ?></div>
                                <div class="stat-label"><?php _e('Medium Confidence', 'heritage-press'); ?></div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-number"><?php echo esc_html($confidence_distribution['low']); ?></div>
                                <div class="stat-label"><?php _e('Low Confidence', 'heritage-press'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Evidence Analyses -->
                <?php if (!empty($evidence_analyses)): ?>
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Supporting Evidence', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-evidence-list">
                            <?php foreach ($evidence_analyses as $index => $evidence): ?>
                            <div class="heritage-evidence-item">
                                <div class="evidence-header">
                                    <div class="evidence-number"><?php echo $index + 1; ?></div>
                                    <div class="evidence-quality">
                                        <span class="heritage-confidence-badge heritage-confidence-<?php echo esc_attr(strtolower($evidence->confidence_level)); ?>">
                                            <?php echo esc_html(ucfirst($evidence->confidence_level)); ?>
                                        </span>
                                        <span class="confidence-score"><?php echo esc_html($evidence->confidence_score); ?>/100</span>
                                    </div>
                                </div>
                                
                                <div class="evidence-content">
                                    <div class="evidence-statement">
                                        <strong><?php _e('Statement:', 'heritage-press'); ?></strong>
                                        <?php echo esc_html($evidence->statement_text); ?>
                                    </div>
                                    
                                    <div class="evidence-source">
                                        <strong><?php _e('Source:', 'heritage-press'); ?></strong>
                                        <?php echo esc_html($evidence->source_citation); ?>
                                    </div>
                                    
                                    <div class="evidence-analysis">
                                        <strong><?php _e('Analysis:', 'heritage-press'); ?></strong>
                                        <?php echo esc_html(wp_trim_words($evidence->analysis_text, 30)); ?>
                                    </div>
                                    
                                    <div class="evidence-actions">
                                        <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=view&id=' . $evidence->id); ?>" 
                                           class="button button-small">
                                            <?php _e('View Full Analysis', 'heritage-press'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Reasoning -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Reasoning', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-reasoning-text">
                            <?php echo wpautop(esc_html($argument->reasoning_text)); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Conclusion -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Conclusion', 'heritage-press'); ?></h3>
                        <?php if (!empty($metadata['confidence_level'])): ?>
                        <span class="heritage-confidence-badge heritage-confidence-<?php echo esc_attr($metadata['confidence_level']); ?>">
                            <?php echo esc_html(ucfirst($metadata['confidence_level'])); ?> Confidence
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-conclusion-text">
                            <?php echo wpautop(esc_html($argument->conclusion_text)); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="heritage-detail-sidebar">
                <!-- Argument Info -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Argument Info', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-meta-item">
                            <strong><?php _e('Proof Standard:', 'heritage-press'); ?></strong>
                            <?php 
                            $standard_descriptions = [
                                'preponderance' => __('Preponderance of Evidence (>50%)', 'heritage-press'),
                                'clear_and_convincing' => __('Clear and Convincing (~75%)', 'heritage-press'),
                                'beyond_reasonable_doubt' => __('Beyond Reasonable Doubt (~95%)', 'heritage-press')
                            ];
                            echo esc_html($standard_descriptions[$argument->proof_standard] ?? ucwords(str_replace('_', ' ', $argument->proof_standard)));
                            ?>
                        </div>
                        
                        <div class="heritage-meta-item">
                            <strong><?php _e('Status:', 'heritage-press'); ?></strong>
                            <span class="heritage-status-badge heritage-status-<?php echo esc_attr($argument->status); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $argument->status))); ?>
                            </span>
                        </div>
                        
                        <div class="heritage-meta-item">
                            <strong><?php _e('Created:', 'heritage-press'); ?></strong>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($argument->created_at))); ?>
                        </div>
                        
                        <div class="heritage-meta-item">
                            <strong><?php _e('Last Updated:', 'heritage-press'); ?></strong>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($argument->updated_at))); ?>
                        </div>
                        
                        <?php if (!empty($metadata['researcher'])): ?>
                        <div class="heritage-meta-item">
                            <strong><?php _e('Researcher:', 'heritage-press'); ?></strong>
                            <?php echo esc_html($metadata['researcher']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quality Assessment -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Quality Assessment', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-quality-meter">
                            <div class="quality-score">
                                <div class="score-circle heritage-quality-<?php echo esc_attr($proof_strength); ?>">
                                    <span class="score-number"><?php echo esc_html($average_confidence); ?></span>
                                    <span class="score-label">/ 100</span>
                                </div>
                            </div>
                            
                            <div class="quality-breakdown">
                                <div class="breakdown-item">
                                    <span class="breakdown-label"><?php _e('Evidence Count:', 'heritage-press'); ?></span>
                                    <span class="breakdown-value"><?php echo esc_html($total_evidence); ?></span>
                                </div>
                                
                                <div class="breakdown-item">
                                    <span class="breakdown-label"><?php _e('High Quality:', 'heritage-press'); ?></span>
                                    <span class="breakdown-value"><?php echo esc_html($confidence_distribution['high']); ?></span>
                                </div>
                                
                                <div class="breakdown-item">
                                    <span class="breakdown-label"><?php _e('Medium Quality:', 'heritage-press'); ?></span>
                                    <span class="breakdown-value"><?php echo esc_html($confidence_distribution['medium']); ?></span>
                                </div>
                                
                                <div class="breakdown-item">
                                    <span class="breakdown-label"><?php _e('Low Quality:', 'heritage-press'); ?></span>
                                    <span class="breakdown-value"><?php echo esc_html($confidence_distribution['low']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                        // Generate recommendations
                        $recommendations = [];
                        if ($total_evidence < 3) {
                            $recommendations[] = __('Consider gathering more evidence to strengthen the argument.', 'heritage-press');
                        }
                        if ($confidence_distribution['low'] > $confidence_distribution['high']) {
                            $recommendations[] = __('Focus on higher quality evidence sources.', 'heritage-press');
                        }
                        if ($average_confidence < 50) {
                            $recommendations[] = __('Current evidence may not meet the selected proof standard.', 'heritage-press');
                        }
                        ?>
                        
                        <?php if (!empty($recommendations)): ?>
                        <div class="heritage-recommendations">
                            <h4><?php _e('Recommendations', 'heritage-press'); ?></h4>
                            <ul>
                                <?php foreach ($recommendations as $recommendation): ?>
                                <li><?php echo esc_html($recommendation); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Actions', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-action-buttons">
                            <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=edit&id=' . $argument_id); ?>" 
                               class="button button-primary">
                                <?php _e('Edit Argument', 'heritage-press'); ?>
                            </a>
                            
                            <button type="button" class="button heritage-duplicate-argument" 
                                    data-argument-id="<?php echo esc_attr($argument_id); ?>">
                                <?php _e('Duplicate Argument', 'heritage-press'); ?>
                            </button>
                            
                            <button type="button" class="button heritage-export-argument" 
                                    data-argument-id="<?php echo esc_attr($argument_id); ?>">
                                <?php _e('Export to PDF', 'heritage-press'); ?>
                            </button>
                            
                            <button type="button" class="button heritage-print-argument">
                                <?php _e('Print', 'heritage-press'); ?>
                            </button>
                            
                            <button type="button" class="button button-link-delete heritage-delete-argument" 
                                    data-argument-id="<?php echo esc_attr($argument_id); ?>">
                                <?php _e('Delete Argument', 'heritage-press'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.heritage-detail-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.heritage-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 20px;
}

.heritage-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #c3c4c7;
    background: #f6f7f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.heritage-card-header h2,
.heritage-card-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.heritage-card-body {
    padding: 20px;
}

.heritage-proof-badges {
    display: flex;
    gap: 8px;
}

.heritage-proof-standard {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-standard-preponderance { background: #fff3cd; color: #664d03; }
.heritage-standard-clear_and_convincing { background: #cff4fc; color: #055160; }
.heritage-standard-beyond_reasonable_doubt { background: #d1e7dd; color: #0f5132; }

.heritage-status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-status-draft { background: #f0f0f1; color: #3c434a; }
.heritage-status-in_review { background: #fff3cd; color: #664d03; }
.heritage-status-complete { background: #d1e7dd; color: #0f5132; }

.heritage-question-display h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    line-height: 1.4;
}

.heritage-category-tag {
    display: inline-block;
    background: #f0f6fc;
    color: #0969da;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.heritage-proof-strength {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-strength-strong { background: #d1e7dd; color: #0f5132; }
.heritage-strength-moderate { background: #fff3cd; color: #664d03; }
.heritage-strength-weak { background: #f8d7da; color: #721c24; }
.heritage-strength-insufficient { background: #f0f0f1; color: #3c434a; }

.heritage-evidence-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #2271b1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    font-weight: 600;
}

.heritage-evidence-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.heritage-evidence-item {
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
}

.evidence-header {
    padding: 12px 15px;
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.evidence-number {
    width: 24px;
    height: 24px;
    background: #2271b1;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.evidence-quality {
    display: flex;
    align-items: center;
    gap: 8px;
}

.heritage-confidence-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-confidence-high { background: #d1e7dd; color: #0f5132; }
.heritage-confidence-medium { background: #fff3cd; color: #664d03; }
.heritage-confidence-low { background: #f8d7da; color: #721c24; }

.confidence-score {
    font-size: 12px;
    color: #646970;
    font-weight: 600;
}

.evidence-content {
    padding: 15px;
}

.evidence-statement,
.evidence-source,
.evidence-analysis {
    margin-bottom: 12px;
}

.evidence-actions {
    margin-top: 15px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f1;
}

.heritage-reasoning-text,
.heritage-conclusion-text {
    line-height: 1.6;
    color: #3c434a;
}

.heritage-meta-item {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f1;
}

.heritage-meta-item:last-child {
    border-bottom: none;
}

.heritage-quality-meter {
    text-align: center;
}

.quality-score {
    margin-bottom: 20px;
}

.score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    margin: 0 auto;
}

.heritage-quality-strong { background: #0f5132; }
.heritage-quality-moderate { background: #664d03; }
.heritage-quality-weak { background: #721c24; }
.heritage-quality-insufficient { background: #3c434a; }

.score-number {
    font-size: 20px;
    line-height: 1;
}

.score-label {
    font-size: 11px;
    opacity: 0.8;
}

.quality-breakdown {
    text-align: left;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.breakdown-item:last-child {
    border-bottom: none;
}

.breakdown-label {
    color: #646970;
    font-size: 13px;
}

.breakdown-value {
    font-weight: 600;
    color: #1d2327;
}

.heritage-recommendations {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f1;
}

.heritage-recommendations h4 {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #1d2327;
}

.heritage-recommendations ul {
    margin: 0;
    padding-left: 20px;
}

.heritage-recommendations li {
    margin-bottom: 8px;
    font-size: 13px;
    color: #3c434a;
    line-height: 1.4;
}

.heritage-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.heritage-action-buttons .button {
    justify-content: center;
}

@media (max-width: 1024px) {
    .heritage-detail-container {
        grid-template-columns: 1fr;
    }
    
    .heritage-evidence-stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media print {
    .heritage-action-buttons,
    .page-title-action,
    .heritage-card-header button {
        display: none !important;
    }
    
    .heritage-detail-container {
        grid-template-columns: 1fr;
    }
    
    .heritage-card {
        break-inside: avoid;
        margin-bottom: 30px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Duplicate argument
    $('.heritage-duplicate-argument').on('click', function() {
        const argumentId = $(this).data('argument-id');
        if (confirm('<?php _e('Create a duplicate of this proof argument?', 'heritage-press'); ?>')) {
            window.location.href = '<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=duplicate&id='); ?>' + argumentId;
        }
    });
    
    // Export argument
    $('.heritage-export-argument').on('click', function() {
        const argumentId = $(this).data('argument-id');
        window.location.href = '<?php echo admin_url('admin-ajax.php?action=heritage_export_proof_argument&id='); ?>' + argumentId;
    });
    
    // Print argument
    $('.heritage-print-argument').on('click', function() {
        window.print();
    });
    
    // Delete argument
    $('.heritage-delete-argument').on('click', function() {
        const argumentId = $(this).data('argument-id');
        if (confirm('<?php _e('Are you sure you want to delete this proof argument? This action cannot be undone.', 'heritage-press'); ?>')) {
            $.post(ajaxurl, {
                action: 'heritage_delete_proof_argument',
                argument_id: argumentId,
                nonce: '<?php echo wp_create_nonce('heritage_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    window.location.href = '<?php echo admin_url('admin.php?page=heritage-proof-arguments'); ?>';
                } else {
                    alert(response.data || '<?php _e('Error deleting proof argument.', 'heritage-press'); ?>');
                }
            });
        }
    });
});
</script>
