<?php
require(__DIR__.'/PluginToolkit.php');
require(__DIR__.'/nLingual.class.php');
require(__DIR__.'/nLingual.aliases.php');

//Initial nLingual
nLingual::init();

require(__DIR__.'/utilities.php');

/**
 * Load the global and admin/public hooks.
 *
 * @since 1.2.0 Separated global hooks; public ones only run if not in the admin.
 * @since 1.0
 */
 
// Load global hooks
require(__DIR__.'/global/nav_menus.php');
require(__DIR__.'/global/queries.php');
require(__DIR__.'/global/rewrites.php');

// Load admin hooks/callbacks
if(is_admin()){
	require(__DIR__.'/presets.php'); // Also load the presets
	require(__DIR__.'/admin/editor.php');
	require(__DIR__.'/admin/misc.php');
	require(__DIR__.'/admin/nav_menus.php');
	require(__DIR__.'/admin/new_translation.php');
	require(__DIR__.'/admin/post_meta.php');
	require(__DIR__.'/admin/process_options.php');
	require(__DIR__.'/admin/save_post.php');
	require(__DIR__.'/admin/settings.php');
}
// Load public hooks (if not in admin_only mode)
elseif(!nL_admin_only()){
	require(__DIR__.'/public/detection.php');
	require(__DIR__.'/public/nav_menus.php');
	require(__DIR__.'/public/queries.php');
	require(__DIR__.'/public/rewrites.php');
	require(__DIR__.'/public/shortcodes.php');
}