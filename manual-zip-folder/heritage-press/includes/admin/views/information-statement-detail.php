<?php
/**
 * Information Statement Detail View
 * 
 * @package Heritage_Press
 */

if (!defined('ABSPATH')) {
    exit;
}

$statement_id = intval($_GET['statement_id']);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Information Statement Details</h1>
    <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=edit&statement_id=' . $statement_id); ?>" class="page-title-action">Edit Statement</a>
    <a href="<?php echo admin_url('admin.php?page=heritage-information-statements'); ?>" class="page-title-action">Back to List</a>
    
    <hr class="wp-header-end">

    <?php if (!$statement): ?>
        <div class="notice notice-error">
            <p>Information statement not found.</p>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <!-- Statement Content -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Statement Details</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Statement Text</th>
                                <td>
                                    <div class="statement-text-display">
                                        <?php echo wpautop(esc_html($statement->statement_text)); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Type</th>
                                <td>
                                    <span class="statement-type-badge statement-type-<?php echo esc_attr($statement->statement_type); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $statement->statement_type))); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Information Quality</th>
                                <td>
                                    <span class="quality-badge quality-<?php echo esc_attr($statement->information_quality); ?>">
                                        <?php echo esc_html(ucfirst($statement->information_quality)); ?>
                                    </span>
                                    <div class="quality-explanation">
                                        <?php
                                        switch ($statement->information_quality) {
                                            case 'primary':
                                                echo '<p><small>Primary information: Created at the time of the event by a participant or observer.</small></p>';
                                                break;
                                            case 'secondary':
                                                echo '<p><small>Secondary information: Created after the event, but by someone with direct knowledge.</small></p>';
                                                break;
                                            case 'tertiary':
                                                echo '<p><small>Tertiary information: Compiled from other sources, potentially multiple generations removed.</small></p>';
                                                break;
                                            case 'undetermined':
                                                echo '<p><small>Quality undetermined: Insufficient information to assess the proximity to the original event.</small></p>';
                                                break;
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php if (!empty($statement->specific_location)): ?>
                            <tr>
                                <th scope="row">Specific Location</th>
                                <td><?php echo esc_html($statement->specific_location); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($statement->transcription_notes)): ?>
                            <tr>
                                <th scope="row">Transcription Notes</th>
                                <td><?php echo wpautop(esc_html($statement->transcription_notes)); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th scope="row">Created</th>
                                <td><?php echo esc_html(date('F j, Y g:i a', strtotime($statement->created_at))); ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Last Updated</th>
                                <td><?php echo esc_html(date('F j, Y g:i a', strtotime($statement->updated_at))); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Research Question Context -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Research Question Context</h2>
                    </div>
                    <div class="inside">
                        <?php
                        if ($statement->research_question_id) {
                            $question_repo = new Research_Question_Repository();
                            $question = $question_repo->find_by_id($statement->research_question_id);
                            
                            if ($question):
                        ?>
                            <div class="research-question-display">
                                <h4>
                                    <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=view&question_id=' . $question->id); ?>">
                                        <?php echo esc_html($question->question_text); ?>
                                    </a>
                                </h4>
                                <div class="question-meta">
                                    <span class="question-type-badge question-type-<?php echo esc_attr($question->question_type); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $question->question_type))); ?>
                                    </span>
                                    <span class="priority-badge priority-<?php echo esc_attr($question->priority); ?>">
                                        <?php echo esc_html(ucfirst($question->priority)); ?> Priority
                                    </span>
                                    <span class="status-badge status-<?php echo esc_attr($question->status); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $question->status))); ?>
                                    </span>
                                </div>
                                <?php if (!empty($question->context)): ?>
                                    <div class="question-context">
                                        <strong>Context:</strong>
                                        <?php echo wpautop(esc_html($question->context)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p>Research question not found (ID: <?php echo esc_html($statement->research_question_id); ?>)</p>
                        <?php endif; ?>
                        <?php } else: ?>
                            <p><em>No research question linked to this statement.</em></p>
                        <?php } ?>
                    </div>
                </div>

                <!-- Source Information -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Source Information</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Source</th>
                                <td>
                                    <?php if ($statement->source_id): ?>
                                        <a href="#">Source #<?php echo esc_html($statement->source_id); ?></a>
                                        <!-- Would link to source detail page when implemented -->
                                    <?php else: ?>
                                        <em>No source linked</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Citation</th>
                                <td>
                                    <?php if ($statement->citation_id): ?>
                                        <div class="citation-display">
                                            Citation #<?php echo esc_html($statement->citation_id); ?>
                                            <!-- Would format actual citation when citation system is implemented -->
                                        </div>
                                    <?php else: ?>
                                        <em>No citation created</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($statement->file_id)): ?>
                            <tr>
                                <th scope="row">Associated File</th>
                                <td>
                                    <a href="#"><?php echo esc_html($statement->file_id); ?></a>
                                    <!-- Would link to file in Heritage Press system -->
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Evidence Analyses -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Evidence Analyses</h2>
                        <div class="handle-actions">
                            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=add&information_statement_id=' . $statement->id); ?>" class="button button-primary">Create Analysis</a>
                        </div>
                    </div>
                    <div class="inside">
                        <?php
                        // Get evidence analyses for this statement
                        $evidence_repo = new Evidence_Analysis_Repository();
                        $analyses = $evidence_repo->find_by_information_statement($statement->id);
                        ?>
                        
                        <?php if (empty($analyses)): ?>
                            <p>No evidence analyses created for this statement yet.</p>
                        <?php else: ?>
                            <div class="analyses-list">
                                <?php foreach ($analyses as $analysis): ?>
                                    <div class="analysis-item">
                                        <div class="analysis-header">
                                            <h4>
                                                <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=view&analysis_id=' . $analysis->id); ?>">
                                                    <?php echo esc_html($analysis->evidence_type); ?> Analysis
                                                </a>
                                            </h4>
                                            <div class="analysis-badges">
                                                <span class="evidence-type-badge"><?php echo esc_html(ucwords(str_replace('_', ' ', $analysis->evidence_type))); ?></span>
                                                <span class="confidence-badge">Confidence: <?php echo esc_html($analysis->confidence_level); ?>%</span>
                                            </div>
                                        </div>
                                        <div class="analysis-preview">
                                            <?php echo esc_html(substr($analysis->analysis_text, 0, 200)) . (strlen($analysis->analysis_text) > 200 ? '...' : ''); ?>
                                        </div>
                                        <div class="analysis-meta">
                                            <span class="weight-assessment">Weight: <?php echo esc_html(ucfirst($analysis->weight_assessment)); ?></span>
                                            <span class="quality-assessment">Quality: <?php echo esc_html(ucfirst($analysis->quality_assessment)); ?></span>
                                            <span class="created-date">Created: <?php echo esc_html(date('M j, Y', strtotime($analysis->created_at))); ?></span>
                                        </div>
                                        <div class="analysis-actions">
                                            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=view&analysis_id=' . $analysis->id); ?>" class="button button-small">View</a>
                                            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=edit&analysis_id=' . $analysis->id); ?>" class="button button-small">Edit</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="postbox-container-1" class="postbox-container">
                <!-- Quick Actions -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Quick Actions</h2>
                    </div>
                    <div class="inside">
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=edit&statement_id=' . $statement->id); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                Edit Statement
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=add&information_statement_id=' . $statement->id); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                Create Analysis
                            </a>
                        </p>
                        <p>
                            <a href="#" onclick="exportStatement(<?php echo esc_attr($statement->id); ?>); return false;" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                Export Citation
                            </a>
                        </p>
                        <p>
                            <a href="#" onclick="duplicateStatement(<?php echo esc_attr($statement->id); ?>); return false;" class="button button-secondary button-large" style="width: 100%; text-align: center;">
                                Duplicate Statement
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Statement Statistics -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Statement Statistics</h2>
                    </div>
                    <div class="inside">
                        <?php
                        $analysis_count = count($analyses ?? []);
                        $word_count = str_word_count($statement->statement_text);
                        $char_count = strlen($statement->statement_text);
                        ?>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $analysis_count; ?></div>
                                <div class="stat-label">Analyses</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $word_count; ?></div>
                                <div class="stat-label">Words</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $char_count; ?></div>
                                <div class="stat-label">Characters</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Statements -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Related Statements</h2>
                    </div>
                    <div class="inside">
                        <?php
                        // Find other statements for the same research question
                        if ($statement->research_question_id) {
                            $info_repo = new Information_Statement_Repository();
                            $related_statements = $info_repo->find_by_research_question($statement->research_question_id);
                            
                            // Remove current statement from results
                            $related_statements = array_filter($related_statements, function($stmt) use ($statement) {
                                return $stmt->id !== $statement->id;
                            });
                        } else {
                            $related_statements = [];
                        }
                        ?>
                        
                        <?php if (empty($related_statements)): ?>
                            <p><em>No other statements for this research question.</em></p>
                        <?php else: ?>
                            <ul class="related-statements-list">
                                <?php foreach (array_slice($related_statements, 0, 5) as $related): ?>
                                    <li>
                                        <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=view&statement_id=' . $related->id); ?>">
                                            <?php echo esc_html(substr($related->statement_text, 0, 60)) . (strlen($related->statement_text) > 60 ? '...' : ''); ?>
                                        </a>
                                        <span class="statement-type"><?php echo esc_html(ucwords(str_replace('_', ' ', $related->statement_type))); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <?php if (count($related_statements) > 5): ?>
                                <p>
                                    <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&research_question_id=' . $statement->research_question_id); ?>">
                                        View all <?php echo count($related_statements); ?> related statements
                                    </a>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.statement-text-display {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    line-height: 1.6;
    font-size: 14px;
}

.statement-type-badge, .quality-badge, .question-type-badge, .priority-badge, .status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    margin-right: 8px;
}

.statement-type-direct_statement { background: #e3f2fd; color: #1976d2; }
.statement-type-indirect_evidence { background: #f3e5f5; color: #7b1fa2; }
.statement-type-negative_evidence { background: #fff3e0; color: #f57c00; }
.statement-type-conflicting_evidence { background: #ffebee; color: #d32f2f; }

.quality-primary { background: #e8f5e8; color: #388e3c; }
.quality-secondary { background: #fff3e0; color: #f57c00; }
.quality-tertiary { background: #ffebee; color: #d32f2f; }
.quality-undetermined { background: #f5f5f5; color: #757575; }

.quality-explanation {
    margin-top: 8px;
}

.quality-explanation small {
    color: #666;
    font-style: italic;
}

.research-question-display {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background: #fff;
}

.research-question-display h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.question-meta {
    margin-bottom: 15px;
}

.question-context {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.analysis-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    background: #fff;
}

.analysis-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.analysis-header h4 {
    margin: 0;
    font-size: 14px;
}

.analysis-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.evidence-type-badge, .confidence-badge {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
}

.confidence-badge {
    background: #e3f2fd;
    color: #1976d2;
}

.analysis-preview {
    margin: 10px 0;
    color: #666;
    font-size: 13px;
    line-height: 1.5;
}

.analysis-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #666;
    margin-bottom: 10px;
}

.analysis-actions {
    border-top: 1px solid #eee;
    padding-top: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    text-align: center;
}

.stat-item {
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.stat-number {
    font-size: 24px;
    font-weight: 600;
    color: #1976d2;
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    margin-top: 5px;
}

.related-statements-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.related-statements-list li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.related-statements-list li:last-child {
    border-bottom: none;
}

.related-statements-list .statement-type {
    font-size: 11px;
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    color: #666;
}
</style>

<script>
function exportStatement(statementId) {
    // Show export options
    if (confirm('Export this statement as a formatted citation?')) {
        jQuery.post(ajaxurl, {
            action: 'export_statement_citation',
            statement_id: statementId,
            nonce: '<?php echo wp_create_nonce('heritage_evidence_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                // Create download or show formatted citation
                alert('Citation exported: ' + response.data.citation);
            } else {
                alert('Error exporting citation: ' + response.data.message);
            }
        });
    }
}

function duplicateStatement(statementId) {
    if (confirm('Create a duplicate of this statement?')) {
        jQuery.post(ajaxurl, {
            action: 'duplicate_information_statement',
            statement_id: statementId,
            nonce: '<?php echo wp_create_nonce('heritage_evidence_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                // Redirect to edit the new statement
                window.location.href = '<?php echo admin_url('admin.php?page=heritage-information-statements&action=edit&statement_id='); ?>' + response.data.new_statement_id;
            } else {
                alert('Error duplicating statement: ' + response.data.message);
            }
        });
    }
}
</script>
