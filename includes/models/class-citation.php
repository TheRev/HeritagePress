<?php
/**
 * Citation Model Class
 *
 * Represents a basic genealogical citation linking sources to individuals,
 * families, or events with simple quality assessment.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

class Citation extends Model {
    protected $table = 'citations';
    
    protected $fillable = [
        'uuid',
        'source_id',
        'individual_id',
        'family_id', 
        'event_id',
        'page_number',
        'quality_assessment',
        'confidence_score',
        'citation_text',
        'notes'
    ];

    protected $rules = [
        'source_id' => ['required'],
        'quality_assessment' => ['in:primary,secondary,other'],
        'confidence_score' => ['numeric', 'min:1', 'max:3'],
        'page_number' => ['max:255']
    ];

    /**
     * Get the source for this citation
     */
    public function source() {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get the individual for this citation
     */
    public function individual() {
        return $this->belongsTo(Individual::class, 'individual_id');
    }

    /**
     * Get the family for this citation
     */
    public function family() {
        return $this->belongsTo(Family::class, 'family_id');
    }

    /**
     * Get the event for this citation
     */
    public function event() {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Format citation for display
     */
    public function getFormattedCitation() {
        $source = $this->source;
        if (!$source) return '';

        $citation = $source->title;
        
        if ($source->author) {
            $citation = "{$source->author}, {$citation}";
        }

        if ($this->page_number) {
            $citation .= ", p. {$this->page_number}";
        }

        return $citation;
    }
}
