<?php
/**
 * Build-version endpoint.
 *
 * Reads .version.json, written at Docker build time by Dockerfile.php from
 * build-args set by the AWS ECR build workflow, and adds two runtime facts:
 * the PHP version actually serving the request and the ECS service name
 * (wp-blue / wp-green) from the task metadata endpoint. Exposed for ops
 * checks so a running container can be quickly mapped back to a release and
 * to the side of the blue/green pair it belongs to.
 *
 * This file runs outside WordPress, so it uses no WP functions. The
 * functions below are plain so they can be unit-tested; the request is only
 * handled when this file is the script being executed.
 */

/**
 * Resolve the ECS service name (e.g. wp-blue, wp-green) from the task
 * metadata endpoint. PHP equivalent of:
 *   curl -s "$ECS_CONTAINER_METADATA_URI_V4/task" | jq -r '.ServiceName'
 *
 * @param callable    $http_get     function (string $url): ?string, body or null on failure.
 * @param string|null $metadata_uri value of ECS_CONTAINER_METADATA_URI_V4, if any.
 * @return string|null null when not on ECS or when the lookup fails.
 */
function kc_version_ecs_service_name( callable $http_get, ?string $metadata_uri ): ?string {
	if ( null === $metadata_uri || '' === $metadata_uri ) {
		return null;
	}

	$body = $http_get( rtrim( $metadata_uri, '/' ) . '/task' );
	if ( ! is_string( $body ) ) {
		return null;
	}

	$data = json_decode( $body, true );
	if ( ! is_array( $data ) || ! isset( $data['ServiceName'] ) || ! is_string( $data['ServiceName'] ) || '' === $data['ServiceName'] ) {
		return null;
	}

	return $data['ServiceName'];
}

/**
 * Build the response body: build metadata from .version.json (or the
 * "unknown" placeholders if it is missing or unreadable) plus runtime facts.
 *
 * @param string|null $version_json raw contents of .version.json, or null if absent.
 * @param string      $php_version  PHP_VERSION of the serving process.
 * @param string|null $service      ECS service name, or null if unknown.
 */
function kc_version_payload( ?string $version_json, string $php_version, ?string $service ): array {
	$build = null;
	if ( is_string( $version_json ) ) {
		$decoded = json_decode( $version_json, true );
		if ( is_array( $decoded ) ) {
			$build = $decoded;
		}
	}

	if ( null === $build ) {
		$build = [
			'version' => 'unknown',
			'build'   => 'unknown',
			'sha'     => 'unknown',
			'branch'  => 'unknown',
			'error'   => 'version file missing — image not built with version args',
		];
	}

	$build['php']     = $php_version;
	$build['service'] = $service;

	return $build;
}

/**
 * Minimal HTTP GET for the link-local metadata endpoint. Returns the body on
 * a 2xx response, null on any failure. Kept short because this runs on every
 * request to the endpoint and the metadata service answers in milliseconds.
 */
function kc_version_http_get( string $url, int $timeout = 1 ): ?string {
	$context = stream_context_create(
		[
			'http' => [
				'method'        => 'GET',
				'timeout'       => $timeout,
				'ignore_errors' => true,
			],
		]
	);

	$body = @file_get_contents( $url, false, $context );
	if ( false === $body ) {
		return null;
	}

	$status = 0;
	foreach ( $http_response_header ?? [] as $header ) {
		if ( preg_match( '#^HTTP/\S+\s+(\d{3})#', $header, $m ) ) {
			$status = (int) $m[1];
		}
	}
	if ( $status < 200 || $status >= 300 ) {
		return null;
	}

	return $body;
}

if ( realpath( $_SERVER['SCRIPT_FILENAME'] ?? '' ) === __FILE__ ) {
	header( 'Content-Type: application/json' );
	$file = __DIR__ . '/.version.json';
	echo json_encode(
		kc_version_payload(
			is_file( $file ) ? (string) file_get_contents( $file ) : null,
			PHP_VERSION,
			kc_version_ecs_service_name( 'kc_version_http_get', getenv( 'ECS_CONTAINER_METADATA_URI_V4' ) ?: null )
		)
	);
}
