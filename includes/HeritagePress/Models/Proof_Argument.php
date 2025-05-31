<?php
/**
 * Proof Argument Model
 *
 * Represents a written proof argument that addresses a research question
 * by analyzing and correlating evidence according to genealogical standards.
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
        'title',
        'summary',
        'correlation_analysis',
        'conclusion',
        'gps_compliance_level',
        'status',
        'author_user_id',
        'created_at',
        'updated_at'
    ];

    protected $rules = [
        'research_question_id' => ['required'],
        'title' => ['required'],
        'conclusion' => ['required'],
        'status' => ['in:DRAFT,REVIEW,FINALIZED,PUBLISHED']
    ];

    /**
     * Get the research question this argument answers
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
        $points = array_sum($compliance);
        $max = 50; // 10 points possible per category
        $percentage = ($points / $max) * 100;
        
        if ($percentage >= 90) {
            $compliance['overall_compliance'] = 'EXCELLENT';
        } elseif ($percentage >= 75) {
            $compliance['overall_compliance'] = 'GOOD';
        } elseif ($percentage >= 60) {
            $compliance['overall_compliance'] = 'ADEQUATE';
        } else {
            $compliance['overall_compliance'] = 'INSUFFICIENT';
        }
        
        return $compliance;
    }
    
    /**
     * Check if search was reasonably exhaustive
     */
    private function assessSearchExhaustiveness() {
        $evidence_count = $this->evidence_analyses()->count();
        
        // Simple heuristic - more sophisticated in practice
        if ($evidence_count >= 5) return 10;
        if ($evidence_count >= 3) return 7;
        if ($evidence_count >= 2) return 5;
        return 3;
    }
    
    /**
     * Assess citation quality
     */
    private function assessCitationQuality() {
        $evidence_analyses = $this->evidence_analyses()->get();
        if (empty($evidence_analyses)) return 0;
        
        $total_score = 0;
        foreach ($evidence_analyses as $analysis) {
            $info_statement = $analysis->information_statement();
            if ($info_statement && $info_statement->citation()) {
                $citation = $info_statement->citation();
                $assessment = $citation->assessEvidenceQuality();
                $total_score += $this->mapAssessmentToScore($assessment['overall_strength']);
            }
        }
        
        return $total_score / count($evidence_analyses);
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
     * Assess how well contradictions are resolved
     */
    private function assessContradictionResolution() {
        $research_question = $this->research_question();
        if (!$research_question) return 5;
        
        // Check if there are conflicts noted
        $conflicts = [];
        $evidence_analyses = $this->evidence_analyses()->get();
        foreach ($evidence_analyses as $analysis) {
            $new_conflicts = $analysis->findConflicts();
            $conflicts = array_merge($conflicts, $new_conflicts);
        }
        
        if (empty($conflicts)) {
            // No conflicts to resolve
            return 10;
        }
        
        // Check if conflicts are addressed in correlation analysis
        $addressed = 0;
        foreach ($conflicts as $conflict) {
            // Check if correlation analysis mentions the conflicting sources
            // This is a simplistic approach - a real implementation would be more sophisticated
            if (strpos($this->correlation_analysis, 'conflict') !== false) {
                $addressed++;
            }
        }
        
        $resolution_rate = $addressed / count($conflicts);
        return min(10, round($resolution_rate * 10) + 3); // At least 3 points for trying
    }
    
    /**
     * Assess soundness of conclusion
     */
    private function assessConclusionSoundness() {
        // Basic checks - more sophisticated in practice
        if (empty($this->conclusion)) return 0;
        
        $score = 5; // Start with medium score
        
        // Check if conclusion references evidence
        if (strpos(strtolower($this->conclusion), 'evidence') !== false) {
            $score += 2;
        }
        
        // Check if conclusion acknowledges limitations
        if (strpos(strtolower($this->conclusion), 'limit') !== false) {
            $score += 1;
        }
        
        // Check conclusion length - more detail is usually better
        if (strlen($this->conclusion) > 300) {
            $score += 2;
        }
        
        return min(10, $score);
    }
    
    /**
     * Map string assessment to numeric score
     */
    private function mapAssessmentToScore($assessment) {
        switch ($assessment) {
            case 'EXCELLENT': return 10;
            case 'GOOD': return 8;
            case 'ADEQUATE': return 6;
            case 'FAIR': return 4;
            case 'POOR': return 2;
            default: return 0;
        }
    }
    
    /**
     * Create model instance from database object
     */
    public static function from_db_object(\stdClass $db_row): Proof_Argument {
        $instance = new static();
        
        // Set properties from database row
        foreach ($db_row as $property => $value) {
            $instance->$property = $value;
        }
        
        return $instance;
    }
}
