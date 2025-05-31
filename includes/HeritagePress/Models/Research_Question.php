<?php
/**
 * Research Question Model
 *
 * Represents a genealogical research question that needs to be answered.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Models;

class Research_Question extends Model {
    protected $table = 'research_questions';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'question_text',
        'question_type',
        'priority',
        'status',
        'assigned_user_id',
        'created_at',
        'updated_at'
    ];

    protected $rules = [
        'question_text' => ['required'],
        'question_type' => ['in:IDENTITY,RELATIONSHIP,EVENT,CHRONOLOGY,LOCATION,OTHER'],
        'status' => ['in:OPEN,IN_PROGRESS,ANSWERED,CLOSED']
    ];

    /**
     * Get evidence analyses for this question
     */
    public function evidence_analyses() {
        return $this->hasMany(Evidence_Analysis::class, 'research_question_id');
    }

    /**
     * Get proof arguments for this question
     */
    public function proof_arguments() {
        return $this->hasMany(Proof_Argument::class, 'research_question_id');
    }

    /**
     * Get research progress
     */
    public function getResearchProgress() {
        $analyses = $this->evidence_analyses()->get();
        
        $strong_evidence = 0;
        $moderate_evidence = 0;
        $weak_evidence = 0;
        
        foreach ($analyses as $analysis) {
            switch ($analysis->evidence_weight) {
                case 'STRONG': $strong_evidence++; break;
                case 'MODERATE': $moderate_evidence++; break;
                case 'WEAK': $weak_evidence++; break;
            }
        }
        
        return [
            'evidence_pieces' => count($analyses),
            'strong_evidence' => $strong_evidence,
            'moderate_evidence' => $moderate_evidence,
            'weak_evidence' => $weak_evidence,
            'completion_percentage' => $this->calculateCompletionPercentage($analyses),
            'proof_arguments' => count($this->proof_arguments()->get())
        ];
    }

    /**
     * Calculate how close the question is to being answered
     */
    private function calculateCompletionPercentage($evidence_analyses) {
        if (empty($evidence_analyses)) {
            return 0;
        }
        
        $total_strength = 0;
        $max_possible = 0;
        
        foreach ($evidence_analyses as $analysis) {
            $strength = $analysis->getStrengthScore();
            $total_strength += $strength;
            $max_possible += 20; // Maximum possible strength score
        }
        
        return round(($total_strength / $max_possible) * 100);
    }

    /**
     * Suggest research strategies
     */
    public function suggestResearchStrategies() {
        $strategies = [];
        $progress = $this->getResearchProgress();
        
        if ($progress['evidence_pieces'] < 3) {
            $strategies[] = [
                'type' => 'evidence_collection',
                'priority' => 'HIGH',
                'description' => 'Collect more evidence from additional sources',
                'suggestions' => $this->getSuggestedSources()
            ];
        }
        
        if ($progress['strong_evidence'] === 0) {
            $strategies[] = [
                'type' => 'source_quality',
                'priority' => 'HIGH',
                'description' => 'Seek higher quality primary sources',
                'suggestions' => $this->getPrimarySourceSuggestions()
            ];
        }
        
        return $strategies;
    }
    
    /**
     * Get suggested sources based on question type
     */
    private function getSuggestedSources() {
        // Simplified version - would be more sophisticated in practice
        $sources = [
            'IDENTITY' => ['Birth records', 'Census records', 'Death certificates'],
            'RELATIONSHIP' => ['Marriage records', 'Wills', 'Census records'],
            'EVENT' => ['Newspapers', 'Court records', 'Military records'],
            'CHRONOLOGY' => ['Census records', 'Tax records', 'City directories'],
            'LOCATION' => ['Land records', 'Maps', 'City directories'],
            'OTHER' => ['General historical records', 'Community histories']
        ];
        
        return $sources[$this->question_type] ?? $sources['OTHER'];
    }
    
    /**
     * Get primary source suggestions
     */
    private function getPrimarySourceSuggestions() {
        // Simplified - would be more dynamic in practice
        return [
            'Original vital records',
            'Church registers',
            'Court documents',
            'Land deeds',
            'Military service records'
        ];
    }
    
    /**
     * Create model instance from database object
     */
    public static function from_db_object(\stdClass $db_row): Research_Question {
        $instance = new static();
        
        // Set properties from database row
        foreach ($db_row as $property => $value) {
            $instance->$property = $value;
        }
        
        return $instance;
    }
}
