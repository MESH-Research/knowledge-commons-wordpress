<?php

namespace MESHResearch\KCScripts;

/**
 * Queries Git log for subtree directories and returns as an array.
 */
function get_subtrees() : array {
	$output = `git log | grep git-subtree-dir | tr -d ' ' | cut -d ":" -f2 | sort | uniq | xargs -I {} bash -c 'if [ -d $(git rev-parse --show-toplevel)/{} ] ; then echo {}; fi'`;
	if ( $output === '' ) {
		return [];
	}
	$output = trim( $output );
	$subtree_directories = explode( "\n", $output );
	return $subtree_directories;
}

function remote_default_branch( string $remote ) : string {
	$default_branch = `git remote show $remote | grep "HEAD branch" | cut -d ":" -f2 | tr -d ' '`;
	if ( ! $default_branch ) {
		throw new Exception( 'Could not determine default branch for remote ' . $remote );
	}
	return trim( $default_branch );
}

function remote_for_subtree( string $subtree_prefix ) : string {
	[ $parent_dir, $subtree_dir ] = explode( '/', $subtree_prefix );
	$remote_for_subtree = $subtree_dir . '-legacy';
	return $remote_for_subtree;
}

function current_local_branch() : string {
	$current_branch = `git branch | grep '*' | cut -d ' ' -f2`;
	if ( $current_branch === '' ) {
		throw new Exception( 'Could not determine current branch.' );
	}
	return trim( $current_branch );
}

function diff_exists_for_subtree( string $subtree_prefix) : bool {
	$remote = remote_for_subtree( $subtree_prefix );
	$default_branch = remote_default_branch( $remote );
	$local_branch = current_local_branch();
	`git fetch $remote $default_branch 2>&1 > /dev/null`;
	echo "Checking diff for $subtree_prefix...\n";
	$diff = `git --no-pager diff --name-only $remote/$default_branch $local_branch:$subtree_prefix 2>/dev/null`;
	return (bool) $diff;
}

function get_diff_for_subtree( string $subtree_prefix ) {
	$remote = remote_for_subtree( $subtree_prefix );
	$default_branch = remote_default_branch( $remote );
	$local_branch = current_local_branch();
	`git fetch $remote $default_branch 2>&1 > /dev/null`;
	echo "Getting diff for $subtree_prefix...\n";
	$diff = `git --no-pager diff $remote/$default_branch $local_branch:$subtree_prefix 2>/dev/null`;
	echo $diff;
}

function last_commit_time( string $remote, string $branch ) : string {
	$last_commit_time = `git --no-pager log -1 --format=%cd --date=short $remote/$branch`;
	if ( ! $last_commit_time ) {
		return '';
	}
	return trim( $last_commit_time );
}

function get_project_root() : string {
	$project_root = `git rev-parse --show-toplevel`;
	if ( $project_root === '' ) {
		throw new Exception( 'Could not determine project root.' );
	}
	return trim( $project_root );
}