<?php
/**
 * Enhanced Citation Model with Evidence Methodology Support
 *
 * Extends the basic citation model to support Elizabeth Shown Mills'
 * Evidence Explained methodology for source analysis.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Models;

class Enhanced_Citation extends Citation {
    
    /**
     * Get information statements extracted from this citation
     */
    public function information_statements() {
        return $this->hasMany(Information_Statement::class, 'citation_id');
    }

    /**
     * Get evidence analysis based on this citation
     */
    public function evidence_analyses() {
        return $this->hasManyThrough(
            Evidence_Analysis::class,
            Information_Statement::class,
            'citation_id',
            'information_statement_id'
        );
    }

    /**
     * Create information statement from this citation
     */
    public function extractInformation($statement_text, $attributes = []) {
        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'file_id' => $this->file_id,
            'source_id' => $this->source_id,
            'citation_id' => $this->id,
            'statement_text' => $statement_text,
            'statement_type' => $this->determineStatementType($statement_text),
            'information_quality' => $this->assessInformationQuality(),
            'specific_location' => $this->page ?? null,
            'extraction_date' => current_time('mysql'),
            'extracted_by_user_id' => get_current_user_id()
        ];

        $data = array_merge($defaults, $attributes);
        return Information_Statement::create($data);
    }

    /**
     * Determine statement type based on source and context
     */
    private function determineStatementType($statement_text) {
        $source = $this->source();
        if (!$source) return 'SECONDARY';

        // Analyze source characteristics
        $assessment = $source->getQualityAssessment();
        
        if ($assessment && isset($assessment['originality'])) {
            switch ($assessment['originality']) {
                case 'primary':
                    return $this->hasFirsthandInformant() ? 'PRIMARY' : 'SECONDARY';
                case 'secondary':
                    return 'SECONDARY';
                default:
                    return 'MIXED';
            }
        }

        return 'SECONDARY';
    }

    /**
     * Assess information quality based on Mills methodology
     */
    private function assessInformationQuality() {
        $source = $this->source();
        if (!$source) return 'UNKNOWN';

        // Check informant relationship
        if ($this->role_in_source) {
            switch ($this->role_in_source) {
                case 'SELF':
                case 'PARENT':
                case 'SPOU':
                    return 'FIRSTHAND';
                case 'WITN':
                case 'FRIEND':
                case 'NGHBR':
                    return 'SECONDHAND';
                default:
                    return 'THIRDHAND';
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Check if source has firsthand informant
     */
    private function hasFirsthandInformant() {
        return in_array($this->role_in_source, ['SELF', 'PARENT', 'SPOU']);
    }

    /**
     * Generate Evidence Explained style citation
     */
    public function getEvidenceExplainedCitation() {
        $source = $this->source();
        if (!$source) return '';

        $formatter = new Evidence_Citation_Formatter();
        return $formatter->format($source, $this);
    }

    /**
     * Assess citation quality using Mills criteria
     */
    public function assessEvidenceQuality() {
        $source = $this->source();
        if (!$source) return null;

        $assessment = [
            'source_originality' => $this->assessSourceOriginality($source),
            'information_type' => $this->assessInformationType(),
            'evidence_directness' => $this->assessEvidenceDirectness(),
            'informant_reliability' => $this->assessInformantReliability(),
            'overall_strength' => null
        ];

        // Calculate overall strength
        $assessment['overall_strength'] = $this->calculateOverallStrength($assessment);

        return $assessment;
    }

    /**
     * Assess source originality
     */
    private function assessSourceOriginality($source) {
        // Check if it's an original document
        if (in_array($source->type, ['birth_record', 'death_record', 'marriage_record', 'will'])) {
            return 'ORIGINAL';
        }

        // Check if it's a known derivative
        if (in_array($source->type, ['transcript', 'extract', 'abstract', 'index'])) {
            return 'DERIVATIVE';
        }

        // Check for authored works
        if ($source->author && in_array($source->type, ['biography', 'history', 'genealogy'])) {
            return 'AUTHORED_DERIVATIVE';
        }

        return 'UNKNOWN';
    }

    /**
     * Assess information type
     */
    private function assessInformationType() {
        if ($this->hasFirsthandInformant()) {
            return 'PRIMARY';
        }

        if (in_array($this->role_in_source, ['WITN', 'OFFICIATOR', 'CLERGY'])) {
            return 'PRIMARY'; // Witness or official capacity
        }

        return 'SECONDARY';
    }

    /**
     * Assess evidence directness
     */
    private function assessEvidenceDirectness() {
        // This would need to be determined based on what the citation is trying to prove
        // For now, return a default assessment
        return 'DIRECT'; // Would need context of research question
    }

    /**
     * Assess informant reliability
     */
    private function assessInformantReliability() {
        switch ($this->role_in_source) {
            case 'OFFICIATOR':
            case 'CLERGY':
                return 'OFFICIAL';
            case 'PARENT':
            case 'SPOU':
                return 'KNOWLEDGEABLE';
            case 'WITN':
            case 'FRIEND':
                return 'AVERAGE';
            default:
                return 'UNKNOWN';
        }
    }

    /**
     * Calculate overall evidence strength
     */
    private function calculateOverallStrength($assessment) {
        $scores = [];

        // Score originality (40% weight)
        switch ($assessment['source_originality']) {
            case 'ORIGINAL': $scores[] = 10 * 0.4; break;
            case 'DERIVATIVE': $scores[] = 7 * 0.4; break;
            case 'AUTHORED_DERIVATIVE': $scores[] = 5 * 0.4; break;
            default: $scores[] = 3 * 0.4;
        }

        // Score information type (30% weight)
        switch ($assessment['information_type']) {
            case 'PRIMARY': $scores[] = 10 * 0.3; break;
            case 'SECONDARY': $scores[] = 6 * 0.3; break;
            default: $scores[] = 3 * 0.3;
        }

        // Score evidence directness (20% weight)
        switch ($assessment['evidence_directness']) {
            case 'DIRECT': $scores[] = 10 * 0.2; break;
            case 'INDIRECT': $scores[] = 6 * 0.2; break;
            default: $scores[] = 3 * 0.2;
        }

        // Score informant reliability (10% weight)
        switch ($assessment['informant_reliability']) {
            case 'OFFICIAL': $scores[] = 10 * 0.1; break;
            case 'KNOWLEDGEABLE': $scores[] = 8 * 0.1; break;
            case 'AVERAGE': $scores[] = 6 * 0.1; break;
            default: $scores[] = 3 * 0.1;
        }

        $total_score = array_sum($scores);

        if ($total_score >= 8.5) return 'STRONG';
        if ($total_score >= 6.5) return 'MODERATE';
        if ($total_score >= 4.0) return 'WEAK';
        return 'VERY_WEAK';
    }

    /**
     * Compare with other citations for conflicts
     */
    public function compareWithOtherCitations($research_question_id = null) {
        $query = Citation::where('individual_id', $this->individual_id)
                        ->where('id', '!=', $this->id);

        if ($research_question_id) {
            // Filter by research question context
            $query->whereHas('evidence_analyses', function($q) use ($research_question_id) {
                $q->where('research_question_id', $research_question_id);
            });
        }

        $other_citations = $query->get();
        $conflicts = [];

        foreach ($other_citations as $citation) {
            $conflict = $this->detectConflict($citation);
            if ($conflict) {
                $conflicts[] = $conflict;
            }
        }

        return $conflicts;
    }

    /**
     * Detect conflicts between citations
     */
    private function detectConflict($other_citation) {
        // This is a simplified conflict detection
        // In practice, you'd want more sophisticated comparison logic
        
        $this_assessment = $this->assessEvidenceQuality();
        $other_assessment = $other_citation->assessEvidenceQuality();

        if ($this_assessment['overall_strength'] !== $other_assessment['overall_strength']) {
            return [
                'type' => 'QUALITY_CONFLICT',
                'citation_1' => $this,
                'citation_2' => $other_citation,
                'description' => 'Citations have different quality assessments',
                'strength_1' => $this_assessment['overall_strength'],
                'strength_2' => $other_assessment['overall_strength']
            ];
        }

        return null;
    }
}
