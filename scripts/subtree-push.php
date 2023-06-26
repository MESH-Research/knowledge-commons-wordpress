#!/usr/bin/env php
<?php

namespace MESHResearch\KCScripts;

require_once __DIR__ . '/lib/command-line.php';
require_once __DIR__ . '/lib/git.php';

function main() {
	$args = CommandLine\parse_command_line_args();

	if ( isset( $args['remote-branch'] ) ) {
		$target_branch = $args['remote-branch'];
	} else {
		$target_branch = 'knowledge-commons-wordpress';
	}

	$current_directory = getcwd();
	$project_root = get_project_root();
	chdir( $project_root );

	if ( isset( $args['subtree'] ) ) {
		$subtree = $args['subtree'];
		push_to_subtree( $subtree, $target_branch );
	} else {
		$subtrees = get_subtrees();
		foreach ( $subtrees as $subtree ) {
			push_to_subtree( $subtree, $target_branch );
		}
	}

	`cd $current_directory`;
}

function push_to_subtree( string $subtree, string $remote_branch ) {
	$remote = remote_for_subtree( $subtree );
	echo "Pushing $subtree to $remote $remote_branch...\n";
	`git subtree push --prefix=$subtree $remote $remote_branch`;
}


main();