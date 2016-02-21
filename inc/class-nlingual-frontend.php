<?php
/**
 * nLingual Frontend Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Frontend Functionality
 *
 * Hooks into various frontend systems to detect
 * the language, redirect as necessary, and filter
 * various forms of data including URLs, options, and
 * queries.
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */

class Frontend extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Don't do anything if in the backend
		if ( is_backend() ) {
			return;
		}

		// Language Detection/Redirection
		static::add_action( 'plugins_loaded', 'detect_language', 10, 0 );
		static::add_filter( 'wp', 'maybe_redirect_language', 10, 0 );
		static::add_filter( 'redirect_canonical', 'localize_canonical', 10, 2 );

		// URL Rewriting
		static::add_filter( 'home_url', 'localize_home_url', 10, 3 );
		static::add_filter( 'page_link', 'localize_page_link', 10, 2 );
		static::add_filter( 'user_trailingslashit', 'fix_trailingshash', 10, 1 );

		// The Mod rewriting
		static::add_filter( 'theme_mod_nav_menu_locations', 'localize_nav_menu_locations', 10, 1 );
		static::add_filter( 'sidebars_widgets', 'localize_sidebar_locations', 10, 1 );
		static::add_filter( 'wp_nav_menu_objects', 'handle_language_links', 10, 1 );

		// General fitlering
		static::add_filter( 'locale', 'rewrite_locale', 10, 0 );
		static::add_filter( 'body_class', 'add_body_classes', 10, 1 );
		static::add_filter( 'option_page_on_front', 'current_language_post', 10, 1 );
		static::add_filter( 'option_page_for_posts', 'current_language_post', 10, 1 );
		static::add_filter( 'option_date_format', 'localize_date_format', 10, 1 );

		// Front-end only query rewrites
		static::add_filter( 'get_previous_post_join', 'add_adjacent_translation_join_clause', 10, 1 );
		static::add_filter( 'get_next_post_join', 'add_adjacent_translation_join_clause', 10, 1 );
		static::add_filter( 'get_previous_post_where', 'add_adjacent_translation_where_clause', 10, 1 );
		static::add_filter( 'get_next_post_where', 'add_adjacent_translation_where_clause', 10, 1 );

		// Locale & GetText Rewrites
		static::add_action( 'wp', 'maybe_patch_wp_locale', 10, 0 );
		static::add_filter( 'gettext_with_context', 'handle_text_direction', 10, 4 );
	}

	// =========================
	// ! Language Detection/Redirection
	// =========================

	/**
	 * Detect the language based on the request or browser info.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate and retrieve a detected language.
	 * @uses Registry::get() to get the query var option.
	 * @uses Rewriter::process_url() to parse the current page URL.
	 * @uses Registry::set_language() to tentatively apply the detected language.
	 */
	public static function detect_language() {
		// First, check if the language was specified by the GET or POST parameters
		if ( ( $query_var = Registry::get( 'query_var' ) ) && isset( $_REQUEST[ $query_var ] ) ) {
			// Even if the language specified is invalid, don't fallback from here.
			$language = Registry::languages()->get( $_REQUEST[ $query_var ] );
			$mode = 'REQUESTED';
		}
		// Failing that, get the language from the url
		elseif ( $language = Rewriter::process_url( null, 'language' ) ) {
			$mode = 'REQUESTED';
		}
		// Fallback to finding the first match in the accepted languages list
		else {
			$accepted_languages = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			// Loop through them and get the first match
			foreach ( $accepted_languages as $language_tag ) {
				// Remove the quality flag
				$language_tag = preg_replace( '/;q=[\d\.]+/', '', $language_tag );

				// Stop at the first matched language found
				if ( $language = Registry::languages()->match_tag( $language_tag ) ) {
					break;
				}
			}
			$mode = 'ACCEPTED';
		}

		if ( $language ) {
			/**
			 * Stores the language originally requested or accepted.
			 *
			 * @since 2.0.0
			 *
			 * @var bool|int
			 */
			define( "NL_{$mode}_LANGUAGE", $language->id );

			// Set the language, but don't lock it
			Registry::set_language( $language );
		}
	}

	/**
	 * Check if the language declared matches that of the queried object.
	 *
	 * If not, redirect appropriately based on post_language_override option.
	 *
	 * @since 2.0.0
	 *
	 * @uses NL_REQUESTED_LANGUAGE to check if the language was specifically requested.
	 * @uses NL_ORIGINAL_URL to compare the redirect URL with the original, to prevent loops.
	 * @uses Registry::current_language() to get the current language object.
	 * @uses Translator::get_post_language() to get the language of the queried post.
	 * @uses Translator::get_post_translation() to find the post's translation.
	 * @uses Registry::get() to retrieve the post_language_override option.
	 * @uses Rewriter::localize_here() to generate the localized URL for the language.
	 */
	public static function maybe_redirect_language() {
		// Don't do anything on non-HEAD/GET request
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && ! in_array( strtoupper( $_SERVER['REQUEST_METHOD'] ), array( 'GET', 'HEAD' ) ) ) {
			return;
		}

		// Don't do anything on 404s either
		if ( is_404() ) {
			return;
		}

		// Default the redirect language to the current one
		$redirect_language = Registry::current_language();

		// Check if the queried object is a post
		if ( is_a( get_queried_object(), 'WP_Post' ) ) {
			// Get the language of the post
			$post_language = Translator::get_post_language( get_queried_object_id() );

			// If the post has a language, and it doesn't match the current one,
			// And the override is set, or otherwise the language wasn't specified,
			// Redirect to the post's language
			if ( $post_language && Registry::is_current_language( $post_language )
			&& ( Registry::get( 'post_language_override', false ) || ! defined( 'NL_REQUESTED_LANGUAGE' ) ) ) {
				$redirect_language = $post_language;
			}
		}
		// If the language was already specified, or otherwise it's the default and skip is enabled, do nothing
		elseif ( defined( 'NL_REQUESTED_LANGUAGE' ) xor ( Registry::in_default_language() && Registry::get( 'skip_default_l10n' ) ) ) {
			return;
		}

		// Get the new URL localized for the redirect language
		$redirect_url = Rewriter::localize_here( $redirect_language );

		/**
		 * Filter the language redirect URL.
		 *
		 * @since 2.0.0
		 *
		 * @param string   $redirect_url      The URL localized for the determined language.
		 * @param Language $redirect_language The Language the URL was localized for.
		 */
		$redirect_url = apply_filters( 'nlingual_maybe_redirect_language', $redirect_url, $redirect_language );

		// Determine the status code to use
		$status = Registry::get( 'redirection_permanent' ) ? 301 : 302;

		// Redirect,but don't allow a loop; make sure they're different (trailing slash agnostic)
		if ( $redirect_url && untrailingslashit( NL_ORIGINAL_URL ) != untrailingslashit( $redirect_url ) ) {
			// Exit if redirect was successful
			if ( wp_redirect( $redirect_url, $status ) ) {
				exit;
			}
		}
	}

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
	 *
	 * @return string The localized home URL.
	 */
	public static function localize_home_url( $url, $path, $scheme ) {
		// Check if we shouldn't actually localize this
		// (will be indicated by custom $scheme value)
		if ( $scheme == NL_UNLOCALIZED ) {
			return $url;
		}

		// Return the localized version of the URL
		return Rewriter::localize_url( $url );
	}

	/**
	 * Localize a page's URL.
	 *
	 * Namely, detect if it's a translation of the home page and return the localize home URL.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Translator::get_post_translation() to get the post for that language.
	 *
	 * @param string $permalink The permalink of the post.
	 * @param int    $page_id   The ID of the page.
	 *
	 * @return string The localized permalink.
	 */
	public static function localize_page_link( $permalink, $page_id ) {
		$current_language = Registry::current_language();
		$translation = Translator::get_post_translation( $page_id, $current_language, true );

		if ( $translation == get_option( 'page_on_front' ) ) {
			$permalink = home_url();
		}

		return $permalink;
	}

	/**
	 * Fix the trailing slash on a URL.
	 *
	 * Makes sure that the trailing slash before the query string if present.
	 *
	 * @since 2.0.0
	 *
	 * @param string $string The URL being filtered.
	 *
	 * @return string The filtered URL.
	 */
	public static function fix_trailingshash( $url ) {
		// First, check if a query string is present
		if ( strpos( $url, '?' ) !== false ) {
			// Assuming the query string doesn't follow a slash already, move it to be after the slash
			$url = preg_replace( '#(?!/)(\?.*?)/$#', '/$1', $url );
		}

		return $url;
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
	 * @uses Registry::default_language() to get the default language ID.
	 * @uses Registry::current_language() to get the current language ID.
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
			return $locations;
		}

		// Get the default and current languages
		$default_language = Registry::default_language();
		$current_language = Registry::current_language();

		// Ensure the unlocalized locations are set to the appropriate version.
		foreach ( $registered as $slug => $name ) {
			// Check if this location specifically supports localizing
			if ( Registry::is_location_localizable( $type, $slug ) ) {
				// Check if a location is set for the current language
				if ( isset( $locations[ "{$slug}-language{$current_language->id}"] ) ) {
					$locations[ $slug ] = $locations[ "{$slug}-language{$current_language->id}"];
				}
				// Alternatively check if a location is set for the default one
				elseif ( isset( $locations[ "{$slug}-language{$default_language->id}"] ) ) {
					$locations[ $slug ] = $locations[ "{$slug}-language{$default_language->id}"];
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
	 * Process any languagelink type menu items into proper links.
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
			if ( $item->type == 'nl_language_link' ) {
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
	 * @uses Registry::current_language() to get the current language.
	 *
	 * @return string The replaced locale.
	 */
	public static function rewrite_locale() {
		// Return the current language's locale_name
		return Registry::current_language( 'locale_name' );
	}

	/**
	 * Add the text direction class and language classes.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 *
	 * @param array $classes The current list of body classes.
	 *
	 * @return array The modified list of classes.
	 */
	public static function add_body_classes( $classes ) {
		// Add text direction if not already there
		$direction = is_rtl() ? 'rtl' : 'ltr';
		if ( ! in_array( $direction, $classes ) ) {
			$classes[] = $direction;
		}

		// Add language slug
		$classes[] = 'language-' . Registry::current_language( 'slug' );

		return $classes;
	}

	/**
	 * Replace a post ID with it's translation for the default language.
	 *
	 * @since 2.0.0
	 *
	 * @api
	 *
	 * @uses Registry::default_language() to get the default language.
	 * @uses Translator::get_object_translation() to get the translated post ID.
	 *
	 * @param int|string $post_id The post ID to be replaced.
	 *
	 * @return int The ID of the translation.
	 */
	public static function default_language_post( $post_id ) {
		$default_language = Registry::default_language();

		$post_id = Translator::get_post_translation( $post_id, $default_language, false );

		return $post_id;
	}

	/**
	 * Replace a post ID with it's translation for the current language.
	 *
	 * @since 2.0.0
	 *
	 * @api
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Translator::get_object_translation() to get the translated post ID.
	 *
	 * @param int|string $post_id The post ID to be replaced.
	 *
	 * @return int The ID of the translation.
	 */
	public static function current_language_post( $post_id ) {
		$current_language = Registry::current_language();

		$post_id = Translator::get_post_translation( $post_id, $current_language, true );

		return $post_id;
	}

	/**
	 * Localizes the date_format option.
	 *
	 * @since 2.0.0
	 *
	 * @param string $format The date format string to filter.
	 *
	 * @return string The filtered date format string.
	 */
	public static function localize_date_format( $format ) {
		// Abort if localize_date option isn't enabled
		if ( ! Registry::get( 'localize_date' ) ) {
			return $format;
		}

		$domain = wp_get_theme()->get( 'TextDomain' );
		$format = \__( $format, $domain );
		return $format;
	}

	// =========================
	// ! Query Filters
	// =========================

	/**
	 * Add the translations table to the join clause for adjacent posts.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::is_post_type_supported() to check for post type support.
	 *
	 * @param string $clause The join clause to add to.
	 *
	 * @return string The modified join clause.
	 */
	public static function add_adjacent_translation_join_clause( $clause ) {
		global $wpdb;

		// Get the current post
		$post = get_post();

		// Abort if post type is not supported
		if ( ! Registry::is_post_type_supported( $post->post_type ) ) {
			return $clause;
		}

		// Add the join for the translations table
		$clause .= " LEFT JOIN $wpdb->nl_translations AS nl ON (p.ID = nl.object_id AND nl.object_type = 'post')";

		return $clause;
	}

	/**
	 * Add the language condition to the where clause for adjacent posts.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::is_post_type_supported() to check for post type support.
	 * @uses Translator::get_post_language() to get the language of the current post.
	 *
	 * @param string $clause The where clause to add to.
	 *
	 * @return string The modified where clause.
	 */
	public static function add_adjacent_translation_where_clause( $clause ) {
		global $wpdb;

		// Get the current post
		$post = get_post();

		// Abort if post type is not supported
		if ( ! Registry::is_post_type_supported( $post->post_type ) ) {
			return $clause;
		}

		// Get the posts language, fail if somehow not found
		if ( ! ( $language = Translator::get_post_language( $post->ID ) ) ) {
			return $clause;
		}

		// Add the language condition
		$clause .= $wpdb->prepare( " AND nl.language_id = %d", $language->id );

		return $clause;
	}

	// =========================
	// ! Locale & GetText Rewrites
	// =========================

	/**
	 * Replace $wp_locale with patched version if desired.
	 *
	 * @since 2.0.0
	 *
	 * @global WP_Locale $wp_locale The original Date/Time Locale object.
	 *
	 * @uses Registry::get() to check for the patch_wp_locale option.
	 */
	public static function maybe_patch_wp_locale() {
		global $wp_locale;

		// Abort if no patching is wanted
		if ( ! Registry::get( 'patch_wp_locale' ) ) {
			return;
		}

		// Replace with new isntance of patched one
		$wp_locale = new Locale();
	}

	/**
	 * Filters the translated form of "ltr" based on current language.
	 *
	 * @since 2.0.0
	 *
	 * @param string $translation The translated text.
	 * @param string $text        The original text.
	 * @param string $context     The unique context for this text.
	 *
	 * @return string The filtered value.
	 */
	public static function handle_text_direction( $translation, $text, $context ) {
		// If text direction context, use language's assigned one
		if ( $context == 'text direction' ) {
			return Registry::current_language( 'direction' );
		}

		return $translation;
	}
}
