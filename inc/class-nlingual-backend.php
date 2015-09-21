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
		static::add_action( 'after_setup_theme', 'register_localized_nav_menus', 999 );
		static::add_action( 'widgets_init', 'register_localized_sidebars', 999 );
	}

	// =========================
	// ! Theme Setup Methods
	// =========================

	/**
	 * Shared logic for nav menu and sidebar localizing.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type   The type of location being localized (singular).
	 * @param string $global The global variable name to be edited.
	 */
	protected static function register_localized_locations( $type, $global ) {
		global $$global;

		// Abort if not supported
		if ( ! Registry::is_feature_localizable( "{$type}_locations", $list ) ) {
			return;
		}

		// Build a new nav menu list; with copies of each menu for each language
		$localized_locations = array();
		foreach ( $$global as $id => $data ) {
			foreach ( Registry::languages() as $lang ) {
				// Check if this location specifically supports localizing
				if ( Registry::is_location_localizable( 'nav_menu', $id ) ) {
					$new_id = $id . '-lang' . $lang->id;
					$name_postfix = ' (' . $lang->system_name . ')';
					if ( is_array( $data ) ) {
						$new_name = $data['name'] . $name_postfix;
						$localized_locations[ $new_id ] = array_merge( $data, array(
							'id' => $new_id,
							'name' => $new_name,
						) );
					} else {
						$new_name = $data . $name_postfix;
						$localized_locations[ $new_id ] = $new_name;
					}
				}
			}
		}

		// Cache the old version of the menus for reference
		Registry::cache_set( 'vars', $global, $$global );

		// Replace the registered nav menu array with the new one
		$$global = $localized_locations;
	}

	/**
	 * Replaces the registered nav menus with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @see Backend::register_localized_locations()
	 *
	 * @global array $_wp_registered_nav_menus The registered nav menus list.
	 */
	public static function register_localized_nav_menus() {
		static::register_localized_locations( 'nav_menu', '_wp_registered_nav_menus' );
	}

	/**
	 * Replaces the registered sidebars with versions for each active language.
	 *
	 * @since 2.0.0
	 *
	 * @see Backend::register_localized_locations()
	 *
	 * @global array $wp_registered_sidebars The registered sidebars list.
	 */
	public static function register_localized_sidebars() {
		static::register_localized_locations( 'sidebar', 'wp_registered_sidebars' );
	}
}

// Initialize
Backend::init();