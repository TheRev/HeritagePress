<?php
namespace HeritagePress\Services;

/**
 * Legacy Data Adapter
 * Handles conversion between legacy genealogy data structures and HeritagePress format
 */
class LegacyDataAdapter
{
    /**
     * Convert legacy individual to HeritagePress format
     *
     * @param array $legacy_individual Legacy individual record
     * @return array HeritagePress individual data
     */
    public function convertIndividual($legacy_individual)
    {
        return [
            'uuid' => wp_generate_uuid4(),
            'external_id' => $legacy_individual['person_id'] ?? null,
            'given_name' => $legacy_individual['first_name'] ?? '',
            'surname' => $legacy_individual['last_name'] ?? '',
            'birth_date' => $this->convertDate($legacy_individual['birth_date'] ?? ''),
            'birth_place' => $legacy_individual['birth_place'] ?? '',
            'death_date' => $this->convertDate($legacy_individual['death_date'] ?? ''),
            'death_place' => $legacy_individual['death_place'] ?? '',
            'gender' => $this->convertGender($legacy_individual['gender'] ?? ''),
            'living' => ($legacy_individual['is_living'] ?? '0') === '1',
            'private' => ($legacy_individual['is_private'] ?? '0') === '1',
        ];
    }

    /**
     * Convert legacy family to HeritagePress format
     *
     * @param array $legacy_family Legacy family record
     * @return array HeritagePress family data
     */
    public function convertFamily($legacy_family)
    {
        return [
            'uuid' => wp_generate_uuid4(),
            'external_id' => $legacy_family['family_id'] ?? null,
            'spouse1_id' => $legacy_family['spouse1'] ?? null,
            'spouse2_id' => $legacy_family['spouse2'] ?? null,
            'marriage_date' => $this->convertDate($legacy_family['marriage_date'] ?? ''),
            'marriage_place' => $legacy_family['marriage_place'] ?? '',
            'divorce_date' => $this->convertDate($legacy_family['divorce_date'] ?? ''),
            'divorce_place' => $legacy_family['divorce_place'] ?? '',
            'private' => ($legacy_family['is_private'] ?? '0') === '1',
        ];
    }

    /**
     * Convert legacy date format to GEDCOM format
     *
     * @param string $legacy_date Legacy date
     * @return string GEDCOM date
     */
    private function convertDate($legacy_date)
    {
        if (empty($legacy_date)) {
            return '';
        }

        // Legacy format is YYYY-MM-DD, convert to GEDCOM format
        $date_parts = explode('-', $legacy_date);
        if (count($date_parts) === 3) {
            return "{$date_parts[2]} " .
                $this->getMonthName((int) $date_parts[1]) . " " .
                $date_parts[0];
        }

        return $legacy_date;
    }

    /**
     * Get month name from number
     *
     * @param int $month_number Month number (1-12)
     * @return string Month name in GEDCOM format
     */
    private function getMonthName($month_number)
    {
        $months = [
            1 => 'JAN',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'APR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AUG',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DEC'
        ];

        return $months[$month_number] ?? '';
    }

    /**
     * Convert legacy gender to HeritagePress format
     *
     * @param string $legacy_gender Legacy gender
     * @return string HeritagePress gender (MALE/FEMALE/UNKNOWN)
     */
    private function convertGender($legacy_gender)
    {
        $map = [
            'M' => 'MALE',
            'F' => 'FEMALE',
            'U' => 'UNKNOWN'
        ];

        return $map[strtoupper($legacy_gender)] ?? 'UNKNOWN';
    }

    /**
     * Convert legacy media to HeritagePress format
     *
     * @param array $legacy_media Legacy media record
     * @return array HeritagePress media data
     */
    public function convertMedia($legacy_media)
    {
        return [
            'uuid' => wp_generate_uuid4(),
            'external_id' => $legacy_media['media_id'] ?? null,
            'title' => $legacy_media['title'] ?? '',
            'file_path' => $legacy_media['path'] ?? '',
            'mime_type' => $this->getMimeType($legacy_media['path'] ?? ''),
            'type' => $this->convertMediaType($legacy_media['type'] ?? ''),
            'date' => $this->convertDate($legacy_media['date'] ?? ''),
            'private' => ($legacy_media['is_private'] ?? '0') === '1',
        ];
    }

    /**
     * Get MIME type from file path
     *
     * @param string $path File path
     * @return string MIME type
     */
    private function getMimeType($path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        return $types[$ext] ?? 'application/octet-stream';
    }

    /**
     * Convert legacy media type to HeritagePress format
     *
     * @param string $legacy_type Legacy media type
     * @return string HeritagePress media type
     */
    private function convertMediaType($legacy_type)
    {
        $map = [
            'PHOTO' => 'photo',
            'DOC' => 'document',
            'GRAVE' => 'headstone',
            'HIST' => 'history',
            'CERT' => 'certificate',
        ];

        return $map[strtoupper($legacy_type)] ?? 'other';
    }
}
