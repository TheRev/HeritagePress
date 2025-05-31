<?php
/**
 * Proof Argument Model
 *
 * Represents the final assembled evidence and reasoning supporting
 * genealogical conclusions using Mills methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Models;

class Proof_Argument extends Model {
    protected $table = 'proof_arguments';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'research_question_id',
        'conclusion_statement',
        'argument_text',
        'conclusion_type',
        'confidence_percentage',
        'methodology_used',
        'evidence_summary',
        'correlation_analysis',
        'gaps_identified',
        'future_research_needed',
        'peer_review_status',
        'reviewed_by_user_id',
        'review_notes',
        'published_status',
        'created_by_user_id'
    ];

    protected $rules = [
        'research_question_id' => ['required'],
        'conclusion_statement' => ['required'],
        'argument_text' => ['required'],
        'conclusion_type' => ['required', 'in:PROVEN,PROBABLE,POSSIBLE,DISPROVEN,INDETERMINATE'],
        'confidence_percentage' => ['numeric', 'min:0', 'max:100'],
        'peer_review_status' => ['in:UNREVIEWED,UNDER_REVIEW,APPROVED,REJECTED'],
        'published_status' => ['in:DRAFT,INTERNAL,PUBLIC']
    ];

    /**
     * Get the research question this argument addresses
     */
    public function research_question() {
        return $this->belongsTo(Research_Question::class, 'research_question_id');
    }

    /**
     * Get evidence analyses used in this argument
     */
    public function evidence_analyses() {
        return $this->belongsToMany(
            Evidence_Analysis::class,
            'proof_evidence_links',
            'proof_argument_id',
            'evidence_analysis_id'
        )->withPivot(['evidence_role', 'weight_in_argument', 'usage_notes']);
    }

    /**
     * Add evidence to this proof argument
     */
    public function addEvidence($evidence_analysis_id, $role = 'SUPPORTING', $weight = 'IMPORTANT', $notes = null) {
        $this->evidence_analyses()->attach($evidence_analysis_id, [
            'evidence_role' => $role,
            'weight_in_argument' => $weight,
            'usage_notes' => $notes,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Generate GPS (Genealogical Proof Standard) compliance report
     */
    public function assessGPSCompliance() {
        $compliance = [
            'reasonably_exhaustive_search' => $this->assessSearchExhaustiveness(),
            'complete_accurate_citations' => $this->assessCitationQuality(),
            'analysis_correlation' => $this->assessAnalysisQuality(),
            'resolution_contradictions' => $this->assessContradictionResolution(),
            'sound_conclusion' => $this->assessConclusionSoundness(),
            'overall_compliance' => null
        ];

        // Calculate overall compliance
        $scores = array_filter($compliance, 'is_numeric');
        $compliance['overall_compliance'] = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

        return $compliance;
    }

    /**
     * Assess search exhaustiveness
     */
    private function assessSearchExhaustiveness() {
        $evidence_count = $this->evidence_analyses()->count();
        $unique_sources = $this->evidence_analyses()
                              ->join('information_statements', 'evidence_analysis.information_statement_id', '=', 'information_statements.id')
                              ->distinct('information_statements.source_id')
                              ->count();

        // Basic scoring - could be much more sophisticated
        if ($evidence_count >= 5 && $unique_sources >= 3) return 9;
        if ($evidence_count >= 3 && $unique_sources >= 2) return 7;
        if ($evidence_count >= 2) return 5;
        return 3;
    }

    /**
     * Assess citation quality
     */
    private function assessCitationQuality() {
        $evidence_analyses = $this->evidence_analyses()->get();
        if ($evidence_analyses->isEmpty()) return 0;

        $total_score = 0;
        foreach ($evidence_analyses as $analysis) {
            $info_statement = $analysis->information_statement();
            if ($info_statement && $info_statement->citation()) {
                $citation = $info_statement->citation();
                $assessment = $citation->assessEvidenceQuality();
                $total_score += $this->mapAssessmentToScore($assessment['overall_strength']);
            }
        }

        return $total_score / $evidence_analyses->count();
    }

    /**
     * Assess analysis and correlation quality
     */
    private function assessAnalysisQuality() {
        if (empty($this->correlation_analysis)) return 3;
        if (strlen($this->correlation_analysis) < 200) return 5;
        if (strlen($this->correlation_analysis) < 500) return 7;
        return 9;
    }

    /**
     * Assess contradiction resolution
     */
    private function assessContradictionResolution() {
        if (empty($this->gaps_identified)) return 5; // No identified contradictions
        
        // Check if argument addresses identified gaps/contradictions
        $addresses_contradictions = !empty($this->argument_text) && 
                                  (strpos(strtolower($this->argument_text), 'conflict') !== false ||
                                   strpos(strtolower($this->argument_text), 'contradict') !== false ||
                                   strpos(strtolower($this->argument_text), 'discrepancy') !== false);

        return $addresses_contradictions ? 8 : 4;
    }

    /**
     * Assess conclusion soundness
     */
    private function assessConclusionSoundness() {
        $confidence = $this->confidence_percentage ?? 50;
        
        switch ($this->conclusion_type) {
            case 'PROVEN':
                return $confidence >= 95 ? 10 : ($confidence >= 85 ? 8 : 6);
            case 'PROBABLE':
                return $confidence >= 75 ? 9 : ($confidence >= 60 ? 7 : 5);
            case 'POSSIBLE':
                return $confidence >= 40 ? 7 : 5;
            case 'DISPROVEN':
                return $confidence >= 95 ? 10 : 6;
            case 'INDETERMINATE':
                return 5;
            default:
                return 3;
        }
    }

    /**
     * Map assessment strength to numeric score
     */
    private function mapAssessmentToScore($strength) {
        switch ($strength) {
            case 'STRONG': return 9;
            case 'MODERATE': return 6;
            case 'WEAK': return 4;
            case 'VERY_WEAK': return 2;
            default: return 5;
        }
    }

    /**
     * Generate formatted proof argument
     */
    public function getFormattedArgument() {
        $formatted = "# " . $this->conclusion_statement . "\n\n";
        
        $formatted .= "**Conclusion Type:** " . $this->conclusion_type;
        if ($this->confidence_percentage) {
            $formatted .= " ({$this->confidence_percentage}% confidence)";
        }
        $formatted .= "\n\n";

        $formatted .= "## Research Question\n";
        if ($this->research_question()) {
            $formatted .= $this->research_question()->getFormattedQuestion() . "\n\n";
        }

        $formatted .= "## Evidence Summary\n";
        if ($this->evidence_summary) {
            $formatted .= $this->evidence_summary . "\n\n";
        }

        $formatted .= "## Detailed Analysis\n";
        $formatted .= $this->argument_text . "\n\n";

        if ($this->correlation_analysis) {
            $formatted .= "## Evidence Correlation\n";
            $formatted .= $this->correlation_analysis . "\n\n";
        }

        $formatted .= "## Supporting Evidence\n";
        $evidence_pieces = $this->evidence_analyses()->get();
        foreach ($evidence_pieces as $evidence) {
            $role = $evidence->pivot->evidence_role;
            $weight = $evidence->pivot->weight_in_argument;
            $formatted .= "### {$role} Evidence ({$weight})\n";
            $formatted .= $evidence->getFormattedForProof() . "\n\n";
        }

        if ($this->gaps_identified) {
            $formatted .= "## Known Limitations\n";
            $formatted .= $this->gaps_identified . "\n\n";
        }

        if ($this->future_research_needed) {
            $formatted .= "## Future Research Recommendations\n";
            $formatted .= $this->future_research_needed . "\n\n";
        }

        $formatted .= "## Methodology\n";
        $formatted .= $this->methodology_used ?: "Standard genealogical analysis";

        return $formatted;
    }

    /**
     * Submit for peer review
     */
    public function submitForReview($reviewer_user_id = null) {
        $this->peer_review_status = 'UNDER_REVIEW';
        if ($reviewer_user_id) {
            $this->reviewed_by_user_id = $reviewer_user_id;
        }
        $this->save();

        // Could trigger notification system here
    }

    /**
     * Complete peer review
     */
    public function completeReview($approved, $review_notes = null) {
        $this->peer_review_status = $approved ? 'APPROVED' : 'REJECTED';
        $this->review_notes = $review_notes;
        $this->review_date = current_time('mysql');
        $this->save();

        // Mark research question as resolved if approved
        if ($approved && $this->research_question()) {
            $this->research_question()->markResolved($this->id);
        }
    }

    /**
     * Calculate argument strength score
     */
    public function getStrengthScore() {
        $evidence_pieces = $this->evidence_analyses()->get();
        if ($evidence_pieces->isEmpty()) return 0;

        $total_strength = 0;
        $weight_multiplier = 0;

        foreach ($evidence_pieces as $evidence) {
            $strength = $evidence->getStrengthScore();
            $weight = $evidence->pivot->weight_in_argument;
            
            switch ($weight) {
                case 'CRITICAL': $multiplier = 3; break;
                case 'IMPORTANT': $multiplier = 2; break;
                case 'SUPPLEMENTARY': $multiplier = 1; break;
                default: $multiplier = 0.5;
            }

            $total_strength += $strength * $multiplier;
            $weight_multiplier += $multiplier;
        }

        return $weight_multiplier > 0 ? round($total_strength / $weight_multiplier, 1) : 0;
    }
}
