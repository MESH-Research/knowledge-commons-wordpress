<?php

use PHPUnit\Framework\TestCase;

/**
 * /.version.php reports the build metadata from .version.json plus two
 * runtime facts: the PHP version serving the request and the ECS service
 * name (wp-blue / wp-green) from the task metadata endpoint.
 */
class VersionEndpointTest extends TestCase {

	private const FILE = __DIR__ . '/../../site/web/.version.php';

	private const BUILD_JSON = '{"version":"1.6.0","build":"1.6.0-abc123","sha":"abc123","branch":"production"}';

	public static function setUpBeforeClass(): void {
		// The file must be requirable without handling a request: under
		// PHPUnit it is not the executing script, so it must stay silent.
		ob_start();
		require_once self::FILE;
		$output = ob_get_clean();
		self::assertSame( '', $output, 'requiring .version.php must not emit output' );
	}

	/** A fake HTTP client that records the URL and returns a canned body. */
	private function http( ?string $body, array &$calls = null ): callable {
		$calls = [];
		return function ( string $url ) use ( $body, &$calls ) {
			$calls[] = $url;
			return $body;
		};
	}

	// --- ECS service name -------------------------------------------------

	public function test_service_name_is_null_without_metadata_uri(): void {
		$calls = [];
		$http  = $this->http( '{"ServiceName":"wp-blue"}', $calls );

		$this->assertNull( kc_version_ecs_service_name( $http, null ) );
		$this->assertNull( kc_version_ecs_service_name( $http, '' ) );
		$this->assertSame( [], $calls, 'no HTTP call should be made without a metadata URI' );
	}

	public function test_service_name_comes_from_task_metadata(): void {
		$calls = [];
		$http  = $this->http( '{"ServiceName":"wp-green","TaskARN":"arn:aws:ecs:..."}', $calls );

		$this->assertSame( 'wp-green', kc_version_ecs_service_name( $http, 'http://169.254.170.2/v4/abc' ) );
		$this->assertSame( [ 'http://169.254.170.2/v4/abc/task' ], $calls );
	}

	public function test_service_name_is_null_when_request_fails(): void {
		$this->assertNull( kc_version_ecs_service_name( $this->http( null ), 'http://169.254.170.2/v4/abc' ) );
	}

	public function test_service_name_is_null_on_malformed_json(): void {
		$this->assertNull( kc_version_ecs_service_name( $this->http( 'not json' ), 'http://169.254.170.2/v4/abc' ) );
	}

	public function test_service_name_is_null_when_key_missing_or_not_a_string(): void {
		$this->assertNull( kc_version_ecs_service_name( $this->http( '{"Other":"x"}' ), 'http://169.254.170.2/v4/abc' ) );
		$this->assertNull( kc_version_ecs_service_name( $this->http( '{"ServiceName":42}' ), 'http://169.254.170.2/v4/abc' ) );
		$this->assertNull( kc_version_ecs_service_name( $this->http( '{"ServiceName":""}' ), 'http://169.254.170.2/v4/abc' ) );
	}

	// --- Payload ----------------------------------------------------------

	public function test_payload_keeps_build_metadata_and_adds_php_and_service(): void {
		$payload = kc_version_payload( self::BUILD_JSON, '8.5.12', 'wp-blue' );

		$this->assertSame( '1.6.0', $payload['version'] );
		$this->assertSame( '1.6.0-abc123', $payload['build'] );
		$this->assertSame( 'abc123', $payload['sha'] );
		$this->assertSame( 'production', $payload['branch'] );
		$this->assertSame( '8.5.12', $payload['php'] );
		$this->assertSame( 'wp-blue', $payload['service'] );
		$this->assertArrayNotHasKey( 'error', $payload );
	}

	public function test_payload_reports_null_service_when_unknown(): void {
		$payload = kc_version_payload( self::BUILD_JSON, '8.5.12', null );

		$this->assertArrayHasKey( 'service', $payload );
		$this->assertNull( $payload['service'] );
	}

	public function test_payload_without_version_file_reports_unknowns_and_error(): void {
		$payload = kc_version_payload( null, '8.5.12', 'wp-green' );

		foreach ( [ 'version', 'build', 'sha', 'branch' ] as $key ) {
			$this->assertSame( 'unknown', $payload[ $key ], $key );
		}
		$this->assertArrayHasKey( 'error', $payload );
		$this->assertSame( '8.5.12', $payload['php'] );
		$this->assertSame( 'wp-green', $payload['service'] );
	}

	public function test_payload_treats_malformed_version_file_as_missing(): void {
		$payload = kc_version_payload( '{not json', '8.5.12', null );

		$this->assertSame( 'unknown', $payload['version'] );
		$this->assertArrayHasKey( 'error', $payload );
		$this->assertSame( '8.5.12', $payload['php'] );
	}

	public function test_payload_is_json_encodable(): void {
		$json = json_encode( kc_version_payload( self::BUILD_JSON, '8.5.12', 'wp-blue' ) );

		$this->assertIsString( $json );
		$decoded = json_decode( $json, true );
		$this->assertSame( '8.5.12', $decoded['php'] );
		$this->assertSame( 'wp-blue', $decoded['service'] );
	}
}
