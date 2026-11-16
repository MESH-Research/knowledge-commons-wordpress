<?php
/**
 * OPcache Status
 *
 * Exposes a compact summary of php-fpm's OPcache at /wp-json/idms/opcache so
 * the cache can be watched in production: hit rate, memory in the shared
 * segment, cached script count against capacity, and restart counters.
 *
 * Access requires either the Profiles bearer token (the same one the other
 * idms/ routes accept) or a logged-in network administrator. Script paths are
 * never included.
 *
 * @package Commons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KC_Opcache_Status {

	const MIB = 1048576;

	public static function register_routes() {
		register_rest_route(
			'idms',
			'/opcache',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'handle_request' ],
				'permission_callback' => [ __CLASS__, 'permission' ],
			]
		);
	}

	/**
	 * Allow the Profiles bearer token or a network administrator.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return true|WP_Error
	 */
	public static function permission( $request ) {
		if ( function_exists( 'cilogon_verify_bearer_token' ) && true === cilogon_verify_bearer_token( $request ) ) {
			return true;
		}

		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_network' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			'Network administrator or Profiles bearer token required.',
			[ 'status' => 401 ]
		);
	}

	/**
	 * @param WP_REST_Request $request Incoming request.
	 * @param array|false|null $status Pre-fetched status; null reads the live cache.
	 * @return array
	 */
	public static function handle_request( $request, $status = null ) {
		if ( null === $status ) {
			$status = self::raw_status();
		}
		return self::summarise( $status );
	}

	/**
	 * @return array|false opcache_get_status() without the per-script list.
	 */
	public static function raw_status() {
		if ( ! function_exists( 'opcache_get_status' ) ) {
			return false;
		}
		return opcache_get_status( false );
	}

	/**
	 * Reduce opcache_get_status() output to the figures worth watching.
	 *
	 * @param array|false $status Output of opcache_get_status( false ), or false.
	 * @return array
	 */
	public static function summarise( $status ) {
		if ( ! is_array( $status ) || empty( $status['opcache_enabled'] ) ) {
			return [ 'enabled' => false ];
		}

		$memory  = $status['memory_usage'] ?? [];
		$strings = $status['interned_strings_usage'] ?? [];
		$stats   = $status['opcache_statistics'] ?? [];

		return [
			'enabled'          => true,
			'cache_full'       => ! empty( $status['cache_full'] ),
			'restart_pending'  => ! empty( $status['restart_pending'] ),
			'memory'           => [
				'used_mb'        => self::mib( $memory['used_memory'] ?? 0 ),
				'free_mb'        => self::mib( $memory['free_memory'] ?? 0 ),
				'wasted_mb'      => self::mib( $memory['wasted_memory'] ?? 0 ),
				'wasted_percent' => round( (float) ( $memory['current_wasted_percentage'] ?? 0 ), 2 ),
			],
			'interned_strings' => [
				'used_mb' => self::mib( $strings['used_memory'] ?? 0 ),
				'free_mb' => self::mib( $strings['free_memory'] ?? 0 ),
			],
			'scripts'          => [
				'cached' => (int) ( $stats['num_cached_scripts'] ?? 0 ),
				'keys'   => (int) ( $stats['num_cached_keys'] ?? 0 ),
				'max'    => (int) ( $stats['max_cached_keys'] ?? 0 ),
			],
			'hits'             => (int) ( $stats['hits'] ?? 0 ),
			'misses'           => (int) ( $stats['misses'] ?? 0 ),
			'hit_rate_percent' => round( (float) ( $stats['opcache_hit_rate'] ?? 0 ), 2 ),
			'restarts'         => [
				'oom'    => (int) ( $stats['oom_restarts'] ?? 0 ),
				'hash'   => (int) ( $stats['hash_restarts'] ?? 0 ),
				'manual' => (int) ( $stats['manual_restarts'] ?? 0 ),
			],
			'started_at'       => (int) ( $stats['start_time'] ?? 0 ),
			'last_restart_at'  => (int) ( $stats['last_restart_time'] ?? 0 ),
			'jit'              => ! empty( $status['jit']['enabled'] ),
		];
	}

	/**
	 * @param int|float $bytes Byte count.
	 * @return float Mebibytes to one decimal place.
	 */
	private static function mib( $bytes ) {
		return round( ( (float) $bytes ) / self::MIB, 1 );
	}
}

add_action( 'rest_api_init', [ 'KC_Opcache_Status', 'register_routes' ] );
