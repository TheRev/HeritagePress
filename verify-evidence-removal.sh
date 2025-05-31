#!/bin/bash
# Heritage Press Evidence System Removal Verification
#
# This script runs a complete verification of the Evidence System removal,
# checking both the database schema and file system to ensure the refactoring
# was successful.
#
# Usage: ./verify-evidence-removal.sh [wordpress_path]

# Default WordPress path
WP_PATH=""

# Check if WordPress path was provided
if [ -n "$1" ]; then
    WP_PATH="$1"
    if [ ! -f "$WP_PATH/wp-load.php" ]; then
        echo "❌ Error: $WP_PATH does not appear to be a valid WordPress installation"
        exit 1
    fi
else
    # Try to find WordPress
    for path in "../.." "../../.." "../../../.."; do
        if [ -f "$path/wp-load.php" ]; then
            WP_PATH="$path"
            break
        fi
    done
    
    if [ -z "$WP_PATH" ]; then
        echo "❌ Error: Could not locate WordPress installation"
        echo "Please provide the path to your WordPress installation:"
        echo "Usage: ./verify-evidence-removal.sh [wordpress_path]"
        exit 1
    fi
fi

echo "==================================================="
echo "Heritage Press Evidence System Removal Verification"
echo "==================================================="
echo ""
echo "WordPress found at: $WP_PATH"
echo ""

# Step 1: Run database schema verification
echo "Step 1: Verifying database schema..."
php admin/tools/heritage-press-schema-verify.php "$WP_PATH/wp-load.php"

# Step 2: Run unit tests
echo ""
echo "Step 2: Running unit tests for Evidence System removal..."
if [ -f "./vendor/bin/phpunit" ]; then
    ./vendor/bin/phpunit tests/EvidenceRemovalTest.php
else
    echo "❌ PHPUnit not found. Please install dependencies first:"
    echo "composer install"
    exit 1
fi

# Step 3: Check for evidence files
echo ""
echo "Step 3: Checking for Evidence System files..."
EVIDENCE_FILES=$(find . -path "*/evidence/*" -name "*.php" | grep -v "remover\|cleanup")

if [ -n "$EVIDENCE_FILES" ]; then
    echo "❌ Evidence system files were found:"
    echo "$EVIDENCE_FILES"
    echo ""
    echo "Consider running the Evidence File Cleanup utility to remove these files."
else
    echo "✓ No Evidence system files found (correct)"
fi

# Step 4: Check for option flags in WordPress
echo ""
echo "Step 4: Checking WordPress option flags..."
OPTION_CHECK=$(php -r "
    require_once('$WP_PATH/wp-load.php');
    \$option = get_option('heritage_press_evidence_system_removed');
    echo \$option === 'yes' ? 'PASS' : 'FAIL';
")

if [ "$OPTION_CHECK" == "PASS" ]; then
    echo "✓ Evidence system removal flag is correctly set in WordPress options"
else
    echo "❌ Evidence system removal flag is not correctly set in WordPress options"
    echo "Run the following SQL to fix:"
    echo "UPDATE wp_options SET option_value = 'yes' WHERE option_name = 'heritage_press_evidence_system_removed';"
fi

# Step 5: Perform basic functionality test
echo ""
echo "Step 5: Testing basic genealogy functionality..."
FUNCTIONALITY_TEST=$(php -r "
    require_once('$WP_PATH/wp-load.php');
    require_once('includes/repositories/class-individual-repository.php');
    
    try {
        \$repo = new \HeritagePress\Repositories\Individual_Repository();
        \$count = \$repo->count();
        echo \"PASS: Found \$count individuals\";
    } catch (Exception \$e) {
        echo \"FAIL: \" . \$e->getMessage();
    }
")

echo "$FUNCTIONALITY_TEST"

# Final assessment
echo ""
echo "==================================================="
echo "Verification Complete"
echo ""
if [[ "$EVIDENCE_FILES" == "" && "$OPTION_CHECK" == "PASS" && "$FUNCTIONALITY_TEST" == PASS* ]]; then
    echo "✅ All checks passed! The Evidence Explained system has been successfully removed."
    echo "Heritage Press is now running as a standard genealogy plugin."
else
    echo "⚠️ Some checks failed. Please review the output above and take appropriate action."
fi
echo "==================================================="
