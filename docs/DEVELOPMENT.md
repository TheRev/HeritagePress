# Heritage Press Plugin Development Guide

## Project Structure

```
heritage-press/
├── admin/             # Admin interface files
├── includes/          # Core plugin files
│   └── HeritagePress/  # PSR-4 compliant classes
├── public/           # Public-facing files
├── languages/        # Translation files
└── tests/           # PHPUnit test files
```

## Development Setup

1. **Requirements**
   - PHP 7.4 or higher
   - WordPress 5.8 or higher
   - Composer
   - PHPUnit (for testing)

2. **Installation**
   ```bash
   # Clone the repository
   git clone [repository-url]
   
   # Install dependencies
   composer install
   ```

3. **Building**
   ```powershell
   # Run full build with tests
   .\build.ps1
   
   # Skip tests
   .\build.ps1 -skipTests
   
   # Specify version
   .\build.ps1 -buildVersion "1.1.0"
   ```

## Database Migrations

1. **Creating a Migration**
   - Create a new file in `includes/HeritagePress/Database/Migrations`
   - Name format: `YYYYMMDD_description.php`
   - Extend the base Migration class

   Example:
   ```php
   namespace HeritagePress\Database\Migrations;
   
   class CreateIndividualsTable {
       public static function up() {
           // Migration code
       }
       
       public static function down() {
           // Rollback code
       }
   }
   ```

2. **Running Migrations**
   - Migrations run automatically during plugin activation   - For manual migration: `HeritagePress\Database\MigrationManager::migrate()`
   - For rollback: `HeritagePress\Database\MigrationManager::rollback()`

## Testing

1. **Running Tests**
   ```bash
   # Run all tests
   vendor/bin/phpunit
   
   # Run specific test file
   vendor/bin/phpunit tests/IndividualTest.php
   
   # Run tests with coverage
   vendor/bin/phpunit --coverage-html coverage
   ```

2. **Writing Tests**
   - Extend `HeritagePressTestCase` for all tests
   - Use WordPress test utilities when needed
   - Mock database operations where possible

## Code Style

1. **PSR Standards**
   - Follow PSR-4 for autoloading
   - Follow PSR-12 for code style
   - Use type hints where possible
   - Document all public methods

2. **WordPress Coding Standards**
   - Follow WordPress naming conventions for hooks
   - Use WordPress functions for database operations
   - Properly sanitize and escape data

## Plugin Structure

1. **Models**
   - Base Model class in `HeritagePress\Models\Model`
   - Entity models extend base Model
   - Use traits for common functionality
   - Implement validation in models

2. **Database**
   - Use Migration system for schema changes
   - Use QueryBuilder for complex queries
   - Implement transactions where needed
   - Cache heavy queries

3. **Services**
   - Use dependency injection
   - Register services in Container
   - Keep services focused and small
   - Use interfaces where possible

## Contributing

1. **Pull Requests**
   - Create feature branch
   - Write/update tests
   - Run full test suite
   - Update documentation
   - Create pull request

2. **Documentation**
   - Update PHPDoc blocks
   - Update README.md if needed
   - Add migration documentation
   - Document breaking changes
