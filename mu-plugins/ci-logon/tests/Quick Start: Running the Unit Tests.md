# Quick Start: Running the Unit Tests

## One-Minute Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Run Tests

```bash
composer test
```

## Expected Output

```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.

..................................................... 50 / 50 (100%)

Time: 00:00.234s, Memory: 8.00 MB

OK (50 tests, 50 assertions)
```

## Common Commands

### Run all tests with verbose output
```bash
composer test-verbose
```

### Generate code coverage report
```bash
composer test-coverage
```

### Run specific test file
```bash
cd tests && phpunit PluginProcessSyncTest.php
```

### Run specific test
```bash
cd tests && phpunit --filter test_http_503_response_returns_false
```

### Run tests in parallel (faster)
```bash
cd tests && phpunit --process-isolation
```

## What the Tests Verify

✅ **HTTP errors prevent login** (400, 401, 403, 404, 500, 502, 503, 504, etc.)
✅ **DNS failures prevent login** (HTTP 0 / connection errors)
✅ **Invalid JSON prevents login**
✅ **Missing required fields prevent login**
✅ **API error codes prevent login**
✅ **Network timeouts prevent login**
✅ **Corrupted responses prevent login**

## Test Structure

```
tests/
├── PluginProcessSyncTest.php           # Basic unit tests (50+ tests)
├── PluginProcessSyncIntegrationTest.php # Advanced integration tests (30+ tests)
├── bootstrap.php                        # Test environment setup
├── phpunit.xml                          # PHPUnit configuration
└── README.md                            # Detailed documentation
```

## Troubleshooting

### "Class not found" Error

Ensure the plugin files are in the `src/` directory:

```
src/
├── Plugin.php
├── CILogonAuth.php
└── ... (other plugin files)
```

### "Function not defined" Error

The `bootstrap.php` file should be automatically loaded. Check `phpunit.xml` has:

```xml
<phpunit bootstrap="bootstrap.php">
```

### Tests won't run

1. Verify PHP version: `php -v` (needs 7.4+)
2. Install dependencies: `composer install`
3. Check file permissions: `chmod +x tests/bootstrap.php`

## Key Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `PluginProcessSyncTest.php` | 50+ | Basic unit tests for all failure scenarios |
| `PluginProcessSyncIntegrationTest.php` | 30+ | Advanced tests with data providers |
| `bootstrap.php` | - | Test environment setup and mocks |
| `phpunit.xml` | - | PHPUnit configuration |

## Test Coverage

The tests cover:

- **HTTP Status Codes**: 0, 100, 199, 200-299, 300, 400s, 500s
- **JSON Parsing**: Valid, invalid, empty, malformed, Unicode
- **API Errors**: Error codes, missing fields, null values
- **Network Failures**: DNS, timeout, connection refused, SSL errors
- **Security**: XSS, SQL injection, large payloads

## Continuous Integration

Add to your CI/CD pipeline:

```bash
composer install
composer test
```

## Next Steps

1. **Read the detailed documentation**: See `tests/README.md`
2. **Add more tests**: Follow the existing patterns
3. **Integrate with CI/CD**: Use the GitHub Actions or GitLab CI examples
4. **Monitor coverage**: Run `composer test-coverage` and review the report

## Questions?

See `tests/README.md` for:
- Detailed test descriptions
- How to add new tests
- Troubleshooting guide
- Security considerations
- Best practices
