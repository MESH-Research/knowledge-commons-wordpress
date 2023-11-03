#!/usr/bin/env php
<?php
/**
 * Import an sql file to the db.
 * 
 * Usage: lando db-import <path-to-sql-file>
 */

namespace MESHResearch\KCScripts;

require_once __DIR__ . '/lib/lando.php';

$import_path = $argv[1];

$info = get_lando_info();
$database = $info['database']['creds']['database'];
$host = $info['database']['internal_connection']['host'];
$user = $info['database']['creds']['user'];
$password = $info['database']['creds']['password'];
$command = "mysql -h $host -u $user -p$password $database < $import_path 2>&1 | grep -v 'Warning: Using a password'";
echo "Running db import: $command\n";
$output = `$command`;
echo $output;
echo "Finished importing $import_path.\n";