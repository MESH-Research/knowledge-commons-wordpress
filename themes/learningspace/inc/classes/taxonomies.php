<?php

namespace learningspace\inc\classes;
class taxonomies extends \learningspace\inc\classes\init {
	public function __construct() {
		parent::__construct();
	}
	public static function install(array $config) {
		//util::write_log($config['name']);
		//util::create_custom_post_type($config['name'], $config['args']);
	}
}
