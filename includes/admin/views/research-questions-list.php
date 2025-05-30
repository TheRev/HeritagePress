<?php
/**
 * Research Questions List View
 * 
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>
        Research Questions
        <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=add&file_id=' . urlencode($_GET['file_id'] ?? '')); ?>" class="page-title-action">Add New</a>
    </h1>

    <div class="heritage-research-questions">
        <!-- Filters -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" action="">
                    <input type="hidden" name="page" value="heritage-research-questions" />
                    
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="OPEN" <?php selected($_GET['status'] ?? '', 'OPEN'); ?>>Open</option>
                        <option value="RESOLVED" <?php selected($_GET['status'] ?? '', 'RESOLVED'); ?>>Resolved</option>
                        <option value="ON_HOLD" <?php selected($_GET['status'] ?? '', 'ON_HOLD'); ?>>On Hold</option>
                        <option value="ABANDONED" <?php selected($_GET['status'] ?? '', 'ABANDONED'); ?>>Abandoned</option>
                    </select>
                    
                    <select name="priority">
                        <option value="">All Priorities</option>
                        <option value="HIGH" <?php selected($_GET['priority'] ?? '', 'HIGH'); ?>>High</option>
                        <option value="MEDIUM" <?php selected($_GET['priority'] ?? '', 'MEDIUM'); ?>>Medium</option>
                        <option value="LOW" <?php selected($_GET['priority'] ?? '', 'LOW'); ?>>Low</option>
                    </select>
                    
                    <select name="question_type">
                        <option value="">All Types</option>
                        <option value="IDENTITY" <?php selected($_GET['question_type'] ?? '', 'IDENTITY'); ?>>Identity</option>
                        <option value="RELATIONSHIP" <?php selected($_GET['question_type'] ?? '', 'RELATIONSHIP'); ?>>Relationship</option>
                        <option value="EVENT" <?php selected($_GET['question_type'] ?? '', 'EVENT'); ?>>Event</option>
                        <option value="DATE" <?php selected($_GET['question_type'] ?? '', 'DATE'); ?>>Date</option>
                        <option value="PLACE" <?php selected($_GET['question_type'] ?? '', 'PLACE'); ?>>Place</option>
                        <option value="OTHER" <?php selected($_GET['question_type'] ?? '', 'OTHER'); ?>>Other</option>
                    </select>
                    
                    <input type="submit" class="button" value="Filter" />
                </form>
            </div>
        </div>

        <!-- Questions Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-question">Question</th>
                    <th scope="col" class="column-type">Type</th>
                    <th scope="col" class="column-priority">Priority</th>
                    <th scope="col" class="column-status">Status</th>
                    <th scope="col" class="column-evidence">Evidence</th>
                    <th scope="col" class="column-proof">Proof Arguments</th>
                    <th scope="col" class="column-date">Created</th>
                    <th scope="col" class="column-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $question): ?>
                        <tr>
                            <td class="column-question">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=view&question_id=' . $question->id); ?>">
                                        <?php echo esc_html(wp_trim_words($question->question_text, 15)); ?>
                                    </a>
                                </strong>
                                <?php if ($question->individual_id): ?>
                                    <div class="row-actions">
                                        <span class="individual-context">Individual: #<?php echo $question->individual_id; ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="column-type">
                                <span class="question-type-badge type-<?php echo strtolower($question->question_type); ?>">
                                    <?php echo esc_html($question->question_type); ?>
                                </span>
                            </td>
                            <td class="column-priority">
                                <span class="priority-badge priority-<?php echo strtolower($question->priority); ?>">
                                    <?php echo esc_html($question->priority); ?>
                                </span>
                            </td>
                            <td class="column-status">
                                <span class="status-badge status-<?php echo strtolower($question->status); ?>">
                                    <?php echo esc_html($question->status); ?>
                                </span>
                            </td>
                            <td class="column-evidence">
                                <?php 
                                $evidence_count = $question->evidence_analyses()->count() ?? 0;
                                echo $evidence_count . ' analyses';
                                ?>
                            </td>
                            <td class="column-proof">
                                <?php 
                                $proof_count = $question->proof_arguments()->count() ?? 0;
                                echo $proof_count . ' arguments';
                                ?>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html(mysql2date('Y/m/d', $question->created_at)); ?>
                            </td>
                            <td class="column-actions">
                                <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=view&question_id=' . $question->id); ?>" class="button button-small">View</a>
                                <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=edit&question_id=' . $question->id); ?>" class="button button-small">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <p>No research questions found.</p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-research-questions&action=add'); ?>" class="button button-primary">Add Your First Research Question</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.heritage-research-questions {
    margin-top: 20px;
}

.tablenav.top {
    margin-bottom: 10px;
}

.tablenav .actions select {
    margin-right: 5px;
}

.question-type-badge,
.priority-badge,
.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

/* Question Type Badges */
.type-identity { background: #e3f2fd; color: #1976d2; }
.type-relationship { background: #fce4ec; color: #c2185b; }
.type-event { background: #e8f5e8; color: #388e3c; }
.type-date { background: #fff3e0; color: #f57c00; }
.type-place { background: #f3e5f5; color: #7b1fa2; }
.type-other { background: #f5f5f5; color: #616161; }

/* Priority Badges */
.priority-high { background: #ffebee; color: #d32f2f; }
.priority-medium { background: #fff8e1; color: #f57c00; }
.priority-low { background: #e8f5e8; color: #388e3c; }

/* Status Badges */
.status-open { background: #e3f2fd; color: #1976d2; }
.status-resolved { background: #e8f5e8; color: #388e3c; }
.status-on_hold { background: #fff8e1; color: #f57c00; }
.status-abandoned { background: #ffebee; color: #d32f2f; }

.column-question { width: 35%; }
.column-type { width: 10%; }
.column-priority { width: 8%; }
.column-status { width: 8%; }
.column-evidence { width: 10%; }
.column-proof { width: 10%; }
.column-date { width: 9%; }
.column-actions { width: 10%; }

.row-actions {
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.individual-context {
    font-style: italic;
}

.button-small {
    padding: 2px 8px;
    font-size: 11px;
    line-height: 1.4;
    height: auto;
}
</style>
