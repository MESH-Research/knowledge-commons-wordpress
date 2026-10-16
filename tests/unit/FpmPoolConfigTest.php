<?php

use PHPUnit\Framework\TestCase;

/**
 * The php-fpm pool must record a stack trace for slow requests and must kill a
 * request on wall-clock time. max_execution_time in php.ini counts CPU time on
 * Linux, so without request_terminate_timeout a worker blocked in a remote
 * call or a lock is never released until nginx gives up.
 */
class FpmPoolConfigTest extends TestCase {

	private const POOL  = __DIR__ . '/../../config/all/php/www.conf';
	private const NGINX = __DIR__ . '/../../config/all/nginx/templates/00-hcommons.conf.template';

	private function pool(): array {
		$this->assertFileExists( self::POOL );
		// RAW: values such as ${PM_MAX_CHILDREN} are env placeholders, not ini syntax.
		$parsed = parse_ini_file( self::POOL, false, INI_SCANNER_RAW );
		$this->assertIsArray( $parsed, 'www.conf must be valid ini syntax' );
		return $parsed;
	}

	/** "150s" → 150, "2m" → 120. */
	private function seconds( string $value ): int {
		$this->assertMatchesRegularExpression( '/^\d+(s|m)?$/', $value );
		$n = (int) $value;
		return str_ends_with( $value, 'm' ) ? $n * 60 : $n;
	}

	public function test_slow_requests_are_traced_to_the_container_log(): void {
		$pool = $this->pool();

		$this->assertArrayHasKey( 'slowlog', $pool );
		$this->assertContains( $pool['slowlog'], [ '/proc/self/fd/2', '/dev/stderr' ], 'the slow log must reach the container log, not a file nobody reads' );
	}

	public function test_slow_threshold_catches_slow_pages_but_not_normal_ones(): void {
		$pool = $this->pool();

		$this->assertArrayHasKey( 'request_slowlog_timeout', $pool );
		$t = $this->seconds( $pool['request_slowlog_timeout'] );
		$this->assertGreaterThanOrEqual( 5, $t );
		$this->assertLessThanOrEqual( 30, $t );
	}

	public function test_access_log_records_host_path_and_timing_per_request(): void {
		$pool = $this->pool();

		$this->assertArrayHasKey( 'access.format', $pool );
		$format = $pool['access.format'];
		$this->assertStringContainsString( '%{HTTP_HOST}e', $format, 'which site' );
		$this->assertStringContainsString( '%{KC_REQUEST_PATH}e', $format, 'which page, without the query string' );
		$this->assertStringContainsString( '%{mili}d', $format, 'wall-clock duration' );
		$this->assertStringContainsString( '%C', $format, 'CPU share, to separate CPU-bound from waiting' );
		$this->assertStringNotContainsString( '%{REQUEST_URI}e', $format, 'REQUEST_URI carries query strings, which can carry tokens' );
	}

	public function test_nginx_passes_the_request_path_without_its_query_string(): void {
		$nginx = file_get_contents( self::NGINX );

		$this->assertMatchesRegularExpression( '/map\s+\$request_uri\s+\$kc_request_path\s*\{/', $nginx );
		$this->assertMatchesRegularExpression( '/fastcgi_param\s+KC_REQUEST_PATH\s+\$kc_request_path;/', $nginx );
	}

	public function test_requests_are_killed_on_wall_clock_before_nginx_gives_up(): void {
		$pool = $this->pool();

		$this->assertArrayHasKey( 'request_terminate_timeout', $pool );
		$terminate = $this->seconds( $pool['request_terminate_timeout'] );

		$nginx = file_get_contents( self::NGINX );
		$this->assertMatchesRegularExpression( '/fastcgi_read_timeout\s+(\d+)s;/', $nginx );
		preg_match( '/fastcgi_read_timeout\s+(\d+)s;/', $nginx, $m );
		$nginx_read = (int) $m[1];

		$this->assertGreaterThanOrEqual( 120, $terminate, 'must not cut off legitimate long admin requests' );
		$this->assertLessThan( $nginx_read, $terminate, 'php-fpm must give up before nginx does, so the worker is freed' );
	}
}
