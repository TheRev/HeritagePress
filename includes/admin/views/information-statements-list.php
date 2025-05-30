<?php
/**
 * Information Statements List View
 * 
 * @package Heritage_Press
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Information Statements</h1>
    <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=add'); ?>" class="page-title-action">Add New Statement</a>
    
    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" id="statements-filter">
                <input type="hidden" name="page" value="heritage-information-statements">
                
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
                
                <select name="statement_type" id="filter-statement-type">
                    <option value="">All Types</option>
                    <option value="direct_statement" <?php selected($_GET['statement_type'] ?? '', 'direct_statement'); ?>>Direct Statement</option>
                    <option value="indirect_evidence" <?php selected($_GET['statement_type'] ?? '', 'indirect_evidence'); ?>>Indirect Evidence</option>
                    <option value="negative_evidence" <?php selected($_GET['statement_type'] ?? '', 'negative_evidence'); ?>>Negative Evidence</option>
                    <option value="conflicting_evidence" <?php selected($_GET['statement_type'] ?? '', 'conflicting_evidence'); ?>>Conflicting Evidence</option>
                </select>
                
                <select name="information_quality" id="filter-quality">
                    <option value="">All Quality Levels</option>
                    <option value="primary" <?php selected($_GET['information_quality'] ?? '', 'primary'); ?>>Primary</option>
                    <option value="secondary" <?php selected($_GET['information_quality'] ?? '', 'secondary'); ?>>Secondary</option>
                    <option value="tertiary" <?php selected($_GET['information_quality'] ?? '', 'tertiary'); ?>>Tertiary</option>
                    <option value="undetermined" <?php selected($_GET['information_quality'] ?? '', 'undetermined'); ?>>Undetermined</option>
                </select>
                
                <input type="text" name="search" id="search-statements" value="<?php echo esc_attr($_GET['search'] ?? ''); ?>" placeholder="Search statements...">
                
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
            </form>
        </div>
        
        <div class="tablenav-pages">
            <?php
            $total_statements = count($statements);
            $per_page = 20;
            $total_pages = ceil($total_statements / $per_page);
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

    <!-- Statements Table -->
    <table class="wp-list-table widefat fixed striped statements">
        <thead>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column">
                    <input id="cb-select-all-1" type="checkbox">
                </th>
                <th scope="col" class="manage-column column-statement sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'statement_text', 'order' => ($_GET['orderby'] ?? '') === 'statement_text' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Statement</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-type sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'statement_type', 'order' => ($_GET['orderby'] ?? '') === 'statement_type' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Type</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-quality sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'information_quality', 'order' => ($_GET['orderby'] ?? '') === 'information_quality' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Quality</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
                <th scope="col" class="manage-column column-research-question">Research Question</th>
                <th scope="col" class="manage-column column-source">Source</th>
                <th scope="col" class="manage-column column-date sortable">
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => 'created_at', 'order' => ($_GET['orderby'] ?? '') === 'created_at' && ($_GET['order'] ?? '') === 'asc' ? 'desc' : 'asc'])); ?>">
                        <span>Created</span>
                        <span class="sorting-indicator"></span>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($statements)): ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="7">No information statements found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($statements as $statement): ?>
                    <tr id="statement-<?php echo esc_attr($statement->id); ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="statement[]" value="<?php echo esc_attr($statement->id); ?>">
                        </th>
                        <td class="column-statement has-row-actions">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=view&statement_id=' . $statement->id); ?>" class="row-title">
                                    <?php echo esc_html(substr($statement->statement_text, 0, 80)) . (strlen($statement->statement_text) > 80 ? '...' : ''); ?>
                                </a>
                            </strong>
                            
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=view&statement_id=' . $statement->id); ?>">View</a> |
                                </span>
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=heritage-information-statements&action=edit&statement_id=' . $statement->id); ?>">Edit</a> |
                                </span>
                                <span class="analyze">
                                    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=add&information_statement_id=' . $statement->id); ?>">Analyze</a> |
                                </span>
                                <span class="delete">
                                    <a href="#" onclick="deleteStatement(<?php echo esc_attr($statement->id); ?>); return false;" class="submitdelete">Delete</a>
                                </span>
                            </div>
                        </td>
                        <td class="column-type">
                            <span class="statement-type-badge statement-type-<?php echo esc_attr($statement->statement_type); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $statement->statement_type))); ?>
                            </span>
                        </td>
                        <td class="column-quality">
                            <span class="quality-badge quality-<?php echo esc_attr($statement->information_quality); ?>">
                                <?php echo esc_html(ucfirst($statement->information_quality)); ?>
                            </span>
                        </td>
                        <td class="column-research-question">
                            <?php
                            if ($statement->research_question_id) {
                                $question = $question_repo->find_by_id($statement->research_question_id);
                                if ($question) {
                                    echo '<a href="' . admin_url('admin.php?page=heritage-research-questions&action=view&question_id=' . $question->id) . '">';
                                    echo esc_html(substr($question->question_text, 0, 60)) . (strlen($question->question_text) > 60 ? '...' : '');
                                    echo '</a>';
                                } else {
                                    echo '<em>Question not found</em>';
                                }
                            } else {
                                echo '<em>No question linked</em>';
                            }
                            ?>
                        </td>
                        <td class="column-source">
                            <?php
                            if ($statement->source_id) {
                                // Would need to implement source repository
                                echo '<a href="#">Source #' . esc_html($statement->source_id) . '</a>';
                            } else {
                                echo '<em>No source</em>';
                            }
                            ?>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(date('M j, Y', strtotime($statement->created_at))); ?>
                            <br>
                            <small><?php echo esc_html(date('g:i a', strtotime($statement->created_at))); ?></small>
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
                <th scope="col" class="manage-column column-statement">Statement</th>
                <th scope="col" class="manage-column column-type">Type</th>
                <th scope="col" class="manage-column column-quality">Quality</th>
                <th scope="col" class="manage-column column-research-question">Research Question</th>
                <th scope="col" class="manage-column column-source">Source</th>
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
                <option value="export">Export</option>
            </select>
            <input type="submit" id="doaction2" class="button action" value="Apply">
        </div>
    </div>
</div>

<style>
.statement-type-badge, .quality-badge {
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.statement-type-direct_statement { background: #e3f2fd; color: #1976d2; }
.statement-type-indirect_evidence { background: #f3e5f5; color: #7b1fa2; }
.statement-type-negative_evidence { background: #fff3e0; color: #f57c00; }
.statement-type-conflicting_evidence { background: #ffebee; color: #d32f2f; }

.quality-primary { background: #e8f5e8; color: #388e3c; }
.quality-secondary { background: #fff3e0; color: #f57c00; }
.quality-tertiary { background: #ffebee; color: #d32f2f; }
.quality-undetermined { background: #f5f5f5; color: #757575; }

.column-statement { width: 35%; }
.column-type { width: 12%; }
.column-quality { width: 10%; }
.column-research-question { width: 25%; }
.column-source { width: 10%; }
.column-date { width: 8%; }

.tablenav .actions {
    padding: 2px 8px 0 0;
}

.tablenav .actions select,
.tablenav .actions input[type="text"] {
    margin-right: 6px;
}

#search-statements {
    width: 200px;
}

.row-actions .analyze a {
    color: #0073aa;
}

.row-actions .analyze a:hover {
    color: #005177;
}
</style>

<script>
function deleteStatement(statementId) {
    if (confirm('Are you sure you want to delete this information statement? This action cannot be undone.')) {
        // Implement AJAX delete functionality
        jQuery.post(ajaxurl, {
            action: 'delete_information_statement',
            statement_id: statementId,
            nonce: '<?php echo wp_create_nonce('heritage_evidence_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                jQuery('#statement-' + statementId).fadeOut(function() {
                    jQuery(this).remove();
                });
                // Show success message
                jQuery('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>Information statement deleted successfully.</p></div>');
            } else {
                alert('Error deleting statement: ' + response.data.message);
            }
        });
    }
}

// Initialize filters
jQuery(document).ready(function($) {
    $('#statements-filter select, #statements-filter input').on('change keyup', function() {
        if ($(this).attr('type') === 'text') {
            // Debounce text input
            clearTimeout($(this).data('timeout'));
            $(this).data('timeout', setTimeout(function() {
                $('#statements-filter').submit();
            }, 500));
        }
    });
});
</script>
