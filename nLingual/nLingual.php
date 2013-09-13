<?php
/*
Plugin Name: nLingual
Plugin URI: https://github.com/dougwollison/nLingual
Description: Simple yet efficient multilingual system.
Version: 1.0
Author: Doug Wollison
Author URI: http://dougw.me
License: GPL2
*/

require 'nLingual.class.php';
require 'nLingual.aliases.php';

//Initial nLingual
nLingual::init();

require 'local.php';
require 'utilities.php';
require 'hooks.php';

if(is_admin()){
	require 'admin.hooks.php';
	require 'admin.settings.php';
}