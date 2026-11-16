<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../mu-plugins/opcache-status.php';

if ( ! function_exists( 'cilogon_verify_bearer_token' ) ) {
	/**
	 * Stand-in for the ci-logon permission callback. Result injected per test.
	 */
	function cilogon_verify_bearer_token( $request ) {
		return $GLOBALS['_mock_bearer_result'] ?? new WP_Error( 'rest_forbidden', 'no token', [ 'status' => 401 ] );
	}
}

/**
 * /wp-json/idms/opcache reports a compact, unit-normalised summary of the
 * OPcache so operators can watch hit rate, memory and restarts in production.
 */
class OpcacheStatusTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_registered_rest_routes'] = [];
		$GLOBALS['_mock_bearer_result']     = null;
		// Capability stub comes from group-permissions-loader.php (_hc_mock).
		$GLOBALS['_hc_mock']['user_can']    = [];
	}

	/** A realistic opcache_get_status( false ) payload. */
	private function status( array $overrides = [] ): array {
		return array_replace_recursive(
			[
				'opcache_enabled'        => true,
				'cache_full'             => false,
				'restart_pending'        => false,
				'restart_in_progress'    => false,
				'memory_usage'           => [
					'used_memory'               => 268435456, // 256 MiB
					'free_memory'               => 805306368, // 768 MiB
					'wasted_memory'             => 5242880,   // 5 MiB
					'current_wasted_percentage' => 0.4882,
				],
				'interned_strings_usage' => [
					'buffer_size'  => 67108864,
					'used_memory'  => 16777216, // 16 MiB
					'free_memory'  => 50331648, // 48 MiB
					'number_of_strings' => 120000,
				],
				'opcache_statistics'     => [
					'num_cached_scripts'   => 41230,
					'num_cached_keys'      => 41235,
					'max_cached_keys'      => 130987,
					'hits'                 => 9900,
					'misses'               => 100,
					'blacklist_misses'     => 0,
					'blacklist_miss_ratio' => 0.0,
					'opcache_hit_rate'     => 99.0,
					'start_time'           => 1760500000,
					'last_restart_time'    => 0,
					'oom_restarts'         => 0,
					'hash_restarts'        => 0,
					'manual_restarts'      => 0,
				],
				'jit'                    => [
					'enabled'     => false,
					'on'          => false,
					'kind'        => 0,
					'opt_level'   => 0,
					'opt_flags'   => 0,
					'buffer_size' => 0,
					'buffer_free' => 0,
				],
			],
			$overrides
		);
	}

	// ---------------------------------------------------------------- summarise

	public function test_reports_disabled_when_opcache_is_unavailable(): void {
		$summary = KC_Opcache_Status::summarise( false );

		$this->assertFalse( $summary['enabled'] );
		$this->assertArrayNotHasKey( 'memory', $summary );
	}

	public function test_reports_disabled_when_status_says_so(): void {
		$summary = KC_Opcache_Status::summarise( $this->status( [ 'opcache_enabled' => false ] ) );

		$this->assertFalse( $summary['enabled'] );
	}

	public function test_memory_is_reported_in_mebibytes(): void {
		$summary = KC_Opcache_Status::summarise( $this->status() );

		$this->assertTrue( $summary['enabled'] );
		$this->assertSame( 256.0, $summary['memory']['used_mb'] );
		$this->assertSame( 768.0, $summary['memory']['free_mb'] );
		$this->assertSame( 5.0, $summary['memory']['wasted_mb'] );
		$this->assertSame( 0.49, $summary['memory']['wasted_percent'] );
		$this->assertSame( 16.0, $summary['interned_strings']['used_mb'] );
		$this->assertSame( 48.0, $summary['interned_strings']['free_mb'] );
	}

	public function test_script_counts_and_capacity_are_reported(): void {
		$summary = KC_Opcache_Status::summarise( $this->status() );

		$this->assertSame( 41230, $summary['scripts']['cached'] );
		$this->assertSame( 130987, $summary['scripts']['max'] );
	}

	public function test_hit_rate_is_reported_to_two_decimals(): void {
		$summary = KC_Opcache_Status::summarise(
			$this->status( [ 'opcache_statistics' => [ 'hits' => 2, 'misses' => 1, 'opcache_hit_rate' => 66.66666 ] ] )
		);

		$this->assertSame( 2, $summary['hits'] );
		$this->assertSame( 1, $summary['misses'] );
		$this->assertSame( 66.67, $summary['hit_rate_percent'] );
	}

	public function test_restart_counters_are_surfaced(): void {
		$summary = KC_Opcache_Status::summarise(
			$this->status( [
				'cache_full'         => true,
				'opcache_statistics' => [ 'oom_restarts' => 3, 'hash_restarts' => 1, 'manual_restarts' => 2, 'last_restart_time' => 1760503600 ],
			] )
		);

		$this->assertTrue( $summary['cache_full'] );
		$this->assertSame( 3, $summary['restarts']['oom'] );
		$this->assertSame( 1, $summary['restarts']['hash'] );
		$this->assertSame( 2, $summary['restarts']['manual'] );
		$this->assertSame( 1760503600, $summary['last_restart_at'] );
		$this->assertSame( 1760500000, $summary['started_at'] );
	}

	public function test_jit_flag_is_reported_and_tolerates_its_absence(): void {
		$with    = KC_Opcache_Status::summarise( $this->status( [ 'jit' => [ 'enabled' => true, 'on' => true ] ] ) );
		$status  = $this->status();
		unset( $status['jit'] );
		$without = KC_Opcache_Status::summarise( $status );

		$this->assertTrue( $with['jit'] );
		$this->assertFalse( $without['jit'] );
	}

	// ----------------------------------------------------------- REST plumbing

	public function test_handle_request_returns_the_summary_of_the_given_status(): void {
		$result = KC_Opcache_Status::handle_request( new WP_REST_Request(), $this->status() );

		$this->assertTrue( $result['enabled'] );
		$this->assertSame( 41230, $result['scripts']['cached'] );
	}

	public function test_permission_granted_with_valid_bearer_token(): void {
		$GLOBALS['_mock_bearer_result'] = true;

		$this->assertTrue( KC_Opcache_Status::permission( new WP_REST_Request() ) );
	}

	public function test_permission_granted_to_network_administrators(): void {
		$GLOBALS['_hc_mock']['user_can'] = [ 'manage_network' => true ];

		$this->assertTrue( KC_Opcache_Status::permission( new WP_REST_Request() ) );
	}

	public function test_permission_denied_to_anonymous_requests(): void {
		$result = KC_Opcache_Status::permission( new WP_REST_Request() );

		$this->assertNotTrue( $result );
	}

	public function test_register_routes_registers_idms_opcache_route(): void {
		KC_Opcache_Status::register_routes();

		$found = false;
		foreach ( $GLOBALS['_registered_rest_routes'] as $r ) {
			if ( 'idms' === $r['namespace'] && '/opcache' === $r['route'] ) {
				$found = true;
				$this->assertSame( 'GET', $r['args']['methods'] );
				$this->assertIsCallable( $r['args']['callback'] );
				$this->assertIsCallable( $r['args']['permission_callback'] );
				$this->assertNotSame( '__return_true', $r['args']['permission_callback'], 'the status must not be public' );
				break;
			}
		}
		$this->assertTrue( $found, 'expected an idms /opcache route to be registered' );
	}
}
