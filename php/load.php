<?php
require(__DIR__.'/nLingual.class.php');
require(__DIR__.'/nLingual.aliases.php');

//Initial nLingual
nLingual::init();

require(__DIR__.'/ocale.php');
require(__DIR__.'/tilities.php');
require(__DIR__.'/ooks.php');

if(is_admin()){
	require(__DIR__.'/admin.hooks.php');
	require(__DIR__.'/admin.settings.php');
}