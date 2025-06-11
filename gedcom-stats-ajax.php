<?php
/**
 * AJAX handler for getting GEDCOM statistics
 */

// Add AJAX handler
add_action('wp_ajax_hp_get_gedcom_stats', 'hp_get_gedcom_stats');

function hp_get_gedcom_stats()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hp_gedcom_upload')) {
        wp_send_json_error('Security check failed');
        return;
    }

    $file_key = sanitize_text_field($_POST['file_key'] ?? '');

    if (empty($file_key)) {
        wp_send_json_error('File key required');
        return;
    }

    // Get file path
    $upload_info = wp_upload_dir();
    $heritagepress_dir = $upload_info['basedir'] . '/heritagepress/';
    $gedcom_file = $heritagepress_dir . $file_key . '.ged';

    if (!file_exists($gedcom_file)) {
        wp_send_json_error('GEDCOM file not found');
        return;
    }

    // Analyze file
    $stats = analyze_gedcom_file_simple($gedcom_file);

    wp_send_json_success($stats);
}

function analyze_gedcom_file_simple($filepath)
{
    $analysis = array(
        'gedcom_version' => '5.5.1',
        'encoding' => 'UTF-8',
        'source_system' => 'Unknown',
        'individuals' => 0,
        'families' => 0,
        'sources' => 0,
        'media' => 0,
        'notes' => 0,
        'repositories' => 0,
        'events' => 0,
        'file_size' => filesize($filepath),
        'total_lines' => 0
    );

    $file_handle = fopen($filepath, 'r');
    if (!$file_handle) {
        return $analysis;
    }

    $in_header = false;

    while (($line = fgets($file_handle)) !== false) {
        $analysis['total_lines']++;
        $line = trim($line);

        if (empty($line))
            continue;

        // Parse GEDCOM line
        if (preg_match('/^(\d+)\s+(.+)$/', $line, $matches)) {
            $level = intval($matches[1]);
            $content = $matches[2];

            // Level 0 records
            if ($level === 0) {
                if ($content === 'HEAD') {
                    $in_header = true;
                    continue;
                } elseif ($content === 'TRLR') {
                    break;
                }

                $in_header = false;

                // Count record types
                if (preg_match('/^@\w+@\s+(\w+)/', $content, $record_matches)) {
                    $record_type = $record_matches[1];
                    switch ($record_type) {
                        case 'INDI':
                            $analysis['individuals']++;
                            break;
                        case 'FAM':
                            $analysis['families']++;
                            break;
                        case 'SOUR':
                            $analysis['sources']++;
                            break;
                        case 'OBJE':
                            $analysis['media']++;
                            break;
                        case 'NOTE':
                            $analysis['notes']++;
                            break;
                        case 'REPO':
                            $analysis['repositories']++;
                            break;
                    }
                }
            } else {
                // Header information
                if ($in_header) {
                    if (preg_match('/^VERS\s+(.+)$/', $content, $version_matches)) {
                        $analysis['gedcom_version'] = trim($version_matches[1]);
                    } elseif (preg_match('/^CHAR\s+(.+)$/', $content, $char_matches)) {
                        $analysis['encoding'] = trim($char_matches[1]);
                    } elseif (preg_match('/^SOUR\s+(.+)$/', $content, $sour_matches)) {
                        $analysis['source_system'] = trim($sour_matches[1]);
                    }
                }

                // Count events
                if (in_array($content, array('BIRT', 'DEAT', 'MARR', 'DIV', 'CHR', 'BURI'))) {
                    $analysis['events']++;
                }
            }
        }
    }

    fclose($file_handle);

    $analysis['total_records'] = $analysis['individuals'] + $analysis['families'] +
        $analysis['sources'] + $analysis['media'] +
        $analysis['notes'] + $analysis['repositories'];

    return $analysis;
}
