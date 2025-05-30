<?php
/**
 * Information Statement Model
 *
 * Represents individual pieces of information extracted from sources,
 * following Elizabeth Shown Mills' Evidence methodology.
 *
 * @package HeritagePress
 */

namespace HeritagePress\Models;

class Information_Statement extends Model {
    protected $table = 'information_statements';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'source_id',
        'citation_id',
        'statement_text',
        'statement_type',
        'information_quality',
        'specific_location',
        'transcription_notes',
        'language_original',
        'informant_assessment',
        'context_notes',
        'extracted_by_user_id',
        'verification_status'
    ];

    protected $rules = [
        'source_id' => ['required'],
        'statement_text' => ['required'],
        'statement_type' => ['required', 'in:PRIMARY,SECONDARY,HEARSAY,MIXED'],
        'information_quality' => ['required', 'in:FIRSTHAND,SECONDHAND,THIRDHAND,UNKNOWN'],
        'verification_status' => ['in:UNVERIFIED,VERIFIED,QUESTIONED,DISPROVEN']
    ];

    /**
     * Get the source this information came from
     */
    public function source() {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get the citation this information is linked to
     */
    public function citation() {
        return $this->belongsTo(Citation::class, 'citation_id');
    }

    /**
     * Get evidence analyses based on this information
     */
    public function evidence_analyses() {
        return $this->hasMany(Evidence_Analysis::class, 'information_statement_id');
    }

    /**
     * Create evidence analysis from this information
     */
    public function analyzeAsEvidence($research_question_id, $attributes = []) {
        $defaults = [
            'uuid' => wp_generate_uuid4(),
            'file_id' => $this->file_id,
            'information_statement_id' => $this->id,
            'research_question_id' => $research_question_id,
            'evidence_type' => $this->determineEvidenceType($research_question_id),
            'relevance_score' => 5,
            'analyst_user_id' => get_current_user_id(),
            'analysis_date' => current_time('mysql')
        ];

        $data = array_merge($defaults, $attributes);
        return Evidence_Analysis::create($data);
    }

    /**
     * Determine evidence type based on research question
     */
    private function determineEvidenceType($research_question_id) {
        // This would involve complex logic to determine if the information
        // directly answers the research question or provides indirect support
        // For now, return a default
        return 'INDIRECT';
    }

    /**
     * Get reliability score based on Mills criteria
     */
    public function getReliabilityScore() {
        $score = 5; // Base score

        // Adjust based on statement type
        switch ($this->statement_type) {
            case 'PRIMARY': $score += 3; break;
            case 'SECONDARY': $score += 1; break;
            case 'HEARSAY': $score -= 1; break;
            case 'MIXED': break; // No adjustment
        }

        // Adjust based on information quality
        switch ($this->information_quality) {
            case 'FIRSTHAND': $score += 2; break;
            case 'SECONDHAND': $score += 1; break;
            case 'THIRDHAND': $score -= 1; break;
        }

        // Adjust based on verification status
        switch ($this->verification_status) {
            case 'VERIFIED': $score += 1; break;
            case 'QUESTIONED': $score -= 1; break;
            case 'DISPROVEN': $score -= 3; break;
        }

        return max(1, min(10, $score)); // Keep between 1-10
    }

    /**
     * Check for potential transcription errors
     */
    public function checkTranscriptionAccuracy() {
        $issues = [];

        if (empty($this->transcription_notes)) {
            $issues[] = 'No transcription notes provided';
        }

        if ($this->language_original && $this->language_original !== 'en') {
            $issues[] = 'Original language differs from transcription';
        }

        // Add more sophisticated checks here
        return $issues;
    }

    /**
     * Format for evidence display
     */
    public function getFormattedStatement() {
        $formatted = "\"" . $this->statement_text . "\"";
        
        if ($this->specific_location) {
            $formatted .= " (Location: {$this->specific_location})";
        }

        $formatted .= " [Type: {$this->statement_type}, Quality: {$this->information_quality}]";

        return $formatted;
    }
}
