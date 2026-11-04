<?php
/**
 * Tests for the Commons signup page URL.
 *
 * Every network sends new members to its own /membership/ page. The URL
 * handed to BuddyPress must be absolute so that plugins embedding it in email
 * (Invite Anyone's accept and opt-out links) produce links that resolve.
 */

use PHPUnit\Framework\TestCase;

class SignupPageUrlTest extends TestCase {

	protected function setUp(): void {
		unset( $GLOBALS['_mock_bp_root_url'], $GLOBALS['_mock_network_home_url'] );
	}

	public function test_membership_url_is_built_from_a_bare_root() {
		$this->assertSame(
			'https://stemedplus.org/membership/',
			hcommons_membership_page_url( 'https://stemedplus.org' )
		);
	}

	public function test_membership_url_does_not_double_the_slash() {
		$this->assertSame(
			'https://stemedplus.org/membership/',
			hcommons_membership_page_url( 'https://stemedplus.org/' )
		);
	}

	public function test_membership_url_keeps_a_root_path() {
		$this->assertSame(
			'https://example.org/commons/membership/',
			hcommons_membership_page_url( 'https://example.org/commons' )
		);
	}

	public function test_signup_filter_returns_the_absolute_membership_url_for_the_current_network() {
		$GLOBALS['_mock_bp_root_url'] = 'https://stemedplus.org';

		$this->assertSame(
			'https://stemedplus.org/membership/',
			hcommons_signup_page_url( 'https://stemedplus.org/register/' )
		);
	}

	public function test_signup_filter_ignores_the_value_buddypress_computed() {
		$GLOBALS['_mock_bp_root_url'] = 'https://hcommons.org';

		$this->assertSame(
			'https://hcommons.org/membership/',
			hcommons_signup_page_url( '/wp-signup.php' )
		);
	}

	public function test_signup_filter_result_is_absolute() {
		$GLOBALS['_mock_bp_root_url'] = 'https://mla.hcommons.org';

		$url = hcommons_signup_page_url();

		$this->assertMatchesRegularExpression( '#^https://[^/]+/#', $url );
		$this->assertStringEndsWith( '/membership/', $url );
	}
}
