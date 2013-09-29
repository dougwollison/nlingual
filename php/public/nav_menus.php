<?php
// ======================== //
//	Nav Menu Related Hooks  //
// ======================== //

/*
 * Alter nav menus; add duplicates for each language
 */
add_action('after_setup_theme', 'nLingual_alter_registered_nav_menus', 999);
function nLingual_alter_registered_nav_menus(){
	global $_wp_registered_nav_menus;

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

/*
 * Alter wp_nav_menu $args to localize theme_location
 */
add_filter('wp_nav_menu_args', 'nLingual_localize_nav_menu_args', 999);
function nLingual_localize_nav_menu_args($args){
	$menus = get_theme_mod('nav_menu_locations');

	if($args['theme_location']){ // Theme location set, localize it
		// First check if it's already localized, abort if so
		if(preg_match('/--([a-z]{2})$/i', $args['theme_location'])) return $args;

		$location = $args['theme_location'].'--'.nL_current_lang(); // current langauge version
		$_location = $args['theme_location'].'--'.nL_default_lang(); // default langauge version

		// Next, check that the location exists and that there are menu items in it
		if(isset($menus[$location]) && ($menu_term = get_term_by('id', $menus[$location], 'nav_menu')) && $menu_term->count > 0){
			$args['theme_location'] = $location;
		}
		// Alternatively, if we're not in the default language already, try the default language
		elseif(isset($menus[$_location]) && ($_menu_term = get_term_by('id', $menus[$_location], 'nav_menu')) && $_menu_term->count > 0){
			$args['theme_location'] = $_location;
		}

		// If those fail, just leave it the way it is.
	}

	return $args;
}

/*
 * Process the langlink menu items
 */
add_filter('wp_nav_menu_objects', 'nLingual_process_menu_objects', 10, 2);
function nLingual_process_menu_objects($items, $args){
	foreach($items as $item){
		if($item->type == 'langlink'){ // Language link, set URL to the localized version of the current
			$item->url = nL_localize_here($item->object);
		}
	}

	return $items;
}