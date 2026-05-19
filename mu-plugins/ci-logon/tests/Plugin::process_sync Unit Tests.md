# Plugin::process_sync Unit Tests

## Overview

This test suite provides comprehensive unit tests for the `Plugin::process_sync` method in the CI Logon WordPress plugin. The tests verify that **users are NOT logged in when API responses fail** (e.g., DNS down, network errors, invalid responses, etc.).

## Critical Security Goal

The primary goal of these tests is to ensure that the authentication system **fails securely**. If the API cannot be reached or returns an invalid response, the user must NOT be logged in. This prevents unauthorized access in scenarios where:

- DNS is down and the API is unreachable
- The API server is down (HTTP 503 Service Unavailable)
- Network connection is lost (connection timeout)
- The API returns corrupted or invalid data
- The API returns incomplete data (missing required fields)
- The API returns error codes indicating the user doesn't exist

## Test Files

### 1. `PluginProcessSyncTest.php`

**Basic unit tests** that verify `process_sync` returns `false` for various failure scenarios.

**Test Categories:**

- **HTTP Error Response Tests** (lines 57-227)
  - Tests all 4xx and 5xx error codes
  - Tests codes outside the 200-299 valid range
  - Includes specific tests for DNS failure (HTTP 503), timeouts (HTTP 504), etc.

- **JSON Parsing Error Tests** (lines 229-272)
  - Invalid JSON responses
  - Empty responses
  - Malformed JSON

- **API Error Code Tests** (lines 274-309)
  - API error code 1005 (user not found)
  - Other API error codes

- **Missing Required Fields Tests** (lines 311-377)
  - Missing memberships array
  - Empty memberships array
  - Null memberships

- **Network Failure Simulation Tests** (lines 379-432)
  - Connection timeout (HTTP 0)
  - DNS resolution failure (HTTP 0)
  - Connection refused (HTTP 0)

- **Edge Case Tests** (lines 434-510)
  - Valid HTTP 200 responses (positive control)
  - Alternative response formats
  - Critical security test: verify failed responses don't set auth cookie

- **Boundary Tests** (lines 512-577)
  - HTTP 200 (lowest valid code)
  - HTTP 299 (highest valid code)

### 2. `PluginProcessSyncIntegrationTest.php`

**Advanced integration tests** with data providers and parameterized tests for comprehensive coverage.

**Test Categories:**

- **Critical Security Tests** (lines 37-147)
  - DNS failure prevents login
  - API timeout prevents login
  - API server down prevents login
  - Corrupted API response prevents login
  - Incomplete API response prevents login
  - User not found in API prevents login

- **HTTP Status Code Tests** (lines 149-246)
  - All 4xx errors (parameterized with data provider)
  - All 5xx errors (parameterized with data provider)
  - Codes outside 200-299 range (parameterized with data provider)

- **JSON Parsing Tests** (lines 248-276)
  - Invalid JSON responses (parameterized with data provider)

- **Response Structure Tests** (lines 278-329)
  - Null memberships
  - Missing data field

- **Network Error Scenarios** (lines 331-387)
  - Connection refused
  - Read timeout
  - SSL certificate error

- **Edge Cases and Boundary Conditions** (lines 389-483)
  - Large response bodies
  - Unicode characters
  - Special characters in error messages
  - Deeply nested JSON structures

- **Security Tests** (lines 485-547)
  - XSS payload in response
  - SQL injection payload in response

## Setup and Installation

### Prerequisites

- PHP 7.4 or higher
- Composer (for dependency management)
- PHPUnit 9.5 or higher
- Mockery (for mocking)

### Installation Steps

1. **Install dependencies:**

```bash
cd /path/to/plugin
composer require --dev phpunit/phpunit mockery/mockery
```

2. **Create a `composer.json` file** (if not already present):

```json
{
    "name": "mesh-research/ci-logon",
    "description": "CI Logon authentication plugin for WordPress",
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "mockery/mockery": "^1.4"
    },
    "autoload": {
        "psr-4": {
            "MeshResearch\\CILogon\\": "src/"
        }
    }
}
```

3. **Ensure your plugin structure matches:**

```
plugin-root/
├── src/
│   ├── Plugin.php
│   ├── CILogonAuth.php
│   └── ... (other plugin files)
├── tests/
│   ├── PluginProcessSyncTest.php
│   ├── PluginProcessSyncIntegrationTest.php
│   ├── bootstrap.php
│   ├── phpunit.xml
│   └── README.md
├── vendor/
├── composer.json
└── ci-logon.php (main plugin file)
```

## Running the Tests

### Run All Tests

```bash
cd /path/to/plugin/tests
phpunit
```

### Run Specific Test File

```bash
phpunit PluginProcessSyncTest.php
```

### Run Specific Test Class

```bash
phpunit --filter PluginProcessSyncTest
```

### Run Specific Test Method

```bash
phpunit --filter test_http_503_response_returns_false
```

### Run with Code Coverage

```bash
phpunit --coverage-html coverage-report
```

This generates an HTML coverage report in the `coverage-report` directory.

### Run with Verbose Output

```bash
phpunit -v
```

### Run Tests in Parallel (faster execution)

```bash
phpunit --process-isolation
```

## Understanding the Test Results

### Successful Test Run

```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.

..................................................... 50 / 50 (100%)

Time: 00:00.234s, Memory: 8.00 MB

OK (50 tests, 50 assertions)
```

### Failed Test Example

If a test fails, you'll see output like:

```
FAIL: test_http_503_response_returns_false
AssertionError: process_sync should return false for HTTP 503
Failed asserting that true is false.
```

This would indicate that `process_sync` is NOT properly handling HTTP 503 errors.

## Test Coverage

The test suite covers:

- **HTTP Status Codes**: 0, 100, 199, 200, 201, 299, 300, 301, 302, 400, 401, 403, 404, 429, 500, 501, 502, 503, 504
- **JSON Parsing**: Valid, invalid, empty, malformed, Unicode, special characters
- **API Error Codes**: 1005 (user not found), 1001 (generic error)
- **Response Structures**: With/without data field, with/without results field, missing/null memberships
- **Network Failures**: DNS failure, connection timeout, connection refused, SSL errors
- **Security**: XSS payloads, SQL injection payloads, large responses, deeply nested structures

## Key Test Assertions

All tests follow this pattern:

```php
public function test_scenario_description()
{
    $code = 503;
    $body = json_encode(['error' => 'Service Unavailable']);
    $username = 'testuser';
    $user = false;

    $result = Plugin::process_sync($code, $body, $username, $user);

    $this->assertFalse($result, 'process_sync should return false for HTTP 503');
}
```

The critical assertion is:

```php
$this->assertFalse($result, 'User must NOT be logged in on API failure');
```

## Mocking and Test Environment

The `bootstrap.php` file provides:

1. **Mock WordPress Functions**: `error_log()`, `get_user_by()`, `wp_insert_user()`, etc.
2. **Mock WordPress Classes**: `WP_Error`, `WP_User`
3. **Autoloader**: For loading plugin classes from the `src/` directory
4. **Test Environment Setup**: Initializes necessary constants and configurations

### Important Notes on Mocking

- The tests do NOT require a full WordPress installation
- WordPress functions are mocked to return safe defaults
- User creation attempts will fail in the test environment (by design)
- This allows testing the `process_sync` method in isolation

## Extending the Tests

### Adding a New Test

1. **Create a new test method** in either test class:

```php
/**
 * Test: Description of what you're testing
 *
 * @test
 */
public function test_new_scenario_description()
{
    $code = 200;
    $body = json_encode(['data' => []]);
    $username = 'testuser';
    $user = false;

    $result = Plugin::process_sync($code, $body, $username, $user);

    $this->assertFalse($result, 'Assertion message');
}
```

2. **Run the new test:**

```bash
phpunit --filter test_new_scenario_description
```

### Using Data Providers

For testing multiple similar scenarios:

```php
/**
 * @test
 * @dataProvider myDataProvider
 */
public function test_with_data_provider($code, $expected)
{
    $body = json_encode(['error' => 'Test']);
    $result = Plugin::process_sync($code, $body, 'testuser', false);
    $this->assertEquals($expected, $result);
}

public function myDataProvider()
{
    return [
        'HTTP 400' => [400, false],
        'HTTP 500' => [500, false],
    ];
}
```

## Troubleshooting

### "Class not found" Error

**Problem**: `PHP Fatal error: Class 'MeshResearch\CILogon\Plugin' not found`

**Solution**: Ensure the plugin files are in the `src/` directory and the autoloader in `bootstrap.php` is configured correctly.

### "Function not defined" Error

**Problem**: `PHP Fatal error: Call to undefined function wp_insert_user()`

**Solution**: The mock functions in `bootstrap.php` should be loaded. Ensure `bootstrap.php` is being used (check `phpunit.xml`).

### Tests Pass Locally but Fail in CI/CD

**Problem**: Tests work on your machine but fail in CI/CD pipeline.

**Solution**:
1. Ensure PHP version matches (7.4+)
2. Run `composer install` before tests
3. Check that all dependencies are installed
4. Verify file paths are correct in the CI/CD environment

### Slow Test Execution

**Problem**: Tests take too long to run.

**Solution**:
1. Run tests in parallel: `phpunit --process-isolation`
2. Run only specific test files: `phpunit PluginProcessSyncTest.php`
3. Use `--stop-on-failure` to stop at first failure: `phpunit --stop-on-failure`

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['7.4', '8.0', '8.1', '8.2']
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests
        run: cd tests && phpunit
```

### GitLab CI Example

```yaml
test:
  image: php:8.1
  script:
    - apt-get update && apt-get install -y git
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
    - cd tests && phpunit
```

## Security Considerations

### What These Tests Verify

✅ **Users are NOT logged in** when API responses fail
✅ **Proper error handling** for network failures
✅ **Validation of API responses** (HTTP codes, JSON, required fields)
✅ **Graceful degradation** when API is unavailable

### What These Tests Do NOT Verify

❌ **HTTPS/TLS certificate validation** (handled by WordPress)
❌ **User input sanitization** (handled by WordPress)
❌ **SQL injection prevention** (handled by WordPress)
❌ **XSS prevention** (handled by WordPress)

These are the responsibility of WordPress and should be tested separately.

## Best Practices

1. **Run tests before committing**: Ensure all tests pass before pushing code
2. **Add tests for new features**: When adding new functionality, add corresponding tests
3. **Use meaningful test names**: Test method names should clearly describe what they test
4. **Keep tests isolated**: Each test should be independent and not rely on others
5. **Mock external dependencies**: Don't make real API calls in tests
6. **Test edge cases**: Include boundary conditions and unusual inputs
7. **Review test coverage**: Aim for >80% code coverage

## References

- [PHPUnit Documentation](https://phpunit.de/)
- [Mockery Documentation](https://docs.mockery.io/)
- [WordPress Plugin Security](https://developer.wordpress.org/plugins/security/)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)

## Contributing

When contributing new tests:

1. Follow the existing test structure and naming conventions
2. Add documentation comments explaining what the test verifies
3. Use descriptive assertion messages
4. Include both positive and negative test cases
5. Test edge cases and boundary conditions
6. Ensure all tests pass before submitting

## License

These tests are part of the CI Logon WordPress plugin and follow the same license as the plugin.

## Support

For issues or questions about the tests:

1. Check the troubleshooting section above
2. Review the test comments for detailed explanations
3. Consult the PHPUnit and Mockery documentation
4. Open an issue in the plugin repository
