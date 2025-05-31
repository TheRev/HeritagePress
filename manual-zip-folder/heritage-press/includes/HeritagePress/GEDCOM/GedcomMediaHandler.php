<?php
namespace HeritagePress\GEDCOM;

class GedcomMediaHandler {    private $base_path;
    private $media_files = [];
    private $wp_upload_dir;
    private $supported_types = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff'],
        'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
        'audio' => ['mp3', 'wav', 'ogg', 'wma', 'm4a'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv']
    ];

    public function __construct($base_path) {
        $this->base_path = $base_path;
        $this->wp_upload_dir = wp_upload_dir();
    }

    /**
     * Get WordPress upload settings
     */
    private function get_upload_settings() {
        return [            'use_wp_media' => get_option('heritage_press_use_wp_media', true),
            'media_privacy' => get_option('heritage_press_media_privacy', 'public'),
            'optimize_images' => get_option('heritage_press_optimize_images', true),
            'max_upload_size' => wp_max_upload_size(),
            'allowed_mime_types' => get_allowed_mime_types()
        ];
    }

    /**
     * Handle media object from GEDCOM
     */
    public function handleMedia($record) {
        if (empty($record['id'])) {
            return null;
        }

        $mediaData = [
            'id' => $record['id'],
            'files' => [],
            'title' => '',
            'type' => '',
            'format' => '',
            'notes' => []
        ];

        foreach ($record['data'] as $item) {
            switch ($item['tag']) {
                case 'FILE':
                    $file = $this->handleFile($item);
                    if ($file) {
                        $mediaData['files'][] = $file;
                    }
                    break;
                case 'TITL':
                    $mediaData['title'] = $item['value'];
                    break;
                case 'TYPE':
                    $mediaData['type'] = $item['value'];
                    break;
                case 'FORM':
                    $mediaData['format'] = $item['value'];
                    break;
                case 'NOTE':
                    $mediaData['notes'][] = $item['value'];
                    break;
            }
        }

        return $mediaData;
    }

    private function handleFile($fileRecord) {
        if (empty($fileRecord['value'])) {
            return null;
        }

        $file = [
            'path' => $this->normalizePath($fileRecord['value']),
            'format' => '',
            'type' => '',
            'title' => '',
            'translations' => []
        ];

        // Handle file format
        if (isset($fileRecord['children'])) {
            foreach ($fileRecord['children'] as $child) {
                switch ($child['tag']) {
                    case 'FORM':
                        $file['format'] = $child['value'];
                        $file['type'] = $this->detectMediaType($child['value']);
                        break;
                    case 'TITL':
                        $file['title'] = $child['value'];
                        break;
                    case 'TRAN':
                        $translation = $this->handleTranslation($child);
                        if ($translation) {
                            $file['translations'][] = $translation;
                        }
                        break;
                }
            }
        }

        // If format not specified, try to detect from file extension
        if (empty($file['format'])) {
            $extension = strtolower(pathinfo($file['path'], PATHINFO_EXTENSION));
            $file['format'] = $extension;
            $file['type'] = $this->detectMediaType($extension);
        }

        return $file;
    }

    private function handleTranslation($translationRecord) {
        if (empty($translationRecord['value'])) {
            return null;
        }

        $translation = [
            'path' => $this->normalizePath($translationRecord['value']),
            'format' => '',
            'language' => ''
        ];

        if (isset($translationRecord['children'])) {
            foreach ($translationRecord['children'] as $child) {
                switch ($child['tag']) {
                    case 'FORM':
                        $translation['format'] = $child['value'];
                        break;
                    case 'LANG':
                        $translation['language'] = $child['value'];
                        break;
                }
            }
        }

        return $translation;
    }

    private function normalizePath($path) {
        // Handle file:// URIs
        if (strpos($path, 'file://') === 0) {
            $path = substr($path, 7);
        }

        // Convert Windows paths
        $path = str_replace('\\', '/', $path);

        // Make relative to base path if not absolute
        if (!$this->isAbsolutePath($path)) {
            $path = $this->base_path . '/' . $path;
        }

        return $path;
    }

    private function isAbsolutePath($path) {
        return strpos($path, '/') === 0 || preg_match('/^[A-Z]:\//i', $path);
    }

    private function detectMediaType($format) {
        $format = strtolower($format);
        foreach ($this->supported_types as $type => $formats) {
            if (in_array($format, $formats)) {
                return $type;
            }
        }
        return 'other';
    }

    /**
     * Extract media files from a GDZ (GEDCOM ZIP) archive
     */
    public function extractMediaFromGdz($gdzPath, $extractPath) {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('ZIP support is required for GEDCOM 7.0 files');
        }

        $zip = new \ZipArchive();
        if ($zip->open($gdzPath) !== true) {
            throw new \Exception('Failed to open GDZ file');
        }

        // Create extraction directory if it doesn't exist
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0777, true);
        }

        // Extract media files
        $mediaCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Check if file is a supported media type
            $isMedia = false;
            foreach ($this->supported_types as $formats) {
                if (in_array($extension, $formats)) {
                    $isMedia = true;
                    break;
                }
            }

            if ($isMedia) {
                $zip->extractTo($extractPath, $filename);
                $this->media_files[] = $extractPath . '/' . $filename;
                $mediaCount++;
            }
        }

        $zip->close();
        return $mediaCount;
    }

    public function getMediaFiles() {
        return $this->media_files;
    }

    public function validateMediaFile($path) {
        // Check if file exists
        if (!file_exists($path)) {
            return ['valid' => false, 'error' => 'File not found'];
        }

        // Check if file is readable
        if (!is_readable($path)) {
            return ['valid' => false, 'error' => 'File not readable'];
        }

        // Get file extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Check if file type is supported
        $supported = false;
        foreach ($this->supported_types as $formats) {
            if (in_array($extension, $formats)) {
                $supported = true;
                break;
            }
        }

        if (!$supported) {
            return ['valid' => false, 'error' => 'Unsupported file type'];
        }

        // Basic file integrity check
        $filesize = filesize($path);
        if ($filesize === 0) {
            return ['valid' => false, 'error' => 'Empty file'];
        }

        return ['valid' => true];
    }

    /**
     * Handle geocoding data from FTM 2024
     */
    private function handleGeocoding($mapData) {
        $geocoding = [
            'latitude' => null,
            'longitude' => null
        ];

        if (isset($mapData['data'])) {
            foreach ($mapData['data'] as $coord) {
                switch ($coord['tag']) {
                    case 'LATI':
                        $geocoding['latitude'] = $this->parseCoordinate($coord['value']);
                        break;
                    case 'LONG':
                        $geocoding['longitude'] = $this->parseCoordinate($coord['value']);
                        break;
                }
            }
        }

        return $geocoding;
    }

    /**
     * Parse coordinate value (handles both FTM and standard GEDCOM formats)
     */
    private function parseCoordinate($value) {
        // Remove N/S/E/W indicators and convert to signed float
        $value = trim($value);
        $negative = false;
        
        if (strpos($value, 'S') !== false || strpos($value, 'W') !== false) {
            $negative = true;
        }
        
        $value = preg_replace('/[NSEW]/', '', $value);
        $coord = floatval($value);
        
        return $negative ? -$coord : $coord;
    }

    /**
     * Handle FTM 2024 photo cropping data
     */
    private function handleCropping($cropData) {
        $crop = [
            'x' => 0,
            'y' => 0,
            'width' => 0,
            'height' => 0
        ];

        if (isset($cropData['data'])) {
            foreach ($cropData['data'] as $param) {
                switch ($param['tag']) {
                    case '_X':
                        $crop['x'] = intval($param['value']);
                        break;
                    case '_Y':
                        $crop['y'] = intval($param['value']);
                        break;
                    case '_W':
                        $crop['width'] = intval($param['value']);
                        break;
                    case '_H':
                        $crop['height'] = intval($param['value']);
                        break;
                }
            }
        }

        return $crop;
    }

    /**
     * Import media file into WordPress
     */
    public function importToWordPress($file_path, $title = '', $description = '') {
        $settings = $this->get_upload_settings();
        
        if (!$settings['use_wp_media']) {
            return false;
        }

        // Validate file
        $validation = $this->validateMediaFile($file_path);
        if (!$validation['valid']) {
            return false;
        }

        // Check file size
        if (filesize($file_path) > $settings['max_upload_size']) {
            return false;
        }

        // Prepare file for upload
        $file = [
            'name' => basename($file_path),
            'type' => mime_content_type($file_path),
            'tmp_name' => $file_path,
            'error' => 0,
            'size' => filesize($file_path)
        ];

        // Upload the file to WordPress
        $upload = wp_handle_sideload($file, ['test_form' => false]);

        if (isset($upload['error'])) {
            return false;
        }

        // Prepare attachment metadata
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title' => !empty($title) ? $title : preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
            'post_content' => $description,
            'post_status' => $settings['media_privacy'] === 'private' ? 'private' : 'inherit'
        ];

        // Insert attachment into WordPress media library
        $attach_id = wp_insert_attachment($attachment, $upload['file']);

        if (is_wp_error($attach_id)) {
            return false;
        }

        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Optimize image if enabled
        if ($settings['optimize_images'] && strpos($upload['type'], 'image/') === 0) {
            $this->optimizeImage($upload['file']);
        }

        return $attach_id;
    }

    /**
     * Optimize uploaded image
     */
    private function optimizeImage($file_path) {
        if (!function_exists('wp_get_image_editor')) {
            return false;
        }

        $editor = wp_get_image_editor($file_path);
        
        if (is_wp_error($editor)) {
            return false;
        }

        // Set quality
        $editor->set_quality(85);

        // Save optimized image
        $saved = $editor->save($file_path);

        return !is_wp_error($saved);
    }
}
