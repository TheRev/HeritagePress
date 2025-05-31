<?php
/**
 * Evidence Analysis Detail View
 * 
 * Displays detailed view of an evidence analysis with quality assessment,
 * related information statements, and proof arguments.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$analysis_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$analysis = null;
$information_statement = null;
$related_analyses = [];
$proof_arguments = [];

if ($analysis_id) {
    global $wpdb;
    
    // Get analysis details
    $analysis = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}hp_evidence_analysis WHERE id = %d",
        $analysis_id
    ));
    
    if ($analysis) {
        // Get related information statement
        $information_statement = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hp_information_statements WHERE id = %d",
            $analysis->information_statement_id
        ));
        
        // Get other analyses for the same information statement
        $related_analyses = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hp_evidence_analysis 
             WHERE information_statement_id = %d AND id != %d
             ORDER BY created_at DESC",
            $analysis->information_statement_id,
            $analysis_id
        ));
        
        // Get proof arguments that reference this analysis
        $proof_arguments = $wpdb->get_results($wpdb->prepare(
            "SELECT pa.*, rq.question_text 
             FROM {$wpdb->prefix}hp_proof_arguments pa
             LEFT JOIN {$wpdb->prefix}hp_research_questions rq ON pa.research_question_id = rq.id
             WHERE pa.evidence_analysis_ids LIKE %s
             ORDER BY pa.created_at DESC",
            '%"' . $analysis_id . '"%'
        ));
    }
}

if (!$analysis) {
    wp_die(__('Evidence analysis not found.', 'heritage-press'));
}

// Parse JSON fields
$analysis_data = json_decode($analysis->analysis_data, true) ?: [];
$quality_factors = json_decode($analysis->quality_factors, true) ?: [];
$metadata = json_decode($analysis->metadata, true) ?: [];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Evidence Analysis Details', 'heritage-press'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis'); ?>" class="page-title-action">
        <?php _e('← Back to Evidence Analysis', 'heritage-press'); ?>
    </a>
    
    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=edit&id=' . $analysis_id); ?>" class="page-title-action">
        <?php _e('Edit Analysis', 'heritage-press'); ?>
    </a>
    
    <hr class="wp-header-end">

    <div class="heritage-admin-content">
        <div class="heritage-detail-container">
            <!-- Main Analysis Details -->
            <div class="heritage-detail-main">
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h2><?php _e('Analysis Overview', 'heritage-press'); ?></h2>
                        <div class="heritage-quality-badge heritage-quality-<?php echo esc_attr(strtolower($analysis->confidence_level)); ?>">
                            <?php echo esc_html(ucfirst($analysis->confidence_level)); ?> Confidence
                        </div>
                    </div>
                    
                    <div class="heritage-card-body">
                        <?php if ($information_statement): ?>
                        <div class="heritage-field-group">
                            <label><?php _e('Information Statement', 'heritage-press'); ?></label>
                            <div class="heritage-statement-preview">
                                <p><strong><?php echo esc_html($information_statement->statement_text); ?></strong></p>
                                <small class="heritage-muted">
                                    Source: <?php echo esc_html($information_statement->source_citation); ?>
                                </small>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="heritage-field-group">
                            <label><?php _e('Analysis Text', 'heritage-press'); ?></label>
                            <div class="heritage-analysis-text">
                                <?php echo wpautop(esc_html($analysis->analysis_text)); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($analysis_data['strengths'])): ?>
                        <div class="heritage-field-group">
                            <label><?php _e('Evidence Strengths', 'heritage-press'); ?></label>
                            <ul class="heritage-strength-list">
                                <?php foreach ($analysis_data['strengths'] as $strength): ?>
                                <li><?php echo esc_html($strength); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($analysis_data['weaknesses'])): ?>
                        <div class="heritage-field-group">
                            <label><?php _e('Evidence Weaknesses', 'heritage-press'); ?></label>
                            <ul class="heritage-weakness-list">
                                <?php foreach ($analysis_data['weaknesses'] as $weakness): ?>
                                <li><?php echo esc_html($weakness); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($analysis_data['conclusions'])): ?>
                        <div class="heritage-field-group">
                            <label><?php _e('Conclusions', 'heritage-press'); ?></label>
                            <div class="heritage-conclusions">
                                <?php echo wpautop(esc_html($analysis_data['conclusions'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quality Assessment -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Quality Assessment', 'heritage-press'); ?></h3>
                        <span class="heritage-confidence-score">
                            Score: <?php echo esc_html($analysis->confidence_score); ?>/100
                        </span>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-quality-breakdown">
                            <?php if (!empty($quality_factors)): ?>
                            <div class="heritage-quality-factors">
                                <?php foreach ($quality_factors as $factor => $value): ?>
                                <div class="heritage-quality-factor">
                                    <span class="factor-name"><?php echo esc_html(ucwords(str_replace('_', ' ', $factor))); ?>:</span>
                                    <span class="factor-value"><?php echo esc_html($value); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="heritage-confidence-bar">
                                <div class="heritage-confidence-fill heritage-confidence-<?php echo esc_attr(strtolower($analysis->confidence_level)); ?>"
                                     style="width: <?php echo esc_attr($analysis->confidence_score); ?>%">
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($analysis_data['assessment_notes'])): ?>
                        <div class="heritage-assessment-notes">
                            <label><?php _e('Assessment Notes', 'heritage-press'); ?></label>
                            <p><?php echo esc_html($analysis_data['assessment_notes']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="heritage-detail-sidebar">
                <!-- Analysis Metadata -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Analysis Info', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-meta-item">
                            <strong><?php _e('Type:', 'heritage-press'); ?></strong>
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $analysis->analysis_type))); ?>
                        </div>
                        
                        <div class="heritage-meta-item">
                            <strong><?php _e('Created:', 'heritage-press'); ?></strong>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($analysis->created_at))); ?>
                        </div>
                        
                        <div class="heritage-meta-item">
                            <strong><?php _e('Last Updated:', 'heritage-press'); ?></strong>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($analysis->updated_at))); ?>
                        </div>
                        
                        <?php if (!empty($metadata['researcher'])): ?>
                        <div class="heritage-meta-item">
                            <strong><?php _e('Researcher:', 'heritage-press'); ?></strong>
                            <?php echo esc_html($metadata['researcher']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($metadata['review_status'])): ?>
                        <div class="heritage-meta-item">
                            <strong><?php _e('Review Status:', 'heritage-press'); ?></strong>
                            <span class="heritage-status-badge heritage-status-<?php echo esc_attr($metadata['review_status']); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $metadata['review_status']))); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Related Analyses -->
                <?php if (!empty($related_analyses)): ?>
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Related Analyses', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-related-list">
                            <?php foreach ($related_analyses as $related): ?>
                            <div class="heritage-related-item">
                                <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=view&id=' . $related->id); ?>">
                                    <div class="related-title">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $related->analysis_type))); ?> Analysis
                                    </div>
                                    <div class="related-confidence">
                                        <?php echo esc_html(ucfirst($related->confidence_level)); ?> Confidence
                                    </div>
                                    <div class="related-date">
                                        <?php echo esc_html(date_i18n('M j, Y', strtotime($related->created_at))); ?>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Proof Arguments -->
                <?php if (!empty($proof_arguments)): ?>
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Proof Arguments Using This Analysis', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-proof-list">
                            <?php foreach ($proof_arguments as $proof): ?>
                            <div class="heritage-proof-item">
                                <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=view&id=' . $proof->id); ?>">
                                    <div class="proof-question">
                                        <?php echo esc_html($proof->question_text); ?>
                                    </div>
                                    <div class="proof-conclusion">
                                        <?php echo esc_html(wp_trim_words($proof->conclusion_text, 15)); ?>
                                    </div>
                                    <div class="proof-date">
                                        <?php echo esc_html(date_i18n('M j, Y', strtotime($proof->created_at))); ?>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="heritage-card">
                    <div class="heritage-card-header">
                        <h3><?php _e('Actions', 'heritage-press'); ?></h3>
                    </div>
                    
                    <div class="heritage-card-body">
                        <div class="heritage-action-buttons">
                            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=edit&id=' . $analysis_id); ?>" 
                               class="button button-primary">
                                <?php _e('Edit Analysis', 'heritage-press'); ?>
                            </a>
                            
                            <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=new&analysis_id=' . $analysis_id); ?>" 
                               class="button">
                                <?php _e('Create Proof Argument', 'heritage-press'); ?>
                            </a>
                            
                            <button type="button" class="button heritage-duplicate-analysis" 
                                    data-analysis-id="<?php echo esc_attr($analysis_id); ?>">
                                <?php _e('Duplicate Analysis', 'heritage-press'); ?>
                            </button>
                            
                            <button type="button" class="button heritage-export-analysis" 
                                    data-analysis-id="<?php echo esc_attr($analysis_id); ?>">
                                <?php _e('Export', 'heritage-press'); ?>
                            </button>
                            
                            <button type="button" class="button button-link-delete heritage-delete-analysis" 
                                    data-analysis-id="<?php echo esc_attr($analysis_id); ?>">
                                <?php _e('Delete Analysis', 'heritage-press'); ?>
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

.heritage-quality-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-quality-high { background: #d1e7dd; color: #0f5132; }
.heritage-quality-medium { background: #fff3cd; color: #664d03; }
.heritage-quality-low { background: #f8d7da; color: #721c24; }

.heritage-field-group {
    margin-bottom: 20px;
}

.heritage-field-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1d2327;
}

.heritage-statement-preview {
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #2271b1;
}

.heritage-analysis-text {
    line-height: 1.6;
    color: #3c434a;
}

.heritage-strength-list,
.heritage-weakness-list {
    margin: 0;
    padding-left: 20px;
}

.heritage-strength-list li {
    color: #0f5132;
    margin-bottom: 5px;
}

.heritage-weakness-list li {
    color: #721c24;
    margin-bottom: 5px;
}

.heritage-conclusions {
    background: #f0f6fc;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #0969da;
}

.heritage-confidence-score {
    font-weight: 600;
    color: #2271b1;
}

.heritage-quality-factors {
    margin-bottom: 15px;
}

.heritage-quality-factor {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f1;
}

.heritage-confidence-bar {
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
}

.heritage-confidence-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.heritage-confidence-high { background: #0f5132; }
.heritage-confidence-medium { background: #664d03; }
.heritage-confidence-low { background: #721c24; }

.heritage-meta-item {
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f1;
}

.heritage-status-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-status-draft { background: #f0f0f1; color: #3c434a; }
.heritage-status-review { background: #fff3cd; color: #664d03; }
.heritage-status-approved { background: #d1e7dd; color: #0f5132; }

.heritage-related-list,
.heritage-proof-list {
    margin: 0;
}

.heritage-related-item,
.heritage-proof-item {
    border-bottom: 1px solid #f0f0f1;
    padding: 10px 0;
}

.heritage-related-item:last-child,
.heritage-proof-item:last-child {
    border-bottom: none;
}

.heritage-related-item a,
.heritage-proof-item a {
    text-decoration: none;
    color: inherit;
}

.heritage-related-item a:hover,
.heritage-proof-item a:hover {
    color: #2271b1;
}

.related-title,
.proof-question {
    font-weight: 600;
    margin-bottom: 4px;
}

.related-confidence,
.related-date,
.proof-conclusion,
.proof-date {
    font-size: 12px;
    color: #646970;
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
}
</style>

<script>
jQuery(document).ready(function($) {
    // Duplicate analysis
    $('.heritage-duplicate-analysis').on('click', function() {
        const analysisId = $(this).data('analysis-id');
        if (confirm('<?php _e('Create a duplicate of this analysis?', 'heritage-press'); ?>')) {
            window.location.href = '<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=duplicate&id='); ?>' + analysisId;
        }
    });
    
    // Export analysis
    $('.heritage-export-analysis').on('click', function() {
        const analysisId = $(this).data('analysis-id');
        window.location.href = '<?php echo admin_url('admin-ajax.php?action=heritage_export_analysis&id='); ?>' + analysisId;
    });
    
    // Delete analysis
    $('.heritage-delete-analysis').on('click', function() {
        const analysisId = $(this).data('analysis-id');
        if (confirm('<?php _e('Are you sure you want to delete this analysis? This action cannot be undone.', 'heritage-press'); ?>')) {
            $.post(ajaxurl, {
                action: 'heritage_delete_analysis',
                analysis_id: analysisId,
                nonce: '<?php echo wp_create_nonce('heritage_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    window.location.href = '<?php echo admin_url('admin.php?page=heritage-evidence-analysis'); ?>';
                } else {
                    alert(response.data || '<?php _e('Error deleting analysis.', 'heritage-press'); ?>');
                }
            });
        }
    });
});
</script>
