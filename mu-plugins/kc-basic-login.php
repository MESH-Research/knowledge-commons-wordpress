<?php
/**
 * Plugin Name: KC Basic Login Gate
 * Description: Reveals the native WordPress login form (instead of the CILogon SSO
 *              redirect) to a browser that presents a pre-shared key in the query
 *              string: /wp-login.php?basic-login=KEY. The key ONLY unhides the
 *              standard login form for that session — it does not authenticate
 *              anyone. Admins still sign in with their own WordPress username and
 *              password. Entirely inert unless the KC_BASIC_LOGIN_KEY environment
 *              variable is set, and refuses to arm on production. Intended for the
 *              VPN-only non-production hosts while CILogon's return point is down.
 * Version: 1.0.0
 * Author: Mesh Research
 * Text Domain: kc-basic-login
 */

namespace KC\BasicLogin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Name of the short-lived cookie that marks a session as "show me the native form". */
const COOKIE_NAME = 'kc_basic_login';

/** How long presenting the key keeps the native form available, in seconds. */
const DEFAULT_TTL = 8 * 3600;

/**
 * Constant-time check that the supplied key equals the shared secret.
 *
 * An empty secret or empty candidate never matches, so a missing/misconfigured
 * key can never degrade into an open gate.
 *
 * @param string $provided The value from the query string.
 * @param string $secret   The configured shared key.
 * @return bool True only when both are non-empty and identical.
 */
function verify_key( string $provided, string $secret ): bool {
	if ( '' === $secret || '' === $provided ) {
		return false;
	}
	return hash_equals( $secret, $provided );
}

/**
 * Mint an expiring, signed token that proves the key was presented, without ever
 * storing the raw key in the browser.
 *
 * @param string $secret The shared key used as the HMAC secret.
 * @param int    $now    Current unix time.
 * @param int    $ttl    Lifetime in seconds.
 * @return string Token of the form "<expires>.<hmac-sha256(expires, secret)>".
 */
function issue_token( string $secret, int $now, int $ttl ): string {
	$expires = $now + $ttl;
	$sig     = hash_hmac( 'sha256', (string) $expires, $secret );
	return $expires . '.' . $sig;
}

/**
 * Validate a session token: the signature must verify (constant-time) and the
 * token must not have expired. Empty, malformed, re-dated or foreign-signed
 * tokens are all rejected.
 *
 * @param string $cookie The token presented by the browser.
 * @param string $secret The shared key used as the HMAC secret.
 * @param int    $now    Current unix time.
 * @return bool True only for an intact, unexpired token.
 */
function validate_token( string $cookie, string $secret, int $now ): bool {
	if ( '' === $secret || '' === $cookie ) {
		return false;
	}
	$parts = explode( '.', $cookie, 2 );
	if ( count( $parts ) !== 2 ) {
		return false;
	}
	[ $expires_raw, $sig ] = $parts;
	if ( ! ctype_digit( $expires_raw ) ) {
		return false;
	}
	$expires  = (int) $expires_raw;
	$expected = hash_hmac( 'sha256', (string) $expires, $secret );
	if ( ! hash_equals( $expected, $sig ) ) {
		return false;
	}
	return $expires > $now;
}

/**
 * Arm the current browser when the pre-shared key is presented, on any URL, then
 * bounce to a clean login URL. Runs on `init`, so the key works whether it is
 * added to the site root (/?basic-login=KEY) or directly to wp-login.php. A
 * wrong or empty key does nothing — no cookie, no redirect, no oracle.
 *
 * This never authenticates: it only records that this browser may see the native
 * login form. WordPress still validates username + password on that form.
 */
function handle_grant(): void {
	if ( ! isset( $_GET['basic-login'] ) ) {
		return;
	}
	if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
		return;
	}

	$secret   = (string) getenv( 'KC_BASIC_LOGIN_KEY' );
	$provided = (string) wp_unslash( $_GET['basic-login'] );
	if ( '' === $secret || ! verify_key( $provided, $secret ) ) {
		return;
	}

	$ttl   = (int) apply_filters( 'kc_basic_login_ttl', DEFAULT_TTL );
	$token = issue_token( $secret, time(), $ttl );
	set_session_cookie( $token, time() + $ttl );

	// Land on the native login form with the secret stripped from the URL so it
	// does not linger in history, server logs or referrer headers.
	wp_safe_redirect( wp_login_url() );
	exit;
}

/**
 * Gate the login view: an armed session (valid cookie) gets WordPress's own
 * login form; everyone else is sent to the fallback URL. Only the default
 * "login" action is gated, so logout and password-reset flows keep working.
 */
function handle_login_init(): void {
	$secret = (string) getenv( 'KC_BASIC_LOGIN_KEY' );
	if ( '' === $secret ) {
		return; // Feature disabled.
	}

	$cookie = isset( $_COOKIE[ COOKIE_NAME ] ) ? (string) $_COOKIE[ COOKIE_NAME ] : '';
	if ( '' !== $cookie && validate_token( $cookie, $secret, time() ) ) {
		return; // Armed: let WordPress render and process its own login.
	}

	$action = isset( $_REQUEST['action'] ) ? (string) $_REQUEST['action'] : 'login';
	if ( 'login' === $action ) {
		wp_safe_redirect( fallback_url() );
		exit;
	}
}

/**
 * Where a visitor without the key is sent when they hit the login form.
 * Defaults to the site home; override with KC_BASIC_LOGIN_FALLBACK_URL (e.g. the
 * CILogon login URL on an instance where SSO is still live).
 */
function fallback_url(): string {
	$configured = (string) getenv( 'KC_BASIC_LOGIN_FALLBACK_URL' );
	if ( '' !== $configured ) {
		return $configured;
	}
	return home_url( '/' );
}

/**
 * Set the gate cookie with hardened flags. Isolated from the decision logic so
 * the testable core stays free of header/IO side effects.
 */
function set_session_cookie( string $value, int $expires ): void {
	setcookie(
		COOKIE_NAME,
		$value,
		array(
			// Root path so the cookie is delivered to /wp-login.php even on a
			// subdirectory multisite (where COOKIEPATH is the per-blog path).
			'expires'  => $expires,
			'path'     => '/',
			'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => true,
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Whether the gate should be wired up: a key must be configured, and we refuse to
 * arm on production unless explicitly overridden. With no key set this returns
 * false, so loading the file only defines functions — which is how tests load it.
 */
function gate_is_armed(): bool {
	if ( ! getenv( 'KC_BASIC_LOGIN_KEY' ) ) {
		return false;
	}
	if ( function_exists( 'wp_get_environment_type' ) && 'production' === wp_get_environment_type() ) {
		return (bool) getenv( 'KC_BASIC_LOGIN_ALLOW_PRODUCTION' );
	}
	return true;
}

if ( gate_is_armed() ) {
	add_action( 'init', __NAMESPACE__ . '\\handle_grant', 0 );
	add_action( 'login_init', __NAMESPACE__ . '\\handle_login_init', 0 );
}
