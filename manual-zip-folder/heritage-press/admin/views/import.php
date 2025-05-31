<?php
/**
 * GEDCOM Import Page
 *
 * @package HeritagePress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $wpdb;
$prefix = $wpdb->prefix . 'heritage_press_';

// Get existing GEDCOM trees
$gedcom_trees = $wpdb->get_results("SELECT * FROM {$prefix}gedcom_trees ORDER BY upload_date DESC");
?>

<div class="wrap">
    <h1><?php _e('GEDCOM Import & Management', 'heritage-press'); ?></h1>

    <div class="heritage-import-dashboard">
        
        <!-- Import New GEDCOM -->
        <div class="import-section">
            <h2><?php _e('Import New GEDCOM File', 'heritage-press'); ?></h2>
            <div class="import-form-container">
                <form method="post" enctype="multipart/form-data" id="gedcom-upload-form">
                    <?php wp_nonce_field('heritage_press_gedcom_import'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gedcom_file"><?php _e('GEDCOM File', 'heritage-press'); ?></label>
                            </th>
                            <td>
                                <input type="file" id="gedcom_file" name="gedcom_file" accept=".ged,.gedcom" required />
                                <p class="description">
                                    <?php _e('Select a GEDCOM file (.ged or .gedcom) to import. Maximum file size: 32MB.', 'heritage-press'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tree_title"><?php _e('Tree Title', 'heritage-press'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="tree_title" name="tree_title" class="regular-text" placeholder="<?php _e('My Family Tree', 'heritage-press'); ?>" />
                                <p class="description">
                                    <?php _e('Optional: Give your family tree a descriptive title.', 'heritage-press'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="tree_description"><?php _e('Description', 'heritage-press'); ?></label>
                            </th>
                            <td>
                                <textarea id="tree_description" name="tree_description" rows="3" cols="50" placeholder="<?php _e('Description of this family tree...', 'heritage-press'); ?>"></textarea>
                                <p class="description">
                                    <?php _e('Optional: Add a description for this family tree.', 'heritage-press'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="import-options">
                        <h3><?php _e('Import Options', 'heritage-press'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Privacy Settings', 'heritage-press'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="radio" name="privacy_mode" value="public" checked />
                                            <?php _e('Public - All data visible', 'heritage-press'); ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="privacy_mode" value="living" />
                                            <?php _e('Protect Living - Hide data for living individuals', 'heritage-press'); ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="privacy_mode" value="private" />
                                            <?php _e('Private - All data protected', 'heritage-press'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Import Mode', 'heritage-press'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="radio" name="import_mode" value="new" checked />
                                            <?php _e('New Tree - Create a new family tree', 'heritage-press'); ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="import_mode" value="update" />
                                            <?php _e('Update Existing - Update an existing tree', 'heritage-press'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" name="import_gedcom" class="button button-primary" value="<?php _e('Import GEDCOM File', 'heritage-press'); ?>" />
                        <span class="spinner" id="import-spinner"></span>
                    </p>
                </form>
                
                <div id="import-progress" style="display: none;">
                    <h3><?php _e('Import Progress', 'heritage-press'); ?></h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                    <p class="progress-text"><?php _e('Preparing import...', 'heritage-press'); ?></p>
                </div>
            </div>
        </div>

        <!-- Existing GEDCOM Trees -->
        <div class="existing-trees-section">
            <h2><?php _e('Existing GEDCOM Trees', 'heritage-press'); ?></h2>
            
            <?php if ($gedcom_trees): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Tree Title', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('File Name', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('Upload Date', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('Status', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('Records', 'heritage-press'); ?></th>
                            <th scope="col"><?php _e('Actions', 'heritage-press'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gedcom_trees as $tree): ?>
                            <?php
                            // Get record counts for this tree
                            $individuals_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$prefix}individuals WHERE file_id = %s AND status = 'active'",
                                $tree->tree_id
                            ));
                            $families_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$prefix}families WHERE file_id = %s AND status = 'active'",
                                $tree->tree_id
                            ));
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($tree->title ?: $tree->file_name); ?></strong>
                                    <?php if ($tree->description): ?>
                                        <br><small class="description"><?php echo esc_html($tree->description); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?php echo esc_html($tree->file_name); ?></code>
                                    <br><small><?php echo esc_html($tree->tree_id); ?></small>
                                </td>
                                <td><?php echo esc_html(mysql2date('M j, Y g:i A', $tree->upload_date)); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr(isset($tree->status) ? $tree->status : 'unknown'); ?>">
                                        <?php echo esc_html(isset($tree->status) ? ucfirst($tree->status) : __('Unknown', 'heritage-press')); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php printf(
                                        __('%d individuals, %d families', 'heritage-press'),
                                        $individuals_count,
                                        $families_count
                                    ); ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="<?php echo admin_url('admin.php?page=heritage-press-individuals&search=' . urlencode($tree->tree_id)); ?>">
                                                <?php _e('View Records', 'heritage-press'); ?>
                                            </a>
                                        </span>
                                        | <span class="export">
                                            <a href="<?php echo admin_url('admin.php?page=heritage-press-import&action=export&tree_id=' . $tree->tree_id); ?>">
                                                <?php _e('Export', 'heritage-press'); ?>
                                            </a>
                                        </span>
                                        <?php if ($tree->status === 'active'): ?>
                                        | <span class="archive">
                                            <a href="<?php echo admin_url('admin.php?page=heritage-press-import&action=archive&tree_id=' . $tree->tree_id); ?>" 
                                               onclick="return confirm('<?php _e('Are you sure you want to archive this tree?', 'heritage-press'); ?>')">
                                                <?php _e('Archive', 'heritage-press'); ?>
                                            </a>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-trees">
                    <p><?php _e('No GEDCOM trees have been imported yet.', 'heritage-press'); ?></p>
                    <p><?php _e('Import your first GEDCOM file using the form above to get started with your genealogy research.', 'heritage-press'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Import Instructions -->
        <div class="import-instructions">
            <h2><?php _e('Import Instructions', 'heritage-press'); ?></h2>
            <div class="instructions-content">
                <h3><?php _e('What is a GEDCOM file?', 'heritage-press'); ?></h3>
                <p><?php _e('GEDCOM (GEnealogical Data COMmunication) is a standard file format used by genealogy software to exchange family tree data. Most genealogy programs can export to GEDCOM format.', 'heritage-press'); ?></p>
                
                <h3><?php _e('Supported Software', 'heritage-press'); ?></h3>
                <ul>
                    <li><strong>Family Tree Maker</strong> - File → Export → GEDCOM</li>
                    <li><strong>Ancestry.com</strong> - Trees → Export Tree → GEDCOM</li>
                    <li><strong>RootsMagic</strong> - File → Export → GEDCOM</li>
                    <li><strong>Legacy Family Tree</strong> - File → Export → GEDCOM</li>
                    <li><strong>Gramps</strong> - Family Trees → Export → GEDCOM</li>
                    <li><strong>MyHeritage</strong> - Family Tree → Manage Tree → Export to GEDCOM</li>
                </ul>
                
                <h3><?php _e('File Requirements', 'heritage-press'); ?></h3>
                <ul>
                    <li><?php _e('File extension must be .ged or .gedcom', 'heritage-press'); ?></li>
                    <li><?php _e('Maximum file size: 32MB', 'heritage-press'); ?></li>
                    <li><?php _e('UTF-8 or ASCII character encoding recommended', 'heritage-press'); ?></li>
                    <li><?php _e('GEDCOM 5.5 or 7.0 format supported', 'heritage-press'); ?></li>
                </ul>

                <h3><?php _e('Import Tips', 'heritage-press'); ?></h3>
                <ul>
                    <li><?php _e('Large files may take several minutes to import', 'heritage-press'); ?></li>
                    <li><?php _e('You can import multiple GEDCOM files as separate trees', 'heritage-press'); ?></li>
                    <li><?php _e('Use privacy settings to control visibility of living individuals', 'heritage-press'); ?></li>
                    <li><?php _e('Review imported data after the process completes', 'heritage-press'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.heritage-import-dashboard {
    max-width: 1200px;
}

.import-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.import-form-container {
    max-width: 800px;
}

.import-options {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.import-options fieldset {
    border: none;
    padding: 0;
    margin: 0;
}

.import-options label {
    display: block;
    margin-bottom: 8px;
}

.existing-trees-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.no-trees {
    text-align: center;
    padding: 40px;
    color: #646970;
}

.import-instructions {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
}

.instructions-content h3 {
    color: #1d2327;
    margin-top: 25px;
    margin-bottom: 10px;
}

.instructions-content ul {
    margin-left: 20px;
}

.instructions-content li {
    margin-bottom: 5px;
}

.status-active {
    color: #00a32a;
    font-weight: bold;
}

.status-archived {
    color: #646970;
    font-style: italic;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-fill {
    height: 100%;
    background-color: #2271b1;
    transition: width 0.3s ease;
}

.progress-text {
    font-style: italic;
    color: #646970;
}

#import-spinner {
    float: none;
    margin-left: 10px;
}

.description {
    color: #646970;
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#gedcom-upload-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'heritage_press_import_gedcom');
        
        $('#import-spinner').addClass('is-active');
        $('#import-progress').show();
        
        // Simulate progress (in real implementation, this would be AJAX-based)
        var progress = 0;
        var progressInterval = setInterval(function() {
            progress += Math.random() * 20;
            if (progress > 90) progress = 90;
            
            $('.progress-fill').css('width', progress + '%');
            
            if (progress < 30) {
                $('.progress-text').text('<?php _e('Reading GEDCOM file...', 'heritage-press'); ?>');
            } else if (progress < 60) {
                $('.progress-text').text('<?php _e('Importing individuals...', 'heritage-press'); ?>');
            } else if (progress < 90) {
                $('.progress-text').text('<?php _e('Importing families and relationships...', 'heritage-press'); ?>');
            }
        }, 500);
        
        // Note: In a real implementation, you would use AJAX here
        // For now, we'll just show a message
        setTimeout(function() {
            clearInterval(progressInterval);
            $('.progress-fill').css('width', '100%');
            $('.progress-text').text('<?php _e('Import feature coming soon! Currently in development.', 'heritage-press'); ?>');
            $('#import-spinner').removeClass('is-active');
        }, 3000);
    });
});
</script>
