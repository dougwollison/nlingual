<?php
namespace nLingual;

/**
 * nLingual Backend Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Backend extends Functional {
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
		// Theme Setup Actions
		static::add_action( 'after_setup_theme', 'add_nav_menu_variations', 999 );
		static::add_action( 'widgets_init', 'add_sidebar_variations', 999 );
	}

	// =========================
	// ! Theme Setup Methods
	// =========================

	/**
	 * Replaces the registered nav menus with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @global array $_wp_registered_nav_menus The registered nav menus list.
	 */
	public static function add_nav_menu_variations() {
		global $_wp_registered_nav_menus;

		// Abort if not supported
		if ( ! Registry::is_feature_localizable( 'nav_menus', $_wp_registered_nav_menus ) ) {
			return;
		}

		// Build a new nav menu list; with copies of each menu for each language
		$localized_menus = array();
		foreach ( $_wp_registered_nav_menus as $id => $name ) {
			foreach ( Registry::languages() as $lang ) {
				// Check if this location specifically supports localizing
				if ( Registry::is_location_localizable( 'nav_menu', $id ) ) {
					$new_id = $slug . '-lang' . $lang->id;
					$new_name = $name . ' (' . $lang->system_name . ')';
					$localized_menus[ $new_id ] = $new_name;
				}
			}
		}

		// Cache the old version of the menus for reference
		Registry::cache_set( 'vars', '_wp_registered_nav_menus', $_wp_registered_nav_menus );

		// Replace the registered nav menu array with the new one
		$_wp_registered_nav_menus = $localized_menus;
	}

	/**
	 * Replaces the registered sidebars with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @global array $wp_registered_sidebars The registered sidebars list.
	 */
	public static function add_sidebar_variations() {
		global $wp_registered_sidebars;

		// Abort if not supported
		if ( ! Registry::is_feature_localizable( 'sidebars', $wp_registered_sidebars ) ) {
			return;
		}

		// Build a new nav menu list; with copies of each menu for each language
		$localized_sidebars = array();
		foreach ( $wp_registered_sidebars as $id => $args ) {
			foreach ( Registry::languages() as $lang ) {
				// Check if this location specifically supports localizing
				if ( Registry::is_location_localizable( 'sidebar', $id ) ) {
					$new_id = $id . '-lang' . $lang->id;
					$new_name = $args['name'] . ' (' . $lang->system_name . ')';
					$localized_sidebars[ $new_id ] = array_merge( $args, array(
						'id' => $new_id,
						'name' => $new_name,
					) );
				}
			}
		}

		// Cache the old version of the menus for reference
		Registry::cache_set( 'vars', 'wp_registered_sidebars', $wp_registered_sidebars );

		// Replace the registered nav menu array with the new one
		$wp_registered_sidebars = $localized_sidebars;
	}
}

// Initialize
Backend::init();