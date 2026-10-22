<?php

use PHPUnit\Framework\TestCase;

/**
 * scripts/build-scripts/resolve-build-version.sh prints the release version
 * a build should carry.
 *
 * main owns the version line: it uses the VERSION file, or the next version
 * commitizen computed when one is passed. Every other branch (dev, staging,
 * production) never receives main's bump commits, so its VERSION file goes
 * stale; those branches take the highest released v* tag instead, falling
 * back to the file when that is higher or when there are no tags.
 */
class ResolveBuildVersionTest extends TestCase {

	private const SCRIPT = __DIR__ . '/../../scripts/build-scripts/resolve-build-version.sh';

	private string $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo = sys_get_temp_dir() . '/kc-version-' . bin2hex( random_bytes( 6 ) );
		mkdir( $this->repo, 0755, true );
		$this->git( 'init -q' );
		$this->git( 'config user.email t@example.com' );
		$this->git( 'config user.name t' );
		$this->git( 'config commit.gpgsign false' );
		// Lightweight tags regardless of any user-level signing config.
		$this->git( 'config tag.gpgsign false' );
		$this->git( 'config tag.forcesignannotated false' );
		file_put_contents( $this->repo . '/VERSION', "1.2.1\n" );
		$this->git( 'add VERSION' );
		$this->git( 'commit -q -m "chore: seed"' );
	}

	protected function tearDown(): void {
		exec( 'rm -rf ' . escapeshellarg( $this->repo ) );
		parent::tearDown();
	}

	private function git( string $args ): void {
		exec( 'git -C ' . escapeshellarg( $this->repo ) . ' ' . $args . ' 2>&1', $out, $rc );
		$this->assertSame( 0, $rc, "git $args: " . implode( "\n", $out ) );
	}

	private function tag( string ...$tags ): void {
		foreach ( $tags as $t ) {
			$this->git( 'tag ' . escapeshellarg( $t ) );
		}
	}

	/** @return array{0:int,1:string} exit status and stdout. */
	private function resolve( string $branch, ?string $next = null ): array {
		$cmd = 'cd ' . escapeshellarg( $this->repo ) . ' && sh ' . escapeshellarg( self::SCRIPT ) . ' ' . escapeshellarg( $branch );
		if ( null !== $next ) {
			$cmd .= ' ' . escapeshellarg( $next );
		}
		exec( $cmd . ' 2>/dev/null', $out, $rc );
		return [ $rc, trim( implode( "\n", $out ) ) ];
	}

	public function test_main_uses_the_version_file(): void {
		$this->tag( 'v1.6.0' );
		$this->assertSame( [ 0, '1.2.1' ], $this->resolve( 'main' ) );
	}

	public function test_main_uses_the_next_version_when_given(): void {
		$this->tag( 'v1.6.0' );
		$this->assertSame( [ 0, '1.7.0' ], $this->resolve( 'main', '1.7.0' ) );
	}

	public function test_other_branches_use_the_highest_released_tag(): void {
		$this->tag( 'v1.2.1', 'v1.5.1', 'v1.6.0', 'v1.10.0' );
		$this->assertSame( [ 0, '1.10.0' ], $this->resolve( 'production' ) );
		$this->assertSame( [ 0, '1.10.0' ], $this->resolve( 'dev' ) );
	}

	public function test_tags_need_not_be_reachable_from_the_branch(): void {
		// Tag on a side branch, as main's bump commits are from production's
		// point of view.
		$this->git( 'checkout -q -b other' );
		file_put_contents( $this->repo . '/VERSION', "1.6.0\n" );
		$this->git( 'commit -q -am "bump: version 1.5.1 → 1.6.0"' );
		$this->tag( 'v1.6.0' );
		$this->git( 'checkout -q -' );

		$this->assertSame( [ 0, '1.6.0' ], $this->resolve( 'production' ) );
	}

	public function test_other_branches_fall_back_to_the_file_without_tags(): void {
		$this->assertSame( [ 0, '1.2.1' ], $this->resolve( 'production' ) );
	}

	public function test_other_branches_keep_the_file_when_it_is_higher_than_any_tag(): void {
		file_put_contents( $this->repo . '/VERSION', "2.0.0\n" );
		$this->tag( 'v1.6.0' );
		$this->assertSame( [ 0, '2.0.0' ], $this->resolve( 'production' ) );
	}

	public function test_non_version_tags_are_ignored(): void {
		$this->tag( 'v1.3.0', 'release-2024', 'vnext', 'v2' );
		$this->assertSame( [ 0, '1.3.0' ], $this->resolve( 'production' ) );
	}

	public function test_fails_without_a_branch_argument(): void {
		[ $rc ] = $this->resolve( '' );
		$this->assertNotSame( 0, $rc );
	}
}
