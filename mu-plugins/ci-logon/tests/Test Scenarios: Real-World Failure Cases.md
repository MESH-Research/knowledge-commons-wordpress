# Test Scenarios: Real-World Failure Cases

This document describes the real-world scenarios that each test covers and why they're important for security.

## Critical Security Scenarios

### Scenario 1: DNS is Down

**What happens**: User tries to log in via CI Logon. The WordPress server tries to contact the Profiles API, but DNS resolution fails.

**API Response**: `wp_remote_get()` returns a `WP_Error` with message like "cURL error 6: Could not resolve host"

**HTTP Code**: 0 (no response)

**Test**: `test_dns_failure_http_0_returns_false()`

**Expected Behavior**: 
- `process_sync()` returns `false`
- User is NOT logged in
- Error is logged

**Why it matters**: If DNS is down, we cannot verify the user's identity. Logging them in anyway would be a serious security breach.

---

### Scenario 2: API Server is Down (HTTP 503)

**What happens**: User tries to log in. The Profiles API server is down or overloaded.

**API Response**: HTTP 503 Service Unavailable

**HTTP Code**: 503

**Test**: `test_http_503_response_returns_false()` and `test_api_server_down_prevents_login()`

**Expected Behavior**:
- `process_sync()` returns `false`
- User is NOT logged in
- Error is logged with HTTP code and response body

**Why it matters**: If the API is down, we cannot verify the user's identity. Logging them in would grant access to an unverified user.

---

### Scenario 3: API Request Times Out

**What happens**: User tries to log in. The connection to the API is established but the API doesn't respond within the timeout period.

**API Response**: `wp_remote_get()` returns a `WP_Error` with message like "cURL error 28: Operation timed out"

**HTTP Code**: 0 (no response) or 504 (Gateway Timeout)

**Test**: `test_api_timeout_prevents_login()` and `test_http_504_response_returns_false()`

**Expected Behavior**:
- `process_sync()` returns `false`
- User is NOT logged in
- Error is logged

**Why it matters**: A timeout means we couldn't verify the user's identity. Logging them in would be unsafe.

---

### Scenario 4: API Returns Corrupted JSON

**What happens**: User tries to log in. The API server responds with HTTP 200 but the response body is corrupted (e.g., due to network corruption or misconfiguration).

**API Response**: HTTP 200 with invalid JSON body

**HTTP Code**: 200

**Body**: `<html>Internal Server Error</html>` or `{invalid json`

**Test**: `test_corrupted_api_response_prevents_login()` and `test_invalid_json_response_returns_false()`

**Expected Behavior**:
- `process_sync()` detects JSON parsing error
- Returns `false`
- User is NOT logged in
- Error is logged with JSON error message

**Why it matters**: If we can't parse the API response, we can't verify user data. Logging them in based on corrupted data is unsafe.

---

### Scenario 5: API Returns Missing Required Fields

**What happens**: User tries to log in. The API returns HTTP 200 with valid JSON, but the response is missing the required `memberships` field.

**API Response**: HTTP 200 with valid JSON but missing `memberships`

**HTTP Code**: 200

**Body**: 
```json
{
  "data": [{
    "profile": {
      "username": "testuser",
      "email": "testuser@example.com",
      "first_name": "Test",
      "last_name": "User"
      // Missing "memberships" field
    }
  }]
}
```

**Test**: `test_incomplete_api_response_prevents_login()` and `test_response_missing_memberships_array_returns_false()`

**Expected Behavior**:
- `process_sync()` checks for `memberships` field
- Field is missing, so returns `false`
- User is NOT logged in
- Error is logged

**Why it matters**: The `memberships` field is critical for determining user roles and permissions. Without it, we can't properly configure the user's access. Logging them in with incomplete data could grant wrong permissions.

---

### Scenario 6: API Returns User Not Found

**What happens**: User tries to log in via CI Logon. The CI Logon server authenticates them, but when we query the Profiles API for their details, the API returns error code 1005 (user not found).

**API Response**: HTTP 200 with error code in response

**HTTP Code**: 200

**Body**:
```json
{
  "meta": {
    "error": {
      "code": 1005,
      "message": "User not found"
    }
  }
}
```

**Test**: `test_user_not_found_in_api_prevents_login()`

**Expected Behavior**:
- `process_sync()` checks for error code 1005
- Error code found, so returns `false`
- User is NOT logged in
- Error is logged with username

**Why it matters**: If the user doesn't exist in the API, we shouldn't create them in WordPress. This prevents unauthorized account creation.

---

## HTTP Status Code Scenarios

### Scenario 7: API Returns 400 Bad Request

**What happens**: The request to the API was malformed.

**API Response**: HTTP 400 Bad Request

**Test**: `test_http_400_response_returns_false()`

**Expected Behavior**: Returns `false`, user NOT logged in

---

### Scenario 8: API Returns 401 Unauthorized

**What happens**: The authentication token for the API is invalid or expired.

**API Response**: HTTP 401 Unauthorized

**Test**: `test_http_401_response_returns_false()`

**Expected Behavior**: Returns `false`, user NOT logged in

---

### Scenario 9: API Returns 403 Forbidden

**What happens**: The API key doesn't have permission to access this endpoint.

**API Response**: HTTP 403 Forbidden

**Test**: `test_http_403_response_returns_false()`

**Expected Behavior**: Returns `false`, user NOT logged in

---

### Scenario 10: API Returns 404 Not Found

**What happens**: The API endpoint doesn't exist (misconfiguration).

**API Response**: HTTP 404 Not Found

**Test**: `test_http_404_response_returns_false()`

**Expected Behavior**: Returns `false`, user NOT logged in

---

### Scenario 11: API Returns 500 Internal Server Error

**What happens**: The API server encountered an unexpected error.

**API Response**: HTTP 500 Internal Server Error

**Test**: `test_http_500_response_returns_false()`

**Expected Behavior**: Returns `false`, user NOT logged in

---

### Scenario 12: API Returns 502 Bad Gateway

**What happens**: The API server is behind a reverse proxy that encountered an error.

**API Response**: HTTP 502 Bad Gateway

**Test**: `test_http_502_response_returns_false()`

**Expected Behavior**: Returns `false`, user NOT logged in

---

## Edge Cases

### Scenario 13: API Response is Empty

**What happens**: The API returns HTTP 200 but with an empty body.

**API Response**: HTTP 200 with empty body

**Test**: `test_empty_json_response_returns_false()`

**Expected Behavior**: Returns `false`, user NOT logged in

---

### Scenario 14: Response Uses Alternative Format (results instead of data)

**What happens**: The API returns HTTP 200 with valid JSON using the `results` field instead of `data` field.

**API Response**: HTTP 200 with `results` field

**Body**:
```json
{
  "results": {
    "username": "testuser",
    "email": "testuser@example.com",
    "first_name": "Test",
    "last_name": "User",
    "memberships": {
      "SOCIETY_A": true
    }
  }
}
```

**Test**: `test_http_200_with_results_field_instead_of_data()`

**Expected Behavior**: Should handle both `data` and `results` formats

---

### Scenario 15: Response Contains Unicode Characters

**What happens**: The API returns user data with Unicode characters (e.g., non-ASCII names).

**API Response**: HTTP 200 with Unicode in user data

**Body**:
```json
{
  "data": [{
    "profile": {
      "username": "testuser",
      "email": "testuser@example.com",
      "first_name": "Tëst",
      "last_name": "用户",
      "memberships": {
        "SOCIÉTÉ_A": true
      }
    }
  }]
}
```

**Test**: `test_unicode_characters_in_response_are_handled()`

**Expected Behavior**: Should handle Unicode without issues

---

### Scenario 16: Response Contains Very Large Payload

**What happens**: The API returns HTTP 200 but with a very large response body (e.g., 10MB).

**API Response**: HTTP 200 with large body

**Test**: `test_large_response_body_is_handled()`

**Expected Behavior**: Should handle large payloads without crashing or hanging

---

## Security Attack Scenarios

### Scenario 17: Response Contains XSS Payload

**What happens**: An attacker has compromised the API and injected JavaScript code into user data.

**API Response**: HTTP 200 with XSS payload in user data

**Body**:
```json
{
  "data": [{
    "profile": {
      "username": "testuser<script>alert('xss')</script>",
      "email": "testuser@example.com",
      "first_name": "Test",
      "last_name": "User",
      "memberships": {
        "SOCIETY_A": true
      }
    }
  }]
}
```

**Test**: `test_xss_payload_in_response_prevents_login()`

**Expected Behavior**: 
- The payload should not be executed
- WordPress should sanitize the data before storing
- User should not be logged in if data is invalid

---

### Scenario 18: Response Contains SQL Injection Payload

**What happens**: An attacker has compromised the API and injected SQL code into user data.

**API Response**: HTTP 200 with SQL injection payload

**Body**:
```json
{
  "data": [{
    "profile": {
      "username": "testuser'; DROP TABLE users; --",
      "email": "testuser@example.com",
      "first_name": "Test",
      "last_name": "User",
      "memberships": {
        "SOCIETY_A": true
      }
    }
  }]
}
```

**Test**: `test_sql_injection_payload_in_response_prevents_login()`

**Expected Behavior**:
- WordPress prepared statements should prevent SQL injection
- User should not be logged in if data is invalid

---

## Test Execution Matrix

| Scenario | Test Method | HTTP Code | Expected Result |
|----------|-------------|-----------|-----------------|
| DNS Down | `test_dns_failure_http_0_returns_false()` | 0 | `false` |
| API Down | `test_http_503_response_returns_false()` | 503 | `false` |
| Timeout | `test_http_504_response_returns_false()` | 504 | `false` |
| Bad JSON | `test_corrupted_api_response_prevents_login()` | 200 | `false` |
| Missing Fields | `test_incomplete_api_response_prevents_login()` | 200 | `false` |
| User Not Found | `test_user_not_found_in_api_prevents_login()` | 200 | `false` |
| Bad Request | `test_http_400_response_returns_false()` | 400 | `false` |
| Unauthorized | `test_http_401_response_returns_false()` | 401 | `false` |
| Forbidden | `test_http_403_response_returns_false()` | 403 | `false` |
| Not Found | `test_http_404_response_returns_false()` | 404 | `false` |
| Server Error | `test_http_500_response_returns_false()` | 500 | `false` |
| Bad Gateway | `test_http_502_response_returns_false()` | 502 | `false` |

## Running Specific Scenarios

To test a specific scenario:

```bash
# Test DNS failure
phpunit --filter test_dns_failure_http_0_returns_false

# Test API server down
phpunit --filter test_api_server_down_prevents_login

# Test all timeout scenarios
phpunit --filter timeout

# Test all security scenarios
phpunit --filter security
```

## Interpreting Test Results

### All Tests Pass ✅

```
OK (50 tests, 50 assertions)
```

This means:
- All failure scenarios properly prevent login
- The plugin is secure against API failures
- No unauthorized access is possible when API is unavailable

### Tests Fail ❌

```
FAIL: test_http_503_response_returns_false
AssertionError: process_sync should return false for HTTP 503
Failed asserting that true is false.
```

This means:
- The plugin is NOT properly handling HTTP 503 errors
- Users could be logged in even when the API is down
- **This is a critical security issue that must be fixed**

## Next Steps

1. **Run all tests**: `composer test`
2. **Review failures**: Check which scenarios are failing
3. **Fix the code**: Update `Plugin::process_sync()` to handle failures
4. **Re-run tests**: Verify all tests pass
5. **Deploy**: Deploy the fixed code to production

## Questions?

See `README.md` for detailed documentation and troubleshooting.
