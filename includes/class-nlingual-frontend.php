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
final class Frontend extends Handler {
	// =========================
	// ! Utilities
	// =========================

	/**
	 * @since 2.0.0
	 *
	 * @return Language|bool The accepted language, false if no match.
	 */
	private static function get_accepted_language() {
		// Abort if no accept-language entry is present
		if ( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			return false;
		}

		$accepted_languages = explode( ',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
		// Loop through them and get the first match
		foreach ( $accepted_languages as $language_tag ) {
			// Remove the quality flag
			$language_tag = preg_replace( '/;q=[\d\.]+/', '', $language_tag );

			// Stop at the first matched language found
			if ( $language = Registry::languages( 'active' )->match_tag( $language_tag ) ) {
				return $language;
			}
		}

		return false;
	}

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
		self::add_action( 'plugins_loaded', 'detect_language', 10, 0 );
		self::add_filter( 'wp', 'redirect_language', 10, 0 );
		self::add_filter( 'redirect_canonical', 'localize_canonical', 10, 2 );

		// The Mod rewriting
		self::add_filter( 'theme_mod_nav_menu_locations', 'localize_nav_menu_locations', 10, 1 );
		self::add_filter( 'sidebars_widgets', 'localize_sidebar_locations', 10, 1 );
		self::add_filter( 'wp_get_nav_menu_items', 'localize_menu_items', 10, 3 );
		self::add_filter( 'wp_nav_menu_objects', 'handle_language_links', 10, 1 );

		// General fitlering
		self::add_filter( 'locale', 'rewrite_locale', 10, 0 );
		self::add_filter( 'body_class', 'add_body_classes', 10, 1 );
		self::add_filter( 'option_page_on_front', 'current_language_post', 10, 1 );
		self::add_filter( 'option_page_for_posts', 'current_language_post', 10, 1 );
		self::add_filter( 'option_date_format', 'localize_date_format', 10, 1 );

		// Frontend-Only Query Rewrites
		self::add_filter( 'get_previous_post_join', 'add_adjacent_translation_join_clause', 10, 1 );
		self::add_filter( 'get_next_post_join', 'add_adjacent_translation_join_clause', 10, 1 );
		self::add_filter( 'get_previous_post_where', 'add_adjacent_translation_where_clause', 10, 1 );
		self::add_filter( 'get_next_post_where', 'add_adjacent_translation_where_clause', 10, 1 );
		self::add_filter( 'getarchives_join', 'add_archives_translation_join_clause', 10, 2 );
		self::add_filter( 'getarchives_where', 'add_archives_translation_where_clause', 10, 2 );

		// Locale & GetText Rewrites
		self::add_action( 'wp', 'maybe_patch_wp_locale', 10, 0 );
		self::add_filter( 'gettext_with_context', 'handle_text_direction', 10, 4 );

		// Frontend-Only URL Rewrites
		self::add_filter( 'site_url', 'localize_uri', 10, 1 );
		self::add_filter( 'stylesheet_directory_uri', 'localize_uri', 10, 1 );
		self::add_filter( 'template_directory_uri', 'localize_uri', 10, 1 );
		self::add_filter( 'upload_dir', 'localize_dir', 10, 1 );
		self::add_filter( 'the_content', 'localize_attachment_urls', 10, 1 );
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
	 * @uses Frontend::get_accepted_language() to determine a perferred language.
	 * @uses Registry::set_language() to tentatively apply the detected language.
	 */
	public static function detect_language() {
		$language = false;

		// First, check if the language was specified by the GET or POST parameters
		if ( ( $query_var = Registry::get( 'query_var' ) ) && isset( $_REQUEST[ $query_var ] ) ) {
			// Even if the language specified is invalid, don't fallback from here.
			$language = Registry::get_language( $_REQUEST[ $query_var ] );
			$mode = 'REQUESTED';
		}
		// Failing that, get the language from the url
		elseif ( ( $the_url = Rewriter::process_url() ) && isset( $the_url->meta['language'] ) ) {
			$language = $the_url->meta['language'];
			// If the language was determined but skip is enabled, redirect.
			if ( Registry::is_language_default( $language )
			&& Registry::get( 'skip_default_l10n' )
			&& Registry::get( 'url_rewrite_method' ) == 'path' ) {
				// Determine the status code to use
				$status = Registry::get( 'redirection_permanent' ) ? 301 : 302;

				// Redirect, exit if successful
				if ( wp_redirect( $the_url->build(), $status ) ) {
					exit;
				}
			}

			$mode = 'REQUESTED';
		}
		// Fallback to finding the first match in the accepted languages list
		elseif ( $language = self::get_accepted_language() ) {
			$mode = 'ACCEPTED';
		}

		/**
		 * Filter the detected language.
		 *
		 * @since 2.0.0
		 *
		 * @param Language $language The language detected.
		 */
		$language = apply_filters( 'nlingual_detected_language', $language );

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
	 * Check if a language redirection is needed.
	 *
	 * For example, if the language requested doesn't match that of the queried object,
	 * or if the language requeste is inactive.
	 *
	 * @since 2.2.0 Regigged post language redirecting to account for untranslated homepage.
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
	public static function redirect_language() {
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
		$queried_object = get_queried_object();
		if ( is_a( $queried_object, 'WP_Post' ) ) {
			// Check if it's post type is supported
			if ( Registry::is_post_type_supported( $queried_object->post_type ) ) {
				// Get the post's language
				$post_language = Translator::get_post_language( $queried_object );

				// If not set, but language_is_required is set, use the default language
				if ( ! $post_language && Registry::get( 'language_is_required' ) ) {
					$post_language = Registry::default_language();
				}

				// If there is a language to test, proceed
				if ( $post_language ) {
					// Now, check if it's different from the current language
					if ( ! Registry::is_language_current( $post_language ) ) {
						// Check if post_language_override is set, or otherwise no language was specified
						if ( Registry::get( 'post_language_override', false ) || ! defined( 'NL_REQUESTED_LANGUAGE' ) ) {
							// Finally, check if the homepage is being requested when there's no translation
							if ( ! ( is_front_page() && ! Translator::get_post_translation( $queried_object ) ) ) {
								$redirect_language = $post_language;
							}
						}
					}
				}
			}
		}
		// If it's the default and skip is enabled, do nothing
		elseif ( Registry::in_default_language() && Registry::get( 'skip_default_l10n' ) ) {
			return;
		}

		// If the language isn't active, fallback to the accepted or default language
		if ( ! $redirect_language->active ) {
			$redirect_language = self::get_accepted_language() ?: Registry::default_language();
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
	// ! Menu/Sidebar Rewriting
	// =========================

	/**
	 * Shared logic for menu/sidebar rewriting.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_location_supported() to check for support.
	 * @uses Registry::default_language() to get the default language ID.
	 * @uses Registry::current_language() to get the current language ID.
	 *
	 * @param string $type       The type of location.
	 * @param array  $locations  The list of locations to filter.
	 * @param array  $registered The list of orignially register locations.
	 *
	 * @return array The modified $locations with unlocalized versions updated.
	 */
	private static function localize_locations( $type, $locations, $registered ) {
		// Abort if not at all supported
		if ( ! Registry::is_location_supported( $type ) ) {
			return $locations;
		}

		// Get the default and current languages
		$default_language = Registry::default_language();
		$current_language = Registry::current_language();

		// Ensure the unlocalized locations are set to the appropriate version.
		foreach ( $registered as $slug => $name ) {
			// Check if this location specifically supports localizing
			if ( Registry::is_location_supported( $type, $slug ) ) {
				$current_id = "{$slug}__language_{$current_language->id}";
				$default_id = "{$slug}__language_{$default_language->id}";

				// Check if a location is set for the current language
				if ( isset( $locations[ $current_id ] ) ) {
					$locations[ $slug ] = $locations[ $current_id ];
				}
				// Alternatively check if a location is set for the default one
				elseif ( isset( $locations[ $default_id ] ) ) {
					$locations[ $slug ] = $locations[ $default_id ];
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

		$locations = self::localize_locations( 'nav_menu', $locations, $_wp_registered_nav_menus );

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

		$locations = self::localize_locations( 'sidebar', $locations, $wp_registered_sidebars );

		return $locations;
	}

	// =========================
	// ! Menu Item Handling
	// =========================

	/**
	 * Localize the menu items if applicable.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $items The nav menu items.
	 * @param object $menu  The menu the items belong to.
	 *
	 * @return array The modified menu items.
	 */
	public static function localize_menu_items( $items, $menu ) {
		// Get the locations so we can find what this menu belongs to
		$theme_location = null;
		foreach ( get_nav_menu_locations() as $location => $menu_id ) {
			if ( $menu_id == $menu->term_id ) {
				$theme_location = $location;
				break;
			}
		}

		// Don't bother if the location wasn't found or is already localizable
		if ( $theme_location && ! Registry::is_location_supported( 'nav_menu', $theme_location ) ) {
			// Loop through each item, attempt to localize
			foreach ( $items as $item ) {
				// If it's for a post that has a translation (that's not itself),
				// update the title/link with that of the translation
				if ( $item->type == 'post_type'
				&& ( $translation = Translator::get_post_translation( $item->object_id, null ) )
				&& $translation != $item->object_id ) {
					$item->object_id = $translation;
					$item->title = get_the_title( $translation );
					$item->url = get_permalink( $translation );
				}
			}
		}
		return $items;
	}

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
				if ( $language = Registry::get_language( $item->object ) ) {
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
		$format = __( $format, $domain );
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
	 * @global \wpdb $wpdb The database abstraction class instance.
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
	 * @global \wpdb $wpdb The database abstraction class instance.
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

	/**
	 * Add the translations table to the join clause for get_archives.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::is_post_type_supported() to check for post type support.
	 *
	 * @param string $clause  The join clause to add to.
	 * @param array  $request The request arguments.
	 *
	 * @return string The modified join clause.
	 */
	public static function add_archives_translation_join_clause( $clause, $request ) {
		global $wpdb;

		// Abort if post type is not supported
		if ( ! Registry::is_post_type_supported( $request['post_type'] ) ) {
			return $clause;
		}

		// Add the join for the translations table
		$clause .= " LEFT JOIN $wpdb->nl_translations AS nl ON ($wpdb->posts.ID = nl.object_id AND nl.object_type = 'post')";

		return $clause;
	}

	/**
	 * Add the language condition to the where clause for get_archives.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::is_post_type_supported() to check for post type support.
	 * @uses Registry::current_language() to get the current language ID.
	 *
	 * @param string $clause The where clause to add to.
	 * @param array  $request The request arguments.
	 *
	 * @return string The modified where clause.
	 */
	public static function add_archives_translation_where_clause( $clause, $request ) {
		global $wpdb;

		// Abort if post type is not supported
		if ( ! Registry::is_post_type_supported( $request['post_type'] ) ) {
			return $clause;
		}

		// Add the language condition
		$clause .= $wpdb->prepare( " AND nl.language_id = %d", Registry::current_language( 'id' ) );

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

	// =========================
	// ! URL Rewriting
	// =========================

	/**
	 * Localize resource URIs.
	 *
	 * @since 2.0.0
	 *
	 * @param string $uri The URI to filter.
	 *
	 * @return string The filtered URI.
	 */
	public static function localize_uri( $uri ) {
		// Only do so if using the domain rewrite method, and if enabled
		if ( Registry::get( 'url_rewrite_method' ) == 'domain'
		&& Rewriter::will_do_localization() ) {
			$uri = Rewriter::localize_url( $uri );
		}
		return $uri;
	}

	/**
	 * Localize the uploads directory URLs.
	 *
	 * @since 2.0.0
	 *
	 * @param array $dir The upload directory array.
	 *
	 * @return array The filtered URLs.
	 */
	public static function localize_dir( $dir ) {
		// Only do so if using the domain rewrite method, and if enabled
		if ( Registry::get( 'url_rewrite_method' ) == 'domain'
		&& Rewriter::will_do_localization() ) {
			$dir['url'] = Rewriter::localize_url( $dir['url'] );
			$dir['baseurl'] = Rewriter::localize_url( $dir['baseurl'] );
		}
		return $dir;
	}

	/**
	 * Localize any attachment URLs found.
	 *
	 * @since 2.0.0
	 *
	 * @param string $content The content to filter.
	 *
	 * @return string The filtered content.
	 */
	public static function localize_attachment_urls( $content ) {
		// Only do so if using the domain rewrite method, and if enabled
		if ( Registry::get( 'url_rewrite_method' ) == 'domain'
		&& Rewriter::will_do_localization() ) {
			$find_url = get_home_url( null, '', 'unlocalized' ) . '/wp-content/uploads/';
			$replace_url = Rewriter::localize_url( $find_url );
			$content = str_replace( $find_url, $replace_url, $content );
		}
		return $content;
	}
}
