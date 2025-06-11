<?php
/**
 * Simple test to show real GEDCOM statistics
 */

// Get file key from URL 
$file_key = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';

if (!empty($file_key)) {
    // Path to uploaded file
    $upload_info = wp_upload_dir();
    $heritagepress_dir = $upload_info['basedir'] . '/heritagepress/';
    $gedcom_file = $heritagepress_dir . $file_key . '.ged';

    if (file_exists($gedcom_file)) {
        echo "<h2>Real GEDCOM Analysis for file: $file_key</h2>";

        // Simple analysis function
        function analyze_gedcom_simple($filepath)
        {
            $analysis = array(
                'individuals' => 0,
                'families' => 0,
                'sources' => 0,
                'media' => 0,
                'notes' => 0,
                'repositories' => 0
            );

            $file_handle = fopen($filepath, 'r');
            if (!$file_handle) {
                return $analysis;
            }

            while (($line = fgets($file_handle)) !== false) {
                $line = trim($line);
                if (preg_match('/^0\s+@\w+@\s+(\w+)/', $line, $matches)) {
                    $record_type = $matches[1];
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
            }

            fclose($file_handle);
            return $analysis;
        }

        $stats = analyze_gedcom_simple($gedcom_file);

        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr><th style='padding: 8px; background: #f0f0f0;'>Record Type</th><th style='padding: 8px; background: #f0f0f0;'>Count</th></tr>";
        echo "<tr><td style='padding: 8px;'>Individuals</td><td style='padding: 8px; text-align: center;'>" . $stats['individuals'] . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>Families</td><td style='padding: 8px; text-align: center;'>" . $stats['families'] . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>Sources</td><td style='padding: 8px; text-align: center;'>" . $stats['sources'] . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>Media Objects</td><td style='padding: 8px; text-align: center;'>" . $stats['media'] . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>Notes</td><td style='padding: 8px; text-align: center;'>" . $stats['notes'] . "</td></tr>";
        echo "<tr><td style='padding: 8px;'>Repositories</td><td style='padding: 8px; text-align: center;'>" . $stats['repositories'] . "</td></tr>";
        echo "</table>";

        echo "<p><strong>File size:</strong> " . number_format(filesize($gedcom_file)) . " bytes</p>";
        echo "<p><strong>Last modified:</strong> " . date('Y-m-d H:i:s', filemtime($gedcom_file)) . "</p>";

    } else {
        echo "<p style='color: red;'>GEDCOM file not found: $gedcom_file</p>";
    }
} else {
    echo "<p style='color: red;'>No file key provided in URL</p>";
}
?>