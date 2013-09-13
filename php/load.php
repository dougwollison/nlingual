<?php
require './nLingual.class.php';
require './nLingual.aliases.php';

//Initial nLingual
nLingual::init();

require './locale.php';
require './utilities.php';
require './hooks.php';

if(is_admin()){
	require './admin.hooks.php';
	require './admin.settings.php';
}