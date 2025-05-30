<?php
/**
 * Proof Argument Form View
 * 
 * Form for creating and editing proof arguments following Elizabeth Shown Mills'
 * Evidence Explained methodology for constructing genealogical proof.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$argument_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$research_question_id = isset($_GET['research_question_id']) ? intval($_GET['research_question_id']) : 0;
$analysis_id = isset($_GET['analysis_id']) ? intval($_GET['analysis_id']) : 0;
$is_edit = $argument_id > 0;
$argument = null;

if ($is_edit) {
    global $wpdb;
    $argument = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}hp_proof_arguments WHERE id = %d",
        $argument_id
    ));
    
    if (!$argument) {
        wp_die(__('Proof argument not found.', 'heritage-press'));
    }
    
    $research_question_id = $argument->research_question_id;
}

// Get research questions for dropdown
global $wpdb;
$research_questions = $wpdb->get_results(
    "SELECT id, question_text, category FROM {$wpdb->prefix}hp_research_questions ORDER BY question_text"
);

// Get available evidence analyses
$evidence_analyses = [];
if ($research_question_id) {
    $evidence_analyses = $wpdb->get_results($wpdb->prepare(
        "SELECT ea.*, is_stmt.statement_text, is_stmt.source_citation
         FROM {$wpdb->prefix}hp_evidence_analysis ea
         LEFT JOIN {$wpdb->prefix}hp_information_statements is_stmt ON ea.information_statement_id = is_stmt.id
         LEFT JOIN {$wpdb->prefix}hp_research_questions rq ON is_stmt.research_question_id = rq.id
         WHERE rq.id = %d
         ORDER BY ea.confidence_score DESC, ea.created_at DESC",
        $research_question_id
    ));
}

// Parse existing data if editing
$argument_data = [];
$selected_evidence = [];
$metadata = [];

if ($argument) {
    $argument_data = json_decode($argument->argument_data, true) ?: [];
    $selected_evidence = json_decode($argument->evidence_analysis_ids, true) ?: [];
    $metadata = json_decode($argument->metadata, true) ?: [];
}

// Pre-select evidence if coming from analysis page
if ($analysis_id && !$is_edit) {
    $selected_evidence = [$analysis_id];
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $is_edit ? __('Edit Proof Argument', 'heritage-press') : __('New Proof Argument', 'heritage-press'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments'); ?>" class="page-title-action">
        <?php _e('← Back to Proof Arguments', 'heritage-press'); ?>
    </a>
    
    <hr class="wp-header-end">

    <div class="heritage-admin-content">
        <form id="heritage-proof-argument-form" method="post">
            <?php wp_nonce_field('heritage_save_proof_argument', 'heritage_proof_nonce'); ?>
            <input type="hidden" name="argument_id" value="<?php echo esc_attr($argument_id); ?>">
            
            <div class="heritage-form-container">
                <!-- Main Form -->
                <div class="heritage-form-main">
                    <!-- Research Question Selection -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Research Question', 'heritage-press'); ?></h3>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div class="heritage-field-group">
                                <label for="research-question-id" class="required">
                                    <?php _e('Select Research Question', 'heritage-press'); ?>
                                </label>
                                <select name="research_question_id" id="research-question-id" required>
                                    <option value=""><?php _e('Choose a research question...', 'heritage-press'); ?></option>
                                    <?php foreach ($research_questions as $question): ?>
                                    <option value="<?php echo esc_attr($question->id); ?>" 
                                            <?php selected($research_question_id, $question->id); ?>
                                            data-category="<?php echo esc_attr($question->category); ?>">
                                        <?php echo esc_html($question->question_text); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="heritage-help-text">
                                    <?php _e('The research question this proof argument addresses.', 'heritage-press'); ?>
                                </small>
                            </div>
                            
                            <div id="question-preview" class="heritage-question-preview" style="display: none;">
                                <div class="question-text"></div>
                                <div class="question-category"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Evidence Selection -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Evidence Analysis', 'heritage-press'); ?></h3>
                            <button type="button" id="refresh-evidence" class="button button-small" style="display: none;">
                                <?php _e('Refresh Evidence', 'heritage-press'); ?>
                            </button>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div class="heritage-field-group">
                                <label><?php _e('Select Evidence Analyses', 'heritage-press'); ?></label>
                                <div id="evidence-selection-container">
                                    <div class="heritage-no-question-message">
                                        <?php _e('Please select a research question first to see available evidence analyses.', 'heritage-press'); ?>
                                    </div>
                                </div>
                                <small class="heritage-help-text">
                                    <?php _e('Choose the evidence analyses that support this proof argument.', 'heritage-press'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Proof Standard -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Proof Standard', 'heritage-press'); ?></h3>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div class="heritage-field-group">
                                <label><?php _e('Standard of Proof', 'heritage-press'); ?></label>
                                <div class="heritage-proof-standards">
                                    <label class="heritage-radio-label">
                                        <input type="radio" name="proof_standard" value="preponderance" 
                                               <?php checked($argument->proof_standard ?? '', 'preponderance'); ?>>
                                        <div class="radio-content">
                                            <strong><?php _e('Preponderance of Evidence', 'heritage-press'); ?></strong>
                                            <small><?php _e('More likely than not (>50% certainty)', 'heritage-press'); ?></small>
                                        </div>
                                    </label>
                                    
                                    <label class="heritage-radio-label">
                                        <input type="radio" name="proof_standard" value="clear_and_convincing" 
                                               <?php checked($argument->proof_standard ?? '', 'clear_and_convincing'); ?>>
                                        <div class="radio-content">
                                            <strong><?php _e('Clear and Convincing Evidence', 'heritage-press'); ?></strong>
                                            <small><?php _e('High probability and well-founded belief (~75% certainty)', 'heritage-press'); ?></small>
                                        </div>
                                    </label>
                                    
                                    <label class="heritage-radio-label">
                                        <input type="radio" name="proof_standard" value="beyond_reasonable_doubt" 
                                               <?php checked($argument->proof_standard ?? '', 'beyond_reasonable_doubt'); ?>>
                                        <div class="radio-content">
                                            <strong><?php _e('Beyond a Reasonable Doubt', 'heritage-press'); ?></strong>
                                            <small><?php _e('Very high certainty (~95% certainty)', 'heritage-press'); ?></small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reasoning -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Reasoning', 'heritage-press'); ?></h3>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div class="heritage-field-group">
                                <label for="reasoning-text" class="required">
                                    <?php _e('Detailed Reasoning', 'heritage-press'); ?>
                                </label>
                                <textarea name="reasoning_text" id="reasoning-text" rows="8" required
                                          placeholder="<?php _e('Explain how the evidence supports your conclusion. Include logical connections, address contradictions, and show why this evidence is sufficient for the chosen proof standard.', 'heritage-press'); ?>"><?php echo esc_textarea($argument->reasoning_text ?? ''); ?></textarea>
                                <small class="heritage-help-text">
                                    <?php _e('Provide a thorough analysis of how your evidence leads to the conclusion.', 'heritage-press'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Conclusion -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Conclusion', 'heritage-press'); ?></h3>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div class="heritage-field-group">
                                <label for="conclusion-text" class="required">
                                    <?php _e('Conclusion Statement', 'heritage-press'); ?>
                                </label>
                                <textarea name="conclusion_text" id="conclusion-text" rows="4" required
                                          placeholder="<?php _e('State your conclusion clearly and definitively.', 'heritage-press'); ?>"><?php echo esc_textarea($argument->conclusion_text ?? ''); ?></textarea>
                                <small class="heritage-help-text">
                                    <?php _e('A clear, concise statement of what you have proven.', 'heritage-press'); ?>
                                </small>
                            </div>
                            
                            <div class="heritage-field-group">
                                <label for="confidence-level"><?php _e('Confidence Level', 'heritage-press'); ?></label>
                                <select name="confidence_level" id="confidence-level">
                                    <option value=""><?php _e('Auto-assess based on evidence', 'heritage-press'); ?></option>
                                    <option value="high" <?php selected($metadata['confidence_level'] ?? '', 'high'); ?>>
                                        <?php _e('High Confidence', 'heritage-press'); ?>
                                    </option>
                                    <option value="medium" <?php selected($metadata['confidence_level'] ?? '', 'medium'); ?>>
                                        <?php _e('Medium Confidence', 'heritage-press'); ?>
                                    </option>
                                    <option value="low" <?php selected($metadata['confidence_level'] ?? '', 'low'); ?>>
                                        <?php _e('Low Confidence', 'heritage-press'); ?>
                                    </option>
                                </select>
                                <small class="heritage-help-text">
                                    <?php _e('Leave blank to automatically assess based on evidence quality.', 'heritage-press'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="heritage-form-sidebar">
                    <!-- Status -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Status', 'heritage-press'); ?></h3>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div class="heritage-field-group">
                                <label for="status"><?php _e('Argument Status', 'heritage-press'); ?></label>
                                <select name="status" id="status">
                                    <option value="draft" <?php selected($argument->status ?? 'draft', 'draft'); ?>>
                                        <?php _e('Draft', 'heritage-press'); ?>
                                    </option>
                                    <option value="in_review" <?php selected($argument->status ?? '', 'in_review'); ?>>
                                        <?php _e('In Review', 'heritage-press'); ?>
                                    </option>
                                    <option value="complete" <?php selected($argument->status ?? '', 'complete'); ?>>
                                        <?php _e('Complete', 'heritage-press'); ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="heritage-field-group">
                                <label for="researcher"><?php _e('Researcher', 'heritage-press'); ?></label>
                                <input type="text" name="researcher" id="researcher" 
                                       value="<?php echo esc_attr($metadata['researcher'] ?? get_current_user()->display_name); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Auto-Assessment -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Quality Assessment', 'heritage-press'); ?></h3>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div id="assessment-results" class="heritage-assessment-results">
                                <div class="heritage-assessment-placeholder">
                                    <?php _e('Select evidence to see quality assessment', 'heritage-press'); ?>
                                </div>
                            </div>
                            
                            <button type="button" id="run-assessment" class="button button-secondary" 
                                    style="display: none;">
                                <?php _e('Run Assessment', 'heritage-press'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Preview -->
                    <div class="heritage-card">
                        <div class="heritage-card-header">
                            <h3><?php _e('Preview', 'heritage-press'); ?></h3>
                        </div>
                        
                        <div class="heritage-card-body">
                            <div id="argument-preview" class="heritage-argument-preview">
                                <div class="preview-placeholder">
                                    <?php _e('Fill out the form to see a preview', 'heritage-press'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="heritage-card">
                        <div class="heritage-card-body">
                            <div class="heritage-form-actions">
                                <input type="submit" name="save_draft" class="button" 
                                       value="<?php _e('Save Draft', 'heritage-press'); ?>">
                                
                                <input type="submit" name="save_complete" class="button button-primary" 
                                       value="<?php echo $is_edit ? __('Update Argument', 'heritage-press') : __('Create Argument', 'heritage-press'); ?>">
                                
                                <?php if ($is_edit): ?>
                                <button type="button" class="button heritage-duplicate-argument" 
                                        data-argument-id="<?php echo esc_attr($argument_id); ?>">
                                    <?php _e('Duplicate', 'heritage-press'); ?>
                                </button>
                                
                                <button type="button" class="button button-link-delete heritage-delete-argument" 
                                        data-argument-id="<?php echo esc_attr($argument_id); ?>">
                                    <?php _e('Delete', 'heritage-press'); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.heritage-form-container {
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

.heritage-card-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.heritage-card-body {
    padding: 20px;
}

.heritage-field-group {
    margin-bottom: 20px;
}

.heritage-field-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1d2327;
}

.heritage-field-group label.required::after {
    content: " *";
    color: #d63638;
}

.heritage-field-group input[type="text"],
.heritage-field-group input[type="email"],
.heritage-field-group select,
.heritage-field-group textarea {
    width: 100%;
    max-width: 100%;
}

.heritage-help-text {
    display: block;
    margin-top: 5px;
    color: #646970;
    font-style: italic;
}

.heritage-question-preview {
    background: #f0f6fc;
    border: 1px solid #c9d6e7;
    border-radius: 4px;
    padding: 15px;
    margin-top: 15px;
}

.question-text {
    font-weight: 600;
    margin-bottom: 8px;
}

.question-category {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
}

.heritage-no-question-message {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
    font-style: italic;
}

.heritage-evidence-list {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.heritage-evidence-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.heritage-evidence-item:last-child {
    border-bottom: none;
}

.heritage-evidence-item input[type="checkbox"] {
    margin-top: 2px;
}

.heritage-evidence-content {
    flex: 1;
}

.evidence-statement {
    font-weight: 600;
    margin-bottom: 5px;
}

.evidence-source {
    font-size: 12px;
    color: #646970;
    margin-bottom: 8px;
}

.evidence-quality {
    display: flex;
    align-items: center;
    gap: 8px;
}

.heritage-confidence-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-confidence-high { background: #d1e7dd; color: #0f5132; }
.heritage-confidence-medium { background: #fff3cd; color: #664d03; }
.heritage-confidence-low { background: #f8d7da; color: #721c24; }

.heritage-proof-standards {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.heritage-radio-label {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.heritage-radio-label:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.heritage-radio-label input[type="radio"] {
    margin: 2px 0 0 0;
}

.radio-content strong {
    display: block;
    margin-bottom: 4px;
}

.radio-content small {
    color: #646970;
}

.heritage-assessment-results {
    min-height: 100px;
}

.heritage-assessment-placeholder {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
    font-style: italic;
}

.heritage-argument-preview {
    min-height: 200px;
    font-size: 13px;
    line-height: 1.5;
}

.preview-placeholder {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
    font-style: italic;
}

.heritage-form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.heritage-form-actions .button {
    justify-content: center;
}

@media (max-width: 1024px) {
    .heritage-form-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Research question change handler
    $('#research-question-id').on('change', function() {
        const questionId = $(this).val();
        const questionText = $(this).find(':selected').text();
        const questionCategory = $(this).find(':selected').data('category');
        
        if (questionId) {
            // Show question preview
            $('#question-preview').show();
            $('.question-text').text(questionText);
            $('.question-category').text(questionCategory || '');
            
            // Load evidence analyses
            loadEvidenceAnalyses(questionId);
            $('#refresh-evidence').show();
        } else {
            $('#question-preview').hide();
            $('#evidence-selection-container').html('<div class="heritage-no-question-message"><?php _e('Please select a research question first to see available evidence analyses.', 'heritage-press'); ?></div>');
            $('#refresh-evidence').hide();
        }
        
        updatePreview();
    });
    
    // Refresh evidence button
    $('#refresh-evidence').on('click', function() {
        const questionId = $('#research-question-id').val();
        if (questionId) {
            loadEvidenceAnalyses(questionId);
        }
    });
    
    // Load evidence analyses for a research question
    function loadEvidenceAnalyses(questionId) {
        $.post(ajaxurl, {
            action: 'heritage_get_evidence_analyses',
            research_question_id: questionId,
            nonce: '<?php echo wp_create_nonce('heritage_admin_nonce'); ?>'
        }, function(response) {
            if (response.success && response.data.analyses) {
                displayEvidenceAnalyses(response.data.analyses);
            } else {
                $('#evidence-selection-container').html('<div class="heritage-no-question-message"><?php _e('No evidence analyses found for this research question.', 'heritage-press'); ?></div>');
            }
        });
    }
    
    // Display evidence analyses
    function displayEvidenceAnalyses(analyses) {
        let html = '<div class="heritage-evidence-list">';
        
        if (analyses.length === 0) {
            html += '<div class="heritage-no-question-message"><?php _e('No evidence analyses found for this research question.', 'heritage-press'); ?></div>';
        } else {
            analyses.forEach(function(analysis) {
                const selected = <?php echo json_encode($selected_evidence); ?>.includes(analysis.id.toString());
                
                html += '<div class="heritage-evidence-item">';
                html += '<input type="checkbox" name="evidence_analysis_ids[]" value="' + analysis.id + '"' + (selected ? ' checked' : '') + '>';
                html += '<div class="heritage-evidence-content">';
                html += '<div class="evidence-statement">' + analysis.statement_text + '</div>';
                html += '<div class="evidence-source">' + analysis.source_citation + '</div>';
                html += '<div class="evidence-quality">';
                html += '<span class="heritage-confidence-badge heritage-confidence-' + analysis.confidence_level.toLowerCase() + '">';
                html += analysis.confidence_level + ' confidence';
                html += '</span>';
                html += '<span>Score: ' + analysis.confidence_score + '/100</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
        }
        
        html += '</div>';
        $('#evidence-selection-container').html(html);
        
        // Bind change event to evidence checkboxes
        $('input[name="evidence_analysis_ids[]"]').on('change', function() {
            updateAssessment();
            updatePreview();
        });
    }
    
    // Form change handlers for preview
    $('#reasoning-text, #conclusion-text, input[name="proof_standard"]').on('input change', function() {
        updatePreview();
    });
    
    // Update preview
    function updatePreview() {
        const questionText = $('#research-question-id').find(':selected').text();
        const proofStandard = $('input[name="proof_standard"]:checked').val();
        const reasoning = $('#reasoning-text').val();
        const conclusion = $('#conclusion-text').val();
        const selectedEvidence = $('input[name="evidence_analysis_ids[]"]:checked').length;
        
        if (!questionText || questionText === '<?php _e('Choose a research question...', 'heritage-press'); ?>') {
            $('#argument-preview').html('<div class="preview-placeholder"><?php _e('Fill out the form to see a preview', 'heritage-press'); ?></div>');
            return;
        }
        
        let html = '<div class="preview-content">';
        html += '<h4>Research Question</h4>';
        html += '<p>' + questionText + '</p>';
        
        if (selectedEvidence > 0) {
            html += '<h4>Evidence</h4>';
            html += '<p>' + selectedEvidence + ' evidence analyses selected</p>';
        }
        
        if (proofStandard) {
            html += '<h4>Proof Standard</h4>';
            html += '<p>' + $('input[name="proof_standard"]:checked').siblings('.radio-content').find('strong').text() + '</p>';
        }
        
        if (reasoning) {
            html += '<h4>Reasoning</h4>';
            html += '<p>' + reasoning.substring(0, 200) + (reasoning.length > 200 ? '...' : '') + '</p>';
        }
        
        if (conclusion) {
            html += '<h4>Conclusion</h4>';
            html += '<p>' + conclusion + '</p>';
        }
        
        html += '</div>';
        $('#argument-preview').html(html);
    }
    
    // Update assessment
    function updateAssessment() {
        const selectedEvidence = [];
        $('input[name="evidence_analysis_ids[]"]:checked').each(function() {
            selectedEvidence.push($(this).val());
        });
        
        if (selectedEvidence.length === 0) {
            $('#assessment-results').html('<div class="heritage-assessment-placeholder"><?php _e('Select evidence to see quality assessment', 'heritage-press'); ?></div>');
            $('#run-assessment').hide();
            return;
        }
        
        $('#run-assessment').show();
    }
    
    // Run assessment
    $('#run-assessment').on('click', function() {
        const selectedEvidence = [];
        $('input[name="evidence_analysis_ids[]"]:checked').each(function() {
            selectedEvidence.push($(this).val());
        });
        
        $.post(ajaxurl, {
            action: 'heritage_assess_proof_strength',
            evidence_ids: selectedEvidence,
            nonce: '<?php echo wp_create_nonce('heritage_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayAssessmentResults(response.data);
            }
        });
    });
    
    // Display assessment results
    function displayAssessmentResults(assessment) {
        let html = '<div class="assessment-content">';
        html += '<h4>Overall Strength: ' + assessment.overall_confidence + '</h4>';
        html += '<p>Score: ' + assessment.average_score + '/100</p>';
        
        if (assessment.recommendations && assessment.recommendations.length > 0) {
            html += '<h4>Recommendations:</h4>';
            html += '<ul>';
            assessment.recommendations.forEach(function(rec) {
                html += '<li>' + rec + '</li>';
            });
            html += '</ul>';
        }
        
        html += '</div>';
        $('#assessment-results').html(html);
    }
    
    // Form submission
    $('#heritage-proof-argument-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'heritage_save_proof_argument');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=view&id='); ?>' + response.data.argument_id;
                } else {
                    alert(response.data || '<?php _e('Error saving proof argument.', 'heritage-press'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Error saving proof argument.', 'heritage-press'); ?>');
            }
        });
    });
    
    // Initialize
    <?php if ($research_question_id): ?>
    loadEvidenceAnalyses(<?php echo $research_question_id; ?>);
    <?php endif; ?>
    
    updatePreview();
});
</script>
