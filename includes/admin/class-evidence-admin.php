<?php
/**
 * Evidence Explained Admin Interface
 *
 * Manages the WordPress admin interface for Evidence Explained functionality.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Admin;

use HeritagePress\Services\Evidence_Analysis_Service;
use HeritagePress\Services\Evidence_Citation_Formatter;
use HeritagePress\Repositories\Research_Question_Repository;
use HeritagePress\Repositories\Information_Statement_Repository;
use HeritagePress\Repositories\Evidence_Analysis_Repository;
use HeritagePress\Repositories\Proof_Argument_Repository;
use HeritagePress\Core\Audit_Log_Observer;

class Evidence_Admin {

    private $evidence_service;
    private $citation_formatter;
    private $research_repo;
    private $info_repo;
    private $evidence_repo;
    private $proof_repo;    public function __construct() {
        global $wpdb;
        $audit_observer = new Audit_Log_Observer($wpdb, $wpdb->prefix . 'heritage_audit_log');
        
        $this->evidence_service = new Evidence_Analysis_Service();
        $this->citation_formatter = new Evidence_Citation_Formatter();
        $this->research_repo = new Research_Question_Repository($audit_observer);
        $this->info_repo = new Information_Statement_Repository($audit_observer);
        $this->evidence_repo = new Evidence_Analysis_Repository($audit_observer);
        $this->proof_repo = new Proof_Argument_Repository($audit_observer);

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);        // AJAX handlers
        add_action('wp_ajax_save_research_question', [$this, 'ajax_save_research_question']);
        add_action('wp_ajax_save_information_statement', [$this, 'ajax_save_information_statement']);
        add_action('wp_ajax_save_evidence_analysis', [$this, 'ajax_save_evidence_analysis']);
        add_action('wp_ajax_save_proof_argument', [$this, 'ajax_save_proof_argument']);
        add_action('wp_ajax_format_citation', [$this, 'ajax_format_citation']);
        add_action('wp_ajax_assess_evidence_quality', [$this, 'ajax_assess_evidence_quality']);
        
        // Quality assessment handlers
        add_action('wp_ajax_heritage_get_quality_assessment', [$this, 'ajax_get_quality_assessment']);
        add_action('wp_ajax_heritage_save_quality_assessment', [$this, 'ajax_save_quality_assessment']);
        
        // Delete handlers
        add_action('wp_ajax_heritage_delete_analysis', [$this, 'ajax_delete_analysis']);
        add_action('wp_ajax_heritage_delete_proof_argument', [$this, 'ajax_delete_proof_argument']);
        add_action('wp_ajax_heritage_delete_research_question', [$this, 'ajax_delete_research_question']);
        add_action('wp_ajax_heritage_delete_information_statement', [$this, 'ajax_delete_information_statement']);
        
        // Export handlers
        add_action('wp_ajax_heritage_export_analysis', [$this, 'ajax_export_analysis']);
        add_action('wp_ajax_heritage_export_proof_argument', [$this, 'ajax_export_proof_argument']);
        add_action('wp_ajax_heritage_export_research_question', [$this, 'ajax_export_research_question']);
        add_action('wp_ajax_heritage_export_information_statement', [$this, 'ajax_export_information_statement']);
        
        // Duplicate handlers
        add_action('wp_ajax_heritage_duplicate_analysis', [$this, 'ajax_duplicate_analysis']);
        add_action('wp_ajax_heritage_duplicate_proof_argument', [$this, 'ajax_duplicate_proof_argument']);
        add_action('wp_ajax_heritage_duplicate_research_question', [$this, 'ajax_duplicate_research_question']);
        add_action('wp_ajax_heritage_duplicate_information_statement', [$this, 'ajax_duplicate_information_statement']);
        
        // Bulk action handlers
        add_action('wp_ajax_heritage_bulk_delete_analyses', [$this, 'ajax_bulk_delete_analyses']);
        add_action('wp_ajax_heritage_bulk_delete_proof_arguments', [$this, 'ajax_bulk_delete_proof_arguments']);
        add_action('wp_ajax_heritage_bulk_delete_research_questions', [$this, 'ajax_bulk_delete_research_questions']);
        add_action('wp_ajax_heritage_bulk_delete_information_statements', [$this, 'ajax_bulk_delete_information_statements']);
    }    /**
     * Add admin menu pages
     */
    public function add_admin_menus() {
        // Main Evidence menu
        add_menu_page(
            'Evidence Research',
            'Evidence Research',
            'manage_options',
            'heritage-evidence',
            [$this, 'research_overview_page'],
            'dashicons-search',
            26
        );

        // Research Questions submenu
        add_submenu_page(
            'heritage-evidence',
            'Research Questions',
            'Research Questions',
            'manage_options',
            'heritage-research-questions',
            [$this, 'research_questions_page']
        );

        // Information Statements submenu
        add_submenu_page(
            'heritage-evidence',
            'Information Statements',
            'Information Statements',
            'manage_options',
            'heritage-information-statements',
            [$this, 'information_statements_page']
        );

        // Evidence Analysis submenu
        add_submenu_page(
            'heritage-evidence',
            'Evidence Analysis',
            'Evidence Analysis',
            'manage_options',
            'heritage-evidence-analysis',
            [$this, 'evidence_analysis_page']
        );

        // Proof Arguments submenu
        add_submenu_page(
            'heritage-evidence',
            'Proof Arguments',
            'Proof Arguments',
            'manage_options',
            'heritage-proof-arguments',
            [$this, 'proof_arguments_page']
        );

        // Citation Tools submenu
        add_submenu_page(
            'heritage-evidence',
            'Citation Tools',
            'Citation Tools',
            'manage_options',
            'heritage-citation-tools',
            [$this, 'citation_tools_page']
        );
    }

    /**
     * Register admin settings
     */
    public function register_settings() {
        register_setting('heritage_evidence_settings', 'heritage_evidence_options');

        add_settings_section(
            'heritage_evidence_general',
            'General Settings',
            [$this, 'general_settings_section_callback'],
            'heritage_evidence_settings'
        );

        add_settings_field(
            'default_citation_style',
            'Default Citation Style',
            [$this, 'citation_style_field_callback'],
            'heritage_evidence_settings',
            'heritage_evidence_general'
        );

        add_settings_field(
            'auto_assess_confidence',
            'Auto-assess Evidence Confidence',
            [$this, 'auto_assess_field_callback'],
            'heritage_evidence_settings',
            'heritage_evidence_general'
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'heritage-') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-datepicker');
        
        wp_enqueue_script(
            'heritage-evidence-admin',
            plugin_dir_url(__FILE__) . '../../admin/js/evidence-admin.js',
            ['jquery', 'jquery-ui-dialog'],
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'heritage-evidence-admin',
            plugin_dir_url(__FILE__) . '../../admin/css/evidence-admin.css',
            ['wp-jquery-ui-dialog'],
            '1.0.0'
        );        // Localize script with AJAX URL and nonce
        wp_localize_script('heritage-evidence-admin', 'heritage_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('heritage_admin_nonce')
        ]);
    }

    /**
     * Research Overview Page
     */
    public function research_overview_page() {
        $file_id = $_GET['file_id'] ?? '';
        $dashboard_data = [];

        if ($file_id) {
            $dashboard_data = $this->evidence_service->get_research_dashboard($file_id);
        }

        include dirname(__FILE__) . '/../admin/views/research-overview.php';
    }

    /**
     * Research Questions Page
     */
    public function research_questions_page() {
        $action = $_GET['action'] ?? 'list';
        $question_id = $_GET['question_id'] ?? null;

        switch ($action) {
            case 'add':
            case 'edit':
                $this->research_question_form($question_id);
                break;
            case 'view':
                $this->research_question_detail($question_id);
                break;
            default:
                $this->research_questions_list();
        }
    }

    /**
     * Information Statements Page
     */
    public function information_statements_page() {
        $action = $_GET['action'] ?? 'list';
        $statement_id = $_GET['statement_id'] ?? null;

        switch ($action) {
            case 'add':
            case 'edit':
                $this->information_statement_form($statement_id);
                break;
            case 'view':
                $this->information_statement_detail($statement_id);
                break;
            default:
                $this->information_statements_list();
        }
    }

    /**
     * Evidence Analysis Page
     */
    public function evidence_analysis_page() {
        $action = $_GET['action'] ?? 'list';
        $analysis_id = $_GET['analysis_id'] ?? null;

        switch ($action) {
            case 'add':
            case 'edit':
                $this->evidence_analysis_form($analysis_id);
                break;
            case 'view':
                $this->evidence_analysis_detail($analysis_id);
                break;
            default:
                $this->evidence_analysis_list();
        }
    }

    /**
     * Proof Arguments Page
     */
    public function proof_arguments_page() {
        $action = $_GET['action'] ?? 'list';
        $argument_id = $_GET['argument_id'] ?? null;

        switch ($action) {
            case 'add':
            case 'edit':
                $this->proof_argument_form($argument_id);
                break;
            case 'view':
                $this->proof_argument_detail($argument_id);
                break;
            default:
                $this->proof_arguments_list();
        }
    }

    /**
     * Citation Tools Page
     */
    public function citation_tools_page() {
        include dirname(__FILE__) . '/../admin/views/citation-tools.php';
    }

    /**
     * Research Questions List View
     */
    private function research_questions_list() {
        $file_id = $_GET['file_id'] ?? '';
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'question_type' => $_GET['question_type'] ?? ''
        ];

        if ($file_id) {
            $questions = $this->research_repo->find_by_file($file_id, $filters);
        } else {
            $questions = $this->research_repo->get_all($filters);
        }

        include dirname(__FILE__) . '/../admin/views/research-questions-list.php';
    }

    /**
     * Research Question Form
     */
    private function research_question_form($question_id = null) {
        $question = null;
        if ($question_id) {
            $question = $this->research_repo->find_by_id($question_id);
        }

        include dirname(__FILE__) . '/../admin/views/research-question-form.php';
    }

    /**
     * Research Question Detail View
     */
    private function research_question_detail($question_id) {
        $question = $this->research_repo->find_by_id($question_id);
        if (!$question) {
            wp_die('Research question not found.');
        }

        $evidence_analyses = $this->evidence_repo->find_by_research_question($question_id);
        $proof_arguments = $this->proof_repo->find_by_research_question($question_id);

        include dirname(__FILE__) . '/../admin/views/research-question-detail.php';
    }

    /**
     * Information Statements List View
     */
    private function information_statements_list() {
        $file_id = $_GET['file_id'] ?? '';
        $filters = [
            'source_id' => $_GET['source_id'] ?? '',
            'statement_type' => $_GET['statement_type'] ?? '',
            'information_quality' => $_GET['information_quality'] ?? ''
        ];

        if ($file_id) {
            $statements = $this->info_repo->find_by_file($file_id, $filters);
        } else {
            $statements = $this->info_repo->get_all($filters);
        }

        include dirname(__FILE__) . '/../admin/views/information-statements-list.php';
    }

    /**
     * Information Statement Form
     */
    private function information_statement_form($statement_id = null) {
        $statement = null;
        if ($statement_id) {
            $statement = $this->info_repo->find_by_id($statement_id);
        }

        include dirname(__FILE__) . '/../admin/views/information-statement-form.php';
    }

    /**
     * Information Statement Detail View
     */
    private function information_statement_detail($statement_id) {
        $statement = $this->info_repo->find_by_id($statement_id);
        if (!$statement) {
            wp_die('Information statement not found.');
        }

        include dirname(__FILE__) . '/../admin/views/information-statement-detail.php';
    }

    /**
     * Evidence Analysis List View
     */
    private function evidence_analysis_list() {
        $file_id = $_GET['file_id'] ?? '';
        $filters = [
            'research_question_id' => $_GET['research_question_id'] ?? '',
            'evidence_type' => $_GET['evidence_type'] ?? '',
            'quality_assessment' => $_GET['quality_assessment'] ?? ''
        ];

        if ($file_id) {
            $analyses = $this->evidence_repo->find_by_file($file_id, $filters);
        } else {
            $analyses = $this->evidence_repo->get_all($filters);
        }

        include dirname(__FILE__) . '/../admin/views/evidence-analysis-list.php';
    }

    /**
     * Evidence Analysis Form
     */
    private function evidence_analysis_form($analysis_id = null) {
        $analysis = null;
        if ($analysis_id) {
            $analysis = $this->evidence_repo->find_by_id($analysis_id);
        }

        include dirname(__FILE__) . '/../admin/views/evidence-analysis-form.php';
    }

    /**
     * Evidence Analysis Detail View
     */
    private function evidence_analysis_detail($analysis_id) {
        $analysis = $this->evidence_repo->find_by_id($analysis_id);
        if (!$analysis) {
            wp_die('Evidence analysis not found.');
        }

        include dirname(__FILE__) . '/../admin/views/evidence-analysis-detail.php';
    }

    /**
     * Proof Arguments List View
     */
    private function proof_arguments_list() {
        $file_id = $_GET['file_id'] ?? '';
        $filters = [
            'research_question_id' => $_GET['research_question_id'] ?? '',
            'argument_type' => $_GET['argument_type'] ?? '',
            'confidence_level' => $_GET['confidence_level'] ?? ''
        ];

        if ($file_id) {
            $arguments = $this->proof_repo->find_by_file($file_id, $filters);
        } else {
            $arguments = $this->proof_repo->get_all($filters);
        }

        include dirname(__FILE__) . '/../admin/views/proof-arguments-list.php';
    }

    /**
     * Proof Argument Form
     */
    private function proof_argument_form($argument_id = null) {
        $argument = null;
        if ($argument_id) {
            $argument = $this->proof_repo->find_by_id($argument_id);
        }

        include dirname(__FILE__) . '/../admin/views/proof-argument-form.php';
    }

    /**
     * Proof Argument Detail View
     */
    private function proof_argument_detail($argument_id) {
        $argument = $this->proof_repo->find_by_id($argument_id);
        if (!$argument) {
            wp_die('Proof argument not found.');
        }

        include dirname(__FILE__) . '/../admin/views/proof-argument-detail.php';
    }

    // AJAX Handlers

    /**
     * AJAX: Save Research Question
     */
    public function ajax_save_research_question() {
        check_ajax_referer('heritage_evidence_nonce', 'nonce');

        $data = [
            'question_text' => sanitize_textarea_field($_POST['question_text']),
            'question_type' => sanitize_text_field($_POST['question_type']),
            'individual_id' => intval($_POST['individual_id']),
            'priority' => sanitize_text_field($_POST['priority']),
            'research_notes' => sanitize_textarea_field($_POST['research_notes']),
            'file_id' => sanitize_text_field($_POST['file_id'])
        ];

        $question_id = intval($_POST['question_id']);

        if ($question_id) {
            $result = $this->research_repo->update($question_id, $data);
        } else {
            $result = $this->research_repo->create($data);
        }

        if ($result) {
            wp_send_json_success(['message' => 'Research question saved successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to save research question.']);
        }
    }

    /**
     * AJAX: Save Information Statement
     */
    public function ajax_save_information_statement() {
        check_ajax_referer('heritage_evidence_nonce', 'nonce');

        $data = [
            'source_id' => intval($_POST['source_id']),
            'citation_id' => intval($_POST['citation_id']),
            'statement_text' => sanitize_textarea_field($_POST['statement_text']),
            'statement_type' => sanitize_text_field($_POST['statement_type']),
            'information_quality' => sanitize_text_field($_POST['information_quality']),
            'specific_location' => sanitize_text_field($_POST['specific_location']),
            'transcription_notes' => sanitize_textarea_field($_POST['transcription_notes']),
            'file_id' => sanitize_text_field($_POST['file_id'])
        ];

        $statement_id = intval($_POST['statement_id']);

        if ($statement_id) {
            $result = $this->info_repo->update($statement_id, $data);
        } else {
            $result = $this->info_repo->create($data);
        }

        if ($result) {
            wp_send_json_success(['message' => 'Information statement saved successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to save information statement.']);
        }
    }    /**
     * AJAX: Save Evidence Analysis
     */
    public function ajax_save_evidence_analysis() {
        check_ajax_referer('heritage_evidence_nonce', 'nonce');

        $data = [
            'information_statement_id' => intval($_POST['information_statement_id']),
            'research_question_id' => intval($_POST['research_question_id']),
            'evidence_type' => sanitize_text_field($_POST['evidence_type']),
            'analysis_text' => sanitize_textarea_field($_POST['analysis_text']),
            'weight_assessment' => sanitize_text_field($_POST['weight_assessment']),
            'reliability_factors' => sanitize_textarea_field($_POST['reliability_factors']),
            'contradictions' => sanitize_textarea_field($_POST['contradictions']),
            'corroboration' => sanitize_textarea_field($_POST['corroboration']),
            'quality_assessment' => sanitize_text_field($_POST['quality_assessment']),
            'confidence_level' => intval($_POST['confidence_level']),
            'file_id' => sanitize_text_field($_POST['file_id'])
        ];

        $analysis_id = intval($_POST['analysis_id']);

        if ($analysis_id) {
            $result = $this->evidence_repo->update($analysis_id, $data);
        } else {
            $result = $this->evidence_repo->create($data);
        }

        if ($result) {
            wp_send_json_success(['message' => 'Evidence analysis saved successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to save evidence analysis.']);
        }
    }

    /**
     * AJAX: Save Proof Argument
     */
    public function ajax_save_proof_argument() {
        check_ajax_referer('heritage_evidence_nonce', 'nonce');

        $data = [
            'research_question_id' => intval($_POST['research_question_id']),
            'argument_text' => sanitize_textarea_field($_POST['argument_text']),
            'evidence_ids' => array_map('intval', $_POST['evidence_ids'] ?? []),
            'proof_standard' => sanitize_text_field($_POST['proof_standard']),
            'conclusion' => sanitize_textarea_field($_POST['conclusion']),
            'confidence_assessment' => sanitize_text_field($_POST['confidence_assessment']),
            'limitations' => sanitize_textarea_field($_POST['limitations']),
            'file_id' => sanitize_text_field($_POST['file_id'])
        ];

        $argument_id = intval($_POST['argument_id']);

        if ($argument_id) {
            $result = $this->proof_repo->update($argument_id, $data);
        } else {
            $result = $this->proof_repo->create($data);
        }

        if ($result) {
            wp_send_json_success(['message' => 'Proof argument saved successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to save proof argument.']);
        }
    }

    /**
     * AJAX: Assess Evidence Quality
     */
    public function ajax_assess_evidence_quality() {
        check_ajax_referer('heritage_evidence_nonce', 'nonce');

        $analysis_id = intval($_POST['analysis_id']);
        $analysis = $this->evidence_repo->find_by_id($analysis_id);

        if (!$analysis) {
            wp_send_json_error(['message' => 'Evidence analysis not found.']);
        }

        // Auto-assess quality based on evidence type and other factors
        $quality_factors = [
            'original_vs_derivative' => sanitize_text_field($_POST['original_vs_derivative']),
            'proximity_to_event' => sanitize_text_field($_POST['proximity_to_event']),
            'informant_knowledge' => sanitize_text_field($_POST['informant_knowledge']),
            'bias_factors' => sanitize_textarea_field($_POST['bias_factors'])
        ];

        // Calculate confidence level based on quality factors
        $confidence_level = $this->calculate_confidence_level($quality_factors);
        
        // Update the analysis with the assessment
        $update_data = [
            'quality_assessment' => json_encode($quality_factors),
            'confidence_level' => $confidence_level
        ];

        $result = $this->evidence_repo->update($analysis_id, $update_data);

        if ($result) {
            wp_send_json_success([
                'message' => 'Evidence quality assessed successfully.',
                'confidence_level' => $confidence_level,
                'quality_factors' => $quality_factors
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to assess evidence quality.']);
        }
    }

    /**
     * Calculate confidence level based on quality factors
     */
    private function calculate_confidence_level($quality_factors) {
        $score = 0;
        $max_score = 100;

        // Original vs derivative source (25 points)
        switch ($quality_factors['original_vs_derivative']) {
            case 'original':
                $score += 25;
                break;
            case 'early_copy':
                $score += 20;
                break;
            case 'later_copy':
                $score += 15;
                break;
            case 'derivative':
                $score += 10;
                break;
        }

        // Proximity to event (25 points)
        switch ($quality_factors['proximity_to_event']) {
            case 'contemporary':
                $score += 25;
                break;
            case 'within_year':
                $score += 20;
                break;
            case 'within_decade':
                $score += 15;
                break;
            case 'later':
                $score += 10;
                break;
        }

        // Informant knowledge (25 points)
        switch ($quality_factors['informant_knowledge']) {
            case 'direct_participant':
                $score += 25;
                break;
            case 'direct_observer':
                $score += 20;
                break;
            case 'informed_observer':
                $score += 15;
                break;
            case 'hearsay':
                $score += 10;
                break;
        }

        // Bias factors (25 points - inverted, fewer biases = higher score)
        $bias_count = substr_count(strtolower($quality_factors['bias_factors']), ',') + 1;
        if (empty(trim($quality_factors['bias_factors']))) {
            $score += 25;
        } elseif ($bias_count <= 2) {
            $score += 20;
        } elseif ($bias_count <= 4) {
            $score += 15;
        } else {
            $score += 10;
        }

        return round(($score / $max_score) * 100);
    }

    /**
     * AJAX: Format Citation
     */
    public function ajax_format_citation() {
        check_ajax_referer('heritage_evidence_nonce', 'nonce');

        $source_id = intval($_POST['source_id']);
        $citation_id = intval($_POST['citation_id']) ?: null;

        // Get source and citation objects
        // This would need to be implemented with proper repository calls
        
        $formatted_citation = "Formatted citation would appear here";

        wp_send_json_success(['citation' => $formatted_citation]);
    }

    /**
     * Settings field callbacks
     */
    public function general_settings_section_callback() {
        echo '<p>Configure general Evidence Explained settings.</p>';
    }

    public function citation_style_field_callback() {
        $options = get_option('heritage_evidence_options');
        $value = $options['default_citation_style'] ?? 'evidence_explained';
        ?>
        <select name="heritage_evidence_options[default_citation_style]">
            <option value="evidence_explained" <?php selected($value, 'evidence_explained'); ?>>Evidence Explained</option>
            <option value="chicago" <?php selected($value, 'chicago'); ?>>Chicago Manual of Style</option>
            <option value="mla" <?php selected($value, 'mla'); ?>>MLA</option>
        </select>
        <?php
    }

    public function auto_assess_field_callback() {
        $options = get_option('heritage_evidence_options');
        $value = $options['auto_assess_confidence'] ?? '1';
        ?>
        <input type="checkbox" name="heritage_evidence_options[auto_assess_confidence]" value="1" <?php checked($value, '1'); ?> />        <label>Automatically assess evidence confidence levels</label>
        <?php
    }

    /**
     * Get quality assessment data for modal display
     */
    public function ajax_get_quality_assessment() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $analysis_id = intval($_POST['analysis_id']);
        $analysis = $this->evidence_repo->find_by_id($analysis_id);

        if (!$analysis) {
            wp_send_json_error('Analysis not found');
            return;
        }

        $quality_factors = json_decode($analysis->quality_factors, true) ?: [];
        
        wp_send_json_success([
            'analysis' => $analysis,
            'quality_factors' => $quality_factors,
            'confidence_level' => $analysis->confidence_level
        ]);
    }

    /**
     * Save quality assessment data
     */
    public function ajax_save_quality_assessment() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $analysis_id = intval($_POST['analysis_id']);
        $quality_factors = $_POST['quality_factors'] ?? [];        try {
            // Update quality factors and recalculate confidence level
            $confidence = $this->calculate_confidence_level($quality_factors);
            
            $success = $this->evidence_repo->update($analysis_id, [
                'quality_factors' => json_encode($quality_factors),
                'confidence_level' => $confidence
            ]);

            if ($success) {
                wp_send_json_success([
                    'message' => 'Quality assessment saved successfully',
                    'confidence_level' => $confidence
                ]);
            } else {
                wp_send_json_error('Failed to save quality assessment');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error saving quality assessment: ' . $e->getMessage());
        }
    }

    /**
     * Delete evidence analysis
     */
    public function ajax_delete_analysis() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->evidence_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count evidence analysis(es) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting evidence analysis: ' . $e->getMessage());
        }
    }

    /**
     * Delete proof argument
     */
    public function ajax_delete_proof_argument() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->proof_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count proof argument(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting proof argument: ' . $e->getMessage());
        }
    }

    /**
     * Delete research question
     */
    public function ajax_delete_research_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->research_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count research question(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting research question: ' . $e->getMessage());
        }
    }

    /**
     * Delete information statement
     */
    public function ajax_delete_information_statement() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->info_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count information statement(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting information statement: ' . $e->getMessage());
        }
    }

    /**
     * Export evidence analysis
     */
    public function ajax_export_analysis() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $analysis = $this->evidence_repo->find_by_id($id);

        if (!$analysis) {
            wp_die('Analysis not found');
        }

        $filename = "evidence-analysis-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($analysis, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Export proof argument
     */
    public function ajax_export_proof_argument() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $argument = $this->proof_repo->find_by_id($id);

        if (!$argument) {
            wp_die('Proof argument not found');
        }

        $filename = "proof-argument-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($argument, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Export research question
     */
    public function ajax_export_research_question() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $question = $this->research_repo->find_by_id($id);

        if (!$question) {
            wp_die('Research question not found');
        }

        $filename = "research-question-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($question, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Export information statement
     */
    public function ajax_export_information_statement() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $statement = $this->info_repo->find_by_id($id);

        if (!$statement) {
            wp_die('Information statement not found');
        }

        $filename = "information-statement-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($statement, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Duplicate evidence analysis
     */
    public function ajax_duplicate_analysis() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->evidence_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original analysis not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['title'] = $duplicate_data['title'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->evidence_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Evidence analysis duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate evidence analysis');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating evidence analysis: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate proof argument
     */
    public function ajax_duplicate_proof_argument() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->proof_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original proof argument not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['title'] = $duplicate_data['title'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->proof_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Proof argument duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate proof argument');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating proof argument: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate research question
     */
    public function ajax_duplicate_research_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->research_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original research question not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['question'] = $duplicate_data['question'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->research_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Research question duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate research question');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating research question: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate information statement
     */
    public function ajax_duplicate_information_statement() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->info_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original information statement not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['title'] = $duplicate_data['title'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->info_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Information statement duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate information statement');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating information statement: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete analyses
     */
    public function ajax_bulk_delete_analyses() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->evidence_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count evidence analysis(es) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete proof arguments
     */
    public function ajax_bulk_delete_proof_arguments() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->proof_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count proof argument(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete research questions
     */
    public function ajax_bulk_delete_research_questions() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->research_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count research question(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete information statements
     */
    public function ajax_bulk_delete_information_statements() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->info_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count information statement(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }    /**
     * Initialize the Evidence Admin (called by Plugin class)
     */
    public function init() {
        // Remove the hook that was added in constructor to avoid duplication
        remove_action('admin_menu', [$this, 'add_admin_menus']);
        
        // Re-add it to ensure it's called at the right time
        add_action('admin_menu', [$this, 'add_admin_menus'], 20);
    }

    /**
     * Settings field callbacks
     */
    public function general_settings_section_callback() {
        echo '<p>Configure general Evidence Explained settings.</p>';
    }

    public function citation_style_field_callback() {
        $options = get_option('heritage_evidence_options');
        $value = $options['default_citation_style'] ?? 'evidence_explained';
        ?>
        <select name="heritage_evidence_options[default_citation_style]">
            <option value="evidence_explained" <?php selected($value, 'evidence_explained'); ?>>Evidence Explained</option>
            <option value="chicago" <?php selected($value, 'chicago'); ?>>Chicago Manual of Style</option>
            <option value="mla" <?php selected($value, 'mla'); ?>>MLA</option>
        </select>
        <?php
    }

    public function auto_assess_field_callback() {
        $options = get_option('heritage_evidence_options');
        $value = $options['auto_assess_confidence'] ?? '1';
        ?>
        <input type="checkbox" name="heritage_evidence_options[auto_assess_confidence]" value="1" <?php checked($value, '1'); ?> />        <label>Automatically assess evidence confidence levels</label>
        <?php
    }

    /**
     * Get quality assessment data for modal display
     */
    public function ajax_get_quality_assessment() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $analysis_id = intval($_POST['analysis_id']);
        $analysis = $this->evidence_repo->find_by_id($analysis_id);

        if (!$analysis) {
            wp_send_json_error('Analysis not found');
            return;
        }

        $quality_factors = json_decode($analysis->quality_factors, true) ?: [];
        
        wp_send_json_success([
            'analysis' => $analysis,
            'quality_factors' => $quality_factors,
            'confidence_level' => $analysis->confidence_level
        ]);
    }

    /**
     * Save quality assessment data
     */
    public function ajax_save_quality_assessment() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $analysis_id = intval($_POST['analysis_id']);
        $quality_factors = $_POST['quality_factors'] ?? [];        try {
            // Update quality factors and recalculate confidence level
            $confidence = $this->calculate_confidence_level($quality_factors);
            
            $success = $this->evidence_repo->update($analysis_id, [
                'quality_factors' => json_encode($quality_factors),
                'confidence_level' => $confidence
            ]);

            if ($success) {
                wp_send_json_success([
                    'message' => 'Quality assessment saved successfully',
                    'confidence_level' => $confidence
                ]);
            } else {
                wp_send_json_error('Failed to save quality assessment');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error saving quality assessment: ' . $e->getMessage());
        }
    }

    /**
     * Delete evidence analysis
     */
    public function ajax_delete_analysis() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->evidence_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count evidence analysis(es) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting evidence analysis: ' . $e->getMessage());
        }
    }

    /**
     * Delete proof argument
     */
    public function ajax_delete_proof_argument() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->proof_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count proof argument(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting proof argument: ' . $e->getMessage());
        }
    }

    /**
     * Delete research question
     */
    public function ajax_delete_research_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->research_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count research question(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting research question: ' . $e->getMessage());
        }
    }

    /**
     * Delete information statement
     */
    public function ajax_delete_information_statement() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->info_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count information statement(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error deleting information statement: ' . $e->getMessage());
        }
    }

    /**
     * Export evidence analysis
     */
    public function ajax_export_analysis() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $analysis = $this->evidence_repo->find_by_id($id);

        if (!$analysis) {
            wp_die('Analysis not found');
        }

        $filename = "evidence-analysis-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($analysis, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Export proof argument
     */
    public function ajax_export_proof_argument() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $argument = $this->proof_repo->find_by_id($id);

        if (!$argument) {
            wp_die('Proof argument not found');
        }

        $filename = "proof-argument-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($argument, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Export research question
     */
    public function ajax_export_research_question() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $question = $this->research_repo->find_by_id($id);

        if (!$question) {
            wp_die('Research question not found');
        }

        $filename = "research-question-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($question, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Export information statement
     */
    public function ajax_export_information_statement() {
        if (!wp_verify_nonce($_GET['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_GET['id'] ?? $_GET['item_id']);
        $statement = $this->info_repo->find_by_id($id);

        if (!$statement) {
            wp_die('Information statement not found');
        }

        $filename = "information-statement-{$id}-" . date('Y-m-d') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate');
        
        echo json_encode($statement, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Duplicate evidence analysis
     */
    public function ajax_duplicate_analysis() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->evidence_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original analysis not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['title'] = $duplicate_data['title'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->evidence_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Evidence analysis duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate evidence analysis');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating evidence analysis: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate proof argument
     */
    public function ajax_duplicate_proof_argument() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->proof_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original proof argument not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['title'] = $duplicate_data['title'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->proof_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Proof argument duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate proof argument');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating proof argument: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate research question
     */
    public function ajax_duplicate_research_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->research_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original research question not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['question'] = $duplicate_data['question'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->research_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Research question duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate research question');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating research question: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate information statement
     */
    public function ajax_duplicate_information_statement() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $id = intval($_POST['item_id']);
        $original = $this->info_repo->find_by_id($id);

        if (!$original) {
            wp_send_json_error('Original information statement not found');
            return;
        }

        try {
            $duplicate_data = (array) $original;
            unset($duplicate_data['id']);
            $duplicate_data['title'] = $duplicate_data['title'] . ' (Copy)';
            $duplicate_data['created_at'] = current_time('mysql');

            $new_id = $this->info_repo->create($duplicate_data);
            
            if ($new_id) {
                wp_send_json_success([
                    'message' => 'Information statement duplicated successfully',
                    'new_id' => $new_id
                ]);
            } else {
                wp_send_json_error('Failed to duplicate information statement');
            }
        } catch (Exception $e) {
            wp_send_json_error('Error duplicating information statement: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete analyses
     */
    public function ajax_bulk_delete_analyses() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->evidence_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count evidence analysis(es) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete proof arguments
     */
    public function ajax_bulk_delete_proof_arguments() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->proof_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count proof argument(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete research questions
     */
    public function ajax_bulk_delete_research_questions() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->research_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count research question(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete information statements
     */
    public function ajax_bulk_delete_information_statements() {
        if (!wp_verify_nonce($_POST['nonce'], 'heritage_admin_nonce')) {
            wp_die('Invalid nonce');
        }

        $ids = array_map('intval', (array) $_POST['ids']);
        $deleted_count = 0;

        try {
            foreach ($ids as $id) {
                if ($this->info_repo->delete($id)) {
                    $deleted_count++;
                }
            }

            wp_send_json_success([
                'message' => "$deleted_count information statement(s) deleted successfully",
                'deleted_count' => $deleted_count
            ]);
        } catch (Exception $e) {
            wp_send_json_error('Error in bulk delete operation: ' . $e->getMessage());
        }
    }
}

// Initialize the admin interface
new Evidence_Admin();
