<?php
namespace HeritagePress\GEDCOM;

class GedcomRecoveryHandler {
    private $corrections = [];
    private $warnings = [];
    private $errors = [];

    private $months = [
        'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
        'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'
    ];

    public function handleError($message, $context = []) {
        $this->errors[] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => time()
        ];

        if (isset($context['type'])) {
            switch ($context['type']) {
                case 'date':
                    return $this->recoverDate($context['value']);
                case 'name':
                    return $this->recoverName($context['value']);
                case 'place':
                    return $this->recoverPlace($context['value']);
                case 'media':
                    return $this->recoverMedia($context['value']);
            }
        }

        return null;
    }

    public function handleWarning($message, $context = []) {
        $this->warnings[] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => time()
        ];
    }

    private function recoverDate($date) {
        $original = $date;
        $date = trim($date);

        // Try to fix common date format issues
        $recovered = preg_replace('/\s+/', ' ', $date); // Fix multiple spaces
        
        // Convert month names to uppercase
        $monthPattern = implode('|', array_map(function($month) {
            return strtolower($month);
        }, $this->months));
        $recovered = preg_replace_callback("/\b($monthPattern)\b/i", function($match) {
            return strtoupper($match[0]);
        }, $recovered);

        // Fix common date separators
        $recovered = str_replace(['/', '-'], ' ', $recovered);

        // Fix year formats
        if (preg_match('/^\d{2}$/', $recovered)) {
            // Two-digit year
            $year = intval($recovered);
            $year = $year > 50 ? "19$recovered" : "20$recovered";
            $recovered = $year;
        }

        // Handle common date keywords
        $keywords = [
            'ABT' => ['ABOUT', 'CIRCA', 'CA', 'C.'],
            'BEF' => ['BEFORE', 'PRIOR TO'],
            'AFT' => ['AFTER', 'PAST'],
            'BET' => ['BETWEEN'],
            'AND' => ['TO', 'UNTIL', '-']
        ];

        foreach ($keywords as $standard => $variants) {
            foreach ($variants as $variant) {
                $recovered = str_ireplace($variant, $standard, $recovered);
            }
        }

        if ($recovered !== $original) {
            $this->addCorrection('date', $original, $recovered);
            return $recovered;
        }

        return null;
    }

    private function recoverName($name) {
        $original = $name;
        $name = trim($name);

        // Fix missing surname slashes
        if (!preg_match('/\/.*\//', $name)) {
            $parts = explode(' ', $name);
            if (count($parts) > 1) {
                $surname = array_pop($parts);
                $given = implode(' ', $parts);
                $name = "$given /$surname/";
            }
        }

        // Fix double slashes
        $name = preg_replace('/\/+/', '/', $name);

        // Fix missing trailing slash
        if (substr_count($name, '/') === 1) {
            $name .= '/';
        }

        if ($name !== $original) {
            $this->addCorrection('name', $original, $name);
            return $name;
        }

        return null;
    }

    private function recoverPlace($place) {
        $original = $place;
        $place = trim($place);

        // Remove multiple spaces
        $place = preg_replace('/\s+/', ' ', $place);

        // Fix common separators
        $place = str_replace([';', '|', '>', '-'], ',', $place);

        // Remove trailing punctuation
        $place = rtrim($place, '.,');

        if ($place !== $original) {
            $this->addCorrection('place', $original, $place);
            return $place;
        }

        return null;
    }

    private function recoverMedia($media) {
        $original = $media;
        $media = trim($media);

        // Fix file path separators
        $media = str_replace('\\', '/', $media);

        // Remove file:// prefix if present
        $media = preg_replace('#^file://#i', '', $media);

        if ($media !== $original) {
            $this->addCorrection('media', $original, $media);
            return $media;
        }

        return null;
    }

    private function addCorrection($type, $original, $corrected) {
        $this->corrections[] = [
            'type' => $type,
            'original' => $original,
            'corrected' => $corrected,
            'timestamp' => time()
        ];
    }

    public function getCorrections() {
        return $this->corrections;
    }

    public function getWarnings() {
        return $this->warnings;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function clearCorrections() {
        $this->corrections = [];
    }

    public function clearWarnings() {
        $this->warnings = [];
    }

    public function clearErrors() {
        $this->errors = [];
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    public function hasWarnings() {
        return !empty($this->warnings);
    }

    public function hasCorrections() {
        return !empty($this->corrections);
    }
}
