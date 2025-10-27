#!/usr/bin/env php
<?php
/**
 * Pulls local secrets from AWS Secrets Manager. 
 * 
 * Updates 
 * 	- .lando/secrets.env
 */

namespace MESHResearch\KCScripts;

require_once __DIR__ . '/lib/composer-autoload.php';
require_once __DIR__ . '/lib/git.php';
require_once __DIR__ . '/lib/filesystem.php';

use Aws\SecretsManager\SecretsManagerClient;

const SECRETS_FILE = '.lando/secrets.env';

function main() {
	$client = new SecretsManagerClient( [
		'region' => 'us-east-1',
		'version' => 'latest',
	] );
	
	$secrets = get_local_env( 'local/secrets.env', $client );
	write_secrets_to_env( $secrets, SECRETS_FILE );
}

function get_local_env( string $secret_id, SecretsManagerClient $client ) : array {
	$result = $client->getSecretValue( [
			'SecretId' => $secret_id,
	] );

	if ( ! isset( $result['SecretString'] ) ) {
		throw new \Exception( 'Local env secret string not found in AWS Secrets Manager.' );
	}

	$secrets = json_decode( $result['SecretString'], true );
	return $secrets;
}

function write_secrets_to_env( array $secrets, string $filename ) : void {
	$lines = [];
	foreach ( $secrets as $key => $value ) {
		$lines[] = "$key='$value'";
	}
	$contents = implode( "\n", $lines );

	$path = trailingslashit( get_project_root() ) . $filename;
  
	echo "Writing secrets to $path...\n";
	file_put_contents_new_directory( $path, $contents );
}

main();

