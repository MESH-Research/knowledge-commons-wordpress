<?php

namespace MESHResearch\KCScripts\CommandLine;

function parse_command_line_args( array $args = null) : array {
	global $argv;

	if ( is_null( $args ) ) {
		$args = $argv;
	}
	
	$parsed_args = [];
	foreach ( array_slice( $args, 1 ) as $arg ) {
		if ( preg_match( '/^--(.*)=(.*)$/', $arg, $matches ) ) {
			$parsed_args[ $matches[1] ] = $matches[2];
		} elseif ( preg_match( '/^--(.*)$/', $arg, $matches ) ) {
			$parsed_args[ $matches[1] ] = true;
		} else {
			$parsed_args[] = $arg;
		}
	}
	
	return $parsed_args;
}