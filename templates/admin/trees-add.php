<?php
/**
 * Add Tree Template for HeritagePress
 * Form for creating new genealogy trees
 * Based on TNG addtree.php structure with WordPress styling
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
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Add New Tree', 'heritagepress'); ?>
        </h1>
        <div class="hp-trees-actions">
            <a href="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Back to Trees', 'heritagepress'); ?>
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo $this->get_error_text($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Add Tree Form -->
    <div class="hp-form-container">
        <form method="post" id="hp-trees-form" class="hp-trees-form">
            <?php wp_nonce_field('hp_trees_action'); ?>
            <input type="hidden" name="action" value="add_tree">

            <!-- Main Form Content -->
            <div class="hp-form-sections">
                <!-- Basic Information Section -->
                <div class="hp-form-section">
                    <div class="hp-section-header">
                        <h2><?php _e('Basic Information', 'heritagepress'); ?></h2>
                        <p class="description"><?php _e('Enter the basic details for your new genealogy tree.', 'heritagepress'); ?></p>
                    </div>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Tree ID (GEDCOM) -->
                            <tr>
                                <th scope="row">
                                    <label for="gedcom"><?php _e('Tree ID', 'heritagepress'); ?> <span class="required">*</span></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="gedcom" 
                                           id="gedcom" 
                                           class="regular-text hp-required" 
                                           value="" 
                                           maxlength="20" 
                                           pattern="[a-zA-Z0-9_-]+"
                                           required>
                                    <p class="description">
                                        <?php _e('Unique identifier for this tree (letters, numbers, hyphens, and underscores only). Example: smith_family', 'heritagepress'); ?>
                                    </p>
                                    <div id="gedcom-validation-message" class="hp-validation-message"></div>
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
                                           value="" 
                                           maxlength="255" 
                                           required>
                                    <p class="description">
                                        <?php _e('Display name for this family tree. Example: "The Smith Family Tree"', 'heritagepress'); ?>
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
                                              rows="4"
                                              placeholder="<?php _e('Optional description of this family tree...', 'heritagepress'); ?>"></textarea>
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
                                           value="" 
                                           maxlength="255"
                                           placeholder="<?php _e('Full name of tree owner...', 'heritagepress'); ?>">
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
                                           value="" 
                                           maxlength="255"
                                           placeholder="<?php _e('contact@example.com', 'heritagepress'); ?>">
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
                                           value="" 
                                           maxlength="255"
                                           placeholder="<?php _e('Street address...', 'heritagepress'); ?>">
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
                                           value="" 
                                           maxlength="100"
                                           placeholder="<?php _e('City or town...', 'heritagepress'); ?>">
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
                                           value="" 
                                           maxlength="100"
                                           placeholder="<?php _e('State or province...', 'heritagepress'); ?>">
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
                                           value="" 
                                           maxlength="20"
                                           placeholder="<?php _e('ZIP or postal code...', 'heritagepress'); ?>">
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
                                           value="" 
                                           maxlength="100"
                                           placeholder="<?php _e('Country name...', 'heritagepress'); ?>">
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
                                           value="" 
                                           maxlength="50"
                                           placeholder="<?php _e('+1 (555) 123-4567', 'heritagepress'); ?>">
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
                                                   value="1">
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
                                                   value="1">
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
                                                   value="1">
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
                                        <option value="0"><?php _e('Public - Anyone can view', 'heritagepress'); ?></option>
                                        <option value="1"><?php _e('Private - Only you can view', 'heritagepress'); ?></option>
                                        <option value="2"><?php _e('Living Only - Hide deceased individuals', 'heritagepress'); ?></option>
                                        <option value="3"><?php _e('Members Only - Registered users only', 'heritagepress'); ?></option>
                                    </select>
                                    <p class="description" id="privacy-description">
                                        <?php _e('Choose who can access this family tree.', 'heritagepress'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tree Configuration Section -->
                <div class="hp-form-section">
                    <div class="hp-section-header">
                        <h2><?php _e('Tree Configuration', 'heritagepress'); ?></h2>
                        <p class="description"><?php _e('Configure how this tree will be managed and displayed.', 'heritagepress'); ?></p>
                    </div>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- Root Person (Optional) -->
                            <tr>
                                <th scope="row">
                                    <label for="rootpersonID"><?php _e('Root Person', 'heritagepress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="rootpersonID" 
                                           id="rootpersonID" 
                                           class="regular-text" 
                                           value="" 
                                           maxlength="22">
                                    <p class="description">
                                        <?php _e('Optional: Person ID to use as the starting point for this tree (can be set later).', 'heritagepress'); ?>
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
                                    $current_user = wp_get_current_user();
                                    $users = get_users(['capability' => 'manage_options']);
                                    ?>
                                    <select name="owner_user_id" id="owner_user_id" class="regular-text">
                                        <option value=""><?php _e('No specific owner', 'heritagepress'); ?></option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user->ID; ?>" 
                                                    <?php selected($user->ID, $current_user->ID); ?>>
                                                <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Assign ownership of this tree to a specific user.', 'heritagepress'); ?>
                                    </p>
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
                           value="<?php _e('Create Tree', 'heritagepress'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-trees'); ?>" 
                       class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
                </div>
                
                <div class="hp-form-help">
                    <p class="description">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('After creating your tree, you can import GEDCOM data or manually add individuals and families.', 'heritagepress'); ?>
                    </p>
                </div>
            </div>
        </form>
    </div>

    <!-- Quick Start Guide -->
    <div class="hp-quick-start-guide">
        <h3><?php _e('Quick Start Guide', 'heritagepress'); ?></h3>
        <ol class="hp-steps-list">
            <li>
                <strong><?php _e('Create Your Tree', 'heritagepress'); ?></strong>
                <p><?php _e('Fill out the form above to create your new family tree.', 'heritagepress'); ?></p>
            </li>
            <li>
                <strong><?php _e('Import Your Data', 'heritagepress'); ?></strong>
                <p><?php _e('Use the Import/Export tool to upload a GEDCOM file with your genealogy data.', 'heritagepress'); ?></p>
            </li>
            <li>
                <strong><?php _e('Manage Your Tree', 'heritagepress'); ?></strong>
                <p><?php _e('Add individuals, families, sources, and media to build your family history.', 'heritagepress'); ?></p>
            </li>
            <li>
                <strong><?php _e('Share and Publish', 'heritagepress'); ?></strong>
                <p><?php _e('Configure privacy settings and share your family tree with others.', 'heritagepress'); ?></p>
            </li>
        </ol>
    </div>
</div>

<!-- Privacy Level Descriptions (Hidden) -->
<div id="privacy-descriptions" style="display: none;">
    <div data-level="0"><?php _e('This tree will be visible to all website visitors. All data will be publicly accessible.', 'heritagepress'); ?></div>
    <div data-level="1"><?php _e('This tree will only be visible to you when logged in. Perfect for sensitive family information.', 'heritagepress'); ?></div>
    <div data-level="2"><?php _e('This tree will hide information about living individuals while showing historical family data.', 'heritagepress'); ?></div>
    <div data-level="3"><?php _e('This tree will only be accessible to registered users who are logged into your website.', 'heritagepress'); ?></div>
</div>
