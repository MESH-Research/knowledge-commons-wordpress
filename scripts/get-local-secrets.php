#!/usr/bin/env php
<?php
/**
 * Pulls local secrets from AWS Secrets Manager. 
 * 
 * Updates 
 * 	- .lando/secrets.env
 * 	- config/local/simplesamlphp/cert/saml.crt
 * 	- config/local/simplesamlphp/cert/saml.pem
 */

namespace MESHResearch\KCScripts;

require_once __DIR__ . '/lib/composer-autoload.php';
require_once __DIR__ . '/lib/git.php';
require_once __DIR__ . '/lib/filesystem.php';

use Aws\SecretsManager\SecretsManagerClient;

const SECRETS_FILE = '.lando/secrets.env';
const SAML_CRT_FILE = 'config/local/simplesamlphp/cert/saml.crt';
const SAML_PEM_FILE = 'config/local/simplesamlphp/cert/saml.pem';

function main() {
	$client = new SecretsManagerClient( [
		'region' => 'us-east-1',
		'version' => 'latest',
	] );
	
	$secrets = get_local_env( 'local/secrets.env', $client );
	write_secrets_to_env( $secrets, SECRETS_FILE );
	write_saml_certs( $client );
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
		$lines[] = "$key = \"$value\"";
	}
	$contents = implode( "\n", $lines );

	$path = trailingslashit( get_project_root() ) . $filename;
  
  echo "Writing secrets to $path...\n";
	file_put_contents_new_directory( $path, $contents );
}

function write_saml_certs( SecretsManagerClient $client ) : void {
	$saml_crt_value = $client->getSecretValue( [
		'SecretId' => 'local/simplesamlphp/cert/saml.crt',
	] );

	$saml_pem_value = $client->getSecretValue( [
		'SecretId' => 'local/simplesamlphp/cert/saml.pem',
	] );

	if ( ! isset( $saml_crt_value['SecretString'] ) || ! isset( $saml_pem_value['SecretString'] ) ) {
		throw new \Exception( 'SAML cert secrets not found in AWS Secrets Manager.' );
	}

	$project_root = get_project_root() . '/';
  
  echo "Writing SAML certs...\n";
	file_put_contents_new_directory( $project_root . SAML_PEM_FILE, $saml_pem_value['SecretString'] );
	file_put_contents_new_directory( $project_root . SAML_CRT_FILE, $saml_crt_value['SecretString'] );
}

main();

