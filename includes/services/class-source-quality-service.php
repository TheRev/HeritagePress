<?php
namespace HeritagePress\Services;

/**
 * Source Quality Assessment Service
 *
 * Analyzes and scores genealogical sources based on various criteria.
 */
class SourceQualityService {
    private $criteria = [
        'originality' => [
            'primary' => 3,
            'secondary' => 2,
            'derivative' => 1
        ],
        'timeframe' => [
            'contemporary' => 3,
            'near_contemporary' => 2,
            'retrospective' => 1
        ],
        'information_type' => [
            'direct' => 3,
            'indirect' => 2,
            'negative' => 1
        ],
        'creator_reliability' => [
            'official' => 3,
            'professional' => 2,
            'personal' => 1
        ]
    ];

    /**
     * Assess source quality
     * 
     * @param array $assessment Assessment criteria values
     * @return array Quality score and analysis
     */
    public function assessSource($assessment) {
        $score = 0;
        $maxScore = 0;
        $analysis = [];

        foreach ($this->criteria as $criterion => $values) {
            if (isset($assessment[$criterion])) {
                $value = $assessment[$criterion];
                if (isset($values[$value])) {
                    $score += $values[$value];
                    $analysis[$criterion] = [
                        'value' => $value,
                        'score' => $values[$value],
                        'max' => max($values)
                    ];
                }
                $maxScore += max($values);
            }
        }

        $percentage = ($score / $maxScore) * 100;
        $reliability = $this->getReliabilityLevel($percentage);

        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'reliability' => $reliability,
            'analysis' => $analysis,
            'recommendations' => $this->getRecommendations($analysis)
        ];
    }

    /**
     * Get reliability level based on score
     */
    private function getReliabilityLevel($percentage) {
        if ($percentage >= 90) {
            return 'Very High';
        } elseif ($percentage >= 75) {
            return 'High';
        } elseif ($percentage >= 60) {
            return 'Medium';
        } elseif ($percentage >= 40) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }

    /**
     * Get recommendations for improving source quality
     */
    private function getRecommendations($analysis) {
        $recommendations = [];

        foreach ($analysis as $criterion => $data) {
            if ($data['score'] < $data['max']) {
                switch ($criterion) {
                    case 'originality':
                        if ($data['value'] === 'derivative') {
                            $recommendations[] = 'Try to locate the original document';
                        }
                        break;
                    case 'timeframe':
                        if ($data['value'] === 'retrospective') {
                            $recommendations[] = 'Look for contemporary records from the time period';
                        }
                        break;
                    case 'information_type':
                        if ($data['value'] === 'indirect') {
                            $recommendations[] = 'Search for direct evidence to support this information';
                        }
                        break;
                    case 'creator_reliability':
                        if ($data['value'] === 'personal') {
                            $recommendations[] = 'Seek official or professional records to corroborate';
                        }
                        break;
                }
            }
        }

        return $recommendations;
    }

    /**
     * Analyze conflicting sources
     * 
     * @param array $sources Array of sources with their assessments
     * @return array Analysis of conflicts and recommendations
     */
    public function analyzeConflicts($sources) {
        $conflicts = [];
        $resolved = [];
        $recommendations = [];

        // Group sources by fact they're addressing
        foreach ($sources as $source) {
            $fact = $source['fact'];
            if (!isset($conflicts[$fact])) {
                $conflicts[$fact] = [];
            }
            $conflicts[$fact][] = $source;
        }

        // Analyze each fact's sources
        foreach ($conflicts as $fact => $factSources) {
            if (count($factSources) > 1) {
                // Sort sources by reliability
                usort($factSources, function($a, $b) {
                    $assessmentA = $this->assessSource($a['assessment']);
                    $assessmentB = $this->assessSource($b['assessment']);
                    return $assessmentB['percentage'] - $assessmentA['percentage'];
                });

                $resolved[$fact] = [
                    'best_source' => $factSources[0],
                    'supporting_sources' => array_slice($factSources, 1),
                    'confidence' => $this->calculateConfidence($factSources)
                ];

                if ($resolved[$fact]['confidence'] < 70) {
                    $recommendations[$fact] = 'Additional sources recommended to resolve conflicts';
                }
            }
        }

        return [
            'conflicts' => $conflicts,
            'resolved' => $resolved,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Calculate confidence level in conflicting sources
     */
    private function calculateConfidence($sources) {
        $totalWeight = 0;
        $agreementWeight = 0;

        foreach ($sources as $i => $source1) {
            $assessment1 = $this->assessSource($source1['assessment']);
            $weight1 = $assessment1['percentage'];

            foreach (array_slice($sources, $i + 1) as $source2) {
                $assessment2 = $this->assessSource($source2['assessment']);
                $weight2 = $assessment2['percentage'];
                
                $totalWeight += ($weight1 + $weight2) / 2;
                if ($this->sourcesAgree($source1, $source2)) {
                    $agreementWeight += ($weight1 + $weight2) / 2;
                }
            }
        }

        return $totalWeight > 0 ? ($agreementWeight / $totalWeight) * 100 : 0;
    }

    /**
     * Check if two sources agree on their facts
     */
    private function sourcesAgree($source1, $source2) {
        // Compare source values with some tolerance for variations
        // This would need to be implemented based on the type of fact
        // being compared (dates, names, places, etc.)
        return true; // Placeholder
    }
}
