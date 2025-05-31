<?php
/**
 * Evidence Analysis Form View
 * 
 * @package Heritage_Press
 */

if (!defined('ABSPATH')) {
    exit;
}

$analysis_id = intval($_GET['analysis_id'] ?? 0);
$information_statement_id = intval($_GET['information_statement_id'] ?? 0);
$research_question_id = intval($_GET['research_question_id'] ?? 0);
$is_edit = !empty($analysis);
$page_title = $is_edit ? 'Edit Evidence Analysis' : 'Create Evidence Analysis';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis'); ?>" class="page-title-action">Back to List</a>
    
    <hr class="wp-header-end">

    <form id="evidence-analysis-form" method="post">
        <?php wp_nonce_field('heritage_evidence_nonce', 'heritage_evidence_nonce'); ?>
        <input type="hidden" name="analysis_id" value="<?php echo esc_attr($analysis_id); ?>">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- Analysis Details -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Analysis Details</h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="research_question_id">Research Question *</label>
                                    </th>
                                    <td>
                                        <select name="research_question_id" id="research_question_id" class="regular-text" required>
                                            <option value="">Select Research Question</option>
                                            <?php
                                            $question_repo = new Research_Question_Repository();
                                            $research_questions = $question_repo->get_all();
                                            foreach ($research_questions as $question):
                                                $selected = '';
                                                if ($is_edit && $analysis->research_question_id == $question->id) {
                                                    $selected = 'selected';
                                                } elseif (!$is_edit && $research_question_id == $question->id) {
                                                    $selected = 'selected';
                                                }
                                            ?>
                                                <option value="<?php echo esc_attr($question->id); ?>" <?php echo $selected; ?>>
                                                    <?php echo esc_html($question->question_text); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">The research question this analysis addresses.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="information_statement_id">Information Statement *</label>
                                    </th>
                                    <td>
                                        <select name="information_statement_id" id="information_statement_id" class="regular-text" required>
                                            <option value="">Select Information Statement</option>
                                            <?php
                                            $info_repo = new Information_Statement_Repository();
                                            $statements = $info_repo->get_all();
                                            foreach ($statements as $statement):
                                                $selected = '';
                                                if ($is_edit && $analysis->information_statement_id == $statement->id) {
                                                    $selected = 'selected';
                                                } elseif (!$is_edit && $information_statement_id == $statement->id) {
                                                    $selected = 'selected';
                                                }
                                            ?>
                                                <option value="<?php echo esc_attr($statement->id); ?>" <?php echo $selected; ?>>
                                                    <?php echo esc_html(substr($statement->statement_text, 0, 80)) . (strlen($statement->statement_text) > 80 ? '...' : ''); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">The information statement being analyzed.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="evidence_type">Evidence Type *</label>
                                    </th>
                                    <td>
                                        <select name="evidence_type" id="evidence_type" class="regular-text" required>
                                            <option value="">Select Evidence Type</option>
                                            <option value="direct" <?php echo ($is_edit && $analysis->evidence_type === 'direct') ? 'selected' : ''; ?>>Direct Evidence</option>
                                            <option value="indirect" <?php echo ($is_edit && $analysis->evidence_type === 'indirect') ? 'selected' : ''; ?>>Indirect Evidence</option>
                                            <option value="circumstantial" <?php echo ($is_edit && $analysis->evidence_type === 'circumstantial') ? 'selected' : ''; ?>>Circumstantial Evidence</option>
                                            <option value="negative" <?php echo ($is_edit && $analysis->evidence_type === 'negative') ? 'selected' : ''; ?>>Negative Evidence</option>
                                        </select>
                                        <p class="description">Classification of evidence according to Evidence Explained methodology.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="analysis_text">Analysis Text *</label>
                                    </th>
                                    <td>
                                        <textarea name="analysis_text" id="analysis_text" rows="12" class="large-text" required><?php echo $is_edit ? esc_textarea($analysis->analysis_text) : ''; ?></textarea>
                                        <p class="description">Detailed analysis of the evidence, including methodology, interpretation, and conclusions.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Evidence Assessment -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Evidence Assessment</h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="weight_assessment">Weight Assessment</label>
                                    </th>
                                    <td>
                                        <select name="weight_assessment" id="weight_assessment" class="regular-text">
                                            <option value="">Select Weight</option>
                                            <option value="substantial" <?php echo ($is_edit && $analysis->weight_assessment === 'substantial') ? 'selected' : ''; ?>>Substantial</option>
                                            <option value="moderate" <?php echo ($is_edit && $analysis->weight_assessment === 'moderate') ? 'selected' : ''; ?>>Moderate</option>
                                            <option value="limited" <?php echo ($is_edit && $analysis->weight_assessment === 'limited') ? 'selected' : ''; ?>>Limited</option>
                                            <option value="minimal" <?php echo ($is_edit && $analysis->weight_assessment === 'minimal') ? 'selected' : ''; ?>>Minimal</option>
                                        </select>
                                        <p class="description">The evidential weight this information carries.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="reliability_factors">Reliability Factors</label>
                                    </th>
                                    <td>
                                        <textarea name="reliability_factors" id="reliability_factors" rows="4" class="large-text"><?php echo $is_edit ? esc_textarea($analysis->reliability_factors) : ''; ?></textarea>
                                        <p class="description">Factors that enhance or diminish the reliability of this evidence.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="contradictions">Contradictions</label>
                                    </th>
                                    <td>
                                        <textarea name="contradictions" id="contradictions" rows="4" class="large-text"><?php echo $is_edit ? esc_textarea($analysis->contradictions) : ''; ?></textarea>
                                        <p class="description">Any contradictions with other evidence or known facts.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="corroboration">Corroboration</label>
                                    </th>
                                    <td>
                                        <textarea name="corroboration" id="corroboration" rows="4" class="large-text"><?php echo $is_edit ? esc_textarea($analysis->corroboration) : ''; ?></textarea>
                                        <p class="description">Other evidence that supports or corroborates this information.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="quality_assessment">Quality Assessment</label>
                                    </th>
                                    <td>
                                        <select name="quality_assessment" id="quality_assessment" class="regular-text">
                                            <option value="">Auto-assess based on factors</option>
                                            <option value="high" <?php echo ($is_edit && $analysis->quality_assessment === 'high') ? 'selected' : ''; ?>>High Quality</option>
                                            <option value="medium" <?php echo ($is_edit && $analysis->quality_assessment === 'medium') ? 'selected' : ''; ?>>Medium Quality</option>
                                            <option value="low" <?php echo ($is_edit && $analysis->quality_assessment === 'low') ? 'selected' : ''; ?>>Low Quality</option>
                                            <option value="questionable" <?php echo ($is_edit && $analysis->quality_assessment === 'questionable') ? 'selected' : ''; ?>>Questionable</option>
                                        </select>
                                        <p class="description">Overall assessment of evidence quality.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="confidence_level">Confidence Level</label>
                                    </th>
                                    <td>
                                        <div class="confidence-input-group">
                                            <input type="range" name="confidence_level" id="confidence_level" min="0" max="100" value="<?php echo $is_edit ? esc_attr($analysis->confidence_level) : '50'; ?>" class="confidence-slider">
                                            <span class="confidence-display"><?php echo $is_edit ? esc_html($analysis->confidence_level) : '50'; ?>%</span>
                                        </div>
                                        <p class="description">Your confidence level in this evidence (0-100%).</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <!-- Save Actions -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Save Analysis</h2>
                        </div>
                        <div class="inside">
                            <div class="submitbox">
                                <div id="save-action">
                                    <input type="submit" name="save_draft" id="save-draft" class="button" value="Save Draft">
                                </div>
                                
                                <div id="publishing-action">
                                    <input type="submit" name="publish" id="publish" class="button button-primary button-large" value="<?php echo $is_edit ? 'Update Analysis' : 'Save Analysis'; ?>">
                                </div>
                                
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Analysis Tools -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Analysis Tools</h2>
                        </div>
                        <div class="inside">
                            <p>
                                <button type="button" id="auto-assess-quality" class="button button-secondary" style="width: 100%; margin-bottom: 10px;">
                                    Auto-Assess Quality
                                </button>
                            </p>
                            
                            <p>
                                <button type="button" id="suggest-analysis" class="button button-secondary" style="width: 100%; margin-bottom: 10px;">
                                    Suggest Analysis Points
                                </button>
                            </p>
                            
                            <?php if ($is_edit): ?>
                                <p>
                                    <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=add&evidence_ids[]=' . $analysis->id); ?>" class="button button-secondary" style="width: 100%; text-align: center;">
                                        Create Proof Argument
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Evidence Quality Factors -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Quality Factors</h2>
                        </div>
                        <div class="inside">
                            <div class="quality-factors">
                                <div class="factor-group">
                                    <h4>Source Quality</h4>
                                    <label><input type="radio" name="source_quality" value="original"> Original</label><br>
                                    <label><input type="radio" name="source_quality" value="early_copy"> Early Copy</label><br>
                                    <label><input type="radio" name="source_quality" value="later_copy"> Later Copy</label><br>
                                    <label><input type="radio" name="source_quality" value="derivative"> Derivative</label>
                                </div>
                                
                                <div class="factor-group">
                                    <h4>Temporal Proximity</h4>
                                    <label><input type="radio" name="temporal_proximity" value="contemporary"> Contemporary</label><br>
                                    <label><input type="radio" name="temporal_proximity" value="within_year"> Within Year</label><br>
                                    <label><input type="radio" name="temporal_proximity" value="within_decade"> Within Decade</label><br>
                                    <label><input type="radio" name="temporal_proximity" value="later"> Much Later</label>
                                </div>
                                
                                <div class="factor-group">
                                    <h4>Informant Knowledge</h4>
                                    <label><input type="radio" name="informant_knowledge" value="participant"> Direct Participant</label><br>
                                    <label><input type="radio" name="informant_knowledge" value="observer"> Direct Observer</label><br>
                                    <label><input type="radio" name="informant_knowledge" value="informed"> Informed Observer</label><br>
                                    <label><input type="radio" name="informant_knowledge" value="hearsay"> Hearsay</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File Association -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">File Association</h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="file_id">Associated File</label>
                                    </th>
                                    <td>
                                        <input type="text" name="file_id" id="file_id" value="<?php echo $is_edit ? esc_attr($analysis->file_id) : ''; ?>" class="regular-text">
                                        <p class="description">File ID or identifier in Heritage Press system.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Analysis Preview -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Analysis Preview</h2>
                        </div>
                        <div class="inside">
                            <div id="analysis-preview">
                                <p><em>Enter analysis text to see preview...</em></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.confidence-input-group {
    display: flex;
    align-items: center;
    gap: 15px;
}

.confidence-slider {
    flex: 1;
    max-width: 200px;
}

.confidence-display {
    font-weight: 600;
    color: #1976d2;
    min-width: 40px;
}

.quality-factors {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.factor-group h4 {
    margin: 0 0 10px 0;
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

.factor-group label {
    display: block;
    font-size: 12px;
    margin-bottom: 5px;
    cursor: pointer;
}

.factor-group input[type="radio"] {
    margin-right: 8px;
}

.submitbox {
    padding: 12px;
}

#save-action, #publishing-action {
    margin-bottom: 10px;
}

#publishing-action {
    text-align: center;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}

#analysis-preview {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    min-height: 80px;
    font-size: 13px;
    line-height: 1.6;
}

#analysis-preview.has-content {
    background: #fff;
}

.form-table th {
    width: 150px;
    vertical-align: top;
    padding-top: 15px;
}

.form-table td {
    padding-top: 10px;
}

.evidence-analysis-help {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.evidence-analysis-help h4 {
    margin: 0 0 10px 0;
    color: #0073aa;
}

.evidence-analysis-help ul {
    margin: 0;
    padding-left: 20px;
}

.evidence-analysis-help li {
    margin-bottom: 5px;
    color: #333;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Form submission handling
    $('#evidence-analysis-form').on('submit', function(e) {
        e.preventDefault();
        saveEvidenceAnalysis();
    });

    // Live preview
    $('#analysis_text').on('input', function() {
        updatePreview();
    });

    // Confidence level slider
    $('#confidence_level').on('input', function() {
        $('.confidence-display').text($(this).val() + '%');
    });

    // Auto-assess quality
    $('#auto-assess-quality').on('click', function() {
        autoAssessQuality();
    });

    // Suggest analysis points
    $('#suggest-analysis').on('click', function() {
        suggestAnalysisPoints();
    });

    // Quality factors change
    $('input[name^="source_quality"], input[name^="temporal_proximity"], input[name^="informant_knowledge"]').on('change', function() {
        calculateConfidenceLevel();
    });

    // Update preview on load if editing
    updatePreview();

    function updatePreview() {
        var text = $('#analysis_text').val().trim();
        var preview = $('#analysis-preview');
        
        if (text) {
            preview.html(escapeHtml(text).replace(/\n/g, '<br>')).addClass('has-content');
        } else {
            preview.html('<em>Enter analysis text to see preview...</em>').removeClass('has-content');
        }
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function saveEvidenceAnalysis() {
        var formData = {
            action: 'save_evidence_analysis',
            nonce: $('#heritage_evidence_nonce').val(),
            analysis_id: $('input[name="analysis_id"]').val(),
            research_question_id: $('#research_question_id').val(),
            information_statement_id: $('#information_statement_id').val(),
            evidence_type: $('#evidence_type').val(),
            analysis_text: $('#analysis_text').val(),
            weight_assessment: $('#weight_assessment').val(),
            reliability_factors: $('#reliability_factors').val(),
            contradictions: $('#contradictions').val(),
            corroboration: $('#corroboration').val(),
            quality_assessment: $('#quality_assessment').val(),
            confidence_level: $('#confidence_level').val(),
            file_id: $('#file_id').val()
        };

        // Disable form during submission
        $('#evidence-analysis-form input, #evidence-analysis-form select, #evidence-analysis-form textarea').prop('disabled', true);
        $('#publish').val('Saving...').prop('disabled', true);

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>Evidence analysis saved successfully.</p></div>');
                
                setTimeout(function() {
                    window.location.href = '<?php echo admin_url('admin.php?page=heritage-evidence-analysis'); ?>';
                }, 1500);
            } else {
                $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>Error: ' + response.data.message + '</p></div>');
                
                // Re-enable form
                $('#evidence-analysis-form input, #evidence-analysis-form select, #evidence-analysis-form textarea').prop('disabled', false);
                $('#publish').val('<?php echo $is_edit ? 'Update Analysis' : 'Save Analysis'; ?>').prop('disabled', false);
            }
        }).fail(function() {
            $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>Network error. Please try again.</p></div>');
            
            // Re-enable form
            $('#evidence-analysis-form input, #evidence-analysis-form select, #evidence-analysis-form textarea').prop('disabled', false);
            $('#publish').val('<?php echo $is_edit ? 'Update Analysis' : 'Save Analysis'; ?>').prop('disabled', false);
        });
    }

    function autoAssessQuality() {
        var sourceQuality = $('input[name="source_quality"]:checked').val();
        var temporalProximity = $('input[name="temporal_proximity"]:checked').val();
        var informantKnowledge = $('input[name="informant_knowledge"]:checked').val();

        if (!sourceQuality || !temporalProximity || !informantKnowledge) {
            alert('Please select all quality factors first.');
            return;
        }

        $.post(ajaxurl, {
            action: 'assess_evidence_quality',
            nonce: $('#heritage_evidence_nonce').val(),
            analysis_id: $('input[name="analysis_id"]').val(),
            original_vs_derivative: sourceQuality,
            proximity_to_event: temporalProximity,
            informant_knowledge: informantKnowledge,
            bias_factors: $('#contradictions').val()
        }, function(response) {
            if (response.success) {
                $('#confidence_level').val(response.data.confidence_level);
                $('.confidence-display').text(response.data.confidence_level + '%');
                
                // Set quality assessment based on confidence level
                var confidence = response.data.confidence_level;
                if (confidence >= 80) {
                    $('#quality_assessment').val('high');
                } else if (confidence >= 60) {
                    $('#quality_assessment').val('medium');
                } else if (confidence >= 40) {
                    $('#quality_assessment').val('low');
                } else {
                    $('#quality_assessment').val('questionable');
                }
                
                alert('Quality assessed automatically. Confidence level: ' + confidence + '%');
            } else {
                alert('Error assessing quality: ' + response.data.message);
            }
        });
    }

    function suggestAnalysisPoints() {
        var evidenceType = $('#evidence_type').val();
        var suggestions = [];

        switch (evidenceType) {
            case 'direct':
                suggestions = [
                    'Evaluate the directness of the statement to the research question',
                    'Assess the credibility of the source and informant',
                    'Consider any potential biases or motivations',
                    'Examine the contemporaneity of the record',
                    'Look for corroborating evidence'
                ];
                break;
            case 'indirect':
                suggestions = [
                    'Identify the logical connection to the research question',
                    'Evaluate the strength of the inferential chain',
                    'Consider alternative explanations for the evidence',
                    'Assess the reliability of the inference',
                    'Look for supporting or contradicting evidence'
                ];
                break;
            case 'circumstantial':
                suggestions = [
                    'Identify patterns and correlations in the evidence',
                    'Evaluate the strength of circumstantial connections',
                    'Consider the totality of circumstances',
                    'Look for clustering of related evidence',
                    'Assess alternative explanations'
                ];
                break;
            case 'negative':
                suggestions = [
                    'Identify what should be present but is absent',
                    'Evaluate the completeness of the record set',
                    'Consider reasons for the absence of evidence',
                    'Assess the significance of the gap',
                    'Look for patterns in missing information'
                ];
                break;
            default:
                suggestions = [
                    'Define the type of evidence being analyzed',
                    'Evaluate the source and its reliability',
                    'Assess the relevance to the research question',
                    'Consider corroborating or contradicting evidence',
                    'Draw logical conclusions based on the analysis'
                ];
        }

        var suggestionText = 'Consider addressing these analysis points:\n\n';
        suggestions.forEach(function(suggestion, index) {
            suggestionText += (index + 1) + '. ' + suggestion + '\n';
        });

        if (confirm(suggestionText + '\nWould you like to add these suggestions to your analysis?')) {
            var currentText = $('#analysis_text').val();
            if (currentText.trim()) {
                currentText += '\n\n--- Analysis Points ---\n';
            }
            
            suggestions.forEach(function(suggestion, index) {
                currentText += '\n' + (index + 1) + '. ' + suggestion + '\n   [Your analysis here]\n';
            });
            
            $('#analysis_text').val(currentText);
            updatePreview();
        }
    }

    function calculateConfidenceLevel() {
        var sourceQuality = $('input[name="source_quality"]:checked').val();
        var temporalProximity = $('input[name="temporal_proximity"]:checked').val();
        var informantKnowledge = $('input[name="informant_knowledge"]:checked').val();

        if (!sourceQuality || !temporalProximity || !informantKnowledge) {
            return;
        }

        var score = 0;

        // Source quality scoring
        switch (sourceQuality) {
            case 'original': score += 25; break;
            case 'early_copy': score += 20; break;
            case 'later_copy': score += 15; break;
            case 'derivative': score += 10; break;
        }

        // Temporal proximity scoring
        switch (temporalProximity) {
            case 'contemporary': score += 25; break;
            case 'within_year': score += 20; break;
            case 'within_decade': score += 15; break;
            case 'later': score += 10; break;
        }

        // Informant knowledge scoring
        switch (informantKnowledge) {
            case 'participant': score += 25; break;
            case 'observer': score += 20; break;
            case 'informed': score += 15; break;
            case 'hearsay': score += 10; break;
        }

        // Base score for having all factors + 25 points buffer
        score += 25;

        $('#confidence_level').val(score);
        $('.confidence-display').text(score + '%');
    }

    // Research question change updates available statements
    $('#research_question_id').on('change', function() {
        var questionId = $(this).val();
        var statementSelect = $('#information_statement_id');
        
        if (questionId) {
            statementSelect.html('<option value="">Loading statements...</option>');
            
            $.post(ajaxurl, {
                action: 'get_question_statements',
                research_question_id: questionId,
                nonce: $('#heritage_evidence_nonce').val()
            }, function(response) {
                if (response.success) {
                    var options = '<option value="">Select Information Statement</option>';
                    $.each(response.data.statements, function(index, statement) {
                        options += '<option value="' + statement.id + '">' + statement.statement_text.substring(0, 80) + (statement.statement_text.length > 80 ? '...' : '') + '</option>';
                    });
                    statementSelect.html(options);
                    
                    // Pre-select if we have a statement ID
                    var preselectedStatementId = '<?php echo $information_statement_id; ?>';
                    if (preselectedStatementId) {
                        statementSelect.val(preselectedStatementId);
                    }
                } else {
                    statementSelect.html('<option value="">No statements available</option>');
                }
            });
        } else {
            statementSelect.html('<option value="">Select Information Statement</option>');
        }
    });
});
</script>
