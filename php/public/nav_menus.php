<?php
// ======================= //
//	Public Nav Menu Hooks  //
// ======================= //

/**
 * wp_nav_menu_args filter.
 *
 * Alter wp_nav_menu() arguments to change theme_location to the localized version.
 *
 * Will only change it if menu items exist for the menu at that location,
 * falling back to the default language version and finally the unlocalized one.
 *
 * @since 1.0.0
 *
 * @uses nL_current_lang()
 * @uses nL_default_lang()
 *
 * @param array $args The wp_nav_menu() arguments.
 *
 * @param array $args The modified arguments list.
 */
function nLingual_localize_nav_menu_args($args){
	$menus = get_theme_mod('nav_menu_locations');

	if($args['theme_location']){ // Theme location set, localize it
		// First check if it's already localized, abort if so
		if(preg_match('/--([a-z]{2})$/i', $args['theme_location'])) return $args;

		$location = $args['theme_location'].'--'.nL_current_lang(); // current language version
		$_location = $args['theme_location'].'--'.nL_default_lang(); // default language version

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
add_filter('wp_nav_menu_args', 'nLingual_localize_nav_menu_args', 999);

/**
 * wp_nav_menu_objects filter.
 *
 * Finds and processes the langlink menu items.
 *
 * @since 1.2.0 Removes langlinks if not for an existing/active language
 * @since 1.0.0
 *
 * @uses nL_lang_exists()
 * @uses nL_localize_here()
 *
 * @param array $items The list of nav menu items.
 *
 * @return array The modified list of nav menu items.
 */
function nLingual_process_menu_objects($items){
	foreach($items as $i => $item){
		if($item->type == 'langlink'){
			// Language link, set URL to the localized version of the current
			// Delete the item if it's for a language that doesn't exist or is inactive
			if(nL_lang_exists($item->object)){
				$item->url = nL_localize_here($item->object);
			}else{
				unset($items[$i]);
			}
		}
	}

	return $items;
}
add_filter('wp_nav_menu_objects', 'nLingual_process_menu_objects', 10, 2);