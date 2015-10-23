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
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Language Detection
		static::add_action( 'plugins_loaded', 'detect_requested_language', 10, 0 );
		static::add_action( 'wp', 'detect_queried_language', 10, 0 );

		// URL Redirection
		static::add_filter( 'redirect_canonical', 'localize_canonical', 10, 2 );
		static::add_action( 'template_redirect', 'maybe_redirect', 10, 0 );

		// URL Rewriting
		static::add_filter( 'home_url', 'localize_home_url', 10, 4 );

		// The Mod rewriting
		static::add_filter( 'theme_mod_nav_menu_locations', 'localize_nav_menu_locations', 10, 1 );
		static::add_filter( 'sidebars_widgets', 'localize_sidebar_locations', 10, 1 );
		static::add_filter( 'wp_nav_menu_objects', 'handle_language_links', 10, 1 );

		// General fitlering
		static::add_filter( 'locale', 'rewrite_locale', 10, 1 );
		static::add_filter( 'body_class', 'add_body_classes', 10, 1 );
		static::add_filter( 'option_page_on_front', 'current_language_version', 10, 1 );
		static::add_filter( 'option_page_for_posts', 'current_language_version', 10, 1 );
	}

	// =========================
	// ! Language Detection
	// =========================

	/**
	 * Detect the language based on the request.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate and retrieve the detected language.
	 * @uses Rewriter::process_url() to parse the current page URL.
	 * @uses Registry::get() to get the query var option.
	 * @uses API::set_language() to tentatively apply the detected language.
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
		&& ( $lang = Registry::languages()->get( $_REQUEST[ $query_var ] ) ) ) {
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
	 * @uses Registry::get() to get the postlang_override option.
	 * @uses Translator::get_object_language() to get the queried posts language.
	 * @uses API::set_language() to permanently apply the detected language.
	 *
	 * @global WP_Query $wp_query The main WP_Query instance.
	 */
	public static function detect_queried_language() {
		global $wp_query;

		// Don't proceed if this feature is disabled
		if ( ! Registry::get( 'postlang_override', 0 ) ) {
			return;
		}

		if ( isset( $wp_query->post ) ) {
			$language = Translator::get_post_language( $wp_query->post->ID );

			// Set the language and lock it
			API::set_language( $language, true );
		}
	}

	// =========================
	// ! Language Redirection
	// =========================

	/**
	 * Check for proper localized redirection.
	 *
	 * @since 2.0.0
	 *
	 * @uses Rewriter::localize_url() to localize both URLs.
	 *
	 * @param string  $redirect_url  The intended redirect URL.
	 * @param string  $requested_url The originally requested URL.
	 *
	 * @return bool|string False if localized versions of both URLs match,
	 *                     otherwise the intended redirect URL.
	 */
	public static function localize_canonical( $redirect_url, $requested_url ) {
		if( Rewriter::localize_url( $redirect_url ) == Rewriter::localize_url( $requested_url ) ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Check if language redirection is necessary.
	 *
	 * @since 2.0.0
	 *
	 * @uses NL_UNLOCALIZED to get the unlocalized home URL.
	 * @uses NL_ORIGINAL_URL for comparison.
	 * @uses Rewriter::localize_here() to determine the proper URL.
	 *
	 * @uses Rewriter::localize_url()
	 */
	public static function maybe_redirect() {
		// Get the plain home URL for comparison (allow unlocalized version)
		$unlocalized_home = get_home_url( null, '', NL_UNLOCALIZED );

		// Abort if it's just the homepage
		if ( untrailingslashit( NL_ORIGINAL_URL ) == $unlocalized_home ) {
			return;
		}

		// Get the correct localized version of the URL
		$redirect_url = Rewriter::localize_here();

		// Perform the redirect if applicable
		if ( NL_ORIGINAL_URL != $redirect_url ) {
			wp_redirect( $redirect_url );
			exit;
		}
	}

	// =========================
	// ! URL Rewriting
	// =========================

	/**
	 * Localize the home URL.
	 *
	 * @since 2.0.0
	 *
	 * @uses NL_UNLOCALIZED to prevent filter recursion.
	 * @uses Rewriter::localize_url() to create the new url.
	 *
	 * @param string      $url     The complete home URL including scheme and path.
	 * @param string      $path    Path relative to the home URL.
	 * @param string|null $scheme  Scheme to give the home URL context.
	 * @param int|null    $blog_id Blog ID, or null for the current blog.
	 *
	 * @return string The localized home URL.
	 */
	public static function localize_home_url( $url, $path, $scheme, $blog_id ) {
		// Check if we shouldn't actually localize this
		// (will be indicated by custom $scheme value)
		if ( $scheme == NL_UNLOCALIZED ) {
			return $url;
		}

		// Return the localized version of the URL
		return Rewriter::localize_url( $url );
	}

	// =========================
	// ! Menu/Sidebar Rewriting
	// =========================

	/**
	 * Shared logic for menu/sidebar rewriting.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_feature_localizable() to check for support.
	 * @uses Registry::default_lang() to get the default language ID.
	 * @uses Registry::current_lang() to get the current language ID.
	 * @uses Registry::is_location_localizable() to check for support.
	 *
	 * @param string $type       The type of location.
	 * @param array  $locations  The list of locations to filter.
	 * @param array  $registered The list of orignially register locations.
	 *
	 * @return array The modified $locations with unlocalized versions updated.
	 */
	protected static function localize_locations( $type, $locations, $registered ) {
		// Abort if not supported
		if ( ! Registry::is_feature_localizable( "{$type}_locations", true ) ) {
			return;
		}

		// Get the default and current languages
		$default_lang = Registry::get( 'default_lang' );
		$current_lang = Registry::get( 'current_lang' ) ?: $default_lang;

		// Ensure the unlocalized locations are set to the appropriate version.
		foreach ( $registered as $slug => $name ) {
			// Check if this location specifically supports localizing
			if ( Registry::is_location_localizable( $type, $slug ) ) {
				// Check if a location is set for the current language
				if ( isset( $locations[ "{$slug}-lang{$current_lang}"] ) ) {
					$locations[ $slug ] = $locations[ "{$slug}-lang{$current_lang}"];
				} else
				// Alternatively check if a location is set for the default one
				if ( isset( $locations[ "{$slug}-lang{$default_lang}"] ) ) {
					$locations[ $slug ] = $locations[ "{$slug}-lang{$default_lang}"];
				}
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

		$locations = static::localize_locations( 'nav_menu', $locations, $_wp_registered_nav_menus );

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
	public static function localize_sidebar_locations( $locations ) {
		global $wp_registered_sidebars;

		$locations = static::localize_locations( 'sidebar', $locations, $wp_registered_sidebars );

		return $locations;
	}

	// =========================
	// ! Langlinks Handler
	// =========================

	/**
	 * Process any langlink type menu items into proper links.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate and retrieve the link's language.
	 * @uses Rewriter::localize_here() to try and localize the current URL.
	 *
	 * @param array $items The nav menu items.
	 *
	 * @return array The modified items.
	 */
	public static function handle_language_links( $items ) {
		foreach ( $items as $i => $item ) {
			if ( $item->type == 'langlink' ) {
				// Language link, set URL to the localized version of the current location
				// Delete the item if it's for a language that doesn't exist or is inactive
				if ( $language = Registry::languages()->get( $item->object ) ) {
					$item->url = Rewriter::localize_here( $language );
				} else {
					unset( $items[$i] );
				}
			}
		}

		return $items;
	}

	// =========================
	// ! General Filtering
	// =========================

	/**
	 * Replace the locale with that of the current language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_lang() to get the current language.
	 *
	 * @param string $locale The locale to replace.
	 *
	 * @return string The replaced locale.
	 */
	public static function rewrite_locale( $locale ) {
		// Abort if in the backend
		if ( is_backend() ) {
			return $locale;
		}

		// Return the current language's locale_name
		return Registry::current_lang( 'locale_name' );
	}

	/**
	 * Add the text direction class and language classes.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_lang() to get the current language.
	 *
	 * @param array $classes The current list of body classes.
	 *
	 * @return array The modified list of classes.
	 */
	public static function add_body_classes( $classes ) {
		// Add text direction
		$classes[] = is_rtl() ? 'rtl' : 'ltr';

		// Add language slug
		$classes[] = 'language-' . Registry::current_lang( 'slug' );

		return $classes;
	}

	/**
	 * Replace a post ID with it's translation for the current language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_lang() to get the current language.
	 * @uses Translator::get_object_translation() to get the translated post ID.
	 *
	 * @param int|string $post_id The post ID to be replaced.
	 *
	 * @return int The ID of the translation.
	 */
	public static function current_language_version( $post_id ) {
		$current_lang = Registry::current_lang();

		$post_id = Translator::get_post_translation( $post_id, $current_lang, true );

		return $post_id;
	}
}