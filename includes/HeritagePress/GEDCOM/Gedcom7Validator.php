<?php
namespace HeritagePress\GEDCOM;

class Gedcom7Validator {
    private $errors = [];
    private $warnings = [];
    private $allowed_tags = [
        'HEAD', 'GEDC', 'CHAR', 'SOUR', 'SUBM', 'INDI', 'FAM', 'REPO', 'NOTE', 'OBJE', 'PLAC'
    ];
    private $allowed_sex_values = ['M', 'F', 'U', 'N', 'UNKNOWN'];
    private $date_formats = [
        // Exact dates
        '/^\d{1,2}\s+[A-Z]{3}\s+\d{4}$/', // e.g., 1 JAN 2000
        '/^[A-Z]{3}\s+\d{4}$/', // e.g., JAN 2000
        '/^\d{4}$/', // e.g., 2000
        // Date ranges
        '/^BET\s+\d{1,2}\s+[A-Z]{3}\s+\d{4}\s+AND\s+\d{1,2}\s+[A-Z]{3}\s+\d{4}$/', // BET date AND date
        '/^FROM\s+\d{4}\s+TO\s+\d{4}$/', // FROM year TO year
        '/^ABT\s+\d{4}$/', // ABT year
        '/^BEF\s+\d{4}$/', // BEF year
        '/^AFT\s+\d{4}$/' // AFT year
    ];

    /**
     * Validate GEDCOM 7.0 data structure
     *
     * @param array $data The parsed GEDCOM data
     * @return bool True if valid, false if there are errors
     */
    public function validate($data) {
        $this->errors = [];
        $this->warnings = [];

        // Validate header
        if (!empty($data['header'])) {
            $this->validateHeader($data['header']);
        } else {
            $this->addError('Missing required HEAD record');
        }

        // Validate individuals
        if (!empty($data['individuals'])) {
            foreach ($data['individuals'] as $individual) {
                $this->validateIndividual($individual);
            }
        }

        // Validate families
        if (!empty($data['families'])) {
            foreach ($data['families'] as $family) {
                $this->validateFamily($family);
            }
        }

        // Validate sources
        if (!empty($data['sources'])) {
            foreach ($data['sources'] as $source) {
                $this->validateSource($source);
            }
        }

        // Validate places
        if (!empty($data['places'])) {
            foreach ($data['places'] as $place) {
                $this->validatePlace($place);
            }
        }

        // Validate media objects
        if (!empty($data['media'])) {
            foreach ($data['media'] as $media) {
                $this->validateMedia($media);
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate header record
     */
    private function validateHeader($header) {
        $required_tags = ['GEDC', 'CHAR'];
        foreach ($required_tags as $tag) {
            if (!$this->hasTag($header, $tag)) {
                $this->addError("Missing required header tag: {$tag}");
            }
        }

        // Validate GEDCOM version
        if ($this->hasTag($header, 'GEDC')) {
            $version = $this->getTagValue($header, 'VERS');
            if (empty($version)) {
                $this->addError('Missing GEDCOM version');
            } else if (!in_array($version, ['5.5.1', '7.0'])) {
                $this->addError("Unsupported GEDCOM version: {$version}");
            }
        }
    }

    /**
     * Validate individual record
     */
    private function validateIndividual($individual) {
        if (empty($individual['id'])) {
            $this->addError('Individual record missing required ID');
            return;
        }

        $hasName = false;
        $hasSex = false;
        $hasMultipleBirths = false;
        $hasMultipleDeaths = false;

        foreach ($individual['data'] as $item) {
            if ($item['tag'] === 'NAME') {
                $hasName = true;
                $this->validateName($item);
            } elseif ($item['tag'] === 'SEX') {
                $hasSex = true;
                if (!in_array($item['value'], $this->allowed_sex_values)) {
                    $this->addWarning("Invalid sex value for individual {$individual['id']}: {$item['value']}");
                }
            } elseif ($item['tag'] === 'BIRT') {
                if ($hasMultipleBirths) {
                    $this->addError("Multiple BIRT records for individual {$individual['id']}");
                }
                $hasMultipleBirths = true;
                $this->validateEvent($item);
            } elseif ($item['tag'] === 'DEAT') {
                if ($hasMultipleDeaths) {
                    $this->addError("Multiple DEAT records for individual {$individual['id']}");
                }
                $hasMultipleDeaths = true;
                $this->validateEvent($item);
            } elseif ($item['tag'] === 'PLAC') {
                $this->validatePlace($item);
            }

            // Check dates
            if ($this->isDateTag($item['tag'])) {
                $this->validateDate($item);
            }
        }

        if (!$hasName) {
            $this->addWarning("Individual {$individual['id']} has no NAME record");
        }
    }

    /**
     * Validate family record
     */
    private function validateFamily($family) {
        if (empty($family['id'])) {
            $this->addError('Family record missing required ID');
            return;
        }

        $hasSpouse = false;
        $hasMultipleMarr = false;

        foreach ($family['data'] as $item) {
            if (in_array($item['tag'], ['HUSB', 'WIFE'])) {
                $hasSpouse = true;
                if (empty($item['value'])) {
                    $this->addError("Empty spouse reference in family {$family['id']}");
                }
            } elseif ($item['tag'] === 'MARR') {
                if ($hasMultipleMarr) {
                    $this->addWarning("Multiple MARR records in family {$family['id']}");
                }
                $hasMultipleMarr = true;
                $this->validateEvent($item);
            } elseif ($item['tag'] === 'PLAC') {
                $this->validatePlace($item);
            }

            // Check dates
            if ($this->isDateTag($item['tag'])) {
                $this->validateDate($item);
            }
        }

        if (!$hasSpouse) {
            $this->addWarning("Family {$family['id']} has no spouse records");
        }
    }

    /**
     * Validate event record (BIRT, DEAT, MARR, etc.)
     */
    private function validateEvent($event) {
        if (empty($event['children'])) {
            return;
        }

        $hasDate = false;
        $hasPlace = false;

        foreach ($event['children'] as $child) {
            if ($child['tag'] === 'DATE') {
                $hasDate = true;
                $this->validateDate($child);
            } elseif ($child['tag'] === 'PLAC') {
                $hasPlace = true;
                $this->validatePlace($child);
            }
        }

        if (!$hasDate && !$hasPlace) {
            $this->addWarning("Event {$event['tag']} has neither DATE nor PLAC");
        }
    }

    /**
     * Validate date structure
     */
    public function validateDate($date) {
        // Ensure $date is an array and has a 'value' key before accessing it.
        if (!is_array($date) || !isset($date['value']) || empty($date['value'])) {
            return true; // Consider empty or malformed date as valid for this check, or decide policy
        }

        $valid = false;
        foreach ($this->date_formats as $format) {
            if (preg_match($format, $date['value'])) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            // Use a more specific check for the tag, if available, for better error reporting.
            $tagName = isset($date['tag']) ? $date['tag'] : 'DATE';
            $this->addWarning("Invalid date format for {$tagName}: {$date['value']}");
            return false; // Return false if invalid
        }
        return true; // Return true if valid
    }

    /**
     * Validate name structure
     */
    public function validateName($name) {
        // Ensure $name is an array and has a 'value' key.
        if (!is_array($name) || !isset($name['value'])) {
            $this->addWarning('Malformed NAME structure or missing value.');
            return false;
        }
        
        if (empty($name['value'])) {
            $this->addWarning('Empty name value');
            return false; // Return false if invalid
        }

        // Check for proper name format (given name /surname/)
        if (!preg_match('/^[^\/]*\/[^\/]*\/.*$/', $name['value']) && 
            !preg_match('/^[^\/]*\/[^\/]*$/', $name['value'])) {
            $this->addWarning("Invalid name format: {$name['value']}");
            return false; // Return false if invalid
        }

        if (isset($name['children']) && is_array($name['children'])) {
            foreach ($name['children'] as $part) {
                // Ensure $part is an array and has 'tag' and 'value' keys before accessing them.
                if (is_array($part) && isset($part['tag'])) {
                    if ($part['tag'] === 'GIVN' && (!isset($part['value']) || empty($part['value']))) {
                        $this->addWarning('Empty given name (GIVN tag present but no value).');
                    } elseif ($part['tag'] === 'SURN' && (!isset($part['value']) || empty($part['value']))) {
                        $this->addWarning('Empty surname (SURN tag present but no value).');
                    }
                } else {
                    $this->addWarning("Malformed part in NAME structure for value: {$name['value']}");
                }
            }
        }
        return true; // Return true if valid
    }

    /**
     * Validate place structure
     */
    private function validatePlace($place) {
        if (empty($place['value'])) {
            $this->addWarning('Empty place value');
            return;
        }

        if (isset($place['children'])) {
            foreach ($place['children'] as $child) {
                if ($child['tag'] === 'LATI') {
                    $this->validateLatitude($child['value']);
                } elseif ($child['tag'] === 'LONG') {
                    $this->validateLongitude($child['value']);
                }
            }
        }
    }

    /**
     * Validate latitude
     */
    private function validateLatitude($value) {
        if (!preg_match('/^[NS]?\d{1,2}(?:\.\d+)?$/', $value)) {
            $this->addWarning("Invalid latitude format: {$value}");
        }
    }

    /**
     * Validate longitude
     */
    private function validateLongitude($value) {
        if (!preg_match('/^[EW]?\d{1,3}(?:\.\d+)?$/', $value)) {
            $this->addWarning("Invalid longitude format: {$value}");
        }
    }

    /**
     * Validate media object record
     */
    private function validateMedia($media) {
        if (empty($media['id'])) {
            $this->addError('Media record missing required ID');
            return;
        }

        $hasFile = false;
        // Check if $media['data'] exists and is an array before iterating
        if (isset($media['data']) && is_array($media['data'])) {
            foreach ($media['data'] as $item) {
                if ($item['tag'] === 'FILE') {
                    $hasFile = true;
                    if (empty($item['value'])) {
                        $this->addError("Empty file path in media {$media['id']}");
                    }
                    // Ensure $item['children'] exists before calling hasTag, or adjust hasTag to handle null
                    if (!isset($item['children']) || !$this->hasTag($item, 'FORM')) { 
                        $this->addWarning("Media file {$media['id']} missing FORM tag or children structure incorrect");
                    }
                }
            }
        } else {
            // If $media['data'] is not set or not an array, it might indicate a structural issue with the media record itself.
            $this->addWarning("Media record {$media['id']} has no 'data' array or it's malformed.");
        }

        if (!$hasFile) {
            $this->addError("Media {$media['id']} has no FILE record");
        }
    }

    /**
     * Validate source record
     */
    private function validateSource($source) {
        if (empty($source['id'])) {
            $this->addError('Source record missing required ID');
            return;
        }

        $hasTitleOrData = false;
        foreach ($source['data'] as $item) {
            if (in_array($item['tag'], ['TITL', 'DATA'])) {
                $hasTitleOrData = true;
            }
        }

        if (!$hasTitleOrData) {
            $this->addWarning("Source {$source['id']} has no title or data");
        }
    }

    /**
     * Check if a tag is a date field
     */
    private function isDateTag($tag) {
        return in_array($tag, ['DATE', 'BIRT', 'DEAT', 'MARR', 'DIV']);
    }

    /**
     * Check if a record has a specific tag
     */
    private function hasTag($record, $tag) {
        // Check if $record['data'] exists and is an array before iterating
        if (isset($record['data']) && is_array($record['data'])) {
            foreach ($record['data'] as $item) {
                if ($item['tag'] === $tag) {
                    return true;
                }
            }
        }
        // Also check if $record['children'] exists for nested structures, if applicable to hasTag's usage
        if (isset($record['children']) && is_array($record['children'])) {
            foreach ($record['children'] as $child_item) {
                if ($child_item['tag'] === $tag) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the value of a specific tag from a record
     */
    private function getTagValue($record, $tag) {
        // Check if $record['data'] exists and is an array before iterating
        if (isset($record['data']) && is_array($record['data'])) {
            foreach ($record['data'] as $item) {
                if ($item['tag'] === $tag) {
                    return $item['value'];
                }
            }
        }
        // Also check if $record['children'] exists for nested structures, if applicable to getTagValue's usage
        if (isset($record['children']) && is_array($record['children'])) {
            foreach ($record['children'] as $child_item) {
                if ($child_item['tag'] === $tag) {
                    return $child_item['value'];
                }
            }
        }
        return null;
    }

    /**
     * Add an error message
     */
    public function addError($message) {
        $this->errors[] = $message;
    }

    /**
     * Add a warning message
     */
    public function addWarning($message) {
        $this->warnings[] = $message;
    }

    /**
     * Get all validation errors
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get all validation warnings
     */
    public function getWarnings() {
        return $this->warnings;
    }
}
