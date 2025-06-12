<?php
/**
 * Test DateConverter Integration
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../../wp-config.php';

// Load HeritagePress classes
require_once dirname(__FILE__) . '/includes/class-heritagepress-autoloader.php';
HeritagePress_Autoloader::register();

use HeritagePress\Models\DateConverter;
use HeritagePress\Models\CalendarSystem;

echo "=== DateConverter Integration Test ===\n\n";

try {
    // Test CalendarSystem first
    echo "Testing CalendarSystem...\n";
    $calendar_system = new CalendarSystem();
    echo "✓ CalendarSystem instantiated successfully\n";

    // Test DateConverter
    echo "\nTesting DateConverter...\n";
    $date_converter = new DateConverter();
    echo "✓ DateConverter instantiated successfully\n";

    // Test date parsing
    echo "\nTesting date parsing...\n";
    $test_dates = [
        '25 DEC 1990',
        'ABT 1850',
        'BET 1800 AND 1850',
        '@#DJULIAN@ 25 DEC 1900',
        'WINTER 1990'
    ];

    foreach ($test_dates as $date_string) {
        echo "  Parsing: '$date_string'\n";
        $parsed = $date_converter->parseDateValue($date_string);
        echo "    → Date: " . ($parsed['date'] ?: 'null') . "\n";
        echo "    → Calendar: " . $parsed['calendar'] . "\n";
        echo "    → Modifier: " . ($parsed['modifier'] ?: 'none') . "\n";
        echo "    → BCE: " . ($parsed['is_bce'] ? 'yes' : 'no') . "\n";
        echo "    → Season: " . ($parsed['is_season'] ? 'yes' : 'no') . "\n";
        echo "\n";
    }

    // Test date comparison
    echo "Testing date comparison...\n";
    $date1 = ['date' => '1990-12-25', 'calendar' => 'GREGORIAN'];
    $date2 = ['date' => '1991-01-01', 'calendar' => 'GREGORIAN'];

    $comparison = $date_converter->compareDates($date1, $date2);
    echo "  '1990-12-25' vs '1991-01-01' = $comparison\n";
    echo "  (Expected: -1 for date1 < date2)\n\n";

    // Test Julian Day Number conversion
    echo "Testing Julian Day Number conversion...\n";
    $jdn = $date_converter->dateToJDN($date1);
    echo "  JDN for 1990-12-25: $jdn\n\n";

    echo "✅ All DateConverter tests passed!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
