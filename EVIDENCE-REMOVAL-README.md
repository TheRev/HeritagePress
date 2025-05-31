# Evidence Explained System Removal

This update refactors the Heritage Press WordPress plugin from an Evidence Explained citation system to a standard genealogy plugin.

## Changes Made

1. **Database Schema Simplification**
   - Removed Evidence Explained specific tables:
     - `research_questions`
     - `information_statements` 
     - `evidence_analysis`
     - `proof_arguments`
     - `proof_evidence_links`
     - `source_quality_assessments`

2. **Citation Model Simplification**
   - Retained core genealogy citation fields
   - Simplified quality assessment to primary/secondary/other
   - Streamlined confidence scoring

3. **Source Model Simplification**
   - Removed complex Evidence Explained assessment logic
   - Simplified to basic source types
   - Maintained core genealogy metadata

4. **Admin Interface Updates**
   - Added Evidence System Removal tool
   - Added File Cleanup utility

## New Admin Tools

### Evidence System Removal Tool
- Located under: Heritage Press → Remove Evidence System
- Safely removes Evidence Explained database tables
- Preserves core genealogy data
- Updates plugin options to reflect changes

### Evidence File Cleanup Utility
- Located under: Heritage Press → Evidence File Cleanup
- Lists all Evidence Explained specific files that can be safely deleted
- Provides status indicators for each file

## Preserved Features

The following core genealogy features have been preserved:
- Individual records
- Family records
- Events
- Places
- Basic sources and citations
- GEDCOM import
- RootsMagic import

## Technical Implementation

1. **Database Changes**
   - Created SQL scripts to drop Evidence Explained specific tables
   - Fixed any potential foreign key constraints

2. **Code Refactoring**
   - Removed Evidence_Admin initialization from Plugin class
   - Created Evidence Table Remover class
   - Added admin tools for removal and cleanup

3. **User Experience**
   - Added confirmation dialog before removal
   - Added file cleanup utility

4. **Automation Tools**
   - Created PowerShell script (`remove-evidence-files.ps1`) for automatic file removal
   - Added CLI tool (`evidence-system-cli-removal.php`) for WP-CLI users
   - Implemented automatic backup of deleted files

## Removal Methods

### Method 1: WordPress Admin Interface
1. Go to Heritage Press → Remove Evidence System
2. Confirm the removal
3. Use the File Cleanup tool to identify files to delete
4. Remove files manually or using provided PowerShell script

### Method 2: PowerShell Script
```powershell
# Run this after using the admin tool to remove database tables
cd /path/to/wordpress/wp-content/plugins/heritage-press
powershell -ExecutionPolicy Bypass -File remove-evidence-files.ps1
```

### Method 3: WP-CLI
```bash
# Run from WordPress root directory
wp eval-file wp-content/plugins/heritage-press/evidence-system-cli-removal.php
```

## Next Steps

After running the Evidence System Removal tool, you can:
1. Use the Evidence File Cleanup tool to identify files that can be safely deleted
2. Update any custom templates or code that may have referenced Evidence Explained components
3. Run the verification scripts to confirm successful removal
4. Continue using the plugin as a standard genealogy tool

## Verification Tools

### Automated Verification Scripts
We've created comprehensive verification scripts to ensure the Evidence Explained system was properly removed:

```powershell
# For Windows users (PowerShell)
.\verify-evidence-removal.ps1 [path-to-wordpress]

# For Windows users (Command Prompt)
verify-evidence-removal.bat [path-to-wordpress]

# For Linux/Mac users
./verify-evidence-removal.sh [path-to-wordpress]
```

These scripts will:
1. Verify database schema integrity
2. Run unit tests to confirm functionality
3. Check for any remaining Evidence Explained files
4. Validate WordPress option settings
5. Test core genealogy functionality

### Unit Tests
The following unit tests have been added to verify removal success:
- `tests/EvidenceRemovalTest.php` - Tests database table removal and data migration
- `tests/EvidenceFileCleanupTest.php` - Tests file identification and cleanup

Run the tests using PHPUnit:
```bash
./vendor/bin/phpunit tests/EvidenceRemovalTest.php
```
