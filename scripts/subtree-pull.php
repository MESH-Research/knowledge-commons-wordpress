#!/usr/bin/env php
<?php

namespace MESHResearch\KCScripts\GitSubtrees;

require_once __DIR__ . '/lib/command-line.php';
require_once __DIR__ . '/lib/git_subtree.php';

use MESHResearch\KCScripts\CommandLine as CommandLine;

function main() {
	$args = CommandLine\parse_command_line_args();
	
	if ( isset( $args['branch'] ) ) {
		$target_branch = $args['branch'];
	} else {
		$target_branch = 'legacy';
	}

	echo "Target branch: $target_branch\n";

	$current_branch = current_local_branch();
	$current_directory = getcwd();
	$project_root = get_project_root();

	`git checkout -B $target_branch`;
	chdir( $project_root );

	echo "Updating remotes...\n";
	`git remote update 2>&1 > /dev/null`;

	if ( isset( $args['subtree'] ) ) {
		$subtree = $args['subtree'];
		pull_subtree( $subtree );
	} else {
		$subtrees = get_subtrees();
		foreach ( $subtrees as $subtree ) {
			pull_subtree( $subtree );
		}
	}
	
	`git checkout $current_branch 2>&1 > /dev/null`;
	`cd $current_directory`;
}

function pull_subtree( string $subtree ) {
	$remote = remote_for_subtree( $subtree );
	$branch = remote_default_branch( $remote );
	echo "Pulling $subtree from $remote $branch...\n";
	`git subtree pull --prefix=$subtree $remote $branch --squash`;
}


main();