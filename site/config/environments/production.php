<?php

/**
 * Docker error logging.
 */
ini_set( 'log_errors', 'on' );
ini_set( 'error_log', 'php://stderr' );
ini_set( 'error_reporting', E_ERROR | E_WARNING | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE );