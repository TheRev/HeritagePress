# Testing Guide

## Test Organization

The test suite is organized into three main categories:

### 1. Unit Tests (`tests/Unit`)
- Tests individual components in isolation
- Mocks external dependencies
- Fast execution
- Examples: Model, Container, Event tests

### 2. Integration Tests (`tests/Integration`)
- Tests component interactions
- Uses test database
- Examples: Database operations, GEDCOM handling

### 3. Functional Tests (`tests/Functional`)
- Tests complete features
- Uses full system integration
- Examples: Import/Export, Quality checks

## Base Test Cases

Each test category has its own base test case class:

1. `UnitTestCase`
   - Basic assertions
   - Mock setup helpers
   - No database access

2. `IntegrationTestCase`
   - Database transaction support
   - WordPress integration
   - Cache handling

3. `FunctionalTestCase`
   - Fixture support
   - Full system setup
   - Cleanup helpers

## Mocks

Mock objects are provided for:

1. WordPress Database (`MockWPDB`)
   - Query tracking
   - Result simulation
   - Error handling

2. WordPress Functions (`MockWP`)
   - Action/Filter handling
   - Cache simulation
   - Plugin helpers

## Running Tests

```bash
# Run all tests
composer test

# Run specific test suite
composer test -- --testsuite unit
composer test -- --testsuite integration
composer test -- --testsuite functional

# Run specific test file
composer test -- tests/Unit/ModelTest.php

# Run with coverage
composer test-coverage
```

## Adding New Tests

1. Choose appropriate category (Unit/Integration/Functional)
2. Create test file in correct directory
3. Extend proper test case class
4. Use available mocks and helpers
5. Follow naming conventions

## Best Practices

1. Keep unit tests focused
2. Use data providers
3. Clean up after tests
4. Mock external dependencies
5. Use transactions for database tests

## Coverage

Coverage reports are generated in `build/coverage`. Aim for:
- 90%+ coverage for Models
- 80%+ coverage for Services
- 70%+ coverage overall
