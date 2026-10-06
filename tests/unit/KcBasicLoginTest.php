<?php
/**
 * Tests for the KC Basic Login gate's security core.
 *
 * The contract: the shared key is accepted only when it matches exactly, and the
 * session token that records "this browser presented the key" cannot be forged,
 * re-dated, borrowed from another secret, or used past its expiry. These lock in
 * behaviour, not implementation — the gate may be re-coded freely as long as a
 * wrong key never opens it and a tampered token never validates.
 */

use PHPUnit\Framework\TestCase;

use function KC\BasicLogin\verify_key;
use function KC\BasicLogin\issue_token;
use function KC\BasicLogin\validate_token;

class KcBasicLoginTest extends TestCase {

	private string $secret = 'correct-horse-battery-staple-shared-key';

	// --- verify_key: the gate opens only for the exact shared key ---------------

	public function test_correct_key_is_accepted() {
		$this->assertTrue( verify_key( $this->secret, $this->secret ) );
	}

	public function test_wrong_key_is_rejected() {
		$this->assertFalse( verify_key( 'not-the-key', $this->secret ) );
	}

	public function test_empty_supplied_key_is_rejected() {
		$this->assertFalse( verify_key( '', $this->secret ) );
	}

	public function test_empty_secret_never_matches() {
		// A missing/misconfigured secret must never become an open door.
		$this->assertFalse( verify_key( '', '' ) );
		$this->assertFalse( verify_key( 'anything', '' ) );
	}

	public function test_near_miss_key_is_rejected() {
		$this->assertFalse( verify_key( $this->secret . 'x', $this->secret ) );
		$this->assertFalse( verify_key( substr( $this->secret, 0, -1 ), $this->secret ) );
	}

	// --- token round-trip: the proof-of-key session marker ----------------------

	public function test_freshly_issued_token_validates() {
		$now   = 1000000;
		$token = issue_token( $this->secret, $now, 3600 );
		$this->assertTrue( validate_token( $token, $this->secret, $now + 1 ) );
	}

	public function test_token_is_rejected_after_expiry() {
		$now   = 1000000;
		$token = issue_token( $this->secret, $now, 3600 );
		$this->assertFalse( validate_token( $token, $this->secret, $now + 3601 ) );
	}

	public function test_token_with_tampered_signature_is_rejected() {
		$now   = 1000000;
		$token = issue_token( $this->secret, $now, 3600 );
		[ $exp, $sig ] = explode( '.', $token, 2 );
		$forged = $exp . '.' . strrev( $sig );
		$this->assertFalse( validate_token( $forged, $this->secret, $now + 1 ) );
	}

	public function test_token_with_extended_expiry_is_rejected() {
		$now   = 1000000;
		$token = issue_token( $this->secret, $now, 3600 );
		[ $exp, $sig ] = explode( '.', $token, 2 );
		// Attacker tries to grant themselves a far-future expiry without re-signing.
		$forged = ( (int) $exp + 1000000 ) . '.' . $sig;
		$this->assertFalse( validate_token( $forged, $this->secret, $now + 1 ) );
	}

	public function test_token_signed_with_other_secret_is_rejected() {
		$now   = 1000000;
		$token = issue_token( 'a-different-secret', $now, 3600 );
		$this->assertFalse( validate_token( $token, $this->secret, $now + 1 ) );
	}

	public function test_malformed_token_is_rejected() {
		$now = 1000000;
		$this->assertFalse( validate_token( '', $this->secret, $now ) );
		$this->assertFalse( validate_token( 'garbage', $this->secret, $now ) );
		$this->assertFalse( validate_token( 'no-separator-here', $this->secret, $now ) );
	}
}
