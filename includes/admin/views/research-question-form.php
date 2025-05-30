<?php
/**
 * Research Question Form
 * 
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = !empty($question);
$page_title = $is_edit ? 'Edit Research Question' : 'Add Research Question';
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>

    <form id="research-question-form" method="post" action="">
        <?php wp_nonce_field('save_research_question', 'research_question_nonce'); ?>
        
        <input type="hidden" name="question_id" value="<?php echo $is_edit ? $question->id : ''; ?>" />
        <input type="hidden" name="file_id" value="<?php echo esc_attr($_GET['file_id'] ?? ($question->file_id ?? '')); ?>" />

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="question_text">Research Question *</label>
                    </th>
                    <td>
                        <textarea id="question_text" name="question_text" rows="4" cols="50" class="large-text" required><?php echo $is_edit ? esc_textarea($question->question_text) : ''; ?></textarea>
                        <p class="description">Clearly state what you are trying to prove or determine. Be specific and focused.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="question_type">Question Type *</label>
                    </th>
                    <td>
                        <select id="question_type" name="question_type" required>
                            <option value="">Select type...</option>
                            <option value="IDENTITY" <?php selected($is_edit ? $question->question_type : '', 'IDENTITY'); ?>>Identity - Who is this person?</option>
                            <option value="RELATIONSHIP" <?php selected($is_edit ? $question->question_type : '', 'RELATIONSHIP'); ?>>Relationship - How are these people related?</option>
                            <option value="EVENT" <?php selected($is_edit ? $question->question_type : '', 'EVENT'); ?>>Event - What happened?</option>
                            <option value="DATE" <?php selected($is_edit ? $question->question_type : '', 'DATE'); ?>>Date - When did something occur?</option>
                            <option value="PLACE" <?php selected($is_edit ? $question->question_type : '', 'PLACE'); ?>>Place - Where did something occur?</option>
                            <option value="OTHER" <?php selected($is_edit ? $question->question_type : '', 'OTHER'); ?>>Other</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="individual_id">Primary Individual</label>
                    </th>
                    <td>
                        <input type="number" id="individual_id" name="individual_id" value="<?php echo $is_edit ? $question->individual_id : ''; ?>" />
                        <p class="description">ID of the main person this question relates to (if applicable).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="family_id">Related Family</label>
                    </th>
                    <td>
                        <input type="number" id="family_id" name="family_id" value="<?php echo $is_edit ? $question->family_id : ''; ?>" />
                        <p class="description">ID of the family this question relates to (if applicable).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="event_id">Related Event</label>
                    </th>
                    <td>
                        <input type="number" id="event_id" name="event_id" value="<?php echo $is_edit ? $question->event_id : ''; ?>" />
                        <p class="description">ID of the event this question relates to (if applicable).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="priority">Priority</label>
                    </th>
                    <td>
                        <select id="priority" name="priority">
                            <option value="MEDIUM" <?php selected($is_edit ? $question->priority : 'MEDIUM', 'MEDIUM'); ?>>Medium</option>
                            <option value="HIGH" <?php selected($is_edit ? $question->priority : '', 'HIGH'); ?>>High</option>
                            <option value="LOW" <?php selected($is_edit ? $question->priority : '', 'LOW'); ?>>Low</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="status">Status</label>
                    </th>
                    <td>
                        <select id="status" name="status">
                            <option value="OPEN" <?php selected($is_edit ? $question->status : 'OPEN', 'OPEN'); ?>>Open</option>
                            <option value="RESOLVED" <?php selected($is_edit ? $question->status : '', 'RESOLVED'); ?>>Resolved</option>
                            <option value="ON_HOLD" <?php selected($is_edit ? $question->status : '', 'ON_HOLD'); ?>>On Hold</option>
                            <option value="ABANDONED" <?php selected($is_edit ? $question->status : '', 'ABANDONED'); ?>>Abandoned</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="target_resolution_date">Target Resolution Date</label>
                    </th>
                    <td>
                        <input type="date" id="target_resolution_date" name="target_resolution_date" value="<?php echo $is_edit ? $question->target_resolution_date : ''; ?>" />
                        <p class="description">Optional target date for resolving this question.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="research_notes">Research Notes</label>
                    </th>
                    <td>
                        <textarea id="research_notes" name="research_notes" rows="6" cols="50" class="large-text"><?php echo $is_edit ? esc_textarea($question->research_notes) : ''; ?></textarea>
                        <p class="description">Notes about research strategy, sources to check, background information, etc.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="methodology_notes">Methodology Notes</label>
                    </th>
                    <td>
                        <textarea id="methodology_notes" name="methodology_notes" rows="4" cols="50" class="large-text"><?php echo $is_edit ? esc_textarea($question->methodology_notes) : ''; ?></textarea>
                        <p class="description">Notes about research methodology and approach for this question.</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if ($is_edit): ?>
            <div class="research-question-meta">
                <h3>Question Information</h3>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">Created</th>
                            <td><?php echo esc_html(mysql2date('F j, Y g:i a', $question->created_at)); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Last Updated</th>
                            <td><?php echo esc_html(mysql2date('F j, Y g:i a', $question->updated_at)); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">UUID</th>
                            <td><code><?php echo esc_html($question->uuid); ?></code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_edit ? 'Update Question' : 'Save Question'; ?>" />
            <a href="<?php echo admin_url('admin.php?page=heritage-research-questions'); ?>" class="button">Cancel</a>
        </p>
    </form>

    <?php if ($is_edit): ?>
        <div class="research-question-tools">
            <h3>Research Tools</h3>
            <div class="tools-grid">
                <div class="tool-card">
                    <h4>Evidence Analysis</h4>
                    <p>Analyze information statements as evidence for this question.</p>
                    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&research_question_id=' . $question->id); ?>" class="button">View Evidence</a>
                </div>
                
                <div class="tool-card">
                    <h4>Proof Arguments</h4>
                    <p>Build and manage proof arguments for this question.</p>
                    <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&research_question_id=' . $question->id); ?>" class="button">View Arguments</a>
                </div>
                
                <div class="tool-card">
                    <h4>Research Strategy</h4>
                    <p>Get suggestions for sources and research approaches.</p>
                    <button type="button" class="button" onclick="showResearchStrategy()">View Strategy</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('#research-question-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=save_research_question&nonce=' + heritage_evidence_ajax.nonce;
        
        $.post(heritage_evidence_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Research question saved successfully!');
                window.location.href = '<?php echo admin_url('admin.php?page=heritage-research-questions'); ?>';
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
});

function showResearchStrategy() {
    // This would show a modal with research strategy suggestions
    alert('Research strategy suggestions would be shown here.');
}
</script>

<style>
.research-question-meta {
    margin-top: 30px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.research-question-tools {
    margin-top: 30px;
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.tool-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.tool-card h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.tool-card p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 13px;
}

.form-table th {
    width: 200px;
}

.form-table textarea.large-text {
    width: 100%;
    max-width: 600px;
}

.form-table input[type="number"],
.form-table input[type="date"] {
    width: 200px;
}

.form-table select {
    width: 250px;
}
</style>
