<?php
/**
 * Research Question Model
 *
 * Represents specific genealogical questions being investigated,
 * providing context for evidence analysis and proof arguments.
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
        'individual_id',
        'family_id',
        'event_id',
        'status',
        'priority',
        'research_notes',
        'methodology_notes',
        'created_by_user_id',
        'assigned_to_user_id',
        'target_resolution_date'
    ];

    protected $rules = [
        'question_text' => ['required'],
        'question_type' => ['required', 'in:IDENTITY,RELATIONSHIP,EVENT,DATE,PLACE,OTHER'],
        'status' => ['in:OPEN,RESOLVED,ABANDONED,ON_HOLD'],
        'priority' => ['in:HIGH,MEDIUM,LOW'],
        'target_resolution_date' => ['date']
    ];

    /**
     * Get the individual this question relates to
     */
    public function individual() {
        return $this->belongsTo(Individual::class, 'individual_id');
    }

    /**
     * Get the family this question relates to
     */
    public function family() {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * Get the event this question relates to
     */
    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }

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
     * Get current best proof argument
     */
    public function getBestProofArgument() {
        return $this->proof_arguments()
                   ->where('peer_review_status', 'APPROVED')
                   ->orderBy('confidence_percentage', 'desc')
                   ->first();
    }

    /**
     * Calculate research progress
     */
    public function getResearchProgress() {
        $evidence_count = $this->evidence_analyses()->count();
        $strong_evidence_count = $this->evidence_analyses()
                                    ->where('evidence_weight', 'STRONG')
                                    ->count();
        $proof_arguments_count = $this->proof_arguments()->count();
        $approved_arguments_count = $this->proof_arguments()
                                       ->where('peer_review_status', 'APPROVED')
                                       ->count();

        return [
            'evidence_pieces' => $evidence_count,
            'strong_evidence' => $strong_evidence_count,
            'proof_arguments' => $proof_arguments_count,
            'approved_arguments' => $approved_arguments_count,
            'completion_percentage' => $this->calculateCompletionPercentage(),
            'quality_score' => $this->calculateQualityScore()
        ];
    }

    /**
     * Calculate completion percentage
     */
    private function calculateCompletionPercentage() {
        $score = 0;
        $max_score = 100;

        // Evidence collection (40 points)
        $evidence_count = $this->evidence_analyses()->count();
        if ($evidence_count >= 1) $score += 10;
        if ($evidence_count >= 3) $score += 10;
        if ($evidence_count >= 5) $score += 10;
        if ($evidence_count >= 10) $score += 10;

        // Quality evidence (20 points)
        $strong_evidence = $this->evidence_analyses()
                               ->where('evidence_weight', 'STRONG')
                               ->count();
        if ($strong_evidence >= 1) $score += 10;
        if ($strong_evidence >= 3) $score += 10;

        // Analysis completion (20 points)
        $analyzed_evidence = $this->evidence_analyses()
                                 ->whereNotNull('interpretation_notes')
                                 ->count();
        if ($analyzed_evidence >= 1) $score += 10;
        if ($analyzed_evidence === $evidence_count && $evidence_count > 0) $score += 10;

        // Proof argument (20 points)
        $proof_count = $this->proof_arguments()->count();
        if ($proof_count >= 1) $score += 10;
        if ($this->proof_arguments()->where('peer_review_status', 'APPROVED')->exists()) $score += 10;

        return min(100, $score);
    }

    /**
     * Calculate research quality score
     */
    private function calculateQualityScore() {
        $evidence_analyses = $this->evidence_analyses()->get();
        if ($evidence_analyses->isEmpty()) return 0;

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
                'suggestions' => $this->getSuggestedPrimarySources()
            ];
        }

        if ($progress['proof_arguments'] === 0 && $progress['evidence_pieces'] >= 2) {
            $strategies[] = [
                'type' => 'analysis',
                'priority' => 'MEDIUM',
                'description' => 'Begin correlating evidence into proof argument',
                'suggestions' => ['Start drafting preliminary conclusion']
            ];
        }

        return $strategies;
    }

    /**
     * Get suggested sources based on question type
     */
    private function getSuggestedSources() {
        switch ($this->question_type) {
            case 'IDENTITY':
                return ['Birth records', 'Death records', 'Census records', 'Immigration records'];
            case 'RELATIONSHIP':
                return ['Marriage records', 'Family Bibles', 'Wills', 'Obituaries'];
            case 'EVENT':
                return ['Contemporary newspapers', 'Official records', 'Witness accounts'];
            case 'DATE':
                return ['Vital records', 'Tombstones', 'Contemporary documents'];
            case 'PLACE':
                return ['Gazetteers', 'Maps', 'Local histories', 'Land records'];
            default:
                return ['Census records', 'Vital records', 'Local repositories'];
        }
    }

    /**
     * Get suggested primary sources
     */
    private function getSuggestedPrimarySources() {
        return [
            'Original vital records from government offices',
            'Contemporary church records',
            'Land deeds and property records',
            'Court records and legal documents',
            'Military service records',
            'Immigration and naturalization papers'
        ];
    }

    /**
     * Mark as resolved with proof
     */
    public function markResolved($proof_argument_id = null) {
        $this->status = 'RESOLVED';
        
        if ($proof_argument_id) {
            $proof = Proof_Argument::find($proof_argument_id);
            if ($proof && $proof->research_question_id === $this->id) {
                $this->research_notes = ($this->research_notes ?? '') . 
                                      "\n\nResolved with proof argument: " . $proof->conclusion_statement;
            }
        }

        $this->save();
    }

    /**
     * Get formatted question for display
     */
    public function getFormattedQuestion() {
        $formatted = $this->question_text;
        
        if ($this->individual()) {
            $name = $this->individual()->getFullName();
            $formatted = str_replace('[individual]', $name, $formatted);
        }

        if ($this->family()) {
            $family_name = $this->family()->getDisplayName();
            $formatted = str_replace('[family]', $family_name, $formatted);
        }

        return $formatted;
    }
}
