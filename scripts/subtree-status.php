#!/usr/bin/env php
<?php

namespace MESHResearch\KCScripts;

require_once __DIR__ . '/lib/git.php';
require_once __DIR__ . '/lib/command-line.php';

function main() {
	$args = parse_command_line_args();

	if ( isset( $args['subtree'] ) ) {
		get_diff_for_subtree( $args['subtree'] );
		return;
	}

	echo "Updating remotes...\n";
	`git remote update 2>&1 > /dev/null`;
	echo "Getting subtrees...\n";
	$subtrees = get_subtrees();
	$status = get_status( $subtrees );
	echo "\n\n";
	print_status( $status );
}

function get_status( array $subtrees ) {
	$status = [];
	foreach ( $subtrees as $subtree ) {
		$remote = remote_for_subtree( $subtree );
		$default_branch = remote_default_branch( $remote );
		$diff = diff_exists_for_subtree( $subtree ) ? 'diff' : 'same';
		$last_commit = last_commit_time( $remote, $default_branch );
		$status[ $subtree ] = [
			'remote' => $remote,
			'default_branch' => $default_branch,
			'diff' => $diff,
			'last_commit' => $last_commit,
		];
	}
	return $status;
}

function print_status( $status ) {
	$format = "%-50s | %-45s | %-8s | %-4s | %-10s \n";
	printf( $format, 'subtree', 'remote', 'branch', 'diff', 'last commit' );
	echo str_repeat( '-', 132 ) . "\n";

	foreach ( $status as $subtree => $subtree_status ) {
		printf(
			$format,
			$subtree,
			$subtree_status['remote'],
			$subtree_status['default_branch'],
			$subtree_status['diff'],
			$subtree_status['last_commit']
		);
	}
}

main();