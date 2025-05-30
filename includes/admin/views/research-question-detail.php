<?php
/**
 * Research Question Detail View
 * 
 * @package Heritage_Press
 */

if (!defined('ABSPATH')) {
    exit;
}

$question_id = intval($_GET['question_id']);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Research Question Details</h1>
    <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=edit&question_id=' . $question_id); ?>" class="page-title-action">Edit Question</a>
    <a href="<?php echo admin_url('admin.php?page=heritage-research-questions'); ?>" class="page-title-action">Back to List</a>
    
    <hr class="wp-header-end">

    <?php if (!$question): ?>
        <div class="notice notice-error">
            <p>Research question not found.</p>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <!-- Main Question Details -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Research Question</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Question Text</th>
                                <td><?php echo esc_html($question->question_text); ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Type</th>
                                <td>
                                    <span class="question-type-badge question-type-<?php echo esc_attr($question->question_type); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $question->question_type))); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Priority</th>
                                <td>
                                    <span class="priority-badge priority-<?php echo esc_attr($question->priority); ?>">
                                        <?php echo esc_html(ucfirst($question->priority)); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Status</th>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($question->status); ?>">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $question->status))); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if (!empty($question->context)): ?>
                            <tr>
                                <th scope="row">Context</th>
                                <td><?php echo wpautop(esc_html($question->context)); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($question->methodology_notes)): ?>
                            <tr>
                                <th scope="row">Methodology Notes</th>
                                <td><?php echo wpautop(esc_html($question->methodology_notes)); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th scope="row">Created</th>
                                <td><?php echo esc_html(date('F j, Y g:i a', strtotime($question->created_at))); ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Last Updated</th>
                                <td><?php echo esc_html(date('F j, Y g:i a', strtotime($question->updated_at))); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Related Information Statements -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Related Information Statements</h2>
                        <div class="handle-actions">
                            <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=add&research_question_id=' . $question->id); ?>" class="button button-primary">Add Statement</a>
                        </div>
                    </div>
                    <div class="inside">
                        <?php
                        // Get related information statements
                        $info_repo = new Information_Statement_Repository();
                        $statements = $info_repo->find_by_research_question($question->id);
                        ?>
                        
                        <?php if (empty($statements)): ?>
                            <p>No information statements linked to this research question yet.</p>
                        <?php else: ?>
                            <div class="statements-list">
                                <?php foreach ($statements as $statement): ?>
                                    <div class="statement-item">
                                        <div class="statement-header">
                                            <h4>
                                                <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=view&statement_id=' . $statement->id); ?>">
                                                    <?php echo esc_html(substr($statement->statement_text, 0, 100)) . (strlen($statement->statement_text) > 100 ? '...' : ''); ?>
                                                </a>
                                            </h4>
                                            <span class="statement-type"><?php echo esc_html(ucwords(str_replace('_', ' ', $statement->statement_type))); ?></span>
                                        </div>
                                        <div class="statement-meta">
                                            <span class="quality-rating">Quality: <?php echo esc_html(ucfirst($statement->information_quality)); ?></span>
                                            <span class="created-date">Created: <?php echo esc_html(date('M j, Y', strtotime($statement->created_at))); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Related Evidence Analysis -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Evidence Analysis</h2>
                        <div class="handle-actions">
                            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=add&research_question_id=' . $question->id); ?>" class="button button-primary">Add Analysis</a>
                        </div>
                    </div>
                    <div class="inside">
                        <?php
                        // Get related evidence analyses
                        $evidence_repo = new Evidence_Analysis_Repository();
                        $analyses = $evidence_repo->find_by_research_question($question->id);
                        ?>
                        
                        <?php if (empty($analyses)): ?>
                            <p>No evidence analyses linked to this research question yet.</p>
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
                                            <span class="confidence-level">Confidence: <?php echo esc_html($analysis->confidence_level); ?>%</span>
                                        </div>
                                        <div class="analysis-preview">
                                            <?php echo esc_html(substr($analysis->analysis_text, 0, 150)) . (strlen($analysis->analysis_text) > 150 ? '...' : ''); ?>
                                        </div>
                                        <div class="analysis-meta">
                                            <span class="quality-assessment">Quality: <?php echo esc_html(ucfirst($analysis->quality_assessment)); ?></span>
                                            <span class="created-date">Created: <?php echo esc_html(date('M j, Y', strtotime($analysis->created_at))); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Proof Arguments -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Proof Arguments</h2>
                        <div class="handle-actions">
                            <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=add&research_question_id=' . $question->id); ?>" class="button button-primary">Add Argument</a>
                        </div>
                    </div>
                    <div class="inside">
                        <?php
                        // Get related proof arguments
                        $proof_repo = new Proof_Argument_Repository();
                        $arguments = $proof_repo->find_by_research_question($question->id);
                        ?>
                        
                        <?php if (empty($arguments)): ?>
                            <p>No proof arguments developed for this research question yet.</p>
                        <?php else: ?>
                            <div class="arguments-list">
                                <?php foreach ($arguments as $argument): ?>
                                    <div class="argument-item">
                                        <div class="argument-header">
                                            <h4>
                                                <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=view&argument_id=' . $argument->id); ?>">
                                                    Proof Argument
                                                </a>
                                            </h4>
                                            <span class="proof-standard"><?php echo esc_html(ucwords(str_replace('_', ' ', $argument->proof_standard))); ?></span>
                                        </div>
                                        <div class="argument-preview">
                                            <?php echo esc_html(substr($argument->argument_text, 0, 150)) . (strlen($argument->argument_text) > 150 ? '...' : ''); ?>
                                        </div>
                                        <div class="argument-meta">
                                            <span class="confidence-assessment">Confidence: <?php echo esc_html(ucfirst($argument->confidence_assessment)); ?></span>
                                            <span class="created-date">Created: <?php echo esc_html(date('M j, Y', strtotime($argument->created_at))); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="postbox-container-1" class="postbox-container">
                <!-- Research Progress -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Research Progress</h2>
                    </div>
                    <div class="inside">
                        <?php
                        $total_statements = count($statements ?? []);
                        $total_analyses = count($analyses ?? []);
                        $total_arguments = count($arguments ?? []);
                        $progress_score = 0;
                        
                        if ($total_statements > 0) $progress_score += 25;
                        if ($total_analyses > 0) $progress_score += 35;
                        if ($total_arguments > 0) $progress_score += 40;
                        ?>
                        
                        <div class="progress-overview">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress_score; ?>%"></div>
                            </div>
                            <p class="progress-text"><?php echo $progress_score; ?>% Complete</p>
                        </div>
                        
                        <ul class="progress-checklist">
                            <li class="<?php echo $total_statements > 0 ? 'completed' : 'pending'; ?>">
                                <span class="dashicons <?php echo $total_statements > 0 ? 'dashicons-yes' : 'dashicons-minus'; ?>"></span>
                                Information Statements (<?php echo $total_statements; ?>)
                            </li>
                            <li class="<?php echo $total_analyses > 0 ? 'completed' : 'pending'; ?>">
                                <span class="dashicons <?php echo $total_analyses > 0 ? 'dashicons-yes' : 'dashicons-minus'; ?>"></span>
                                Evidence Analyses (<?php echo $total_analyses; ?>)
                            </li>
                            <li class="<?php echo $total_arguments > 0 ? 'completed' : 'pending'; ?>">
                                <span class="dashicons <?php echo $total_arguments > 0 ? 'dashicons-yes' : 'dashicons-minus'; ?>"></span>
                                Proof Arguments (<?php echo $total_arguments; ?>)
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Quick Actions</h2>
                    </div>
                    <div class="inside">
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=edit&question_id=' . $question->id); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                Edit Question
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=add&research_question_id=' . $question->id); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                Add Statement
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=add&research_question_id=' . $question->id); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center; margin-bottom: 10px;">
                                Add Analysis
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=add&research_question_id=' . $question->id); ?>" class="button button-secondary button-large" style="width: 100%; text-align: center;">
                                Add Proof Argument
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.question-type-badge, .priority-badge, .status-badge {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.question-type-identity { background: #e3f2fd; color: #1976d2; }
.question-type-relationship { background: #f3e5f5; color: #7b1fa2; }
.question-type-event { background: #e8f5e8; color: #388e3c; }
.question-type-causation { background: #fff3e0; color: #f57c00; }
.question-type-comparative { background: #fce4ec; color: #c2185b; }

.priority-high { background: #ffebee; color: #d32f2f; }
.priority-medium { background: #fff3e0; color: #f57c00; }
.priority-low { background: #e8f5e8; color: #388e3c; }

.status-active { background: #e8f5e8; color: #388e3c; }
.status-on_hold { background: #fff3e0; color: #f57c00; }
.status-completed { background: #e3f2fd; color: #1976d2; }
.status-archived { background: #f5f5f5; color: #757575; }

.statement-item, .analysis-item, .argument-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 10px;
    background: #fff;
}

.statement-header, .analysis-header, .argument-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.statement-header h4, .analysis-header h4, .argument-header h4 {
    margin: 0;
    font-size: 14px;
}

.statement-type, .confidence-level, .proof-standard {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
}

.statement-meta, .analysis-meta, .argument-meta {
    display: flex;
    gap: 15px;
    font-size: 12px;
    color: #666;
    margin-top: 10px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4caf50, #8bc34a);
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    font-weight: 500;
    margin: 0 0 15px 0;
}

.progress-checklist {
    list-style: none;
    margin: 0;
    padding: 0;
}

.progress-checklist li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    font-size: 13px;
}

.progress-checklist li.completed {
    color: #388e3c;
}

.progress-checklist li.pending {
    color: #757575;
}

.statements-list, .analyses-list, .arguments-list {
    max-height: 400px;
    overflow-y: auto;
}
</style>
