#!/usr/bin/env php
<?php

namespace MESHResearch\KCScripts;

require_once __DIR__ . '/lib/command-line.php';
require_once __DIR__ . '/lib/git.php';

function main() {
	$args = parse_command_line_args();
	
	if ( isset( $args['local-branch'] ) ) {
		$local_branch = $args['local-branch'];
	} else {
		$local_branch = 'legacy';
	}

	if ( isset( $args['remote-branch'] ) ) {
		$remote_branch = $args['remote-branch'];
	} else {
		$remote_branch = null;
	}

	echo "Local branch: $local_branch\n";

	$current_branch = current_local_branch();
	$current_directory = getcwd();
	$project_root = get_project_root();

	`git checkout -B $local_branch`;
	chdir( $project_root );

	echo "Updating remotes...\n";
	`git remote update 2>&1 > /dev/null`;

	if ( isset( $args['subtree'] ) ) {
		$subtree = $args['subtree'];
		pull_subtree( $subtree, $remote_branch );
	} else {
		$subtrees = get_subtrees();
		foreach ( $subtrees as $subtree ) {
			pull_subtree( $subtree, $remote_branch );
		}
	}
	
	`git checkout $current_branch 2>&1 > /dev/null`;
	`cd $current_directory`;
}

function pull_subtree( string $subtree, string $remote_branch = null) {
	$remote = remote_for_subtree( $subtree );
	$branch = $remote_branch ? $remote_branch : remote_default_branch( $remote );
	echo "Pulling $subtree from $remote $branch...\n";
	`git subtree pull --prefix=$subtree $remote $branch --squash`;
}


main();