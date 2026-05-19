<?php
/**
 * Unit Tests for Open Redirect Prevention
 *
 * Tests verify that redirect URLs are properly validated to prevent
 * open redirect vulnerabilities.
 *
 * @package MeshResearch\CILogon\Tests
 */

namespace MeshResearch\CILogon\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test suite for Open Redirect Prevention
 *
 * SECURITY: This is a regression test suite to ensure that user-provided
 * redirect URLs are validated and cannot be used to redirect users to
 * external malicious sites.
 */
class OpenRedirectTest extends TestCase
{
    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any stored redirect data
        unset($GLOBALS['_wp_safe_redirect_location']);
        unset($GLOBALS['_wp_redirect_location']);
        // Note: Redirect storage now uses WordPress transients instead of $_SESSION
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['_wp_safe_redirect_location']);
        unset($GLOBALS['_wp_redirect_location']);
        parent::tearDown();
    }

    // ========================================================================
    // wp_validate_redirect FUNCTION TESTS
    // ========================================================================

    /**
     * Test: Relative URLs are allowed
     *
     * @test
     */
    public function test_relative_url_is_allowed()
    {
        $result = wp_validate_redirect('/some-page/', home_url());

        $this->assertEquals('/some-page/', $result);
    }

    /**
     * Test: Relative URL with query string is allowed
     *
     * @test
     */
    public function test_relative_url_with_query_is_allowed()
    {
        $result = wp_validate_redirect('/search?q=test', home_url());

        $this->assertEquals('/search?q=test', $result);
    }

    /**
     * Test: Same-site absolute URL is allowed
     *
     * @test
     */
    public function test_same_site_absolute_url_is_allowed()
    {
        $result = wp_validate_redirect('https://example.com/page/', home_url());

        $this->assertEquals('https://example.com/page/', $result);
    }

    /**
     * Test: External URL is rejected
     *
     * SECURITY: This is the critical test - external URLs must be rejected
     *
     * @test
     */
    public function test_external_url_is_rejected()
    {
        $result = wp_validate_redirect('https://evil.com/phishing', home_url());

        $this->assertEquals(home_url(), $result, 'SECURITY: External URLs must be rejected');
    }

    /**
     * Test: External URL with similar domain is rejected
     *
     * @test
     */
    public function test_similar_domain_is_rejected()
    {
        $result = wp_validate_redirect('https://example.com.evil.com/', home_url());

        $this->assertEquals(home_url(), $result, 'SECURITY: Similar domains must be rejected');
    }

    /**
     * Test: Protocol-relative URL to external site is rejected
     *
     * @test
     */
    public function test_protocol_relative_external_url_is_rejected()
    {
        $result = wp_validate_redirect('//evil.com/page', home_url());

        $this->assertEquals(home_url(), $result, 'SECURITY: Protocol-relative external URLs must be rejected');
    }

    /**
     * Test: Empty URL returns fallback
     *
     * @test
     */
    public function test_empty_url_returns_fallback()
    {
        $result = wp_validate_redirect('', home_url());

        $this->assertEquals(home_url(), $result);
    }

    /**
     * Test: URL with javascript protocol is rejected
     *
     * @test
     */
    public function test_javascript_url_is_rejected()
    {
        $result = wp_validate_redirect('javascript:alert(1)', home_url());

        $this->assertEquals(home_url(), $result, 'SECURITY: JavaScript URLs must be rejected');
    }

    /**
     * Test: URL with data protocol is rejected
     *
     * @test
     */
    public function test_data_url_is_rejected()
    {
        $result = wp_validate_redirect('data:text/html,<script>alert(1)</script>', home_url());

        $this->assertEquals(home_url(), $result, 'SECURITY: Data URLs must be rejected');
    }

    // ========================================================================
    // wp_sanitize_redirect FUNCTION TESTS
    // ========================================================================

    /**
     * Test: Newlines are stripped to prevent header injection
     *
     * @test
     */
    public function test_newlines_are_stripped()
    {
        $malicious = "/page\r\nSet-Cookie: evil=value";
        $result = wp_sanitize_redirect($malicious);

        $this->assertStringNotContainsString("\r", $result);
        $this->assertStringNotContainsString("\n", $result);
    }

    /**
     * Test: Whitespace is trimmed
     *
     * @test
     */
    public function test_whitespace_is_trimmed()
    {
        $result = wp_sanitize_redirect('  /page/  ');

        $this->assertEquals('/page/', $result);
    }

    // ========================================================================
    // INTEGRATION TESTS WITH CILogonAuth
    // ========================================================================

    /**
     * Test: Malicious redirect_to is sanitized when stored
     *
     * Simulates storing a malicious redirect URL and verifies it's validated
     *
     * @test
     */
    public function test_malicious_redirect_is_blocked_on_storage()
    {
        // Simulate what happens in CILogonAuth::handle_cilogon()
        $malicious_url = 'https://evil.com/phishing';

        $validated = wp_validate_redirect(
            wp_sanitize_redirect($malicious_url),
            home_url()
        );

        // Should fall back to home_url(), not the malicious URL
        $this->assertEquals(home_url(), $validated);
        $this->assertNotEquals($malicious_url, $validated);
    }

    /**
     * Test: Valid local redirect_to is preserved when stored
     *
     * @test
     */
    public function test_valid_redirect_is_preserved()
    {
        $valid_url = '/my-account/settings/';

        $validated = wp_validate_redirect(
            wp_sanitize_redirect($valid_url),
            home_url()
        );

        $this->assertEquals($valid_url, $validated);
    }

    /**
     * Test: Protocol downgrade attack is prevented
     *
     * @test
     */
    public function test_http_to_javascript_is_rejected()
    {
        // Some attacks try to use URL encoding or other tricks
        $attack = 'javascript:alert(document.cookie)';

        $validated = wp_validate_redirect(
            wp_sanitize_redirect($attack),
            home_url()
        );

        $this->assertEquals(home_url(), $validated, 'SECURITY: JavaScript protocol must be rejected');
    }

    /**
     * Test: Multiple redirect attempts don't bypass validation
     *
     * @test
     */
    public function test_double_encoding_attack_is_prevented()
    {
        // Attacker might try to double-encode
        $attack = 'https%3A%2F%2Fevil.com';

        $validated = wp_validate_redirect(
            wp_sanitize_redirect($attack),
            home_url()
        );

        // The encoded URL doesn't have a valid scheme so it should be rejected
        $this->assertEquals(home_url(), $validated);
    }

    // ========================================================================
    // SPECIFIC ATTACK VECTOR TESTS
    // ========================================================================

    /**
     * Test: Backslash URL bypass is prevented
     *
     * Some parsers treat backslash as forward slash
     *
     * @test
     */
    public function test_backslash_bypass_is_prevented()
    {
        $attack = 'https://example.com\\@evil.com/';

        $validated = wp_validate_redirect(
            wp_sanitize_redirect($attack),
            home_url()
        );

        // If this contains evil.com anywhere it should be rejected
        // Note: behavior depends on how parse_url handles backslashes
        $this->assertStringNotContainsString('evil.com', $validated);
    }

    /**
     * Test: @ symbol URL bypass is prevented
     *
     * URLs like https://good.com@evil.com actually go to evil.com
     *
     * @test
     */
    public function test_at_symbol_bypass_is_prevented()
    {
        $attack = 'https://example.com@evil.com/page';

        $validated = wp_validate_redirect(
            wp_sanitize_redirect($attack),
            home_url()
        );

        // parse_url will see evil.com as the host
        $this->assertEquals(home_url(), $validated, 'SECURITY: @ symbol bypass must be prevented');
    }

    /**
     * Test: Subdomain confusion is prevented
     *
     * example.com.evil.com is NOT a subdomain of example.com
     *
     * @test
     */
    public function test_subdomain_confusion_is_prevented()
    {
        $attack = 'https://example.com.evil.com/';

        $validated = wp_validate_redirect(
            wp_sanitize_redirect($attack),
            home_url()
        );

        $this->assertEquals(home_url(), $validated, 'SECURITY: Subdomain confusion must be prevented');
    }

    /**
     * Test: Fragment-only URLs are handled
     *
     * @test
     */
    public function test_fragment_only_url_uses_fallback()
    {
        $result = wp_validate_redirect('#section', home_url());

        // Fragment-only URLs don't have a path starting with /
        // Behavior should be to use fallback
        $this->assertEquals(home_url(), $result);
    }

    /**
     * Test: Deep relative path is allowed
     *
     * @test
     */
    public function test_deep_relative_path_is_allowed()
    {
        $result = wp_validate_redirect('/a/b/c/d/e/page/', home_url());

        $this->assertEquals('/a/b/c/d/e/page/', $result);
    }
}
