# Heritage Press Plugin

A comprehensive genealogy management system for WordPress with GEDCOM 7.0 support and extensible architecture.

## Development Plan

### Phase 1: Foundation Setup
1. **Plugin Structure**
   - Create base plugin file
   - Set up autoloading
   - Implement activation hooks
   - Configure deactivation cleanup

2. **Database Design**
   - Create tables:
     - Individuals
     - Families
     - Events
     - Media
     - Places
     - Sources
   - Implement database versioning
   - Add indexes and foreign keys

3. **Core Classes**
   - Models for each entity
   - Data access layer
   - Service containers
   - Utility classes

### Phase 2: GEDCOM Integration
1. **Parser Development**
   - GEDCOM 7.0 parser
   - Validation system
   - Error handling
   - Import processor

2. **Export System**
   - GEDCOM export
   - Data mapping
   - Validation
   - File handling

### Phase 3: Frontend Development
1. **Template System**
   - Base templates
   - Override system
   - Custom shortcodes
   - Widget support

2. **Individual Profiles**
   - Details tab
   - Family tree tab
   - Family sheet tab
   - Timeline tab
   - Media gallery

### Phase 4: Admin Interface
1. **Settings Pages**
   - General configuration
   - Import/Export tools
   - Privacy settings
   - User permissions

### Phase 5: Extension System
1. **Developer Tools**
   - Action hooks
   - Filter hooks
   - API endpoints
   - Template hooks

### Phase 6: Advanced Features
1. **Performance**
   - Caching system
   - Query optimization
   - Asset optimization

2. **Security**
   - Access control
   - Privacy features
   - GDPR compliance

## Extension System

### Core Add-ons
1. **DNA Integration**
   - DNA match management
   - Haplogroup tracking
   - Testing company integrations

2. **Research Tools**
   - Research logs
   - To-do lists
   - Source templates
   - Correspondence tracker

3. **Advanced Charts**
   - Fan charts
   - Hourglass charts
   - Statistics graphs
   - Migration maps

4. **Archive Integration**
   - FamilySearch API connection
   - National Archives
   - Library interfaces

## Technical Requirements
- PHP 8.0+
- WordPress 6.0+
- MySQL 5.7+ or MariaDB 10.3+
- Modern browser support

## Development Stack
- Composer for dependency management
- npm for asset bundling
- PHPUnit for testing
- GitHub Actions for CI/CD

## Installation
1. Download the plugin
2. Upload to wp-content/plugins/
3. Activate through WordPress admin
4. Configure settings

## Documentation
- [User Guide](docs/user-guide.md)
- [Developer Documentation](docs/developer.md)
- [API Reference](docs/api.md)
- [Contributing Guide](CONTRIBUTING.md)

## Support
- GitHub Issues for bug reports
- WordPress.org forum for support
- Premium support options

## Roadmap
- Version 1.0: Core functionality
- Version 1.1: Advanced charts
- Version 1.2: DNA integration
- Version 1.3: Research tools
- Version 2.0: Full API integration

## Testing
- PHPUnit for unit tests
- WordPress test suite integration
- End-to-end testing with Cypress
- GitHub Actions automation

## Contributing
[Contributing guidelines will be added]

## License
GPL v2 or later
