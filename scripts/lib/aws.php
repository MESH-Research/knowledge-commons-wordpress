<?php

function filename_from_key( string $key ) : string {
	$parts = explode( '/', $key );
	return array_pop( $parts );
}