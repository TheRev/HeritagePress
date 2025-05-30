<?php
/**
 * Source Model Class
 *
 * Represents a genealogical source in the database. Sources provide evidence
 * for genealogical facts and events, and can be linked to multiple records.
 *
 * @package HeritagePress
 * @subpackage Models
 */

namespace HeritagePress\Models;

/**
 * Source model class
 * 
 * @property string $title Source title
 * @property string $author Author of the source
 * @property string $publication_info Publication information
 * @property string $repository Repository where source is held
 * @property string $call_number Call number or reference ID
 * @property string $type Source type (document, census, birth_record, etc.)
 * @property string $url URL to online version if available
 * @property text $notes Additional notes about the source
 * @property date $date Date of the source
 */
class Source extends Model {
    protected $table = 'sources';
      protected $fillable = [
        'uuid',
        'repository_id',
        'file_id',
        'title',
        'author',
        'publication_info',
        'repository',
        'call_number',
        'type',
        'url',
        'notes',
        'date',
        'status'
    ];

    protected $rules = [
        'title' => ['required', 'max:255'],
        'type' => ['required', 'max:50'],
        'date' => ['date']
    ];    /**
     * Get all citations referencing this source
     */
    public function citations() {
        return $this->hasMany(Citation::class, 'source_id');
    }

    /**
     * Get quality rating counts for this source
     */
    public function getQualityDistribution() {
        return $this->citations()
            ->select('quality_assessment', 'COUNT(*) as count')
            ->groupBy('quality_assessment')
            ->get();
    }

    /**
     * Get all individuals referenced by this source
     */
    public function individuals() {
        return $this->belongsToMany(
            Individual::class,
            'citations',
            'source_id',
            'individual_id'
        );
    }

    /**
     * Get all families referenced by this source
     */
    public function families() {
        return $this->belongsToMany(
            Family::class,
            'citations',
            'source_id',
            'family_id'
        );
    }

    /**
     * Get all events referenced by this source
     */
    public function events() {
        return $this->belongsToMany(
            Event::class,
            'citations',
            'source_id',
            'event_id'
        );
    }

    /**
     * Get repository for this source
     */
    public function repository() {
        return $this->belongsTo(Repository::class, 'repository_id');
    }

    /**
     * Assess source quality
     */
    public function assessQuality($assessment = null) {
        if ($assessment === null) {
            $assessment = $this->getDefaultAssessment();
        }

        $service = new \HeritagePress\Services\SourceQualityService();
        return $service->assessSource($assessment);
    }

    /**
     * Get default quality assessment based on source type
     */
    private function getDefaultAssessment() {
        $assessment = [];

        switch ($this->type) {
            case 'birth_record':
            case 'death_record':
            case 'marriage_record':
                $assessment = [
                    'originality' => 'primary',
                    'timeframe' => 'contemporary',
                    'information_type' => 'direct',
                    'creator_reliability' => 'official'
                ];
                break;

            case 'census':
                $assessment = [
                    'originality' => 'primary',
                    'timeframe' => 'contemporary',
                    'information_type' => 'direct',
                    'creator_reliability' => 'official'
                ];
                break;

            case 'obituary':
                $assessment = [
                    'originality' => 'secondary',
                    'timeframe' => 'contemporary',
                    'information_type' => 'indirect',
                    'creator_reliability' => 'professional'
                ];
                break;

            case 'family_bible':
                $assessment = [
                    'originality' => 'primary',
                    'timeframe' => 'near_contemporary',
                    'information_type' => 'direct',
                    'creator_reliability' => 'personal'
                ];
                break;

            case 'will':
                $assessment = [
                    'originality' => 'primary',
                    'timeframe' => 'contemporary',
                    'information_type' => 'direct',
                    'creator_reliability' => 'official'
                ];
                break;

            default:
                $assessment = [
                    'originality' => 'secondary',
                    'timeframe' => 'retrospective',
                    'information_type' => 'indirect',
                    'creator_reliability' => 'personal'
                ];
        }

        return $assessment;
    }

    /**
     * Compare this source with others for the same fact
     */
    public function compareWithOtherSources($fact, $otherSources) {
        $sources = array_merge(
            [['source' => $this, 'fact' => $fact, 'assessment' => $this->getDefaultAssessment()]],
            array_map(function($source) use ($fact) {
                return [
                    'source' => $source,
                    'fact' => $fact,
                    'assessment' => $source->getDefaultAssessment()
                ];
            }, $otherSources)
        );

        $service = new \HeritagePress\Services\SourceQualityService();
        return $service->analyzeConflicts($sources);
    }
}
