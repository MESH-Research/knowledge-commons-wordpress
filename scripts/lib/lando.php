<?php

namespace MESHResearch\KCScripts;

function get_lando_info() {
	$info = getenv( 'LANDO_INFO' );
	if ( $info ) {
		return json_decode( $info, true );
	} 
	
	$info = json_decode( shell_exec( 'lando info --format json' ), true );
	return $info;
}