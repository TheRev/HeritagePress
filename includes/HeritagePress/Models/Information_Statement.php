<?php
/**
 * Information Statement Model
 *
 * Represents factual statements extracted from a source.
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
        'statement_text',
        'statement_type',
        'reliability',
        'citation_details',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $rules = [
        'statement_text' => ['required'],
        'source_id' => ['required', 'numeric']
    ];

    /**
     * Get source this information is from
     */
    public function source() {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get evidence analyses based on this information
     */
    public function evidence_analyses() {
        return $this->hasMany(Evidence_Analysis::class, 'information_statement_id');
    }

    /**
     * Get formatted statement
     */
    public function getFormattedStatement() {
        return $this->statement_text;
    }

    /**
     * Get reliability score
     */
    public function getReliabilityScore() {
        // Default to medium if not specifically rated
        if (empty($this->reliability)) {
            return 5;
        }
        
        // Convert string ratings to numeric scores
        switch ($this->reliability) {
            case 'HIGH':
                return 8;
            case 'MEDIUM':
                return 5;
            case 'LOW':
                return 3;
            default:
                return (int)$this->reliability;
        }
    }

    /**
     * Get citation for this statement
     */
    public function citation() {
        $source = $this->source();
        if (!$source) {
            return null;
        }
        
        // We might want to implement a more sophisticated citation system
        return $source->getCitation();
    }
    
    /**
     * Create model instance from database object
     */
    public static function from_db_object(\stdClass $db_row): Information_Statement {
        $instance = new static();
        
        // Set properties from database row
        foreach ($db_row as $property => $value) {
            $instance->$property = $value;
        }
        
        return $instance;
    }
}
