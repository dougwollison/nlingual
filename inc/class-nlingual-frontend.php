<?php
namespace nLingual;

/**
 * nLingual Frontend Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Frontend extends Functional {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The name of the class.
	 *
	 * @since 2.0.0
	 *
	 * @access protected (static)
	 *
	 * @var string
	 */
	protected static $name;

	// =========================
	// ! Methods
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// The Mod rewriting
		static::add_filter( 'theme_mod_nav_menu_locations', 'localize_nav_menu_locations', 10, 1 );
		static::add_filter( 'sidebars_widgets', 'localize_sidebar_locations', 10, 1 );
	}

	// =========================
	// ! Theme Setup Methods
	// =========================

	/**
	 * Shared logic for menu/sidebar rewriting.
	 *
	 * @since 2.0.0
	 *
	 * @param array $locations  The list of locatiosn to filter.
	 * @param array $registered The list of register nav menus or sidebars.
	 *
	 * @return array The modified $locations with unlocalized versions updated.
	 */
	protected static function localize_locations( $locations, $registered ) {
		// Get the default and current languages
		$default_lang = Registry::get( 'default_lang', 0 );
		$current_lang = Registry::get( 'current_lang', $default_lang );

		// Ensure the unlocalized locations are set to the appropriate version.
		foreach ( $registered as $slug => $name ) {
			// Check if a location is set for the current language
			if ( isset( $locations[ "{$slug}-lang{$current_lang}"] ) ) {
				$locations[ $slug ] = $locations[ "{$slug}-lang{$current_lang}"];
			} else
			// Alternatively check if a location is set for the default one
			if ( isset( $locations[ "{$slug}-lang{$default_lang}"] ) ) {
				$locations[ $slug ] = $locations[ "{$slug}-lang{$default_lang}"];
			}
		}

		return $locations;
	}

	/**
	 * Replaces the registered nav menus with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @see Frontend::localize_locations()
	 *
	 * @global array $_wp_registered_nav_menus The registered nav menus list.
	 */
	public static function localize_nav_menu_locations( $locations ) {
		global $_wp_registered_nav_menus;

		$locations = static::localize_locations( $locations, $_wp_registered_nav_menus );

		return $locations;
	}

	/**
	 * Replaces the registered sidebars with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @see Frontend::localize_locations()
	 *
	 * @global array $wp_registered_sidebars The registered sidebars list.
	 */
	public static function localize_sidebar_locations( $sidebars ) {
		global $wp_registered_sidebars;

		$locations = static::localize_locations( $locations, $wp_registered_sidebars );

		return $locations;
	}
}

// Initialize
Frontend::init();