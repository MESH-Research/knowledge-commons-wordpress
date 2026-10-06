<?php

use PHPUnit\Framework\TestCase;

/**
 * The container entrypoints must refuse to start when a static AWS access
 * key pair is present in the environment, and must start normally when the
 * environment carries no static credentials (for example when running under
 * an ECS task role).
 */
class DockerEntrypointAwsCredentialsTest extends TestCase {

	private const SCRIPTS_DIR = __DIR__ . '/../../scripts/build-scripts';

	/**
	 * Exit status an entrypoint uses to signal a refused start.
	 * 78 is EX_CONFIG from sysexits(3): "configuration error".
	 */
	private const EXIT_REFUSED = 78;

	private string $tmp;

	protected function setUp(): void {
		parent::setUp();
		$this->tmp = sys_get_temp_dir() . '/kc-entrypoint-' . bin2hex( random_bytes( 6 ) );
		mkdir( $this->tmp . '/bin', 0755, true );

		// Stubs for binaries the cron entrypoint expects to find on PATH.
		// Each records that it was reached by touching a marker file.
		foreach ( [ 'crontab', 'crond' ] as $bin ) {
			$path = $this->tmp . '/bin/' . $bin;
			file_put_contents( $path, "#!/bin/sh\ntouch \"{$this->tmp}/{$bin}.ran\"\nexit 0\n" );
			chmod( $path, 0755 );
		}
	}

	protected function tearDown(): void {
		foreach ( glob( $this->tmp . '/bin/*' ) ?: [] as $f ) {
			unlink( $f );
		}
		foreach ( glob( $this->tmp . '/*' ) ?: [] as $f ) {
			is_dir( $f ) ? rmdir( $f ) : unlink( $f );
		}
		rmdir( $this->tmp );
		parent::tearDown();
	}

	/**
	 * Run an entrypoint with a fully controlled environment.
	 *
	 * @return array{0:int,1:string} exit status and combined stdout/stderr.
	 */
	private function run_entrypoint( string $script, array $env, array $args = [] ): array {
		$base_env = [
			'PATH' => $this->tmp . '/bin:' . getenv( 'PATH' ),
			'HOME' => $this->tmp,
		];
		$cmd = array_merge( [ 'sh', self::SCRIPTS_DIR . '/' . $script ], $args );

		$proc = proc_open(
			$cmd,
			[ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ],
			$pipes,
			$this->tmp,
			array_merge( $base_env, $env )
		);
		$this->assertIsResource( $proc );

		$out = stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );

		return [ proc_close( $proc ), $out ];
	}

	private function marker( string $name ): string {
		return $this->tmp . '/' . $name;
	}

	private function static_credentials(): array {
		return [
			'AWS_ACCESS_KEY_ID'     => 'AKIAEXAMPLEEXAMPLE',
			'AWS_SECRET_ACCESS_KEY' => 'not-a-real-secret',
		];
	}

	// ---------------------------------------------------------------------
	// php-fpm entrypoint
	// ---------------------------------------------------------------------

	public function test_php_entrypoint_refuses_to_start_with_static_credentials(): void {
		[ $status ] = $this->run_entrypoint(
			'docker-php-entrypoint.sh',
			$this->static_credentials(),
			[ 'touch', $this->marker( 'php.ran' ) ]
		);

		$this->assertSame( self::EXIT_REFUSED, $status );
		$this->assertFileDoesNotExist( $this->marker( 'php.ran' ), 'php-fpm must not be exec\'d' );
	}

	public function test_php_entrypoint_refuses_with_only_access_key_id(): void {
		[ $status ] = $this->run_entrypoint(
			'docker-php-entrypoint.sh',
			[ 'AWS_ACCESS_KEY_ID' => 'AKIAEXAMPLEEXAMPLE' ],
			[ 'touch', $this->marker( 'php.ran' ) ]
		);

		$this->assertSame( self::EXIT_REFUSED, $status );
		$this->assertFileDoesNotExist( $this->marker( 'php.ran' ) );
	}

	public function test_php_entrypoint_refuses_with_only_secret_access_key(): void {
		[ $status ] = $this->run_entrypoint(
			'docker-php-entrypoint.sh',
			[ 'AWS_SECRET_ACCESS_KEY' => 'not-a-real-secret' ],
			[ 'touch', $this->marker( 'php.ran' ) ]
		);

		$this->assertSame( self::EXIT_REFUSED, $status );
		$this->assertFileDoesNotExist( $this->marker( 'php.ran' ) );
	}

	public function test_php_entrypoint_starts_without_static_credentials(): void {
		[ $status, $out ] = $this->run_entrypoint(
			'docker-php-entrypoint.sh',
			[],
			[ 'touch', $this->marker( 'php.ran' ) ]
		);

		$this->assertSame( 0, $status, $out );
		$this->assertFileExists( $this->marker( 'php.ran' ) );
	}

	public function test_php_entrypoint_starts_under_ecs_task_role(): void {
		// ECS task roles hand out credentials via the metadata endpoint, not
		// via a static key pair. That must not be mistaken for a violation.
		[ $status, $out ] = $this->run_entrypoint(
			'docker-php-entrypoint.sh',
			[
				'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' => '/v2/credentials/abc',
				'AWS_REGION'                             => 'us-east-1',
			],
			[ 'touch', $this->marker( 'php.ran' ) ]
		);

		$this->assertSame( 0, $status, $out );
		$this->assertFileExists( $this->marker( 'php.ran' ) );
	}

	public function test_php_entrypoint_treats_empty_credentials_as_absent(): void {
		[ $status, $out ] = $this->run_entrypoint(
			'docker-php-entrypoint.sh',
			[ 'AWS_ACCESS_KEY_ID' => '', 'AWS_SECRET_ACCESS_KEY' => '' ],
			[ 'touch', $this->marker( 'php.ran' ) ]
		);

		$this->assertSame( 0, $status, $out );
		$this->assertFileExists( $this->marker( 'php.ran' ) );
	}

	// ---------------------------------------------------------------------
	// cron entrypoint
	// ---------------------------------------------------------------------

	public function test_cron_entrypoint_refuses_to_start_with_static_credentials(): void {
		[ $status ] = $this->run_entrypoint( 'docker-cron-entrypoint.sh', $this->static_credentials() );

		$this->assertSame( self::EXIT_REFUSED, $status );
		$this->assertFileDoesNotExist( $this->marker( 'crontab.ran' ), 'crontab must not be installed' );
		$this->assertFileDoesNotExist( $this->marker( 'crond.ran' ), 'crond must not be exec\'d' );
	}

	public function test_cron_entrypoint_proceeds_without_static_credentials(): void {
		// The host has no /app, so the script cannot run to completion here.
		// Reaching the crontab install proves it got past the guard.
		[ $status ] = $this->run_entrypoint( 'docker-cron-entrypoint.sh', [] );

		$this->assertNotSame( self::EXIT_REFUSED, $status );
		$this->assertFileExists( $this->marker( 'crontab.ran' ) );
	}

	// ---------------------------------------------------------------------
	// nginx entrypoint
	// ---------------------------------------------------------------------

	public function test_nginx_entrypoint_refuses_to_start_with_static_credentials(): void {
		[ $status ] = $this->run_entrypoint( 'docker-nginx-entrypoint.sh', $this->static_credentials() );

		$this->assertSame( self::EXIT_REFUSED, $status );
	}

	public function test_nginx_entrypoint_proceeds_without_static_credentials(): void {
		// /docker-entrypoint.sh is absent on the host, so exec fails with a
		// different status. It must not be the refusal status.
		[ $status ] = $this->run_entrypoint( 'docker-nginx-entrypoint.sh', [] );

		$this->assertNotSame( self::EXIT_REFUSED, $status );
	}

	// ---------------------------------------------------------------------
	// The bulk-import script must be gone.
	// ---------------------------------------------------------------------

	public function test_bulk_secret_import_script_is_removed(): void {
		$this->assertFileDoesNotExist( self::SCRIPTS_DIR . '/get-aws-secrets.sh' );
	}
}
