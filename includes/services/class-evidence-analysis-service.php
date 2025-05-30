<?php
/**
 * Evidence Analysis Service Class
 *
 * Service for coordinating Evidence analysis workflow following Elizabeth Shown Mills' methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Services;

use HeritagePress\Models\Research_Question;
use HeritagePress\Models\Information_Statement;
use HeritagePress\Models\Evidence_Analysis;
use HeritagePress\Models\Proof_Argument;
use HeritagePress\Repositories\Research_Question_Repository;
use HeritagePress\Repositories\Information_Statement_Repository;
use HeritagePress\Repositories\Evidence_Analysis_Repository;
use HeritagePress\Repositories\Proof_Argument_Repository;
use HeritagePress\Core\Audit_Log_Observer; // Added import
use HeritagePress\Database\DatabaseManager; // Corrected import: DatabaseManager (no underscore)

class Evidence_Analysis_Service {

    private $research_repo;
    private $info_repo;
    private $evidence_repo;
    private $proof_repo;
    private $audit_observer; // Added property

    public function __construct() {
        global $wpdb; // Added to get $wpdb
        // Would typically inject these dependencies
        
        // Create Audit_Log_Observer instance
        $audit_table_name = DatabaseManager::get_table_prefix() . 'audit_log'; // Corrected call: DatabaseManager
        $this->audit_observer = new Audit_Log_Observer($wpdb, $audit_table_name);

        $this->research_repo = new Research_Question_Repository($this->audit_observer); // Passed observer
        $this->info_repo = new Information_Statement_Repository($this->audit_observer); // Passed observer
        $this->evidence_repo = new Evidence_Analysis_Repository($this->audit_observer); // Passed observer
        $this->proof_repo = new Proof_Argument_Repository($this->audit_observer); // Passed observer
    }

    /**
     * Start a new research project with initial question
     */
    public function start_research_project($file_id, $question_text, $question_type, $individual_id = null) {
        $question_data = [
            'file_id' => $file_id,
            'question_text' => $question_text,
            'question_type' => $question_type,
            'individual_id' => $individual_id,
            'status' => 'OPEN',
            'priority' => 'MEDIUM'
        ];

        $question = $this->research_repo->create($question_data);

        if ($question) {
            return [
                'success' => true,
                'question' => $question,
                'next_steps' => $this->get_research_recommendations($question)
            ];
        }

        return ['success' => false, 'error' => 'Failed to create research question'];
    }

    /**
     * Extract information from a source and link to research question
     */
    public function extract_information($source_id, $statement_text, $research_question_id = null, $attributes = []) {
        $info_data = array_merge([
            'source_id' => $source_id,
            'statement_text' => $statement_text
        ], $attributes);

        $info_statement = $this->info_repo->create($info_data);

        if (!$info_statement) {
            return ['success' => false, 'error' => 'Failed to create information statement'];
        }

        $result = [
            'success' => true,
            'information_statement' => $info_statement
        ];

        // If linked to research question, create evidence analysis
        if ($research_question_id) {
            $evidence = $this->analyze_as_evidence($info_statement->id, $research_question_id);
            if ($evidence) {
                $result['evidence_analysis'] = $evidence;
            }
        }

        return $result;
    }

    /**
     * Analyze information statement as evidence for research question
     */
    public function analyze_as_evidence($info_statement_id, $research_question_id, $analysis_attributes = []) {
        $info_statement = $this->info_repo->find_by_id($info_statement_id);
        $research_question = $this->research_repo->find_by_id($research_question_id);

        if (!$info_statement || !$research_question) {
            return false;
        }

        // Determine evidence type based on question and information
        $evidence_type = $this->determine_evidence_type($info_statement, $research_question);
        
        $evidence_data = array_merge([
            'information_statement_id' => $info_statement_id,
            'research_question_id' => $research_question_id,
            'evidence_type' => $evidence_type,
            'relevance_score' => 5, // Default, should be assessed by analyst
            'interpretation_notes' => '' // Should be filled by analyst
        ], $analysis_attributes);

        $evidence = $this->evidence_repo->create($evidence_data);

        if ($evidence) {
            // Auto-assess initial confidence
            $evidence->autoAssessConfidence();
            
            // Check for conflicts with existing evidence
            $conflicts = $evidence->findConflicts();
            
            return [
                'evidence_analysis' => $evidence,
                'conflicts' => $conflicts,
                'recommendations' => $this->get_analysis_recommendations($evidence)
            ];
        }

        return false;
    }

    /**
     * Build proof argument from evidence analyses
     */
    public function build_proof_argument($research_question_id, $evidence_analysis_ids, $argument_data) {
        $research_question = $this->research_repo->find_by_id($research_question_id);
        if (!$research_question) {
            return ['success' => false, 'error' => 'Research question not found'];
        }

        // Validate evidence analyses
        $evidence_analyses = [];
        foreach ($evidence_analysis_ids as $evidence_id) {
            $evidence = $this->evidence_repo->find_by_id($evidence_id);
            if ($evidence && $evidence->research_question_id == $research_question_id) {
                $evidence_analyses[] = $evidence;
            }
        }

        if (empty($evidence_analyses)) {
            return ['success' => false, 'error' => 'No valid evidence analyses provided'];
        }

        // Create proof argument
        $proof_data = array_merge([
            'research_question_id' => $research_question_id,
            'file_id' => $research_question->file_id
        ], $argument_data);

        $proof_argument = $this->proof_repo->create($proof_data);

        if (!$proof_argument) {
            return ['success' => false, 'error' => 'Failed to create proof argument'];
        }

        // Link evidence to proof argument
        foreach ($evidence_analyses as $evidence) {
            $role = $this->determine_evidence_role($evidence);
            $weight = $this->determine_evidence_weight($evidence);
            
            $this->proof_repo->add_evidence(
                $proof_argument->id, 
                $evidence->id, 
                $role, 
                $weight
            );
        }

        // Assess GPS compliance
        $gps_compliance = $proof_argument->assessGPSCompliance();

        return [
            'success' => true,
            'proof_argument' => $proof_argument,
            'gps_compliance' => $gps_compliance,
            'recommendations' => $this->get_proof_recommendations($proof_argument, $gps_compliance)
        ];
    }

    /**
     * Get research progress for a question
     */
    public function get_research_progress($research_question_id) {
        $research_question = $this->research_repo->find_by_id($research_question_id);
        if (!$research_question) {
            return null;
        }

        $progress = $research_question->getResearchProgress();
        
        // Get evidence breakdown
        $evidence_analyses = $this->evidence_repo->find_by_research_question($research_question_id);
        $evidence_strength = $this->evidence_repo->get_strength_distribution($research_question_id);
        
        // Get proof arguments
        $proof_arguments = $this->proof_repo->find_by_research_question($research_question_id);
        
        // Get conflicts
        $conflicts = $this->evidence_repo->find_conflicts($research_question_id);

        return [
            'question' => $research_question,
            'progress' => $progress,
            'evidence_count' => count($evidence_analyses),
            'evidence_strength' => $evidence_strength,
            'proof_arguments' => count($proof_arguments),
            'conflicts' => count($conflicts),
            'status' => $research_question->status,
            'completion_percentage' => $progress['completion_percentage'],
            'recommendations' => $this->get_research_recommendations($research_question)
        ];
    }

    /**
     * Resolve conflicts between evidence analyses
     */
    public function resolve_conflicts($research_question_id, $resolution_notes) {
        $conflicts = $this->evidence_repo->find_conflicts($research_question_id);
        
        if (empty($conflicts)) {
            return ['success' => true, 'message' => 'No conflicts found'];
        }

        // Update research question with conflict resolution notes
        $this->research_repo->update($research_question_id, [
            'research_notes' => $resolution_notes
        ]);

        // Mark conflicting evidence for review
        foreach ($conflicts as $conflict) {
            if (isset($conflict['evidence_1'])) {
                $this->evidence_repo->update($conflict['evidence_1']->id, [
                    'conflicts_with' => $resolution_notes
                ]);
            }
            if (isset($conflict['evidence_2'])) {
                $this->evidence_repo->update($conflict['evidence_2']->id, [
                    'conflicts_with' => $resolution_notes
                ]);
            }
        }

        return [
            'success' => true,
            'conflicts_resolved' => count($conflicts),
            'message' => 'Conflicts documented and marked for resolution'
        ];
    }

    /**
     * Get dashboard statistics for Evidence system
     */
    public function get_dashboard_statistics($file_id = null) {
        return [
            'research_questions' => $this->research_repo->get_research_statistics($file_id),
            'evidence_analyses' => $this->evidence_repo->get_statistics($file_id),
            'proof_arguments' => $this->proof_repo->get_statistics($file_id),
            'information_statements' => $this->info_repo->get_extraction_statistics($file_id),
            'pending_reviews' => [
                'questions_needing_attention' => count($this->research_repo->get_questions_needing_attention($file_id)),
                'analyses_needing_review' => count($this->evidence_repo->get_analyses_needing_review($file_id)),
                'arguments_awaiting_review' => count($this->proof_repo->get_awaiting_review($file_id))
            ]
        ];
    }

    /**
     * Determine evidence type based on information and research question
     */
    private function determine_evidence_type($info_statement, $research_question) {
        // Simplified logic - would be more sophisticated in practice
        $statement_text = strtolower($info_statement->statement_text);
        $question_text = strtolower($research_question->question_text);

        // Look for direct answers to the question
        $question_keywords = $this->extract_keywords($question_text);
        $statement_keywords = $this->extract_keywords($statement_text);

        $matches = array_intersect($question_keywords, $statement_keywords);
        
        if (count($matches) >= 2) {
            return 'DIRECT';
        } elseif (count($matches) >= 1) {
            return 'INDIRECT';
        } else {
            return 'INDIRECT'; // Default to indirect
        }
    }

    /**
     * Determine evidence role in proof argument
     */
    private function determine_evidence_role($evidence) {
        switch ($evidence->evidence_weight) {
            case 'STRONG':
                return $evidence->evidence_type === 'DIRECT' ? 'PRIMARY' : 'SUPPORTING';
            case 'MODERATE':
                return 'SUPPORTING';
            case 'WEAK':
                return 'CONTEXTUAL';
            default:
                return 'CONTEXTUAL';
        }
    }

    /**
     * Determine evidence weight in argument
     */
    private function determine_evidence_weight($evidence) {
        $strength_score = $evidence->getStrengthScore();
        
        if ($strength_score >= 15) return 'CRITICAL';
        if ($strength_score >= 10) return 'IMPORTANT';
        if ($strength_score >= 5) return 'SUPPLEMENTARY';
        return 'MINIMAL';
    }

    /**
     * Get research recommendations based on current progress
     */
    private function get_research_recommendations($research_question) {
        $progress = $research_question->getResearchProgress();
        $strategies = $research_question->getResearchStrategies();
        
        $recommendations = [];
        
        if ($progress['evidence_pieces'] < 3) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'action' => 'Gather more evidence',
                'description' => 'Research question needs at least 3 pieces of evidence for credible analysis'
            ];
        }
        
        if ($progress['strong_evidence'] === 0) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'action' => 'Find stronger evidence',
                'description' => 'No strong evidence found yet. Consider primary sources.'
            ];
        }
        
        if ($progress['completion_percentage'] > 70 && $progress['proof_arguments'] === 0) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'action' => 'Build proof argument',
                'description' => 'Sufficient evidence collected. Ready to build proof argument.'
            ];
        }

        return array_merge($recommendations, $strategies);
    }

    /**
     * Get analysis recommendations
     */
    private function get_analysis_recommendations($evidence) {
        $recommendations = [];
        
        if ($evidence->relevance_score < 5) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'action' => 'Review relevance',
                'description' => 'Consider if this evidence is truly relevant to the research question'
            ];
        }
        
        if (empty($evidence->interpretation_notes)) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'action' => 'Add interpretation notes',
                'description' => 'Explain how this information functions as evidence'
            ];
        }
        
        if ($evidence->confidence_level === 'UNCERTAIN') {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'action' => 'Improve confidence assessment',
                'description' => 'Consider what additional information might increase confidence'
            ];
        }

        return $recommendations;
    }

    /**
     * Get proof argument recommendations
     */
    private function get_proof_recommendations($proof_argument, $gps_compliance) {
        $recommendations = [];
        
        if ($gps_compliance['overall_compliance'] < 6) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'action' => 'Improve GPS compliance',
                'description' => 'Proof argument does not meet GPS standards'
            ];
        }
        
        if ($proof_argument->confidence_percentage < 70) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'action' => 'Strengthen argument',
                'description' => 'Consider additional evidence or analysis to increase confidence'
            ];
        }
        
        if ($proof_argument->peer_review_status === 'UNREVIEWED') {
            $recommendations[] = [
                'priority' => 'LOW',
                'action' => 'Submit for peer review',
                'description' => 'Argument ready for peer review process'
            ];
        }

        return $recommendations;
    }

    /**
     * Extract keywords from text for comparison
     */
    private function extract_keywords($text) {
        // Remove common words and extract meaningful terms
        $common_words = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'was', 'is', 'are', 'were', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should'];
        
        $words = str_word_count(strtolower($text), 1);
        $keywords = array_diff($words, $common_words);
        
        return array_filter($keywords, function($word) {
            return strlen($word) > 2; // Only words longer than 2 characters
        });
    }
}
