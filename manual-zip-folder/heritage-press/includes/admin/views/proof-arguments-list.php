<?php
/**
 * Proof Arguments List View
 * 
 * Displays a list of all proof arguments with filtering, searching, and management options.
 * Following Elizabeth Shown Mills' Evidence Explained methodology for proof construction.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get filter parameters
$research_question_filter = isset($_GET['research_question']) ? intval($_GET['research_question']) : 0;
$proof_standard_filter = isset($_GET['proof_standard']) ? sanitize_text_field($_GET['proof_standard']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Pagination
$per_page = 20;
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($page - 1) * $per_page;

global $wpdb;

// Build query
$where_conditions = ['1=1'];
$query_params = [];

if ($research_question_filter) {
    $where_conditions[] = "pa.research_question_id = %d";
    $query_params[] = $research_question_filter;
}

if ($proof_standard_filter) {
    $where_conditions[] = "pa.proof_standard = %s";
    $query_params[] = $proof_standard_filter;
}

if ($status_filter) {
    $where_conditions[] = "pa.status = %s";
    $query_params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(pa.conclusion_text LIKE %s OR pa.reasoning_text LIKE %s OR rq.question_text LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(*) 
    FROM {$wpdb->prefix}hp_proof_arguments pa
    LEFT JOIN {$wpdb->prefix}hp_research_questions rq ON pa.research_question_id = rq.id
    WHERE {$where_clause}
";

$total_items = $wpdb->get_var($query_params ? $wpdb->prepare($count_query, $query_params) : $count_query);

// Get proof arguments
$query = "
    SELECT pa.*, rq.question_text, rq.category
    FROM {$wpdb->prefix}hp_proof_arguments pa
    LEFT JOIN {$wpdb->prefix}hp_research_questions rq ON pa.research_question_id = rq.id
    WHERE {$where_clause}
    ORDER BY pa.created_at DESC
    LIMIT %d OFFSET %d
";

$query_params[] = $per_page;
$query_params[] = $offset;

$proof_arguments = $wpdb->get_results($wpdb->prepare($query, $query_params));

// Get research questions for filter dropdown
$research_questions = $wpdb->get_results(
    "SELECT id, question_text FROM {$wpdb->prefix}hp_research_questions ORDER BY question_text"
);

// Calculate pagination
$total_pages = ceil($total_items / $per_page);

// Get summary statistics
$stats = $wpdb->get_row("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN proof_standard = 'preponderance' THEN 1 ELSE 0 END) as preponderance,
        SUM(CASE WHEN proof_standard = 'clear_and_convincing' THEN 1 ELSE 0 END) as clear_convincing,
        SUM(CASE WHEN proof_standard = 'beyond_reasonable_doubt' THEN 1 ELSE 0 END) as beyond_doubt,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
        SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as complete
    FROM {$wpdb->prefix}hp_proof_arguments
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Proof Arguments', 'heritage-press'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=new'); ?>" class="page-title-action">
        <?php _e('Add New Proof Argument', 'heritage-press'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- Summary Statistics -->
    <div class="heritage-stats-grid">
        <div class="heritage-stat-card">
            <div class="stat-number"><?php echo esc_html($stats->total); ?></div>
            <div class="stat-label"><?php _e('Total Arguments', 'heritage-press'); ?></div>
        </div>
        
        <div class="heritage-stat-card">
            <div class="stat-number"><?php echo esc_html($stats->preponderance); ?></div>
            <div class="stat-label"><?php _e('Preponderance', 'heritage-press'); ?></div>
        </div>
        
        <div class="heritage-stat-card">
            <div class="stat-number"><?php echo esc_html($stats->clear_convincing); ?></div>
            <div class="stat-label"><?php _e('Clear & Convincing', 'heritage-press'); ?></div>
        </div>
        
        <div class="heritage-stat-card">
            <div class="stat-number"><?php echo esc_html($stats->beyond_doubt); ?></div>
            <div class="stat-label"><?php _e('Beyond Doubt', 'heritage-press'); ?></div>
        </div>
        
        <div class="heritage-stat-card">
            <div class="stat-number"><?php echo esc_html($stats->complete); ?></div>
            <div class="stat-label"><?php _e('Complete', 'heritage-press'); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="heritage-filters-container">
        <form method="get" class="heritage-filters-form">
            <input type="hidden" name="page" value="heritage-proof-arguments">
            
            <div class="heritage-filter-group">
                <label for="research-question-filter"><?php _e('Research Question:', 'heritage-press'); ?></label>
                <select name="research_question" id="research-question-filter">
                    <option value=""><?php _e('All Questions', 'heritage-press'); ?></option>
                    <?php foreach ($research_questions as $question): ?>
                    <option value="<?php echo esc_attr($question->id); ?>" 
                            <?php selected($research_question_filter, $question->id); ?>>
                        <?php echo esc_html(wp_trim_words($question->question_text, 10)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="heritage-filter-group">
                <label for="proof-standard-filter"><?php _e('Proof Standard:', 'heritage-press'); ?></label>
                <select name="proof_standard" id="proof-standard-filter">
                    <option value=""><?php _e('All Standards', 'heritage-press'); ?></option>
                    <option value="preponderance" <?php selected($proof_standard_filter, 'preponderance'); ?>>
                        <?php _e('Preponderance of Evidence', 'heritage-press'); ?>
                    </option>
                    <option value="clear_and_convincing" <?php selected($proof_standard_filter, 'clear_and_convincing'); ?>>
                        <?php _e('Clear and Convincing', 'heritage-press'); ?>
                    </option>
                    <option value="beyond_reasonable_doubt" <?php selected($proof_standard_filter, 'beyond_reasonable_doubt'); ?>>
                        <?php _e('Beyond Reasonable Doubt', 'heritage-press'); ?>
                    </option>
                </select>
            </div>
            
            <div class="heritage-filter-group">
                <label for="status-filter"><?php _e('Status:', 'heritage-press'); ?></label>
                <select name="status" id="status-filter">
                    <option value=""><?php _e('All Statuses', 'heritage-press'); ?></option>
                    <option value="draft" <?php selected($status_filter, 'draft'); ?>>
                        <?php _e('Draft', 'heritage-press'); ?>
                    </option>
                    <option value="in_review" <?php selected($status_filter, 'in_review'); ?>>
                        <?php _e('In Review', 'heritage-press'); ?>
                    </option>
                    <option value="complete" <?php selected($status_filter, 'complete'); ?>>
                        <?php _e('Complete', 'heritage-press'); ?>
                    </option>
                </select>
            </div>
            
            <div class="heritage-filter-group">
                <label for="search-input"><?php _e('Search:', 'heritage-press'); ?></label>
                <input type="search" name="s" id="search-input" 
                       value="<?php echo esc_attr($search); ?>" 
                       placeholder="<?php _e('Search arguments...', 'heritage-press'); ?>">
            </div>
            
            <div class="heritage-filter-actions">
                <input type="submit" class="button" value="<?php _e('Filter', 'heritage-press'); ?>">
                <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments'); ?>" class="button">
                    <?php _e('Clear', 'heritage-press'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="heritage-bulk-actions">
        <form method="post" id="heritage-bulk-form">
            <?php wp_nonce_field('heritage_bulk_actions', 'heritage_bulk_nonce'); ?>
            
            <div class="heritage-bulk-controls">
                <select name="bulk_action" id="bulk-action-selector">
                    <option value=""><?php _e('Bulk Actions', 'heritage-press'); ?></option>
                    <option value="export"><?php _e('Export Selected', 'heritage-press'); ?></option>
                    <option value="mark_complete"><?php _e('Mark as Complete', 'heritage-press'); ?></option>
                    <option value="mark_draft"><?php _e('Mark as Draft', 'heritage-press'); ?></option>
                    <option value="delete"><?php _e('Delete', 'heritage-press'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php _e('Apply', 'heritage-press'); ?>" disabled>
            </div>
        </form>
    </div>

    <!-- Proof Arguments List -->
    <div class="heritage-arguments-grid">
        <?php if (empty($proof_arguments)): ?>
        <div class="heritage-no-results">
            <h3><?php _e('No proof arguments found', 'heritage-press'); ?></h3>
            <p><?php _e('Try adjusting your filters or create your first proof argument.', 'heritage-press'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=new'); ?>" class="button button-primary">
                <?php _e('Create Proof Argument', 'heritage-press'); ?>
            </a>
        </div>
        <?php else: ?>
        
        <?php foreach ($proof_arguments as $argument): 
            $metadata = json_decode($argument->metadata, true) ?: [];
            $evidence_count = 0;
            if (!empty($argument->evidence_analysis_ids)) {
                $evidence_ids = json_decode($argument->evidence_analysis_ids, true) ?: [];
                $evidence_count = count($evidence_ids);
            }
        ?>
        <div class="heritage-argument-card">
            <div class="heritage-card-header">
                <div class="heritage-card-controls">
                    <input type="checkbox" name="selected_arguments[]" 
                           value="<?php echo esc_attr($argument->id); ?>" 
                           class="heritage-bulk-checkbox">
                </div>
                
                <div class="heritage-card-badges">
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
                <div class="heritage-question-context">
                    <h4><?php echo esc_html($argument->question_text); ?></h4>
                    <?php if (!empty($argument->category)): ?>
                    <span class="heritage-category-tag">
                        <?php echo esc_html(ucwords(str_replace('_', ' ', $argument->category))); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="heritage-conclusion">
                    <strong><?php _e('Conclusion:', 'heritage-press'); ?></strong>
                    <p><?php echo esc_html(wp_trim_words($argument->conclusion_text, 25)); ?></p>
                </div>
                
                <div class="heritage-argument-summary">
                    <div class="heritage-summary-item">
                        <span class="summary-label"><?php _e('Evidence Used:', 'heritage-press'); ?></span>
                        <span class="summary-value"><?php echo esc_html($evidence_count); ?> analyses</span>
                    </div>
                    
                    <?php if (!empty($metadata['confidence_level'])): ?>
                    <div class="heritage-summary-item">
                        <span class="summary-label"><?php _e('Confidence:', 'heritage-press'); ?></span>
                        <span class="summary-value heritage-confidence-<?php echo esc_attr($metadata['confidence_level']); ?>">
                            <?php echo esc_html(ucfirst($metadata['confidence_level'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="heritage-summary-item">
                        <span class="summary-label"><?php _e('Created:', 'heritage-press'); ?></span>
                        <span class="summary-value">
                            <?php echo esc_html(date_i18n('M j, Y', strtotime($argument->created_at))); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="heritage-card-actions">
                <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=view&id=' . $argument->id); ?>" 
                   class="button">
                    <?php _e('View', 'heritage-press'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=edit&id=' . $argument->id); ?>" 
                   class="button">
                    <?php _e('Edit', 'heritage-press'); ?>
                </a>
                
                <button type="button" class="button heritage-duplicate-argument" 
                        data-argument-id="<?php echo esc_attr($argument->id); ?>">
                    <?php _e('Duplicate', 'heritage-press'); ?>
                </button>
                
                <button type="button" class="button button-link-delete heritage-delete-argument" 
                        data-argument-id="<?php echo esc_attr($argument->id); ?>">
                    <?php _e('Delete', 'heritage-press'); ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="heritage-pagination">
        <?php
        $pagination_args = [
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => '&laquo; ' . __('Previous', 'heritage-press'),
            'next_text' => __('Next', 'heritage-press') . ' &raquo;',
            'total' => $total_pages,
            'current' => $page,
            'show_all' => false,
            'end_size' => 2,
            'mid_size' => 2
        ];
        
        echo paginate_links($pagination_args);
        ?>
    </div>
    <?php endif; ?>
</div>

<style>
.heritage-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.heritage-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
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

.heritage-filters-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.heritage-filters-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.heritage-filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}

.heritage-filter-group select,
.heritage-filter-group input[type="search"] {
    width: 100%;
}

.heritage-filter-actions {
    display: flex;
    gap: 10px;
}

.heritage-bulk-actions {
    margin: 20px 0;
}

.heritage-bulk-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.heritage-arguments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.heritage-argument-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
}

.heritage-card-header {
    padding: 15px;
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.heritage-card-badges {
    display: flex;
    gap: 8px;
}

.heritage-proof-standard {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-standard-preponderance { background: #fff3cd; color: #664d03; }
.heritage-standard-clear_and_convincing { background: #cff4fc; color: #055160; }
.heritage-standard-beyond_reasonable_doubt { background: #d1e7dd; color: #0f5132; }

.heritage-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.heritage-status-draft { background: #f0f0f1; color: #3c434a; }
.heritage-status-in_review { background: #fff3cd; color: #664d03; }
.heritage-status-complete { background: #d1e7dd; color: #0f5132; }

.heritage-card-body {
    padding: 20px;
}

.heritage-question-context h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
    line-height: 1.4;
}

.heritage-category-tag {
    display: inline-block;
    background: #f0f6fc;
    color: #0969da;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.heritage-conclusion {
    margin: 15px 0;
}

.heritage-conclusion p {
    margin: 5px 0 0 0;
    color: #3c434a;
    line-height: 1.5;
}

.heritage-argument-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f1;
}

.heritage-summary-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.summary-label {
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
    font-weight: 600;
}

.summary-value {
    font-size: 13px;
    color: #1d2327;
    font-weight: 600;
}

.heritage-confidence-high { color: #0f5132; }
.heritage-confidence-medium { color: #664d03; }
.heritage-confidence-low { color: #721c24; }

.heritage-card-actions {
    padding: 15px;
    background: #f6f7f7;
    border-top: 1px solid #c3c4c7;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.heritage-card-actions .button {
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
}

.heritage-no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.heritage-no-results h3 {
    color: #646970;
    margin-bottom: 10px;
}

.heritage-pagination {
    margin: 30px 0;
    text-align: center;
}

.heritage-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    text-decoration: none;
    border: 1px solid #c3c4c7;
    background: #fff;
    color: #2271b1;
}

.heritage-pagination .page-numbers.current {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.heritage-pagination .page-numbers:hover:not(.current) {
    background: #f6f7f7;
}

@media (max-width: 1024px) {
    .heritage-arguments-grid {
        grid-template-columns: 1fr;
    }
    
    .heritage-filters-form {
        grid-template-columns: 1fr;
    }
    
    .heritage-filter-actions {
        justify-content: stretch;
    }
    
    .heritage-filter-actions .button {
        flex: 1;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Bulk checkbox handling
    $('.heritage-bulk-checkbox').on('change', function() {
        const checkedBoxes = $('.heritage-bulk-checkbox:checked').length;
        $('#bulk-action-selector').next('input[type="submit"]').prop('disabled', checkedBoxes === 0);
    });
    
    // Select all functionality
    $('#heritage-select-all').on('change', function() {
        $('.heritage-bulk-checkbox').prop('checked', this.checked);
        $('.heritage-bulk-checkbox').trigger('change');
    });
    
    // Bulk form submission
    $('#heritage-bulk-form').on('submit', function(e) {
        const action = $('#bulk-action-selector').val();
        const checkedBoxes = $('.heritage-bulk-checkbox:checked').length;
        
        if (!action || checkedBoxes === 0) {
            e.preventDefault();
            alert('<?php _e('Please select an action and at least one argument.', 'heritage-press'); ?>');
            return false;
        }
        
        if (action === 'delete') {
            if (!confirm('<?php _e('Are you sure you want to delete the selected arguments? This action cannot be undone.', 'heritage-press'); ?>')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Duplicate argument
    $('.heritage-duplicate-argument').on('click', function() {
        const argumentId = $(this).data('argument-id');
        if (confirm('<?php _e('Create a duplicate of this proof argument?', 'heritage-press'); ?>')) {
            window.location.href = '<?php echo admin_url('admin.php?page=heritage-proof-arguments&action=duplicate&id='); ?>' + argumentId;
        }
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
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('Error deleting proof argument.', 'heritage-press'); ?>');
                }
            });
        }
    });
    
    // Real-time search
    let searchTimeout;
    $('#search-input').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            if (searchTerm.length >= 3 || searchTerm.length === 0) {
                $(this).closest('form').submit();
            }
        }.bind(this), 500);
    });
});
</script>
