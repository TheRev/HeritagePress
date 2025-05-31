<?php
/**
 * Evidence Analysis List View
 * 
 * @package Heritage_Press
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Evidence Analysis</h1>
    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=add'); ?>" class="page-title-action">Add New Analysis</a>
    
    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" id="analyses-filter">
                <input type="hidden" name="page" value="heritage-evidence-analysis">
                
                <select name="research_question_id" id="filter-research-question">
                    <option value="">All Research Questions</option>
                    <?php
                    $question_repo = new Research_Question_Repository();
                    $research_questions = $question_repo->get_all();
                    foreach ($research_questions as $question):
                    ?>
                        <option value="<?php echo esc_attr($question->id); ?>" <?php selected($_GET['research_question_id'] ?? '', $question->id); ?>>
                            <?php echo esc_html(substr($question->question_text, 0, 50)) . (strlen($question->question_text) > 50 ? '...' : ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="evidence_type" id="filter-evidence-type">
                    <option value="">All Evidence Types</option>
                    <option value="direct" <?php selected($_GET['evidence_type'] ?? '', 'direct'); ?>>Direct Evidence</option>
                    <option value="indirect" <?php selected($_GET['evidence_type'] ?? '', 'indirect'); ?>>Indirect Evidence</option>
                    <option value="circumstantial" <?php selected($_GET['evidence_type'] ?? '', 'circumstantial'); ?>>Circumstantial Evidence</option>
                    <option value="negative" <?php selected($_GET['evidence_type'] ?? '', 'negative'); ?>>Negative Evidence</option>
                </select>
                
                <select name="quality_assessment" id="filter-quality">
                    <option value="">All Quality Levels</option>
                    <option value="high" <?php selected($_GET['quality_assessment'] ?? '', 'high'); ?>>High Quality</option>
                    <option value="medium" <?php selected($_GET['quality_assessment'] ?? '', 'medium'); ?>>Medium Quality</option>
                    <option value="low" <?php selected($_GET['quality_assessment'] ?? '', 'low'); ?>>Low Quality</option>
                    <option value="questionable" <?php selected($_GET['quality_assessment'] ?? '', 'questionable'); ?>>Questionable</option>
                </select>
                
                <select name="confidence_range" id="filter-confidence">
                    <option value="">All Confidence Levels</option>
                    <option value="90-100" <?php selected($_GET['confidence_range'] ?? '', '90-100'); ?>>90-100% (Very High)</option>
                    <option value="70-89" <?php selected($_GET['confidence_range'] ?? '', '70-89'); ?>>70-89% (High)</option>
                    <option value="50-69" <?php selected($_GET['confidence_range'] ?? '', '50-69'); ?>>50-69% (Medium)</option>
                    <option value="30-49" <?php selected($_GET['confidence_range'] ?? '', '30-49'); ?>>30-49% (Low)</option>
                    <option value="0-29" <?php selected($_GET['confidence_range'] ?? '', '0-29'); ?>>0-29% (Very Low)</option>
                </select>
                
                <input type="text" name="search" id="search-analyses" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>" placeholder="Search analyses...">
                
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
            </form>
        </div>
        
        <div class="tablenav-pages">
            <?php
            $total_analyses = count($analyses);
            $per_page = 20;
            $total_pages = ceil($total_analyses / $per_page);
            $current_page = max(1, intval($_GET['paged'] ?? 1));
            
            if ($total_pages > 1) {
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page
                ]);
            }
            ?>
        </div>
    </div>

    <!-- Analysis Summary Cards -->
    <div class="analysis-summary-cards">
        <?php
        $summary_stats = [
            'total' => count($analyses),
            'high_confidence' => count(array_filter($analyses, function($a) { return $a->confidence_level >= 80; })),
            'recent' => count(array_filter($analyses, function($a) { return strtotime($a->created_at) > strtotime('-7 days'); })),
            'needs_review' => count(array_filter($analyses, function($a) { return empty($a->quality_assessment) || $a->quality_assessment === 'questionable'; }))
        ];
        ?>
        
        <div class="summary-card">
            <div class="summary-number"><?php echo $summary_stats['total']; ?></div>
            <div class="summary-label">Total Analyses</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-number"><?php echo $summary_stats['high_confidence']; ?></div>
            <div class="summary-label">High Confidence</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-number"><?php echo $summary_stats['recent']; ?></div>
            <div class="summary-label">Recent (7 days)</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-number"><?php echo $summary_stats['needs_review']; ?></div>
            <div class="summary-label">Needs Review</div>
        </div>
    </div>

    <!-- Analyses Table -->
    <table class="wp-list-table widefat fixed striped analyses">
        <thead>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column">
                    <input id="cb-select-all-1" type="checkbox">
                </th>
                <th scope="col" class="manage-column column-analysis sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'analysis_text', 'order' => ($_GET['orderby'] ?? '') === 'analysis_text' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Analysis</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-evidence-type sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'evidence_type', 'order' => ($_GET['orderby'] ?? '') === 'evidence_type' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Evidence Type</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-confidence sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'confidence_level', 'order' => ($_GET['orderby'] ?? '') === 'confidence_level' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Confidence</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-quality sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'quality_assessment', 'order' => ($_GET['orderby'] ?? '') === 'quality_assessment' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Quality</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-research-question">Research Question</th>
                <th scope="col" class="manage-column column-statement">Information Statement</th>
                <th scope="col" class="manage-column column-date sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'created_at', 'order' => ($_GET['orderby'] ?? '') === 'created_at' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Created</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($analyses)): ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="8">No evidence analyses found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($analyses as $analysis): ?>
                    <tr id="analysis-<?php echo esc_attr($analysis->id); ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="analysis[]" value="<?php echo esc_attr($analysis->id); ?>">
                        </th>
                        <td class="column-analysis has-row-actions">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=view&analysis_id=' . $analysis->id); ?>" class="row-title">
                                    <?php echo esc_html(substr($analysis->analysis_text, 0, 80)) . (strlen($analysis->analysis_text) > 80 ? '...' : ''); ?>
                                </a>
                            </strong>
                            
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=view&analysis_id=' . $analysis->id); ?>">View</a> |
                                </span>
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=edit&analysis_id=' . $analysis->id); ?>">Edit</a> |
                                </span>
                                <span class="assess">
                                    <a href="#" onclick="assessEvidence(<?php echo esc_attr($analysis->id); ?>); return false;">Assess Quality</a> |
                                </span>
                                <span class="proof">
                                    <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=add&evidence_ids[]=' . $analysis->id); ?>">Create Proof</a> |
                                </span>
                                <span class="delete">
                                    <a href="#" onclick="deleteAnalysis(<?php echo esc_attr($analysis->id); ?>); return false;" class="submitdelete">Delete</a>
                                </span>
                            </div>
                        </td>
                        <td class="column-evidence-type">
                            <span class="evidence-type-badge evidence-type-<?php echo esc_attr($analysis->evidence_type); ?>">
                                <?php echo esc_html(ucwords($analysis->evidence_type)); ?>
                            </span>
                        </td>
                        <td class="column-confidence">
                            <div class="confidence-display">
                                <div class="confidence-bar">
                                    <div class="confidence-fill" style="width: <?php echo esc_attr($analysis->confidence_level); ?>%"></div>
                                </div>
                                <span class="confidence-text"><?php echo esc_html($analysis->confidence_level); ?>%</span>
                            </div>
                        </td>
                        <td class="column-quality">
                            <span class="quality-badge quality-<?php echo esc_attr($analysis->quality_assessment); ?>">
                                <?php echo esc_html(ucfirst($analysis->quality_assessment)); ?>
                            </span>
                        </td>
                        <td class="column-research-question">
                            <?php
                            if ($analysis->research_question_id) {
                                $question = $question_repo->find_by_id($analysis->research_question_id);
                                if ($question) {
                                    echo '<a href="' . admin_url('admin.php?page=heritage-research-questions&action=view&question_id=' . $question->id) . '">';
                                    echo esc_html(substr($question->question_text, 0, 50)) . (strlen($question->question_text) > 50 ? '...' : '');
                                    echo '</a>';
                                } else {
                                    echo '<em>Question not found</em>';
                                }
                            } else {
                                echo '<em>No question linked</em>';
                            }
                            ?>
                        </td>
                        <td class="column-statement">
                            <?php
                            if ($analysis->information_statement_id) {
                                $info_repo = new Information_Statement_Repository();
                                $statement = $info_repo->find_by_id($analysis->information_statement_id);
                                if ($statement) {
                                    echo '<a href="' . admin_url('admin.php?page=heritage-information-statements&action=view&statement_id=' . $statement->id) . '">';
                                    echo esc_html(substr($statement->statement_text, 0, 40)) . (strlen($statement->statement_text) > 40 ? '...' : '');
                                    echo '</a>';
                                } else {
                                    echo '<em>Statement not found</em>';
                                }
                            } else {
                                echo '<em>No statement linked</em>';
                            }
                            ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(date('M j, Y', strtotime($analysis->created_at))); ?>
                            <br>
                            <small><?php echo esc_html(date('g:i a', strtotime($analysis->created_at))); ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-cb check-column">
                    <input id="cb-select-all-2" type="checkbox">
                </th>
                <th scope="col" class="manage-column column-analysis">Analysis</th>
                <th scope="col" class="manage-column column-evidence-type">Evidence Type</th>
                <th scope="col" class="manage-column column-confidence">Confidence</th>
                <th scope="col" class="manage-column column-quality">Quality</th>
                <th scope="col" class="manage-column column-research-question">Research Question</th>
                <th scope="col" class="manage-column column-statement">Information Statement</th>
                <th scope="col" class="manage-column column-date">Created</th>
            </tr>
        </tfoot>
    </table>

    <!-- Bulk Actions -->
    <div class="tablenav bottom">
        <div class="alignleft actions bulkactions">
            <select name="action2" id="bulk-action-selector-bottom">
                <option value="-1">Bulk Actions</option>
                <option value="delete">Delete</option>
                <option value="assess_quality">Assess Quality</option>
                <option value="export">Export</option>
            </select>
            <input type="submit" id="doaction2" class="button action" value="Apply">
        </div>
    </div>
</div>

<!-- Quality Assessment Modal -->
<div id="quality-assessment-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assess Evidence Quality</h3>
            <span class="modal-close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="quality-assessment-form">
                <input type="hidden" id="assessment-analysis-id" name="analysis_id">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="original_vs_derivative">Original vs Derivative</label>
                        </th>
                        <td>
                            <select name="original_vs_derivative" id="original_vs_derivative" required>
                                <option value="">Select...</option>
                                <option value="original">Original Record</option>
                                <option value="early_copy">Early Copy</option>
                                <option value="later_copy">Later Copy</option>
                                <option value="derivative">Derivative Source</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="proximity_to_event">Proximity to Event</label>
                        </th>
                        <td>
                            <select name="proximity_to_event" id="proximity_to_event" required>
                                <option value="">Select...</option>
                                <option value="contemporary">Contemporary</option>
                                <option value="within_year">Within a Year</option>
                                <option value="within_decade">Within a Decade</option>
                                <option value="later">Much Later</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="informant_knowledge">Informant Knowledge</label>
                        </th>
                        <td>
                            <select name="informant_knowledge" id="informant_knowledge" required>
                                <option value="">Select...</option>
                                <option value="direct_participant">Direct Participant</option>
                                <option value="direct_observer">Direct Observer</option>
                                <option value="informed_observer">Informed Observer</option>
                                <option value="hearsay">Hearsay</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bias_factors">Bias Factors</label>
                        </th>
                        <td>
                            <textarea name="bias_factors" id="bias_factors" rows="3" class="large-text" placeholder="Identify any potential biases or motivations..."></textarea>
                        </td>
                    </tr>
                </table>
                
                <div class="modal-actions">
                    <button type="submit" class="button button-primary">Assess Quality</button>
                    <button type="button" class="button modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.analysis-summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.summary-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.summary-number {
    font-size: 28px;
    font-weight: 600;
    color: #1976d2;
    line-height: 1;
}

.summary-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
    margin-top: 8px;
}

.evidence-type-badge, .quality-badge {
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.evidence-type-direct { background: #e8f5e8; color: #388e3c; }
.evidence-type-indirect { background: #fff3e0; color: #f57c00; }
.evidence-type-circumstantial { background: #f3e5f5; color: #7b1fa2; }
.evidence-type-negative { background: #ffebee; color: #d32f2f; }

.quality-high { background: #e8f5e8; color: #388e3c; }
.quality-medium { background: #fff3e0; color: #f57c00; }
.quality-low { background: #ffebee; color: #d32f2f; }
.quality-questionable { background: #f5f5f5; color: #757575; }

.confidence-display {
    display: flex;
    align-items: center;
    gap: 8px;
}

.confidence-bar {
    width: 50px;
    height: 8px;
    background: #f0f0f1;
    border-radius: 4px;
    overflow: hidden;
}

.confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, #d32f2f 0%, #f57c00 30%, #388e3c 70%);
    transition: width 0.3s ease;
}

.confidence-text {
    font-size: 12px;
    font-weight: 500;
    min-width: 35px;
}

.column-analysis { width: 30%; }
.column-evidence-type { width: 12%; }
.column-confidence { width: 10%; }
.column-quality { width: 10%; }
.column-research-question { width: 18%; }
.column-statement { width: 15%; }
.column-date { width: 5%; }

.tablenav .actions {
    padding: 2px 8px 0 0;
}

.tablenav .actions select,
.tablenav .actions input[type="text"] {
    margin-right: 6px;
}

#search-analyses {
    width: 200px;
}

.row-actions .assess a {
    color: #0073aa;
}

.row-actions .proof a {
    color: #7b1fa2;
}

/* Modal Styles */
.modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    width: 80%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    background: #f6f7f7;
    border-bottom: 1px solid #ccd0d4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #666;
}

.modal-close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}

.modal-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.modal-actions .button {
    margin-left: 10px;
}
</style>

<script>
function deleteAnalysis(analysisId) {
    if (confirm('Are you sure you want to delete this evidence analysis? This action cannot be undone.')) {
        jQuery.post(ajaxurl, {
            action: 'delete_evidence_analysis',
            analysis_id: analysisId,
            nonce: '<?php echo wp_create_nonce('heritage_evidence_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                jQuery('#analysis-' + analysisId).fadeOut(function() {
                    jQuery(this).remove();
                });
                jQuery('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>Evidence analysis deleted successfully.</p></div>');
            } else {
                alert('Error deleting analysis: ' + response.data.message);
            }
        });
    }
}

function assessEvidence(analysisId) {
    jQuery('#assessment-analysis-id').val(analysisId);
    jQuery('#quality-assessment-modal').show();
}

// Initialize modal functionality
jQuery(document).ready(function($) {
    // Close modal
    $('.modal-close, .modal-cancel').on('click', function() {
        $('#quality-assessment-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if (event.target === document.getElementById('quality-assessment-modal')) {
            $('#quality-assessment-modal').hide();
        }
    });

    // Handle quality assessment form
    $('#quality-assessment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'assess_evidence_quality',
            nonce: '<?php echo wp_create_nonce('heritage_evidence_nonce'); ?>',
            analysis_id: $('#assessment-analysis-id').val(),
            original_vs_derivative: $('#original_vs_derivative').val(),
            proximity_to_event: $('#proximity_to_event').val(),
            informant_knowledge: $('#informant_knowledge').val(),
            bias_factors: $('#bias_factors').val()
        };

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                $('#quality-assessment-modal').hide();
                $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>Evidence quality assessed successfully. Confidence level: ' + response.data.confidence_level + '%</p></div>');
                // Refresh the page to show updated data
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                alert('Error assessing evidence quality: ' + response.data.message);
            }
        });
    });

    // Initialize filters
    $('#analyses-filter select, #analyses-filter input').on('change keyup', function() {
        if ($(this).attr('type') === 'text') {
            // Debounce text input
            clearTimeout($(this).data('timeout'));
            $(this).data('timeout', setTimeout(function() {
                $('#analyses-filter').submit();
            }, 500));
        }
    });
});
</script>
