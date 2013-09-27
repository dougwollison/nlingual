<?php
require(__DIR__.'/nLingual.class.php');
require(__DIR__.'/nLingual.aliases.php');

//Initial nLingual
nLingual::init();

require(__DIR__.'/utilities.php');

// Load public side files
require(__DIR__.'/public/detection.php');
require(__DIR__.'/public/nav_menus.php');
require(__DIR__.'/public/queries.php');
require(__DIR__.'/public/rewrites.php');
require(__DIR__.'/public/shortcodes.php');

// Load admin side files if needed
if(is_admin()){
	require(__DIR__.'/presets.php');
	require(__DIR__.'/admin/editor.php');
	require(__DIR__.'/admin/misc.php');
	require(__DIR__.'/admin/nav_menus.php');
	require(__DIR__.'/admin/new_translation.php');
	require(__DIR__.'/admin/post_meta.php');
	require(__DIR__.'/admin/process_options.php');
	require(__DIR__.'/admin/save_post.php');
	require(__DIR__.'/admin/settings.php');
}