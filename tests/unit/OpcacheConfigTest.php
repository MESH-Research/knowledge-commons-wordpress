<?php

use PHPUnit\Framework\TestCase;

/**
 * The PHP image must ship with OPcache enabled and configured for a large,
 * mostly immutable codebase that also loads some code from an EFS mount.
 *
 * These assertions are about the shape of the shipped configuration, not its
 * exact values: the ini must parse, the cache must be on, files must be
 * revalidated (so EFS and local-dev changes are picked up) but not on every
 * request, and the shared segment must be large enough for this codebase.
 */
class OpcacheConfigTest extends TestCase {

	private const INI        = __DIR__ . '/../../config/all/php/opcache.ini';
	private const DOCKERFILE = __DIR__ . '/../../Dockerfile.php';

	private function ini(): array {
		$this->assertFileExists( self::INI );
		$parsed = parse_ini_file( self::INI, false, INI_SCANNER_TYPED );
		$this->assertIsArray( $parsed, 'opcache.ini must be valid ini syntax' );
		return $parsed;
	}

	public function test_opcache_is_enabled_for_fpm(): void {
		$ini = $this->ini();
		$this->assertArrayHasKey( 'opcache.enable', $ini );
		$this->assertSame( 1, (int) $ini['opcache.enable'] );
	}

	public function test_opcache_is_not_enabled_for_cli(): void {
		// wp-cli runs in the cron container; a CLI cache is wasted memory there.
		$ini = $this->ini();
		$this->assertArrayHasKey( 'opcache.enable_cli', $ini );
		$this->assertSame( 0, (int) $ini['opcache.enable_cli'] );
	}

	public function test_files_are_revalidated_but_not_on_every_request(): void {
		$ini = $this->ini();
		$this->assertSame( 1, (int) $ini['opcache.validate_timestamps'], 'EFS and local-dev code changes must be picked up without a restart' );
		$this->assertGreaterThanOrEqual( 30, (int) $ini['opcache.revalidate_freq'], 'stat() per file per request defeats the point on NFS' );
	}

	public function test_shared_segment_is_sized_for_this_codebase(): void {
		$ini = $this->ini();
		$this->assertGreaterThanOrEqual( 256, (int) $ini['opcache.memory_consumption'] );
		$this->assertGreaterThanOrEqual( 65407, (int) $ini['opcache.max_accelerated_files'], 'WordPress + ~100 plugins is well over the 16k default' );
		$this->assertGreaterThanOrEqual( 16, (int) $ini['opcache.interned_strings_buffer'] );
	}

	/**
	 * The deploy workflow builds the `cloud` stage on top of a prebuilt base
	 * image, so enabling the extension in the `base` stage alone would have no
	 * effect on a dev push. The cloud stage itself must enable it and install
	 * the ini into conf.d.
	 */
	public function test_cloud_stage_enables_opcache_and_installs_the_ini(): void {
		$dockerfile = file_get_contents( self::DOCKERFILE );
		$this->assertNotFalse( $dockerfile );

		$cloud_start = strpos( $dockerfile, 'AS cloud' );
		$this->assertNotFalse( $cloud_start, 'Dockerfile.php must have a cloud stage' );
		$cloud = substr( $dockerfile, $cloud_start );

		$this->assertStringContainsString( 'docker-php-ext-enable opcache', $cloud );
		$this->assertMatchesRegularExpression(
			'#config/all/php/opcache\.ini .*?/usr/local/etc/php/conf\.d/#s',
			$cloud,
			'the cloud stage must place opcache.ini in conf.d'
		);
	}
}
