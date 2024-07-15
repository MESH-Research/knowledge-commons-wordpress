<?php
/**
 * Generates an nginx configuration for redirecting from Humcore item URLs to the new Works URLs.
 */

const WORKS_BASE_URL = 'https://works.hcommons.org/records/';
const COMMONS_BASE_LOCATION = '/deposits/item/';

function main( $args ) {
	$migration_file = $args[1];
	$output_file = $args[2];
	$migration_lines = file($migration_file, FILE_IGNORE_NEW_LINES);

	file_put_contents($output_file, '');

	foreach ($migration_lines as $line) {
		$data = json_decode($line, true);
		if ( array_key_exists('invenio_recid', $data) && array_key_exists('commons_id', $data) ) {
			$works_url = WORKS_BASE_URL . $data['invenio_recid'];
			$commons_location = COMMONS_BASE_LOCATION . $data['commons_id'] . '/';
			$redirect = "location $commons_location {\n\treturn 301 $works_url; \n}\n\n";
			file_put_contents($output_file, $redirect, FILE_APPEND);
		}
	}
}

main($argv);