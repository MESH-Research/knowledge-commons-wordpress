<?php
/**
 * Loads the KC Basic Login gate so its pure helpers can be unit-tested in
 * isolation. Only the key verification and session-token signing/validation are
 * exercised here; the login_init glue (cookies, redirects, exit) is WordPress
 * wiring and is deliberately not unit-tested.
 *
 * With KC_BASIC_LOGIN_KEY unset (the default under test), requiring the file
 * simply defines the functions and arms no hooks.
 */

require_once dirname( __DIR__, 2 ) . '/mu-plugins/kc-basic-login.php';
