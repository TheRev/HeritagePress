# HeritagePress Plugin – Comprehensive Development Outline (From Ground Up)

*A GEDCOM 7-Compliant WordPress Genealogy Plugin Inspired by TNG Genealogy*

**Created:** June 2025  
**Status:** Initial Development Planning  
**Target:** WordPress 2025 Template Compatibility  
**Inspiration:** TNG Genealogy Program Interface & Functionality

---

## Executive Summary

This document outlines the comprehensive development plan for HeritagePress, a WordPress genealogy plugin built from the ground up with GEDCOM 7 compliance as its core foundation. The plugin will provide an extremely user-friendly interface inspired by the TNG genealogy standalone program while integrating seamlessly with WordPress backend and frontend systems.

**Key Differentiators:**
- **GEDCOM 7 Core Foundation:** Built specifically for the latest GEDCOM standard
- **TNG-Inspired Interface:** Familiar workflow for genealogy researchers
- **WordPress 2025 Ready:** Modern template compatibility and responsive design
- **Enterprise-Level Architecture:** Scalable database with audit trails and data integrity
- **Freemium Business Model:** Free core functionality with premium advanced features

---

## Architecture Overview

### Database Foundation (30 Tables - GEDCOM 7 Compliant)

The plugin's database architecture consists of three strategic layers:

#### Core Tables (16 Tables)
**Primary Genealogy Data:**
- `individuals` - Person records with core demographic data
- `names` - Name variations and cultural naming conventions
- `families` - Family units and relationship structures
- `family_links` - Individual-to-family relationship mappings
- `events` - Life events, facts, and occurrences
- `event_links` - Event-to-individual/family associations
- `places` - Geographic locations with hierarchical structure
- `event_types` - Customizable event categories and definitions

**Documentation & Evidence:**
- `repositories` - Archives, libraries, and source repositories
- `sources` - Source materials and documentation
- `citations` - Specific source references and evidence
- `citation_links` - Citation-to-data associations
- `notes` - Research notes and annotations
- `note_links` - Note-to-data associations
- `trees` - Family tree management and organization
- `media_links` - Media file associations and metadata

#### GEDCOM 7 Extended Tables (8 Tables)
**Advanced Genealogy Features:**
- `aliases` - Alternative names and pseudonyms
- `ages` - Age information and calculations
- `relationships` - Extended relationship types and qualifiers
- `multimedia_objects` - Digital media objects and files
- `multimedia_files` - Media file storage and metadata
- `multimedia_references` - Media-to-data associations
- `multimedia_identifiers` - Unique media identification
- `multimedia_cross_references` - Cross-referenced media links

#### GEDCOM 7 Compliance Tables (6 Tables)
**Data Integrity & Standards:**
- `submitters` - Data contributors and researchers
- `header` - GEDCOM file headers and metadata
- `change_tracking` - Audit trail and version control
- `external_identifiers` - External system references
- `user_reference_numbers` - User-defined reference systems
- `unique_identifiers` - Global unique identifier management

### Technical Specifications

**WordPress Integration:**
- PSR-4 autoloading without Composer dependency
- WordPress Coding Standards compliance
- Native WordPress media library integration
- Hook-based architecture for extensibility

**Database Features:**
- UTF-8mb4 collation for international character support
- Strategic indexing for performance optimization
- Foreign key constraints for data integrity
- JSON columns for flexible data storage
- Prepared statements for security

**Security & Performance:**
- Capability-based access control
- Nonce verification for all forms
- SQL injection prevention
- Optimized queries with proper indexing
- Caching strategies for large datasets

---

## Development Phases

### Phase I: GEDCOM 7 Database Foundation (Weeks 1-3)
*Priority: Database structure is the absolute foundation - everything builds from here*

#### I.1 Database Architecture Design & Planning
- **Objective:** Design the complete GEDCOM 7-compliant database structure
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Entity-Relationship Diagram (ERD) for all 30 tables
  - GEDCOM 7 specification analysis and mapping
  - Database normalization strategy
  - Performance optimization planning (indexes, constraints)
  - Data integrity rules and validation requirements
  - Table relationship documentation

#### I.2 Core Database Tables Implementation (Priority 1)
- **Objective:** Implement primary genealogy data tables
- **Status:** 🔴 Not Started
- **Deliverables:**
  - `individuals` - Person records with core demographic data
  - `names` - Name variations and cultural naming conventions
  - `families` - Family units and relationship structures
  - `family_links` - Individual-to-family relationship mappings
  - `events` - Life events, facts, and occurrences
  - `event_links` - Event-to-individual/family associations
  - `places` - Geographic locations with hierarchical structure
  - `event_types` - Customizable event categories and definitions

#### I.3 Documentation & Evidence Tables (Priority 2)
- **Objective:** Implement source and documentation management tables
- **Status:** 🔴 Not Started
- **Deliverables:**
  - `repositories` - Archives, libraries, and source repositories
  - `sources` - Source materials and documentation
  - `citations` - Specific source references and evidence
  - `citation_links` - Citation-to-data associations
  - `notes` - Research notes and annotations
  - `note_links` - Note-to-data associations
  - `trees` - Family tree management and organization
  - `media_links` - Media file associations and metadata

#### I.4 GEDCOM 7 Extended Tables (Priority 3)
- **Objective:** Implement advanced GEDCOM 7 specific features
- **Status:** 🔴 Not Started
- **Deliverables:**
  - `aliases` - Alternative names and pseudonyms
  - `ages` - Age information and calculations
  - `relationships` - Extended relationship types and qualifiers
  - `multimedia_objects` - Digital media objects and files
  - `multimedia_files` - Media file storage and metadata
  - `multimedia_references` - Media-to-data associations
  - `multimedia_identifiers` - Unique media identification
  - `multimedia_cross_references` - Cross-referenced media links

#### I.5 GEDCOM 7 Compliance Tables (Priority 4)
- **Objective:** Implement data integrity and standards compliance
- **Status:** 🔴 Not Started
- **Deliverables:**
  - `submitters` - Data contributors and researchers
  - `header` - GEDCOM file headers and metadata
  - `change_tracking` - Audit trail and version control
  - `external_identifiers` - External system references
  - `user_reference_numbers` - User-defined reference systems
  - `unique_identifiers` - Global unique identifier management

#### I.6 Database Management System
- **Objective:** Create robust database management and migration system
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Database creation and migration scripts
  - Version management system
  - Table factory pattern for object creation
  - Data validation layer
  - Performance optimization (indexes, constraints)
  - Backup and recovery procedures

### Phase II: Minimal Plugin Structure (Week 4)
*Priority: Just enough WordPress structure to support the database*

#### II.1 Basic WordPress Plugin Framework
- **Objective:** Create minimal plugin structure to house the database
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Basic plugin file (`heritagepress.php`) with headers
  - Simple activation hook for database creation
  - Deactivation and uninstall hooks
  - Basic autoloader for database classes
  - Security checks and WordPress compatibility

#### II.2 Database Integration with WordPress
- **Objective:** Connect database structure to WordPress environment
- **Status:** 🔴 Not Started
- **Deliverables:**
  - WordPress database integration using `$wpdb`
  - Table prefix handling
  - WordPress-style database operations
  - Error handling and logging
  - Database status verification tools

### Phase III: GEDCOM 7 Data Management (Weeks 5-6)
*Priority: Data import/export capabilities to populate and validate the database*

#### III.1 GEDCOM Parser Engine
- **Objective:** Build robust GEDCOM 7 import/export system
- **Status:** 🔴 Not Started
- **Deliverables:**
  - GEDCOM 7 specification parser
  - Legacy GEDCOM format support (5.5.1, 5.5.5)
  - Data validation and error reporting
  - Progress tracking for large imports
  - Character encoding detection
  - Memory-efficient processing for large files

#### III.2 Data Validation System
- **Objective:** Ensure data integrity and compliance
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Date format validation (GEDCOM 7 standard)
  - Relationship logic validation
  - Duplicate detection algorithms
  - Data consistency checks
  - Automatic error correction suggestions
  - Custom validation rules

#### III.3 Import/Export Tools
- **Objective:** Seamless data exchange capabilities
- **Status:** 🔴 Not Started
- **Deliverables:**
  - GEDCOM import wizard with preview
  - Export functionality with filtering options
  - CSV import/export for basic data
  - RootsMagic database import (Premium)
  - Family Tree Maker import (Premium)
  - Automatic media file linking

### Phase IV: Basic Admin Interface (Weeks 7-8)
*Priority: Essential WordPress admin interface for data management*

#### IV.1 WordPress Admin Framework
- **Objective:** Create basic WordPress admin interface
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Main admin menu with genealogy-specific icon
  - Basic tabbed interface for major sections
  - Capability-based access control
  - Admin CSS framework with WordPress styling
  - Error handling and user feedback

#### IV.2 Data Management Interface
- **Objective:** CRUD operations for genealogy data
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Individual profile management
  - Family relationship editor
  - Event and fact management
  - Source and citation tools
  - Basic search and filtering
  - Data validation feedback

#### IV.3 Import Management Interface
- **Objective:** User-friendly GEDCOM import interface
- **Status:** 🔴 Not Started
- **Deliverables:**
  - File upload interface
  - Import progress tracking
  - Error reporting and resolution
  - Data preview before import
  - Import history and logs

### Phase V: TNG-Inspired User Interface (Weeks 9-10)
*Priority: Professional genealogy workflow interface*

#### V.1 Individual Management Interface
- **Objective:** Create intuitive person-centric interface
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Individual profile editor inspired by TNG layout
  - Dynamic name variations management
  - Event timeline with visual indicators
  - Relationship builder with drag-and-drop
  - Photo gallery integration
  - Research note organization

#### V.2 Family Relationship Manager
- **Objective:** Streamline family structure management
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Family unit editor with visual family tree
  - Spouse and children management
  - Relationship type definitions
  - Family event timeline
  - Adoption and step-relationship handling
  - Marriage and divorce tracking

#### V.3 Research-Focused Tools
- **Objective:** Support genealogical research workflows
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Source management with repository integration
  - Citation builder with templates
  - Research log and to-do list
  - Conflicting data resolution tools
  - DNA match integration placeholders
  - Collaboration features (Premium)

### Phase VI: Frontend Display System (Weeks 11-12)
*Priority: Public-facing genealogy displays*

#### VI.1 WordPress 2025 Template Integration
- **Objective:** Seamless frontend integration with modern WordPress
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Block-based template compatibility
  - Responsive design for all devices
  - Accessibility compliance (WCAG 2.1)
  - SEO optimization for genealogy content
  - Schema.org markup for search engines
  - Social media sharing integration

#### VI.2 Shortcode System
- **Objective:** Flexible content display options
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Individual profile shortcode
  - Family tree display shortcode
  - Ancestor chart shortcode
  - Timeline display shortcode
  - Gallery shortcode for family photos
  - Search form shortcode

#### VI.3 Interactive Visualizations
- **Objective:** Engaging visual family tree displays
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Interactive pedigree charts
  - Descendant tree visualization
  - Timeline views for family history
  - Interactive maps for places
  - Photo timeline integration
  - Relationship path finder

### Phase VII: Advanced Features & Polish (Weeks 13-14)
*Priority: Professional features for serious genealogists*

#### VII.1 Geographic Integration
- **Objective:** Location-based genealogy features
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Place hierarchy management
  - Historical place name support
  - Interactive mapping with Leaflet integration
  - Migration path visualization
  - Geographic research suggestions
  - Place standardization tools

#### VII.2 Advanced Search & Reporting
- **Objective:** Powerful data discovery and analysis
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Advanced search with multiple criteria
  - Soundex and phonetic matching
  - Custom report builder
  - PDF report generation (Premium)
  - Data completeness analysis
  - Research timeline reports

#### VII.3 Privacy & Permissions
- **Objective:** Protect sensitive family information
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Privacy rules for living persons
  - Graduated access levels
  - GDPR compliance features
  - Data anonymization tools
  - User permission management
  - Audit trail for data access

### Phase VIII: Performance & Optimization (Weeks 15-16)
*Priority: Enterprise-level scalability and reliability*

#### VIII.1 Performance Optimization
- **Objective:** Ensure scalability for large datasets
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Query optimization and indexing
  - Caching strategies implementation
  - Lazy loading for large family trees
  - Database query profiling
  - Memory usage optimization
  - Background processing for large operations

#### VIII.2 Testing & Quality Assurance
- **Objective:** Comprehensive testing coverage
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Unit tests for core functionality
  - Integration tests for GEDCOM processing
  - Performance benchmarking
  - Cross-browser compatibility testing
  - Accessibility testing
  - Security penetration testing

#### VIII.3 Documentation & Support
- **Objective:** Comprehensive user and developer documentation
- **Status:** 🔴 Not Started
- **Deliverables:**
  - User manual with tutorials
  - Video training series
  - Developer API documentation
  - Migration guides from other software
  - Troubleshooting guides
  - Community forum integration

### Phase IX: Premium Features & Business Model (Weeks 17-18)
*Priority: Implement freemium business model*

#### IX.1 License Management System
- **Objective:** Premium feature control and licensing
- **Status:** 🔴 Not Started
- **Deliverables:**
  - License validation system
  - Feature availability control
  - Usage limitation enforcement
  - Premium upgrade prompts
  - License renewal management
  - Customer portal integration

#### IX.2 Premium-Only Features
- **Objective:** Implement advanced premium functionality
- **Status:** 🔴 Not Started
- **Deliverables:**
  - Unlimited individuals and trees
  - Advanced import/export formats
  - Multi-user collaboration features
  - Advanced privacy controls
  - Professional reporting tools
  - Priority support system

#### IX.3 Marketplace Preparation
- **Objective:** Prepare for distribution and sales
- **Status:** 🔴 Not Started
- **Deliverables:**
  - WordPress.org plugin submission
  - Premium version marketplace setup
  - Payment processing integration
  - Customer support documentation
  - Marketing materials and website
  - Beta testing program coordination

---

## Feature Comparison: Free vs. Premium

### Free Version Features
- **Data Limits:** 250 individuals, 1 family tree
- **Basic Management:** Individual and family records
- **Events:** Standard life events with custom types
- **Import:** Basic GEDCOM import only
- **Export:** CSV export only
- **Media:** Basic photo attachments (limited)
- **Privacy:** Basic privacy controls
- **Display:** Charts limited to 4 generations
- **Maps:** Basic Leaflet integration

### Premium Version Features
- **Data Limits:** Unlimited individuals and trees
- **Advanced Management:** Extended custom fields
- **Enhanced Import/Export:** Full GEDCOM export, RootsMagic/FTM import
- **Advanced Media:** Photos in trees, unlimited attachments
- **Collaboration:** Multi-user editing, revision history
- **Advanced Privacy:** Granular permission controls
- **Reports:** PDF generation, advanced analytics
- **Visualization:** Interactive charts, migration maps
- **Integration:** Add-on system, API access
- **Support:** Priority support, advanced tutorials

---

## Technical Implementation Notes

### WordPress 2025 Compatibility
- **Block Editor Integration:** Custom blocks for genealogy content
- **Full Site Editing:** Compatible with FSE themes
- **Modern PHP Requirements:** PHP 8.0+ with type declarations
- **JavaScript Framework:** Vanilla JS with ES6+ features
- **CSS Framework:** CSS Grid and Flexbox for layouts
- **Accessibility:** ARIA labels and keyboard navigation

### TNG Genealogy Inspiration
- **Interface Layout:** Familiar tabbed interface structure
- **Workflow Patterns:** Research-focused data entry
- **Navigation Logic:** Hierarchical browsing similar to TNG
- **Data Presentation:** Clean, organized information display
- **Advanced Features:** Power user tools for serious genealogists

### Database Optimization Strategies
- **Indexing:** Strategic indexes on frequently queried columns
- **Partitioning:** Large table partitioning for performance
- **Caching:** WordPress transients for expensive queries
- **Batch Processing:** Background jobs for large operations
- **Connection Pooling:** Efficient database connection management

### Security Considerations
- **Input Validation:** Comprehensive data sanitization
- **SQL Injection Prevention:** Prepared statements throughout
- **CSRF Protection:** Nonce verification for all forms
- **Capability Checks:** WordPress role-based access control
- **Data Encryption:** Sensitive data encryption at rest

---

## Success Metrics & Milestones

### Development Milestones
1. **Foundation Complete:** Core plugin structure and database deployment
2. **GEDCOM Import Working:** Successfully import and display genealogy data
3. **Admin Interface Complete:** Full-featured WordPress admin experience
4. **Frontend Display Ready:** Public-facing family tree displays
5. **Premium Features Active:** Freemium business model operational
6. **Performance Optimized:** Scalable for 10,000+ individual databases

### Quality Metrics
- **Performance:** Page load times under 2 seconds
- **Compatibility:** Works with top 10 WordPress themes
- **Accessibility:** WCAG 2.1 AA compliance
- **Security:** Zero critical vulnerabilities
- **Usability:** 90%+ user satisfaction in testing
- **Data Integrity:** 100% GEDCOM 7 compliance

### Business Metrics
- **User Adoption:** Target 1,000 active installations in first 6 months
- **Premium Conversion:** 15% free-to-premium conversion rate
- **Support Efficiency:** Average response time under 24 hours
- **Community Growth:** Active user forum with regular participation

---

## Conclusion

This comprehensive development outline provides a roadmap for creating a professional-grade genealogy plugin that leverages WordPress's strengths while providing the specialized functionality that genealogists need. By starting with a solid GEDCOM 7-compliant foundation and building upon it with TNG-inspired interfaces and modern WordPress integration, HeritagePress will serve both casual family historians and serious genealogical researchers.

The phased approach ensures that each component is thoroughly tested and optimized before moving to the next phase, resulting in a robust, scalable, and user-friendly plugin that sets new standards for WordPress genealogy software.

**Next Steps:**
1. Begin Phase I development with plugin foundation
2. Implement and test database architecture
3. Create detailed user interface mockups
4. Establish development environment and testing protocols
5. Begin community feedback collection process

---

*This document serves as the master development plan and should be updated as phases are completed and requirements evolve.*
    Relationship Calculator (relationship.php):
    Timeline (timeline.php): For an individual or family.
    Surnames Index (surnames.php): List of surnames with links.
    Places Index (places.php): List of places with links.
    Sources Index (sources.php):
    Repositories Index (repositories.php):
    Media Gallery (browsemedia.php):
    Notes Index (browsenotes.php):
    Statistics Page (stats.php):
    "What's New" Page (whatsnew.php):
4.3. Navigation & Linking:
    Dynamic links between individuals, families, sources, etc., on frontend pages.
    Configurable main genealogy menu (possibly integrated with WordPress navigation menus).
4.4. Basic Search Functionality:
    Frontend search form for individuals (name, dates).
4.5. Privacy Controls on Frontend:
    Implement logic to hide/show data based on privacy settings (e.g., for living individuals).
    Integration with WordPress user roles for access control.
4.6. Theme Integration:
    Ensure blocks and pages are stylable and adapt to the active theme (especially 2025 theme).
    Provide sensible default styling.

---

Phase 5: Advanced Features & User Experience Enhancements

5.1. Advanced Data Visualization:
    Interactive JavaScript-based charts (e.g., using D3.js, GoJS) for pedigree, descendant, fan charts.
5.2. GEDCOM Export Functionality:
    Admin interface to export selected trees or data subsets in GEDCOM 7 format.
5.3. Advanced Search & Filtering:
    Multi-criteria search for admin and frontend (e.g., search by event type, date range, place).
5.4. Reporting & Custom Reports:
    Generate predefined reports (e.g., missing birth dates, end-of-line individuals).
5.5. User Contributions & Collaboration (Optional):
    Frontend forms for registered users to suggest corrections or add information (moderated).
5.6. Internationalization (i18n) & Localization (l10n):
    Ensure all plugin strings are translatable.
    Support for different date and name display formats based on locale.
5.7. Mapping Integration:
    Display event locations on interactive maps (e.g., Leaflet.js, OpenStreetMap).
5.8. Data Validation & Integrity Tools (Advanced):
    Admin tools to find inconsistencies, potential duplicates, logical errors (e.g., birth after death).

---

Phase 6: Optimization, Testing, Documentation & Release

6.1. Performance Optimization:
    Database query optimization (use of WP_Query patterns where applicable, direct $wpdb for complex queries).
    Caching strategies (WordPress Object Cache, Transients API).
    Lazy loading for images and other assets.
6.2. Security Hardening & Audit:
    Review all code for security vulnerabilities (XSS, CSRF, SQLi).
    Implement proper nonces and capability checks throughout.
6.3. Comprehensive Testing:
    Unit tests for critical functions (especially parser, data mappers, database interactions).
    Integration tests for component interactions.
    End-to-end testing of user flows (import, admin CRUD, frontend display).
    Testing with a wide variety of valid and malformed GEDCOM files.
    Cross-browser and responsive design testing.
6.4. User Documentation:
    Detailed user guide for installation, configuration, import, data management, and frontend usage.
    FAQs and troubleshooting tips.
6.5. Developer Documentation (if applicable):
    Notes on hooks, filters, API endpoints for extensibility.
6.6. Release Preparation:
    Beta testing program.
    Preparation for submission to WordPress.org plugin repository (if planned).

---

Cross-Cutting Concerns (To be addressed throughout all phases):

Code Quality: Adherence to WordPress Coding Standards, PHP best practices, clear commenting.
User Experience (UX): Prioritize intuitive navigation and clear presentation of complex data.
Accessibility (a11y): Design with WCAG guidelines in mind for both admin and frontend.
Error Handling: Graceful error handling and informative messages for users and admins.
Logging: Implement logging for debugging and auditing critical processes (like import).
