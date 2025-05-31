<?php
/**
 * Citation Model Class
 *
 * Represents a specific citation of a source, linking it to individuals,
 * families, or events with specific page numbers and quality assessments.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

/**
 * Citation model class
 * 
 * @property int $source_id Referenced source ID
 * @property int $individual_id Optional individual ID
 * @property int $family_id Optional family ID
 * @property int $event_id Optional event ID
 * @property string $page_number Specific page number(s) in the source
 * @property string $quality_assessment Assessment of source quality (primary/secondary/etc)
 * @property string $confidence_rating Confidence in the information (high/medium/low)
 * @property text $citation_text Full citation text
 * @property text $notes Additional notes about the citation
 */
class Citation extends Model {
    protected $table = 'citations';
    
    protected $fillable = [
        'uuid',
        'file_id',
        'source_id',
        'individual_id',
        'family_id',
        'event_id',
        'page_number',
        'quality_assessment',
        'confidence_rating',
        'citation_text',
        'notes',
        'status'
    ];    protected $rules = [
        'source_id' => ['required'],
        'quality_assessment' => ['max:50', 'in:primary,secondary,derivative,unknown'],
        'confidence_score' => ['numeric', 'min:1', 'max:3'],
        'page' => ['max:255'],
        'citation_text' => ['max:1000']
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
     * Check if this is a primary source citation
     */
    public function isPrimary() {
        return $this->quality_assessment === 'primary';
    }

    /**
     * Get the confidence level (1-3)
     */
    public function getConfidenceLevel() {
        return (int)$this->confidence_score;
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

        if ($source->publication_info) {
            $citation .= " ({$source->publication_info})";
        }

        if ($this->page_number) {
            $citation .= ", p. {$this->page_number}";
        }

        if ($source->repository) {
            $citation .= "; {$source->repository}";
            if ($source->call_number) {
                $citation .= " ({$source->call_number})";
            }
        }

        return $citation;
    }
}
