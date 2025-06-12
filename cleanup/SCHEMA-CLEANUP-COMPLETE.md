# HeritagePress Schema Cleanup Complete

## Schema Files Removed

The following redundant and conflicting schema files have been removed:

### Redundant Files (Duplicate Content):
1. `complete-advanced-schema.sql` - Tables already in complete-genealogy-schema.sql
2. `complete-documentation-schema.sql` - Conflicting table definitions
3. `complete-schema.sql` - Redundant complete schema
4. `core-tables-full.sql` - Core tables already in main schema
5. `core-tables.sql` - Core tables already in main schema
6. `documentation-tables-full.sql` - Conflicting definitions
7. `documentation-tables-new.sql` - Conflicting definitions
8. `documentation-tables.sql` - **CRITICAL REMOVAL** - Was causing duplicate table conflicts
9. `remaining-genealogy-tables.sql` - Tables already in complete schema
10. `gedcom7-tables-fixed.sql` - GEDCOM7 extensions already in main schema
11. `gedcom7-tables.sql` - GEDCOM7 extensions already in main schema

### TNG-Referenced Files (Already Cleaned):
12. `complete-tng-schema.sql` - Renamed to complete-genealogy-schema.sql
13. `remaining-tng-tables.sql` - Redundant content

### Non-Core Tables Removed:
14. `compliance-tables.sql` - Tables not part of core 36 genealogy structure

## Current Schema Structure

### Files Retained:
1. **`complete-genealogy-schema.sql`** - Contains all 39 core genealogy tables (22 core + 17 advanced)
2. **`default-event-types.sql`** - Standard GEDCOM event type data
3. **`README.md`** - Documentation

## Core 39 Tables (Verified Complete):

### Core Tables (22):
1. `hp_trees` - Genealogy tree container
2. `hp_people` - Individual person records
3. `hp_families` - Family unit records
4. `hp_children` - Parent-child relationships
5. `hp_events` - Life events (birth, death, marriage, etc.)
6. `hp_eventtypes` - Event type definitions
7. `hp_places` - Geographic locations
8. `hp_sources` - Source documents/records
9. `hp_repositories` - Archives, libraries, institutions
10. `hp_citations` - Source citations
11. `hp_media` - Photos, documents, media files
12. `hp_medialinks` - Media-to-person/family links
13. `hp_xnotes` - Extended notes
14. `hp_notelinks` - Note-to-entity links
15. `hp_associations` - Person associations
16. `hp_countries` - Country lookup table
17. `hp_states` - State/province lookup table
18. `hp_mediatypes` - Media type definitions
19. `hp_languages` - Language support
20. `hp_gedcom7_enums` - GEDCOM 7 enumerations
21. `hp_gedcom7_extensions` - GEDCOM 7 extensions
22. `hp_gedcom7_data` - GEDCOM 7 extended data

### Advanced Tables (17):
23. `hp_address` - Physical addresses
24. `hp_albums` - Photo album organization
25. `hp_albumlinks` - Album-media associations
26. `hp_album2entities` - Album-entity links
27. `hp_branches` - Family branch organization
28. `hp_branchlinks` - Branch-entity links
29. `hp_cemeteries` - Cemetery information
30. `hp_dna_groups` - DNA testing groups
31. `hp_dna_links` - DNA test associations
32. `hp_dna_tests` - Complete DNA test records
33. `hp_image_tags` - Photo tagging system
34. `hp_mostwanted` - Research wish lists
35. `hp_reports` - Custom report definitions
36. `hp_saveimport` - Import progress tracking
37. `hp_temp_events` - Pending user submissions
38. `hp_templates` - Template system
39. `hp_users` - User management

## Database Manager Updated

Updated `includes/Database/Manager.php` to load only:
1. `complete-genealogy-schema.sql` (39 tables)
2. `default-event-types.sql` (data population)

## Critical Fix Applied

**MAJOR ISSUE RESOLVED**: The `documentation-tables.sql` file was creating duplicate tables with conflicting field names:
- `hp_repositories` (different structure than in main schema)
- `hp_sources` (different field names)  
- `hp_citations` (different field names)
- `hp_notes` (different structure)
- `hp_media` (different structure)

This was causing database conflicts and preventing proper GEDCOM import. All conflicting definitions have been removed.

## Result

✅ **Clean, Consistent Schema Achieved**
- Exactly 39 genealogy tables as intended
- No duplicate or conflicting table definitions
- Single source of truth: `complete-genealogy-schema.sql`
- All TNG references removed
- Only essential files retained
- Database conflicts eliminated

The HeritagePress schema is now optimized for reliable GEDCOM import with proven genealogy software compatibility.
