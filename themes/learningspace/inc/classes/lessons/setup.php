<?php

namespace learningspace\inc\classes\lessons;

use learningspace\inc\classes\lessons\posttypes;

class setup extends \learningspace\inc\classes\init {
	public function __construct() {
		parent::__construct();
	}

	public static function start() {
		$pt = new posttypes();
		$pt->install( $pt->get_config() );
	}
}
