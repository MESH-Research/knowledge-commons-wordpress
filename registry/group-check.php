<?php

const REST_BASE = 'https://registry.hcommons.org/registry';
const API_USER = 'Humanities Commons.restuser';
const API_KEY = 'jc1i-w3u4-xe1k-wder';

function main() {
  $response = make_comanage_request( '/co_groups', [ 'coid' => 2 ] );
  echo $response;
}

function make_comanage_request( $endpoint, $params = [], $method = 'GET', $headers = [], $body = '', $format = 'json' ) {
  $headers['Authorization'] = 'Basic ' . base64_encode( API_USER . ':' . API_KEY );
  $param_str = http_build_query( $params );
  $url = REST_BASE . $endpoint . '.' . $format . '?' . $param_str;
  xdebug_break();
  $response = wp_remote_request(
    $url,
    [
      'method'  => $method,
      'headers' => $headers
    ]
  );
  xdebug_break();
  return $response;
}

main();