<?php
/*
Plugin Name: Stem Mail Template
Plugin URI: https://github.com/jawngee/the-wonder
Description: Model for mail templates
Author: interfacelab
Version: 0.1.5
Author URI: http://interfacelab.io
*/


define('STEM_MAIL_TEMPLATE_DIR', dirname(__FILE__));

if (file_exists(STEM_MAIL_TEMPLATE_DIR.'/vendor/autoload.php')) {
	require_once STEM_MAIL_TEMPLATE_DIR.'/vendor/autoload.php';
}

new \Stem\Packages\Package(STEM_MAIL_TEMPLATE_DIR, 'Mail Templates', 'Package providing user editable mail templates.');