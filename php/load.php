<?php
require(__DIR__.'/nLingual.class.php');
require(__DIR__.'/nLingual.aliases.php');

//Initial nLingual
nLingual::init();

require(__DIR__.'/utilities.php');
require(__DIR__.'/hooks.php');

if(is_admin()){
	require(__DIR__.'/admin.hooks.php');
	require(__DIR__.'/admin.settings.php');
}