<?php
/**
 * ECS Service Info
 *
 * Exposes the running ECS task's ServiceName via a small REST endpoint at
 * /wp-json/idms/service. PHP equivalent of:
 *   curl -s "$ECS_CONTAINER_METADATA_URI_V4/task" | jq -r '.ServiceName'
 *
 * Useful for diagnostics and infrastructure introspection.
 *
 * @package Commons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KC_Ecs_Service_Info {

	const TRANSIENT_KEY = 'kc_ecs_service_name';
	const HTTP_TIMEOUT  = 2;

	public static function register_routes() {
		register_rest_route(
			'idms',
			'/service',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'handle_request' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public static function handle_request( $request ) {
		return [ 'service' => self::fetch_service_name() ];
	}

	public static function fetch_service_name() {
		$uri = getenv( 'ECS_CONTAINER_METADATA_URI_V4' );
		if ( ! is_string( $uri ) || '' === $uri ) {
			return null;
		}

		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $uri . '/task', [ 'timeout' => self::HTTP_TIMEOUT ] );
		if ( is_wp_error( $response ) ) {
			return null;
		}
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) || ! isset( $data['ServiceName'] ) || ! is_string( $data['ServiceName'] ) ) {
			return null;
		}

		set_transient( self::TRANSIENT_KEY, $data['ServiceName'], HOUR_IN_SECONDS );
		return $data['ServiceName'];
	}
}

add_action( 'rest_api_init', [ 'KC_Ecs_Service_Info', 'register_routes' ] );
