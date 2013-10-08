<?php
// ======================= //
//	Global Nav Menu Hooks  //
// ======================= //

/**
 * after_setup_theme action.
 *
 * Alters the registered nav menus, creating new language specific versions to use.
 *
 * @since 1.2.0 Moved to global hooks folder.
 * @since 1.0.0
 *
 * @global array $_wp_registered_nav_menus The registered nav menus list.
 *
 * @uses nL_languages()
 * @uses nL_cache_set()
 */
function nLingual_alter_registered_nav_menus(){
	global $_wp_registered_nav_menus;
	
	if(!$_wp_registered_nav_menus) return;

	// Loop through each registered nav menu and make copies for each language.
	$localized_menus = array();
	foreach($_wp_registered_nav_menus as $slug => $name){
		foreach(nL_languages() as $lang){
			$_slug = "$slug--$lang->slug";
			$_name = "$name ($lang->system_name)";
			$localized_menus[$_slug] = $_name;
		}
	}

	// Cache the old version just in case
	nL_cache_set('_wp_registered_nav_menus', $_wp_registered_nav_menus, 'vars');

	// Replace the registered nav menu array with the new one
	$_wp_registered_nav_menus = $localized_menus;
}
add_action('after_setup_theme', 'nLingual_alter_registered_nav_menus', 999);