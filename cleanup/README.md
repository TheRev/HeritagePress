# HeritagePress

A comprehensive genealogy management plugin for WordPress.

## Description

HeritagePress is a standalone WordPress plugin that provides genealogy management capabilities. The plugin is designed to work without any external dependencies or third-party libraries, ensuring maximum compatibility and ease of installation.

## Development

### Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- XAMPP or similar local development environment

### Development Setup

This project uses Composer and NPM scripts for development purposes only. The plugin itself has no runtime dependencies and will work without these tools in production.

1. Clone the repository
2. For development, run:
   ```powershell
   composer install
   npm install
   npm run dev:setup
   ```
   
Development Commands:
```powershell
# Run tests
npm test

# Check PHP coding standards
npm run check-php

# Fix PHP coding standards automatically
npm run fix-php

# Create production package
npm run package
```

These commands provide access to development tools like:
- PHPUnit for testing
- PHP_CodeSniffer for WordPress coding standards
- PHP Compatibility checker

### Building for Production

No build step is required. The plugin can be installed directly in WordPress by copying these files to the wp-content/plugins directory:
- `heritagepress.php`
- `includes/` directory

Do not copy:
- `vendor/` directory (development tools only)
- `tests/` directory
- `bin/` directory
- Development configuration files (composer.json, phpunit.xml, etc.)

## Features

- Family tree management
- Individual records
- Family relationships
- Events and dates
- Places and locations
- Media attachments
- Source citations
- GEDCOM import/export

## Installation

1. Upload the plugin files to `/wp-content/plugins/heritagepress`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the HeritagePress menu to configure the plugin

## License

GPL v2 or later