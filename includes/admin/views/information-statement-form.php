<?php
/**
 * Information Statement Form View
 * 
 * @package Heritage_Press
 */

if (!defined('ABSPATH')) {
    exit;
}

$statement_id = intval($_GET['statement_id'] ?? 0);
$research_question_id = intval($_GET['research_question_id'] ?? 0);
$is_edit = !empty($statement);
$page_title = $is_edit ? 'Edit Information Statement' : 'Add New Information Statement';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=heritage-information-statements'); ?>" class="page-title-action">Back to List</a>
    
    <hr class="wp-header-end">

    <form id="information-statement-form" method="post">
        <?php wp_nonce_field('heritage_evidence_nonce', 'heritage_evidence_nonce'); ?>
        <input type="hidden" name="statement_id" value="<?php echo esc_attr($statement_id); ?>">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- Main Statement Content -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Statement Details</h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="research_question_id">Research Question *</label>
                                    </th>
                                    <td>
                                        <select name="research_question_id" id="research_question_id" class="regular-text" required>
                                            <option value="">Select Research Question</option>
                                            <?php
                                            $question_repo = new Research_Question_Repository();
                                            $research_questions = $question_repo->get_all();
                                            foreach ($research_questions as $question):
                                                $selected = '';
                                                if ($is_edit && $statement->research_question_id == $question->id) {
                                                    $selected = 'selected';
                                                } elseif (!$is_edit && $research_question_id == $question->id) {
                                                    $selected = 'selected';
                                                }
                                            ?>
                                                <option value="<?php echo esc_attr($question->id); ?>" <?php echo $selected; ?>>
                                                    <?php echo esc_html($question->question_text); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description">The research question this statement helps answer.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="statement_text">Statement Text *</label>
                                    </th>
                                    <td>
                                        <textarea name="statement_text" id="statement_text" rows="8" class="large-text" required><?php echo $is_edit ? esc_textarea($statement->statement_text) : ''; ?></textarea>
                                        <p class="description">Enter the exact information statement as found in the source. Use quotation marks for direct quotes.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="statement_type">Statement Type *</label>
                                    </th>
                                    <td>
                                        <select name="statement_type" id="statement_type" class="regular-text" required>
                                            <option value="">Select Type</option>
                                            <option value="direct_statement" <?php echo ($is_edit && $statement->statement_type === 'direct_statement') ? 'selected' : ''; ?>>Direct Statement</option>
                                            <option value="indirect_evidence" <?php echo ($is_edit && $statement->statement_type === 'indirect_evidence') ? 'selected' : ''; ?>>Indirect Evidence</option>
                                            <option value="negative_evidence" <?php echo ($is_edit && $statement->statement_type === 'negative_evidence') ? 'selected' : ''; ?>>Negative Evidence</option>
                                            <option value="conflicting_evidence" <?php echo ($is_edit && $statement->statement_type === 'conflicting_evidence') ? 'selected' : ''; ?>>Conflicting Evidence</option>
                                        </select>
                                        <p class="description">Classification based on Evidence Explained methodology.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="information_quality">Information Quality *</label>
                                    </th>
                                    <td>
                                        <select name="information_quality" id="information_quality" class="regular-text" required>
                                            <option value="">Select Quality Level</option>
                                            <option value="primary" <?php echo ($is_edit && $statement->information_quality === 'primary') ? 'selected' : ''; ?>>Primary</option>
                                            <option value="secondary" <?php echo ($is_edit && $statement->information_quality === 'secondary') ? 'selected' : ''; ?>>Secondary</option>
                                            <option value="tertiary" <?php echo ($is_edit && $statement->information_quality === 'tertiary') ? 'selected' : ''; ?>>Tertiary</option>
                                            <option value="undetermined" <?php echo ($is_edit && $statement->information_quality === 'undetermined') ? 'selected' : ''; ?>>Undetermined</option>
                                        </select>
                                        <p class="description">Assessment of information quality according to proximity to original event.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="specific_location">Specific Location</label>
                                    </th>
                                    <td>
                                        <input type="text" name="specific_location" id="specific_location" value="<?php echo $is_edit ? esc_attr($statement->specific_location) : ''; ?>" class="large-text">
                                        <p class="description">Page number, section, or other specific location within the source (e.g., "p. 234", "line 15", "Section 3.2").</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="transcription_notes">Transcription Notes</label>
                                    </th>
                                    <td>
                                        <textarea name="transcription_notes" id="transcription_notes" rows="4" class="large-text"><?php echo $is_edit ? esc_textarea($statement->transcription_notes) : ''; ?></textarea>
                                        <p class="description">Notes about transcription challenges, abbreviations expanded, spelling variations, etc.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Source Information -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Source Information</h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="source_id">Source</label>
                                    </th>
                                    <td>
                                        <select name="source_id" id="source_id" class="regular-text">
                                            <option value="">Select Source</option>
                                            <!-- Sources would be populated from source repository -->
                                            <?php if ($is_edit && $statement->source_id): ?>
                                                <option value="<?php echo esc_attr($statement->source_id); ?>" selected>Source #<?php echo esc_html($statement->source_id); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <p class="description">The source document or record containing this information.</p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="citation_id">Citation</label>
                                    </th>
                                    <td>
                                        <select name="citation_id" id="citation_id" class="regular-text">
                                            <option value="">Select Citation</option>
                                            <!-- Citations would be populated based on selected source -->
                                            <?php if ($is_edit && $statement->citation_id): ?>
                                                <option value="<?php echo esc_attr($statement->citation_id); ?>" selected>Citation #<?php echo esc_html($statement->citation_id); ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <p class="description">Specific citation format for referencing this information.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <!-- Save Actions -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Save Statement</h2>
                        </div>
                        <div class="inside">
                            <div class="submitbox">
                                <div id="save-action">
                                    <input type="submit" name="save_draft" id="save-draft" class="button" value="Save Draft">
                                </div>
                                
                                <div id="publishing-action">
                                    <input type="submit" name="publish" id="publish" class="button button-primary button-large" value="<?php echo $is_edit ? 'Update Statement' : 'Save Statement'; ?>">
                                </div>
                                
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Statement Preview -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Preview</h2>
                        </div>
                        <div class="inside">
                            <div id="statement-preview">
                                <p><em>Enter statement text to see preview...</em></p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Analysis -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Quick Analysis</h2>
                        </div>
                        <div class="inside">
                            <p>After saving this statement, you can:</p>
                            <ul>
                                <li>• Create evidence analysis</li>
                                <li>• Link to other statements</li>
                                <li>• Develop proof arguments</li>
                                <li>• Export citations</li>
                            </ul>
                            
                            <?php if ($is_edit): ?>
                                <p style="margin-top: 15px;">
                                    <a href="<?php echo admin_url('admin.php?page=heritage-evidence-analysis&action=add&information_statement_id=' . $statement->id); ?>" class="button button-secondary">
                                        Create Analysis
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- File Association -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">File Association</h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="file_id">Associated File</label>
                                    </th>
                                    <td>
                                        <input type="text" name="file_id" id="file_id" value="<?php echo $is_edit ? esc_attr($statement->file_id) : ''; ?>" class="regular-text">
                                        <p class="description">File ID or identifier in Heritage Press system.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.statement-form-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.statement-form-section h3 {
    margin: 0;
    padding: 15px 20px;
    background: #f6f7f7;
    border-bottom: 1px solid #ccd0d4;
    font-size: 14px;
    font-weight: 600;
}

.statement-form-section .form-content {
    padding: 20px;
}

.form-table th {
    width: 150px;
    vertical-align: top;
    padding-top: 15px;
}

.form-table td {
    padding-top: 10px;
}

.submitbox {
    padding: 12px;
}

#save-action, #publishing-action {
    margin-bottom: 10px;
}

#publishing-action {
    text-align: center;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}

#statement-preview {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    min-height: 60px;
    font-style: italic;
}

#statement-preview.has-content {
    font-style: normal;
    background: #fff;
}

.postbox .inside ul {
    padding-left: 0;
    list-style: none;
}

.postbox .inside ul li {
    padding: 2px 0;
    color: #666;
}

.required-field {
    color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Form submission handling
    $('#information-statement-form').on('submit', function(e) {
        e.preventDefault();
        saveInformationStatement();
    });

    // Live preview
    $('#statement_text').on('input', function() {
        updatePreview();
    });

    // Update preview on load if editing
    updatePreview();

    function updatePreview() {
        var text = $('#statement_text').val().trim();
        var preview = $('#statement-preview');
        
        if (text) {
            preview.html('<strong>Statement:</strong><br>' + escapeHtml(text)).addClass('has-content');
        } else {
            preview.html('<em>Enter statement text to see preview...</em>').removeClass('has-content');
        }
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function saveInformationStatement() {
        var formData = {
            action: 'save_information_statement',
            nonce: $('#heritage_evidence_nonce').val(),
            statement_id: $('input[name="statement_id"]').val(),
            research_question_id: $('#research_question_id').val(),
            statement_text: $('#statement_text').val(),
            statement_type: $('#statement_type').val(),
            information_quality: $('#information_quality').val(),
            specific_location: $('#specific_location').val(),
            transcription_notes: $('#transcription_notes').val(),
            source_id: $('#source_id').val(),
            citation_id: $('#citation_id').val(),
            file_id: $('#file_id').val()
        };

        // Disable form during submission
        $('#information-statement-form input, #information-statement-form select, #information-statement-form textarea').prop('disabled', true);
        $('#publish').val('Saving...').prop('disabled', true);

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                // Show success message
                $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>Information statement saved successfully.</p></div>');
                
                // Redirect to list or view page
                setTimeout(function() {
                    window.location.href = '<?php echo admin_url('admin.php?page=heritage-information-statements'); ?>';
                }, 1500);
            } else {
                // Show error message
                $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>Error: ' + response.data.message + '</p></div>');
                
                // Re-enable form
                $('#information-statement-form input, #information-statement-form select, #information-statement-form textarea').prop('disabled', false);
                $('#publish').val('<?php echo $is_edit ? 'Update Statement' : 'Save Statement'; ?>').prop('disabled', false);
            }
        }).fail(function() {
            $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>Network error. Please try again.</p></div>');
            
            // Re-enable form
            $('#information-statement-form input, #information-statement-form select, #information-statement-form textarea').prop('disabled', false);
            $('#publish').val('<?php echo $is_edit ? 'Update Statement' : 'Save Statement'; ?>').prop('disabled', false);
        });
    }

    // Source selection changes citation options
    $('#source_id').on('change', function() {
        var sourceId = $(this).val();
        var citationSelect = $('#citation_id');
        
        citationSelect.html('<option value="">Loading citations...</option>');
        
        if (sourceId) {
            // Load citations for selected source
            $.post(ajaxurl, {
                action: 'get_source_citations',
                source_id: sourceId,
                nonce: $('#heritage_evidence_nonce').val()
            }, function(response) {
                if (response.success) {
                    var options = '<option value="">Select Citation</option>';
                    $.each(response.data.citations, function(index, citation) {
                        options += '<option value="' + citation.id + '">' + citation.citation_text + '</option>';
                    });
                    citationSelect.html(options);
                } else {
                    citationSelect.html('<option value="">No citations available</option>');
                }
            });
        } else {
            citationSelect.html('<option value="">Select Citation</option>');
        }
    });
});
</script>
