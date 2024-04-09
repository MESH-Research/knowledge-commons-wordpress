<?php

namespace learningspace\inc\classes\documents;

use learningspace\inc\classes\documents\posttypes;

class setup extends \learningspace\inc\classes\init {
	public function __construct() {
		parent::__construct();
	}

	public static function start() {
		$pt = new posttypes();
		$pt->install( $pt->get_config() );
	}
}
