<?php
/**
 * The plugin must not write a log line on every request when nothing is
 * wrong. On 2026-10-15 two such lines produced 37,000 messages in 45 minutes
 * of production log, each a synchronous write to the container log driver.
 *
 * @package MeshResearch\CILogon\Tests
 */

use PHPUnit\Framework\TestCase;
use MeshResearch\CILogon\Plugin;
use MeshResearch\CILogon\BrokerAuth;

class PerRequestLoggingTest extends TestCase
{
    private string|false $originalProfilesUrl;
    private string|false $originalBearerToken;
    private string|false $originalSilentSSO;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalProfilesUrl = getenv('PROFILES_API_URL');
        $this->originalBearerToken = getenv('PROFILES_API_BEARER_TOKEN');
        $this->originalSilentSSO = getenv('BROKER_SILENT_SSO_ENABLED');
        $GLOBALS['_cilogon_logged'] = [];
        $GLOBALS['_mock_is_user_logged_in'] = false;
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        foreach ([
            'PROFILES_API_URL' => $this->originalProfilesUrl,
            'PROFILES_API_BEARER_TOKEN' => $this->originalBearerToken,
            'BROKER_SILENT_SSO_ENABLED' => $this->originalSilentSSO,
        ] as $name => $value) {
            putenv(false === $value ? $name : "$name=$value");
        }
        parent::tearDown();
    }

    private function checkConfiguration(): void
    {
        $method = new \ReflectionMethod(Plugin::class, 'check_configuration');
        $method->invoke(Plugin::get_instance());
    }

    private function logged(): array
    {
        return $GLOBALS['_cilogon_logged'] ?? [];
    }

    public function test_valid_configuration_logs_nothing(): void
    {
        putenv('PROFILES_API_URL=https://profiles.example.com/');
        putenv('PROFILES_API_BEARER_TOKEN=secret');

        $this->checkConfiguration();

        $this->assertSame([], $this->logged());
    }

    public function test_missing_configuration_is_still_reported(): void
    {
        putenv('PROFILES_API_URL');
        putenv('PROFILES_API_BEARER_TOKEN');

        $this->checkConfiguration();

        $this->assertCount(1, $this->logged());
        $this->assertStringContainsString('Missing required environment variables', $this->logged()[0]);
    }

    public function test_silent_sso_exclusion_logs_nothing_when_feature_is_off(): void
    {
        putenv('BROKER_SILENT_SSO_ENABLED');
        putenv('PROFILES_API_URL=https://profiles.example.com/');
        putenv('PROFILES_API_BEARER_TOKEN=secret');

        $excluded = (new BrokerAuth())->is_sso_excluded_request();

        $this->assertTrue($excluded);
        $this->assertSame([], $this->logged());
    }

    public function test_silent_sso_exclusion_logs_nothing_for_logged_in_users(): void
    {
        putenv('BROKER_SILENT_SSO_ENABLED=1');
        putenv('PROFILES_API_URL=https://profiles.example.com/');
        putenv('PROFILES_API_BEARER_TOKEN=secret');
        $GLOBALS['_mock_is_user_logged_in'] = true;

        $excluded = (new BrokerAuth())->is_sso_excluded_request();

        $this->assertTrue($excluded);
        $this->assertSame([], $this->logged());
    }
}
