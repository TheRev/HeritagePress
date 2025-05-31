<?php
/**
 * Research Question Service
 *
 * Provides business logic for managing research questions in the Evidence Explained methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Services;

use HeritagePress\Models\Research_Question;
use HeritagePress\Repositories\Research_Question_Repository;
use HeritagePress\Repositories\Information_Statement_Repository;
use HeritagePress\Repositories\Evidence_Analysis_Repository;
use HeritagePress\Repositories\Proof_Argument_Repository;

class Research_Question_Service {

    private $research_repo;
    private $info_repo;
    private $evidence_repo;
    private $proof_repo;

    public function __construct() {
        $this->research_repo = new Research_Question_Repository();
        $this->info_repo = new Information_Statement_Repository();
        $this->evidence_repo = new Evidence_Analysis_Repository();
        $this->proof_repo = new Proof_Argument_Repository();
    }

    /**
     * Calculate research progress for a question
     *
     * @param int $question_id Research question ID
     * @return array Progress statistics
     */
    public function calculate_progress($question_id) {
        $statements = $this->info_repo->find_by_research_question($question_id);
        $analyses = $this->evidence_repo->find_by_research_question($question_id);
        $proofs = $this->proof_repo->find_by_research_question($question_id);

        $total_statements = count($statements);
        $analyzed_statements = 0;
        $proven_statements = 0;

        foreach ($statements as $statement) {
            $statement_analyses = $this->evidence_repo->find_by_information_statement($statement->id);
            if (!empty($statement_analyses)) {
                $analyzed_statements++;
            }

            $statement_proofs = $this->proof_repo->find_by_information_statement($statement->id);
            if (!empty($statement_proofs)) {
                $proven_statements++;
            }
        }

        $analysis_progress = $total_statements > 0 ? ($analyzed_statements / $total_statements) * 100 : 0;
        $proof_progress = $total_statements > 0 ? ($proven_statements / $total_statements) * 100 : 0;

        return [
            'total_statements' => $total_statements,
            'analyzed_statements' => $analyzed_statements,
            'proven_statements' => $proven_statements,
            'analysis_progress' => round($analysis_progress, 1),
            'proof_progress' => round($proof_progress, 1),
            'overall_progress' => round(($analysis_progress + $proof_progress) / 2, 1)
        ];
    }

    /**
     * Get research question status
     *
     * @param int $question_id Research question ID
     * @return string Status (draft, in_progress, under_review, complete)
     */
    public function get_status($question_id) {
        $progress = $this->calculate_progress($question_id);
        
        if ($progress['overall_progress'] == 0) {
            return 'draft';
        } elseif ($progress['overall_progress'] < 50) {
            return 'in_progress';
        } elseif ($progress['overall_progress'] < 100) {
            return 'under_review';
        } else {
            return 'complete';
        }
    }

    /**
     * Get related content for a research question
     *
     * @param int $question_id Research question ID
     * @return array Related statements, analyses, and proofs
     */
    public function get_related_content($question_id) {
        return [
            'statements' => $this->info_repo->find_by_research_question($question_id),
            'analyses' => $this->evidence_repo->find_by_research_question($question_id),
            'proofs' => $this->proof_repo->find_by_research_question($question_id)
        ];
    }

    /**
     * Validate research question data
     *
     * @param array $data Question data
     * @return array Validation results
     */
    public function validate($data) {
        $errors = [];

        if (empty($data['question'])) {
            $errors['question'] = 'Research question is required.';
        }

        if (empty($data['objective'])) {
            $errors['objective'] = 'Research objective is required.';
        }

        if (!empty($data['deadline']) && !strtotime($data['deadline'])) {
            $errors['deadline'] = 'Invalid deadline format.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create a new research question
     *
     * @param array $data Question data
     * @return Research_Question|false Created question or false on failure
     */
    public function create($data) {
        $validation = $this->validate($data);
        if (!$validation['valid']) {
            return false;
        }

        $question = new Research_Question();
        $question->question = sanitize_text_field($data['question']);
        $question->objective = sanitize_textarea_field($data['objective']);
        $question->context = sanitize_textarea_field($data['context'] ?? '');
        $question->priority = sanitize_text_field($data['priority'] ?? 'medium');
        $question->status = 'draft';
        $question->deadline = !empty($data['deadline']) ? sanitize_text_field($data['deadline']) : null;
        $question->notes = sanitize_textarea_field($data['notes'] ?? '');
        $question->created_at = current_time('mysql');
        $question->updated_at = current_time('mysql');

        return $this->research_repo->create($question);
    }

    /**
     * Update a research question
     *
     * @param int $id Question ID
     * @param array $data Updated data
     * @return bool Success status
     */
    public function update($id, $data) {
        $validation = $this->validate($data);
        if (!$validation['valid']) {
            return false;
        }

        $question = $this->research_repo->find($id);
        if (!$question) {
            return false;
        }

        $question->question = sanitize_text_field($data['question']);
        $question->objective = sanitize_textarea_field($data['objective']);
        $question->context = sanitize_textarea_field($data['context'] ?? '');
        $question->priority = sanitize_text_field($data['priority'] ?? 'medium');
        $question->deadline = !empty($data['deadline']) ? sanitize_text_field($data['deadline']) : null;
        $question->notes = sanitize_textarea_field($data['notes'] ?? '');
        $question->updated_at = current_time('mysql');

        return $this->research_repo->update($question);
    }

    /**
     * Delete a research question and all related content
     *
     * @param int $id Question ID
     * @return bool Success status
     */
    public function delete($id) {
        // Delete related content first
        $statements = $this->info_repo->find_by_research_question($id);
        foreach ($statements as $statement) {
            $this->info_repo->delete($statement->id);
        }

        $analyses = $this->evidence_repo->find_by_research_question($id);
        foreach ($analyses as $analysis) {
            $this->evidence_repo->delete($analysis->id);
        }

        $proofs = $this->proof_repo->find_by_research_question($id);
        foreach ($proofs as $proof) {
            $this->proof_repo->delete($proof->id);
        }

        // Delete the question
        return $this->research_repo->delete($id);
    }
}
