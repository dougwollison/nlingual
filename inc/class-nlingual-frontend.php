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
		// Language Detection
		static::add_action( 'plugins_loaded', 'detect_requested_language' );
		static::add_action( 'wp', 'detect_queried_language' );

		// Redirection
		static::add_action( 'wp', 'maybe_redirect' );

		// The Mod rewriting
		static::add_filter( 'theme_mod_nav_menu_locations', 'localize_nav_menu_locations', 10, 1 );
		static::add_filter( 'sidebars_widgets', 'localize_sidebar_locations', 10, 1 );
	}

	// =========================
	// ! Language Detection Methods
	// =========================

	/**
	 * Detect the language based on the request.
	 *
	 * @since 2.0.0
	 */
	public static function detect_requested_language() {
		// Get the accepted language
		$accepted_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

		// Start with that if it exists
		$language = Registry::languages()->get( $accepted_language );

		// Override with result of url_process() if it works
		$processed_url = Rewriter::process_url();
		if ( $processed_url['lang'] ) {
			$language = $processed_url['lang'];
		}

		// Override with $query_var if present
		$query_var = Registry::get( 'query_var' );
		if ( $query_var && isset( $_REQUEST[ $query_var ] )
		&& $lang = Registry::languages()->get( $_REQUEST[ $query_var ] ) ) {
			$language = $lang;
		}

		// Set the language if it worked, but don't lock it in
		if ( $language ) {
			API::set_language( $language );
		}
	}

	/**
	 * Detect the language based on the first queried post.
	 *
	 * @since 2.0.0
	 *
	 * @global WP_Query $wp_query The main WP_Query instance.
	 */
	public static function detect_queried_language() {
		global $wp_query;

		if ( isset( $wp_query->post ) ) {
			$language = Translator::get_post_language( $wp_query->post->ID );

			// Set the language and lock it
			API::set_language( $language, true );
		}
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

	// =========================
	// ! Nav Menu "Langlinks"
	// =========================

	/**
	 * Process any langlink type menu items into proper links.
	 *
	 * @since 2.0.0
	 *
	 * @param array $items The nav menu items.
	 *
	 * @return array The modified items.
	 */
	public static function wp_nav_menu_objects( $items ) {
		foreach ( $items as $i => $item ) {
			if ( $item->type == 'langlink' ) {
				// Language link, set URL to the localized version of the current location
				// Delete the item if it's for a language that doesn't exist or is inactive
				if ( Registry::language_exists( $item->object ) ) {
					$item->url = API::localize_here( $item->object );
				} else {
					unset( $items[$i] );
				}
			}
		}

		return $items;
	}
}

// Initialize
Frontend::init();