<?php
namespace HeritagePress\GEDCOM;

class GedcomPlaceHandler {
    private $places = [];
    private $standardized_places = [];
    private $abbreviations = [
        // Countries
        'USA' => 'United States',
        'UK' => 'United Kingdom',
        // US States
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona',
        'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado',
        'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida',
        'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
        'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
        'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana',
        'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts',
        'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
        'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
        'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
        'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina',
        'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
        'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
        'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee',
        'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
        'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
        'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        // Common place abbreviations
        'St.' => 'Saint',
        'Ft.' => 'Fort',
        'Mt.' => 'Mount',
        'Co.' => 'County',
        'Twp.' => 'Township',
        'Dist.' => 'District'
    ];

    /**
     * Handle place record from GEDCOM
     */
    public function handlePlace($place) {
        if (empty($place['value'])) {
            return $place;
        }

        $placeData = [
            'original' => $place['value'],
            'parts' => $this->parsePlaceParts($place['value']),
            'coordinates' => [],
            'notes' => [],
            'standardized' => []
        ];

        // Parse coordinates and other data
        if (isset($place['children'])) {
            foreach ($place['children'] as $child) {
                switch ($child['tag']) {
                    case 'LATI':
                        $placeData['coordinates']['latitude'] = $this->parseLatitude($child['value']);
                        break;
                    case 'LONG':
                        $placeData['coordinates']['longitude'] = $this->parseLongitude($child['value']);
                        break;
                    case 'MAP':
                        $this->parseMapData($child, $placeData);
                        break;
                    case 'NOTE':
                        $placeData['notes'][] = $child['value'];
                        break;
                    case 'FORM':
                        $placeData['format'] = $child['value'];
                        break;
                }
            }
        }

        // Standardize place names
        $placeData['standardized'] = $this->standardizePlace($placeData['parts']);
        
        // Store for later reference
        $this->places[] = $placeData;
        
        return $placeData;
    }

    /**
     * Parse place parts from hierarchical format
     */
    private function parsePlaceParts($value) {
        $parts = array_map('trim', explode(',', $value));
        return array_filter($parts); // Remove empty parts
    }

    /**
     * Parse latitude value
     */
    private function parseLatitude($value) {
        // Remove N/S indicator and convert to decimal
        $value = trim($value);
        $multiplier = (stripos($value, 'S') !== false) ? -1 : 1;
        $value = preg_replace('/[NS]/i', '', $value);
        return $multiplier * floatval($value);
    }

    /**
     * Parse longitude value
     */
    private function parseLongitude($value) {
        // Remove E/W indicator and convert to decimal
        $value = trim($value);
        $multiplier = (stripos($value, 'W') !== false) ? -1 : 1;
        $value = preg_replace('/[EW]/i', '', $value);
        return $multiplier * floatval($value);
    }

    /**
     * Parse map data from GEDCOM
     */
    private function parseMapData($mapData, &$placeData) {
        if (isset($mapData['children'])) {
            foreach ($mapData['children'] as $child) {
                switch ($child['tag']) {
                    case 'LATI':
                        $placeData['coordinates']['latitude'] = $this->parseLatitude($child['value']);
                        break;
                    case 'LONG':
                        $placeData['coordinates']['longitude'] = $this->parseLongitude($child['value']);
                        break;
                }
            }
        }
    }

    /**
     * Standardize place names
     */
    private function standardizePlace($parts) {
        $standardized = [];
        
        // Process from smallest jurisdiction to largest
        $parts = array_reverse($parts);
        
        foreach ($parts as $index => $part) {
            $level = count($parts) - $index;
            $context = array_slice($parts, $index + 1);
            $standardized[] = $this->standardizePlacePart($part, $level, $context);
        }
        
        return array_reverse($standardized);
    }

    /**
     * Standardize a single place part
     */
    private function standardizePlacePart($part, $level, $context) {
        // Clean and standardize the part
        $standardized = trim($part);
        
        // Expand abbreviations
        $standardized = $this->expandAbbreviations($standardized);
        
        // Title case (with exceptions)
        $standardized = $this->titleCase($standardized);
        
        // Store standardized version
        $key = $standardized . '|' . implode('|', $context);
        $this->standardized_places[$key] = [
            'original' => $part,
            'standardized' => $standardized,
            'level' => $level,
            'context' => $context
        ];
        
        return $standardized;
    }

    /**
     * Expand common place abbreviations
     */
    private function expandAbbreviations($text) {
        $words = explode(' ', $text);
        $result = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (isset($this->abbreviations[strtoupper($word)])) {
                $result[] = $this->abbreviations[strtoupper($word)];
            } elseif (isset($this->abbreviations[$word])) {
                $result[] = $this->abbreviations[$word];
            } else {
                $result[] = $word;
            }
        }
        
        return implode(' ', $result);
    }

    /**
     * Convert a string to title case, with exceptions
     */
    private function titleCase($string) {
        // Words that should not be capitalized
        $small_words = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'en', 'for', 'if', 
                       'in', 'of', 'on', 'or', 'the', 'to', 'via', 'vs', 'vs.'];

        // Split into words
        $words = explode(' ', strtolower($string));
        $final = [];

        foreach ($words as $key => $word) {
            // Always capitalize first and last word
            if ($key == 0 || $key == count($words) - 1) {
                $final[] = ucfirst($word);
            }
            // Don't capitalize small words
            elseif (in_array($word, $small_words)) {
                $final[] = $word;
            }
            // Capitalize everything else
            else {
                $final[] = ucfirst($word);
            }
        }

        return implode(' ', $final);
    }

    /**
     * Get all standardized places
     */
    public function getStandardizedPlaces() {
        return $this->standardized_places;
    }

    /**
     * Get all places
     */
    public function getAllPlaces() {
        return $this->places;
    }

    /**
     * Find a place by name
     */
    public function findPlace($placeName) {
        foreach ($this->places as $place) {
            if ($place['original'] === $placeName || in_array($placeName, $place['standardized'])) {
                return $place;
            }
        }
        return null;
    }

    /**
     * Check if a place has coordinates
     */
    public function hasCoordinates($place) {
        return !empty($place['coordinates']) && 
               isset($place['coordinates']['latitude']) && 
               isset($place['coordinates']['longitude']);
    }

    /**
     * Validate place data
     */
    public function validatePlace($place) {
        $errors = [];
        
        // Check if place has basic required data
        if (empty($place['original'])) {
            $errors[] = 'Missing place name';
        }

        // Validate coordinates if present
        if (isset($place['coordinates'])) {
            if (isset($place['coordinates']['latitude'])) {
                $lat = $place['coordinates']['latitude'];
                if ($lat < -90 || $lat > 90) {
                    $errors[] = 'Invalid latitude value';
                }
            }
            if (isset($place['coordinates']['longitude'])) {
                $long = $place['coordinates']['longitude'];
                if ($long < -180 || $long > 180) {
                    $errors[] = 'Invalid longitude value';
                }
            }
        }

        return $errors;
    }
}
