<?php
/**
 * Families Management Page
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $wpdb;
$prefix = $wpdb->prefix . 'heritage_press_';

// Handle actions
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$family_id = isset($_GET['family_id']) ? intval($_GET['family_id']) : 0;

if ($action === 'view' && $family_id) {
    // Display family details
    $family = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$prefix}families WHERE id = %d AND status = 'active'",
        $family_id
    ));
    
    if (!$family) {
        echo '<div class="notice notice-error"><p>' . __('Family not found.', 'heritage-press') . '</p></div>';
        $action = '';
    } else {
        // Get husband and wife details
        $husband = null;
        $wife = null;
        
        if ($family->husband_id) {
            $husband = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}individuals WHERE id = %d",
                $family->husband_id
            ));
        }
        
        if ($family->wife_id) {
            $wife = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$prefix}individuals WHERE id = %d",
                $family->wife_id
            ));
        }
        
        // Get children
        $children = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, fc.relationship_type 
             FROM {$prefix}individuals i 
             INNER JOIN {$prefix}family_children fc ON i.id = fc.child_id 
             WHERE fc.family_id = %d 
             ORDER BY i.birth_date",
            $family_id
        ));
    }
}

// Get families list
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$where_clause = "WHERE f.status = 'active'";
$search_params = [];

if ($search) {
    $where_clause .= " AND (h.surname LIKE %s OR w.surname LIKE %s OR h.given_names LIKE %s OR w.given_names LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $search_params = [$search_term, $search_term, $search_term, $search_term];
}

$families_query = "
    SELECT f.*, 
           h.given_names as husband_given_names, h.surname as husband_surname,
           w.given_names as wife_given_names, w.surname as wife_surname
    FROM {$prefix}families f
    LEFT JOIN {$prefix}individuals h ON f.husband_id = h.id
    LEFT JOIN {$prefix}individuals w ON f.wife_id = w.id
    $where_clause 
    ORDER BY h.surname, w.surname 
    LIMIT 50
";

$families = $wpdb->get_results($wpdb->prepare($families_query, ...$search_params));

$total_families = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$prefix}families f 
     LEFT JOIN {$prefix}individuals h ON f.husband_id = h.id
     LEFT JOIN {$prefix}individuals w ON f.wife_id = w.id
     $where_clause",
    ...$search_params
));
?>

<div class="wrap">
    <h1>
        <?php _e('Families Management', 'heritage-press'); ?>
        <a href="<?php echo admin_url('admin.php?page=heritage-press-families&action=add'); ?>" class="page-title-action">
            <?php _e('Add New Family', 'heritage-press'); ?>
        </a>
    </h1>

    <?php if ($action === 'view' && $family): ?>
        <!-- Family Details View -->
        <div class="family-details">
            <h2><?php _e('Family Details', 'heritage-press'); ?></h2>
            
            <div class="family-info">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Husband', 'heritage-press'); ?></th>
                        <td>
                            <?php if ($husband): ?>
                                <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals&action=view&individual_id=' . $husband->id); ?>">
                                    <?php echo esc_html($husband->given_names . ' ' . $husband->surname); ?>
                                </a>
                                <?php if ($husband->birth_date): ?>
                                    (<?php printf(__('b. %s', 'heritage-press'), esc_html($husband->birth_date)); ?>)
                                <?php endif; ?>
                            <?php else: ?>
                                <?php _e('Unknown', 'heritage-press'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Wife', 'heritage-press'); ?></th>
                        <td>
                            <?php if ($wife): ?>
                                <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals&action=view&individual_id=' . $wife->id); ?>">
                                    <?php echo esc_html($wife->given_names . ' ' . $wife->surname); ?>
                                </a>
                                <?php if ($wife->birth_date): ?>
                                    (<?php printf(__('b. %s', 'heritage-press'), esc_html($wife->birth_date)); ?>)
                                <?php endif; ?>
                            <?php else: ?>
                                <?php _e('Unknown', 'heritage-press'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Marriage Date', 'heritage-press'); ?></th>
                        <td><?php echo esc_html($family->marriage_date ?: __('Unknown', 'heritage-press')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Divorce Date', 'heritage-press'); ?></th>
                        <td><?php echo esc_html($family->divorce_date ?: __('N/A', 'heritage-press')); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('UUID', 'heritage-press'); ?></th>
                        <td><code><?php echo esc_html($family->uuid); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('File ID', 'heritage-press'); ?></th>
                        <td><code><?php echo esc_html($family->file_id); ?></code></td>
                    </tr>
                    <?php if ($family->notes): ?>
                    <tr>
                        <th scope="row"><?php _e('Notes', 'heritage-press'); ?></th>
                        <td><?php echo wpautop(esc_html($family->notes)); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Children -->
            <div class="family-children">
                <h3><?php _e('Children', 'heritage-press'); ?></h3>
                <?php if ($children): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php _e('Name', 'heritage-press'); ?></th>
                                <th scope="col"><?php _e('Birth Date', 'heritage-press'); ?></th>
                                <th scope="col"><?php _e('Gender', 'heritage-press'); ?></th>
                                <th scope="col"><?php _e('Relationship', 'heritage-press'); ?></th>
                                <th scope="col"><?php _e('Actions', 'heritage-press'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($children as $child): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals&action=view&individual_id=' . $child->id); ?>">
                                            <?php echo esc_html($child->given_names . ' ' . $child->surname); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($child->birth_date ?: '—'); ?></td>
                                    <td><?php echo esc_html($child->gender ?: '—'); ?></td>
                                    <td><?php echo esc_html(ucfirst($child->relationship_type)); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals&action=view&individual_id=' . $child->id); ?>" class="button button-small">
                                            <?php _e('View', 'heritage-press'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No children recorded for this family.', 'heritage-press'); ?></p>
                <?php endif; ?>
            </div>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=heritage-press-families'); ?>" class="button">
                    <?php _e('← Back to Families List', 'heritage-press'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=heritage-press-families&action=edit&family_id=' . $family->id); ?>" class="button button-primary">
                    <?php _e('Edit Family', 'heritage-press'); ?>
                </a>
            </p>
        </div>

    <?php elseif ($action === 'add'): ?>
        <!-- Add New Family Form -->
        <div class="family-form">
            <h2><?php _e('Add New Family', 'heritage-press'); ?></h2>
            <p><?php _e('Note: This is a basic form. For full functionality, please use the GEDCOM import feature.', 'heritage-press'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('heritage_press_add_family'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="marriage_date"><?php _e('Marriage Date', 'heritage-press'); ?></label></th>
                        <td><input type="date" id="marriage_date" name="marriage_date" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="divorce_date"><?php _e('Divorce Date', 'heritage-press'); ?></label></th>
                        <td><input type="date" id="divorce_date" name="divorce_date" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notes"><?php _e('Notes', 'heritage-press'); ?></label></th>
                        <td><textarea id="notes" name="notes" rows="4" cols="50"></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Add Family', 'heritage-press'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=heritage-press-families'); ?>" class="button">
                        <?php _e('Cancel', 'heritage-press'); ?>
                    </a>
                </p>
            </form>
        </div>

    <?php else: ?>
        <!-- Families List -->
        <div class="families-list">
            <!-- Search Form -->
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="heritage-press-families" />
                <p class="search-box">
                    <label for="search-families"><?php _e('Search Families:', 'heritage-press'); ?></label>
                    <input type="search" id="search-families" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by spouse name...', 'heritage-press'); ?>" />
                    <input type="submit" class="button" value="<?php _e('Search', 'heritage-press'); ?>" />
                    <?php if ($search): ?>
                        <a href="<?php echo admin_url('admin.php?page=heritage-press-families'); ?>" class="button">
                            <?php _e('Clear', 'heritage-press'); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </form>

            <!-- Results Summary -->
            <p class="results-summary">
                <?php
                if ($search) {
                    printf(
                        __('Found %d families matching "%s"', 'heritage-press'),
                        $total_families,
                        esc_html($search)
                    );
                } else {
                    printf(
                        __('Showing %d of %d total families', 'heritage-press'),
                        count($families),
                        $total_families
                    );
                }
                ?>
            </p>

            <!-- Families Table -->
            <?php if ($families): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Husband', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('Wife', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('Marriage Date', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('Actions', 'heritage-press'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($families as $family): ?>
                            <tr>
                                <td>
                                    <?php if ($family->husband_given_names || $family->husband_surname): ?>
                                        <?php echo esc_html($family->husband_given_names . ' ' . $family->husband_surname); ?>
                                    <?php else: ?>
                                        <?php _e('Unknown', 'heritage-press'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($family->wife_given_names || $family->wife_surname): ?>
                                        <?php echo esc_html($family->wife_given_names . ' ' . $family->wife_surname); ?>
                                    <?php else: ?>
                                        <?php _e('Unknown', 'heritage-press'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($family->marriage_date ?: '—'); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=heritage-press-families&action=view&family_id=' . $family->id); ?>" class="button button-small">
                                        <?php _e('View', 'heritage-press'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <p><?php _e('No families found.', 'heritage-press'); ?></p>
                    <?php if (!$search): ?>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=heritage-press-import'); ?>" class="button button-primary">
                                <?php _e('Import GEDCOM File', 'heritage-press'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=heritage-press-families&action=add'); ?>" class="button">
                                <?php _e('Add Family Manually', 'heritage-press'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.family-details {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.family-children {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #c3c4c7;
}

.family-form {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.search-form {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.search-form .search-box {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-form input[type="search"] {
    min-width: 300px;
}

.results-summary {
    font-style: italic;
    color: #646970;
    margin-bottom: 15px;
}

.no-results {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 40px;
    text-align: center;
}

.no-results p {
    font-size: 16px;
    margin-bottom: 20px;
}
</style>
