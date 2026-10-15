<?php

use PHPUnit\Framework\TestCase;

/**
 * Both container entrypoints install the Redis object-cache drop-in when a
 * Redis/Valkey host is configured. Both must do it: a container that writes
 * to the database without the drop-in cannot invalidate keys the other
 * container is serving. KC_OBJECT_CACHE=off is the operator's escape hatch.
 */
class DockerEntrypointObjectCacheTest extends TestCase {

	private const SCRIPTS_DIR = __DIR__ . '/../../scripts/build-scripts';

	private string $tmp;

	protected function setUp(): void {
		parent::setUp();
		$this->tmp = sys_get_temp_dir() . '/kc-objcache-' . bin2hex( random_bytes( 6 ) );
		mkdir( $this->tmp . '/bin', 0755, true );
		mkdir( $this->tmp . '/site/web/app/plugins/redis-cache/includes', 0755, true );
		file_put_contents( $this->dropin_source(), "<?php // redis object cache drop-in\n" );

		foreach ( [ 'crontab', 'crond' ] as $bin ) {
			$path = $this->tmp . '/bin/' . $bin;
			file_put_contents( $path, "#!/bin/sh\nexit 0\n" );
			chmod( $path, 0755 );
		}
	}

	protected function tearDown(): void {
		exec( 'rm -rf ' . escapeshellarg( $this->tmp ) );
		parent::tearDown();
	}

	private function dropin_source(): string {
		return $this->tmp . '/site/web/app/plugins/redis-cache/includes/object-cache.php';
	}

	private function dropin_target(): string {
		return $this->tmp . '/site/web/app/object-cache.php';
	}

	/** @return array{0:int,1:string} */
	private function run_entrypoint( string $script, array $env, array $args = [] ): array {
		$base_env = [
			'PATH'     => $this->tmp . '/bin:' . getenv( 'PATH' ),
			'HOME'     => $this->tmp,
			'APP_ROOT' => $this->tmp,
		];
		$proc = proc_open(
			array_merge( [ 'sh', self::SCRIPTS_DIR . '/' . $script ], $args ),
			[ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ],
			$pipes,
			$this->tmp,
			array_merge( $base_env, $env )
		);
		$out = stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		return [ proc_close( $proc ), $out ];
	}

	// ------------------------------------------------------------ php-fpm

	public function test_php_entrypoint_installs_dropin_when_redis_host_is_set(): void {
		[ $status, $out ] = $this->run_entrypoint( 'docker-php-entrypoint.sh', [ 'WP_REDIS_HOST' => 'valkey.internal' ], [ 'true' ] );

		$this->assertSame( 0, $status, $out );
		$this->assertFileExists( $this->dropin_target() );
		$this->assertFileEquals( $this->dropin_source(), $this->dropin_target() );
	}

	public function test_php_entrypoint_skips_dropin_without_redis_host(): void {
		[ $status ] = $this->run_entrypoint( 'docker-php-entrypoint.sh', [], [ 'true' ] );

		$this->assertSame( 0, $status );
		$this->assertFileDoesNotExist( $this->dropin_target() );
	}

	public function test_php_entrypoint_removes_dropin_when_switched_off(): void {
		file_put_contents( $this->dropin_target(), 'stale' );

		[ $status ] = $this->run_entrypoint(
			'docker-php-entrypoint.sh',
			[ 'WP_REDIS_HOST' => 'valkey.internal', 'KC_OBJECT_CACHE' => 'off' ],
			[ 'true' ]
		);

		$this->assertSame( 0, $status );
		$this->assertFileDoesNotExist( $this->dropin_target() );
	}

	public function test_php_entrypoint_still_starts_when_plugin_is_missing(): void {
		unlink( $this->dropin_source() );

		[ $status, $out ] = $this->run_entrypoint( 'docker-php-entrypoint.sh', [ 'WP_REDIS_HOST' => 'valkey.internal' ], [ 'true' ] );

		$this->assertSame( 0, $status, $out );
		$this->assertFileDoesNotExist( $this->dropin_target() );
	}

	// --------------------------------------------------------------- cron

	public function test_cron_entrypoint_installs_the_same_dropin(): void {
		[ $status, $out ] = $this->run_entrypoint( 'docker-cron-entrypoint.sh', [ 'WP_REDIS_HOST' => 'valkey.internal' ] );

		$this->assertSame( 0, $status, $out );
		$this->assertFileEquals( $this->dropin_source(), $this->dropin_target() );
	}

	public function test_cron_entrypoint_skips_dropin_without_redis_host(): void {
		[ $status ] = $this->run_entrypoint( 'docker-cron-entrypoint.sh', [] );

		$this->assertSame( 0, $status );
		$this->assertFileDoesNotExist( $this->dropin_target() );
	}
}
