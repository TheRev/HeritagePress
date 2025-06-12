<?php
/**
 * Edit Tree Template for HeritagePress
 * Form for editing existing genealogy trees
 * Based on TNG edittree.php structure with WordPress styling
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Extract variables
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
?>

<div class="wrap hp-trees-wrap">
    <!-- Page Header -->
    <div class="hp-trees-header">
        <h1 class="hp-trees-title">
            <span class="dashicons dashicons-edit"></span>
            <?php printf(__('Edit Tree: %s', 'heritagepress'), esc_html($tree->title)); ?>
        </h1>
        <div class="hp-trees-actions">
            <a href="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Back to Trees', 'heritagepress'); ?>
            </a>
            <a href="<?php echo home_url('/genealogy/tree/' . $tree->gedcom); ?>" class="button" target="_blank">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('View Tree', 'heritagepress'); ?>
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo $this->get_error_text($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Tree Statistics Box -->
    <div class="hp-tree-stats-box">
        <h3><?php _e('Tree Statistics', 'heritagepress'); ?></h3>
        <div class="hp-stats-grid">
            <div class="hp-stat-item">
                <div class="hp-stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="hp-stat-content">
                    <div class="hp-stat-number"><?php echo number_format($tree->people_count); ?></div>
                    <div class="hp-stat-label"><?php _e('Individuals', 'heritagepress'); ?></div>
                </div>
            </div>
            <div class="hp-stat-item">
                <div class="hp-stat-icon">
                    <span class="dashicons dashicons-heart"></span>
                </div>
                <div class="hp-stat-content">
                    <div class="hp-stat-number"><?php echo number_format($tree->families_count); ?></div>
                    <div class="hp-stat-label"><?php _e('Families', 'heritagepress'); ?></div>
                </div>
            </div>
            <div class="hp-stat-item">
                <div class="hp-stat-icon">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="hp-stat-content">
                    <div class="hp-stat-number"><?php echo number_format($tree->sources_count); ?></div>
                    <div class="hp-stat-label"><?php _e('Sources', 'heritagepress'); ?></div>
                </div>
            </div>
            <?php if (isset($tree->media_count)): ?>
            <div class="hp-stat-item">
                <div class="hp-stat-icon">
                    <span class="dashicons dashicons-format-image"></span>
                </div>
                <div class="hp-stat-content">
                    <div class="hp-stat-number"><?php echo number_format($tree->media_count); ?></div>
                    <div class="hp-stat-label"><?php _e('Media', 'heritagepress'); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Tree Form -->
    <div class="hp-form-container">
        <form method="post" id="hp-trees-form" class="hp-trees-form">
            <?php wp_nonce_field('hp_trees_action'); ?>
            <input type="hidden" name="action" value="update_tree">
            <input type="hidden" name="tree_id" value="<?php echo esc_attr($tree->treeID); ?>">

            <!-- Main Form Content -->
            <div class="hp-form-sections">
                <!-- Basic Information Section -->
                <div class="hp-form-section">
                    <div class="hp-section-header">
                        <h2><?php _e('Basic Information', 'heritagepress'); ?></h2>
                        <p class="description"><?php _e('Update the basic details for this genealogy tree.', 'heritagepress'); ?></p>
                    </div>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Tree ID (Read-only) -->
                            <tr>
                                <th scope="row">
                                    <label for="gedcom_display"><?php _e('Tree ID', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="gedcom_display" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->gedcom); ?>" 
                                           readonly>
                                    <p class="description">
                                        <?php _e('Tree ID cannot be changed after creation to maintain data integrity.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Tree Title -->
                            <tr>
                                <th scope="row">
                                    <label for="title"><?php _e('Tree Name', 'heritagepress'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="title" 
                                           id="title" 
                                           class="regular-text hp-required" 
                                           value="<?php echo esc_attr($tree->title); ?>" 
                                           maxlength="255" 
                                           required>
                                    <p class="description">
                                        <?php _e('Display name for this family tree.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>                            <!-- Description -->
                            <tr>
                                <th scope="row">
                                    <label for="description"><?php _e('Description', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <textarea name="description" 
                                              id="description" 
                                              class="large-text" 
                                              rows="4"><?php echo esc_textarea($tree->description); ?></textarea>
                                    <p class="description">
                                        <?php _e('Optional description to help identify this tree and its contents.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Owner Contact Information Section -->
                <div class="hp-form-section">
                    <div class="hp-section-header">
                        <h2><?php _e('Owner Contact Information', 'heritagepress'); ?></h2>
                        <p class="description"><?php _e('Contact details for the tree owner (optional but recommended for genealogy sharing).', 'heritagepress'); ?></p>
                    </div>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Owner Name -->
                            <tr>
                                <th scope="row">
                                    <label for="owner"><?php _e('Owner Name', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="owner" 
                                           id="owner" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->owner ?? ''); ?>" 
                                           maxlength="255">
                                    <p class="description">
                                        <?php _e('Full name of the tree owner or researcher.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Email Address -->
                            <tr>
                                <th scope="row">
                                    <label for="email"><?php _e('Email Address', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->email ?? ''); ?>" 
                                           maxlength="255">
                                    <p class="description">
                                        <?php _e('Contact email for genealogy inquiries about this tree.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Address -->
                            <tr>
                                <th scope="row">
                                    <label for="address"><?php _e('Address', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="address" 
                                           id="address" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->address ?? ''); ?>" 
                                           maxlength="255">
                                    <p class="description">
                                        <?php _e('Street address (optional).', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- City -->
                            <tr>
                                <th scope="row">
                                    <label for="city"><?php _e('City', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="city" 
                                           id="city" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->city ?? ''); ?>" 
                                           maxlength="100">
                                    <p class="description">
                                        <?php _e('City or town.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- State/Province -->
                            <tr>
                                <th scope="row">
                                    <label for="state"><?php _e('State/Province', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="state" 
                                           id="state" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->state ?? ''); ?>" 
                                           maxlength="100">
                                    <p class="description">
                                        <?php _e('State, province, or region.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- ZIP/Postal Code -->
                            <tr>
                                <th scope="row">
                                    <label for="zip"><?php _e('ZIP/Postal Code', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="zip" 
                                           id="zip" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->zip ?? ''); ?>" 
                                           maxlength="20">
                                    <p class="description">
                                        <?php _e('ZIP or postal code.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Country -->
                            <tr>
                                <th scope="row">
                                    <label for="country"><?php _e('Country', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="country" 
                                           id="country" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->country ?? ''); ?>" 
                                           maxlength="100">
                                    <p class="description">
                                        <?php _e('Country name.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Phone -->
                            <tr>
                                <th scope="row">
                                    <label for="phone"><?php _e('Phone Number', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="tel" 
                                           name="phone" 
                                           id="phone" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->phone ?? ''); ?>" 
                                           maxlength="50">
                                    <p class="description">
                                        <?php _e('Contact phone number (optional).', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Privacy and Security Section -->
                <div class="hp-form-section">
                    <div class="hp-section-header">
                        <h2><?php _e('Privacy Settings', 'heritagepress'); ?></h2>
                        <p class="description"><?php _e('Control who can view this genealogy tree and its data.', 'heritagepress'); ?></p>
                    </div>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Keep Private Checkbox -->
                            <tr>
                                <th scope="row">
                                    <?php _e('Tree Privacy', 'heritagepress'); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <label for="private">
                                            <input type="checkbox" 
                                                   name="private" 
                                                   id="private" 
                                                   value="1" 
                                                   <?php checked($tree->private ?? 0, 1); ?>>
                                            <?php _e('Keep this tree private', 'heritagepress'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('When checked, this tree will be hidden from public view.', 'heritagepress'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>

                            <!-- GEDCOM Creation -->
                            <tr>
                                <th scope="row">
                                    <?php _e('GEDCOM Options', 'heritagepress'); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <label for="disallowgedcreate">
                                            <input type="checkbox" 
                                                   name="disallowgedcreate" 
                                                   id="disallowgedcreate" 
                                                   value="1" 
                                                   <?php checked($tree->disallowgedcreate ?? 0, 1); ?>>
                                            <?php _e('Disable GEDCOM file extraction', 'heritagepress'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Prevent visitors from downloading GEDCOM files of this tree.', 'heritagepress'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>

                            <!-- PDF Generation -->
                            <tr>
                                <th scope="row">
                                    <?php _e('PDF Options', 'heritagepress'); ?>
                                </th>
                                <td>
                                    <fieldset>
                                        <label for="disallowpdf">
                                            <input type="checkbox" 
                                                   name="disallowpdf" 
                                                   id="disallowpdf" 
                                                   value="1" 
                                                   <?php checked($tree->disallowpdf ?? 0, 1); ?>>
                                            <?php _e('Disable PDF report generation', 'heritagepress'); ?>
                                        </label>
                                        <p class="description">
                                            <?php _e('Prevent PDF reports from being generated for this tree.', 'heritagepress'); ?>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>

                            <!-- Privacy Level -->
                            <tr>
                                <th scope="row">
                                    <label for="privacy_level"><?php _e('Privacy Level', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <select name="privacy_level" id="privacy_level" class="regular-text">
                                        <option value="0" <?php selected($tree->privacy_level, 0); ?>><?php _e('Public - Anyone can view', 'heritagepress'); ?></option>
                                        <option value="1" <?php selected($tree->privacy_level, 1); ?>><?php _e('Private - Only you can view', 'heritagepress'); ?></option>
                                        <option value="2" <?php selected($tree->privacy_level, 2); ?>><?php _e('Living Only - Hide deceased individuals', 'heritagepress'); ?></option>
                                        <option value="3" <?php selected($tree->privacy_level, 3); ?>><?php _e('Members Only - Registered users only', 'heritagepress'); ?></option>
                                    </select>
                                    <p class="description" id="privacy-description">
                                        <?php _e('Choose who can access this family tree.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tree Management Section -->
                <div class="hp-form-section">
                    <div class="hp-section-header">
                        <h2><?php _e('Tree Management', 'heritagepress'); ?></h2>
                        <p class="description"><?php _e('Configure ownership and root person for this tree.', 'heritagepress'); ?></p>
                    </div>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Root Person -->
                            <tr>
                                <th scope="row">
                                    <label for="rootpersonID"><?php _e('Root Person', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="rootpersonID" 
                                           id="rootpersonID" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr($tree->rootpersonID); ?>" 
                                           maxlength="22">
                                    <p class="description">
                                        <?php _e('Person ID to use as the starting point for this tree (leave blank for automatic selection).', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Owner Assignment -->
                            <tr>
                                <th scope="row">
                                    <label for="owner_user_id"><?php _e('Tree Owner', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    $users = get_users(['capability' => 'manage_options']);
                                    ?>
                                    <select name="owner_user_id" id="owner_user_id" class="regular-text">
                                        <option value=""><?php _e('No specific owner', 'heritagepress'); ?></option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user->ID; ?>" 
                                                    <?php selected($user->ID, $tree->owner_user_id); ?>>
                                                <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Assign ownership of this tree to a specific user.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Creation/Update Info -->
                            <tr>
                                <th scope="row">
                                    <?php _e('Tree Information', 'heritagepress'); ?>
                                </th>
                                <td>
                                    <div class="hp-tree-info">
                                        <p>
                                            <strong><?php _e('Created:', 'heritagepress'); ?></strong>
                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tree->created_at)); ?>
                                        </p>
                                        <p>
                                            <strong><?php _e('Last Updated:', 'heritagepress'); ?></strong>
                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tree->updated_at)); ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="hp-form-actions">
                <div class="hp-form-buttons">
                    <input type="submit" 
                           name="submit" 
                           id="submit" 
                           class="button button-primary" 
                           value="<?php _e('Update Tree', 'heritagepress'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>" 
                       class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tree Management Tools -->
    <div class="hp-tree-management-tools">
        <h3><?php _e('Tree Management Tools', 'heritagepress'); ?></h3>
        <div class="hp-tools-grid">
            <div class="hp-tool-card">
                <div class="hp-tool-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="hp-tool-content">
                    <h4><?php _e('Manage Individuals', 'heritagepress'); ?></h4>
                    <p><?php _e('Add, edit, and organize people in this tree.', 'heritagepress'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-individuals&tree=' . $tree->gedcom); ?>" 
                       class="button"><?php _e('Manage People', 'heritagepress'); ?></a>
                </div>
            </div>

            <div class="hp-tool-card">
                <div class="hp-tool-icon">
                    <span class="dashicons dashicons-upload"></span>
                </div>
                <div class="hp-tool-content">
                    <h4><?php _e('Import Data', 'heritagepress'); ?></h4>
                    <p><?php _e('Import GEDCOM data into this tree.', 'heritagepress'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-importexport&action=import&tree=' . $tree->treeID); ?>" 
                       class="button"><?php _e('Import GEDCOM', 'heritagepress'); ?></a>
                </div>
            </div>

            <div class="hp-tool-card">
                <div class="hp-tool-icon">
                    <span class="dashicons dashicons-download"></span>
                </div>
                <div class="hp-tool-content">
                    <h4><?php _e('Export Data', 'heritagepress'); ?></h4>
                    <p><?php _e('Export this tree as a GEDCOM file.', 'heritagepress'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-importexport&action=export&tree=' . $tree->treeID); ?>" 
                       class="button"><?php _e('Export GEDCOM', 'heritagepress'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="hp-danger-zone">
        <h3><?php _e('Danger Zone', 'heritagepress'); ?></h3>
        <div class="hp-danger-content">
            <div class="hp-danger-action">
                <div class="hp-danger-info">
                    <h4><?php _e('Delete Tree', 'heritagepress'); ?></h4>
                    <p><?php _e('Permanently delete this tree and all associated data. This action cannot be undone.', 'heritagepress'); ?></p>
                </div>
                <div class="hp-danger-button">
                    <button type="button" 
                            class="button button-link-delete hp-delete-tree" 
                            data-tree-id="<?php echo $tree->treeID; ?>" 
                            data-tree-name="<?php echo esc_attr($tree->title); ?>">
                        <?php _e('Delete Tree', 'heritagepress'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Level Descriptions (Hidden) -->
<div id="privacy-descriptions" style="display: none;">
    <div data-level="0"><?php _e('This tree will be visible to all website visitors. All data will be publicly accessible.', 'heritagepress'); ?></div>
    <div data-level="1"><?php _e('This tree will only be visible to you when logged in. Perfect for sensitive family information.', 'heritagepress'); ?></div>
    <div data-level="2"><?php _e('This tree will hide information about living individuals while showing historical family data.', 'heritagepress'); ?></div>
    <div data-level="3"><?php _e('This tree will only be accessible to registered users who are logged into your website.', 'heritagepress'); ?></div>
</div>

<!-- Delete Confirmation Modal -->
<div id="hp-delete-tree-modal" class="hp-modal" style="display: none;">
    <div class="hp-modal-content">
        <div class="hp-modal-header">
            <h3><?php _e('Delete Tree', 'heritagepress'); ?></h3>
            <button type="button" class="hp-modal-close">&times;</button>
        </div>
        <div class="hp-modal-body">
            <p><?php _e('Are you sure you want to delete this tree?', 'heritagepress'); ?></p>
            <p><strong id="hp-delete-tree-name"></strong></p>
            <p class="hp-warning">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('This action cannot be undone. All associated data (people, families, sources, media) will be permanently deleted.', 'heritagepress'); ?>
            </p>
        </div>
        <div class="hp-modal-footer">
            <form method="post" id="hp-delete-tree-form">
                <?php wp_nonce_field('hp_trees_action'); ?>
                <input type="hidden" name="action" value="delete_tree">
                <input type="hidden" name="tree_id" id="hp-delete-tree-id">
                <button type="button" class="button hp-modal-cancel"><?php _e('Cancel', 'heritagepress'); ?></button>
                <button type="submit" class="button button-primary hp-delete-confirm"><?php _e('Delete Tree', 'heritagepress'); ?></button>
            </form>
        </div>
    </div>
</div>
