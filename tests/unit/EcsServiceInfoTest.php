<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../mu-plugins/ecs-service-info.php';

class EcsServiceInfoTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_mock_transients']             = [];
		$GLOBALS['_mock_wp_remote_get_callback'] = null;
		$GLOBALS['_registered_rest_routes']      = [];
		putenv( 'ECS_CONTAINER_METADATA_URI_V4' );
	}

	protected function tearDown(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4' );
		parent::tearDown();
	}

	private function mockHttp( callable $cb ): void {
		$GLOBALS['_mock_wp_remote_get_callback'] = $cb;
	}

	private function ok( string $body, int $code = 200 ): array {
		return [ 'response' => [ 'code' => $code ], 'body' => $body ];
	}

	public function test_returns_null_when_env_var_unset(): void {
		$this->assertNull( KC_Ecs_Service_Info::fetch_service_name() );
	}

	public function test_returns_service_name_on_success(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4=http://169.254.170.2/v4/abc' );
		$this->mockHttp( function ( $url, $args ) {
			$this->assertSame( 'http://169.254.170.2/v4/abc/task', $url );
			return $this->ok( '{"ServiceName":"wp-staging","TaskARN":"arn:aws:..."}' );
		} );
		$this->assertSame( 'wp-staging', KC_Ecs_Service_Info::fetch_service_name() );
	}

	public function test_returns_null_on_wp_error(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4=http://169.254.170.2/v4/abc' );
		$this->mockHttp( fn() => new WP_Error( 'http_request_failed', 'connection refused' ) );
		$this->assertNull( KC_Ecs_Service_Info::fetch_service_name() );
	}

	public function test_returns_null_on_non_200_status(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4=http://169.254.170.2/v4/abc' );
		$this->mockHttp( fn() => $this->ok( '{"ServiceName":"x"}', 500 ) );
		$this->assertNull( KC_Ecs_Service_Info::fetch_service_name() );
	}

	public function test_returns_null_on_malformed_json(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4=http://169.254.170.2/v4/abc' );
		$this->mockHttp( fn() => $this->ok( 'not json' ) );
		$this->assertNull( KC_Ecs_Service_Info::fetch_service_name() );
	}

	public function test_returns_null_when_servicename_key_missing(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4=http://169.254.170.2/v4/abc' );
		$this->mockHttp( fn() => $this->ok( '{"OtherField":"x"}' ) );
		$this->assertNull( KC_Ecs_Service_Info::fetch_service_name() );
	}

	public function test_uses_transient_on_subsequent_call(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4=http://169.254.170.2/v4/abc' );
		$call_count = 0;
		$this->mockHttp( function () use ( &$call_count ) {
			$call_count++;
			return $this->ok( '{"ServiceName":"wp-prod"}' );
		} );

		$this->assertSame( 'wp-prod', KC_Ecs_Service_Info::fetch_service_name() );
		$this->assertSame( 'wp-prod', KC_Ecs_Service_Info::fetch_service_name() );
		$this->assertSame( 1, $call_count, 'second call should hit the transient cache, not HTTP' );
	}

	public function test_handle_request_returns_service_array(): void {
		putenv( 'ECS_CONTAINER_METADATA_URI_V4=http://169.254.170.2/v4/abc' );
		$this->mockHttp( fn() => $this->ok( '{"ServiceName":"wp-staging"}' ) );

		$result = KC_Ecs_Service_Info::handle_request( new WP_REST_Request() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'service', $result );
		$this->assertSame( 'wp-staging', $result['service'] );
	}

	public function test_handle_request_returns_null_service_when_unconfigured(): void {
		$result = KC_Ecs_Service_Info::handle_request( new WP_REST_Request() );
		$this->assertSame( [ 'service' => null ], $result );
	}

	public function test_register_routes_registers_idms_service_route(): void {
		KC_Ecs_Service_Info::register_routes();

		$found = false;
		foreach ( $GLOBALS['_registered_rest_routes'] as $r ) {
			if ( 'idms' === $r['namespace'] && '/service' === $r['route'] ) {
				$found = true;
				$this->assertSame( 'GET', $r['args']['methods'] );
				$this->assertSame( '__return_true', $r['args']['permission_callback'] );
				$this->assertIsCallable( $r['args']['callback'] );
				break;
			}
		}
		$this->assertTrue( $found, 'expected an idms /service route to be registered' );
	}
}
