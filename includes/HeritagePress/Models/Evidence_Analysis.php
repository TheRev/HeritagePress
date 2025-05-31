<?php
/**
 * Evidence Analysis Model
 *
 * Represents the analysis and interpretation of information statements
 * as evidence for specific research questions.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Models;

use HeritagePress\Database\QueryBuilder;

class Evidence_Analysis extends Model {
    protected $table = 'evidence_analysis';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'information_statement_id',
        'research_question_id',
        'evidence_type',
        'relevance_score',
        'reliability_assessment',
        'interpretation_notes',
        'limitations',
        'corroboration_needed',
        'conflicts_with',
        'analyst_user_id',
        'confidence_level',
        'evidence_weight'
    ];

    protected $rules = [
        'information_statement_id' => ['required'],
        'evidence_type' => ['required', 'in:DIRECT,INDIRECT,NEGATIVE'],
        'relevance_score' => ['numeric', 'min:1', 'max:10'],
        'interpretation_notes' => ['required'],
        'confidence_level' => ['in:HIGH,MEDIUM,LOW,UNCERTAIN'],
        'evidence_weight' => ['in:STRONG,MODERATE,WEAK,NEGLIGIBLE']
    ];
    
    /**
     * Static query builder
     * 
     * Provides a fluent interface for building queries against this model.
     * 
     * @param string $column The column to filter on
     * @param mixed $operator The operator or value if operator is omitted
     * @param mixed $value The value to compare against (optional)
     * @return \HeritagePress\Database\QueryBuilder
     */
    public static function where($column, $operator, $value = null) {
        $instance = new static();
        $query = new QueryBuilder($instance);
        return $query->where($column, $operator, $value);
    }

    /**
     * Get the information statement this analysis is based on
     */
    public function information_statement() {
        return $this->belongsTo(Information_Statement::class, 'information_statement_id');
    }

    /**
     * Get the research question this evidence addresses
     */
    public function research_question() {
        return $this->belongsTo(Research_Question::class, 'research_question_id');
    }

    /**
     * Get proof arguments that use this evidence
     */
    public function proof_arguments() {
        return $this->belongsToMany(
            Proof_Argument::class,
            'proof_evidence_links',
            'evidence_analysis_id',
            'proof_argument_id'
        )->withPivot(['evidence_role', 'weight_in_argument', 'usage_notes']);
    }

    /**
     * Calculate evidence strength score
     */
    public function getStrengthScore() {
        $score = $this->relevance_score; // Base score from relevance

        // Adjust based on evidence type
        switch ($this->evidence_type) {
            case 'DIRECT': $score += 3; break;
            case 'INDIRECT': $score += 1; break;
            case 'NEGATIVE': $score += 2; break; // Negative evidence can be quite valuable
        }

        // Adjust based on confidence level
        switch ($this->confidence_level) {
            case 'HIGH': $score += 2; break;
            case 'MEDIUM': $score += 1; break;
            case 'LOW': $score -= 1; break;
            case 'UNCERTAIN': $score -= 2; break;
        }

        // Get information statement reliability
        $info_statement = $this->information_statement();
        if ($info_statement) {
            $reliability = $info_statement->getReliabilityScore();
            $score += ($reliability - 5); // Adjust by deviation from neutral (5)
        }

        return max(1, min(20, $score)); // Keep between 1-20
    }

    /**
     * Check if this evidence conflicts with other evidence
     */
    public function findConflicts() {
        if (!$this->research_question_id) {
            return [];
        }

        $other_evidence = Evidence_Analysis::where('research_question_id', $this->research_question_id)
                                          ->where('id', '!=', $this->id)
                                          ->get();

        $conflicts = [];
        foreach ($other_evidence as $evidence) {
            $conflict = $this->detectConflictWith($evidence);
            if ($conflict) {
                $conflicts[] = $conflict;
            }
        }

        return $conflicts;
    }

    /**
     * Detect conflict with another evidence analysis
     */
    private function detectConflictWith($other_evidence) {
        // Simplified conflict detection
        // In practice, this would be much more sophisticated
        
        if ($this->evidence_type === 'DIRECT' && $other_evidence->evidence_type === 'DIRECT') {
            // Two direct evidence pieces for the same question might conflict
            return [
                'type' => 'DIRECT_CONFLICT',
                'evidence_1' => $this,
                'evidence_2' => $other_evidence,
                'description' => 'Two direct evidence pieces may contradict each other'
            ];
        }

        return null;
    }

    /**
     * Generate analysis summary
     */
    public function getSummary() {
        $info_statement = $this->information_statement();
        $source_title = $info_statement && $info_statement->source() ? 
                       $info_statement->source()->title : 'Unknown Source';

        return [
            'source' => $source_title,
            'evidence_type' => $this->evidence_type,
            'strength_score' => $this->getStrengthScore(),
            'confidence' => $this->confidence_level,
            'weight' => $this->evidence_weight,
            'relevance' => $this->relevance_score,
            'key_points' => $this->extractKeyPoints()
        ];
    }

    /**
     * Extract key points from interpretation notes
     */
    private function extractKeyPoints() {
        // Simple extraction - look for bullet points or numbered lists
        $notes = $this->interpretation_notes;
        if (!$notes) return [];

        $lines = explode("\n", $notes);
        $key_points = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[•\-\*\d+\.]\s+(.+)/', $line, $matches)) {
                $key_points[] = $matches[1];
            }
        }

        return $key_points;
    }

    /**
     * Update confidence level based on new information
     */
    public function reassessConfidence($new_evidence_count = 0, $conflicting_evidence_count = 0) {
        $current_score = $this->getStrengthScore();
        
        // Increase confidence with more supporting evidence
        if ($new_evidence_count > 0) {
            $current_score += min(3, $new_evidence_count);
        }

        // Decrease confidence with conflicting evidence
        if ($conflicting_evidence_count > 0) {
            $current_score -= min(5, $conflicting_evidence_count * 2);
        }

        // Map score to confidence level
        if ($current_score >= 15) {
            $this->confidence_level = 'HIGH';
        } elseif ($current_score >= 10) {
            $this->confidence_level = 'MEDIUM';
        } elseif ($current_score >= 5) {
            $this->confidence_level = 'LOW';
        } else {
            $this->confidence_level = 'UNCERTAIN';
        }

        $this->save();
    }

    /**
     * Format for display in proof argument
     */
    public function getFormattedForProof() {
        $info_statement = $this->information_statement();
        $formatted = '';

        if ($info_statement) {
            $formatted = $info_statement->getFormattedStatement();
        }

        $formatted .= "\n\nAnalysis: " . $this->interpretation_notes;
        
        if ($this->limitations) {
            $formatted .= "\n\nLimitations: " . $this->limitations;
        }

        return $formatted;
    }

    /**
     * Create model instance from database object
     */
    public static function from_db_object(\stdClass $db_row): Evidence_Analysis {
        $instance = new static();
        
        // Set properties from database row
        foreach ($db_row as $property => $value) {
            $instance->$property = $value;
        }
        
        return $instance;
    }

    /**
     * Automatically assess confidence level based on evidence characteristics
     */
    public function autoAssessConfidence() {
        $base_score = 5; // Start with neutral confidence
        
        // Adjust based on evidence type
        switch ($this->evidence_type) {
            case 'DIRECT':
                $base_score += 4;
                break;
            case 'INDIRECT':
                $base_score += 2;
                break;
            case 'NEGATIVE':
                $base_score += 1;
                break;
        }
        
        // Adjust based on relevance score
        $base_score += ($this->relevance_score - 5); // Deviation from neutral
        
        // Get information statement reliability if available
        $info_statement = $this->information_statement();
        if ($info_statement) {
            $reliability = $info_statement->getReliabilityScore();
            $base_score += ($reliability - 5); // Deviation from neutral
        }
        
        // Check for conflicts with other evidence
        $conflicts = $this->findConflicts();
        $base_score -= count($conflicts) * 2; // Reduce confidence for conflicts
        
        // Map score to confidence level
        if ($base_score >= 12) {
            $this->confidence_level = 'HIGH';
        } elseif ($base_score >= 8) {
            $this->confidence_level = 'MEDIUM';
        } elseif ($base_score >= 4) {
            $this->confidence_level = 'LOW';
        } else {
            $this->confidence_level = 'UNCERTAIN';
        }
        
        // Save the updated confidence level
        $this->save();
    }
}
