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
	// ! Properties
	// =========================

	/**
	 * Record of added hooks.
	 *
	 * @internal Used by the Handler enable/disable methods.
	 *
	 * @since 2.6.0
	 *
	 * @var array
	 */
	protected static $implemented_hooks = array();

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.8.0 Added rewrite_locale from System.
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Don't do anything if in the backend
		if ( is_backend() ) {
			return;
		}

		// Language Rewriting
		self::add_hook( 'locale', 'rewrite_locale', 10, 0 );

		// Language Redirection
		self::add_hook( 'wp', 'redirect_language', 10, 0 );
		self::add_hook( 'redirect_canonical', 'localize_canonical', 10, 2 );

		// The Mod rewriting
		self::add_hook( 'theme_mod_nav_menu_locations', 'localize_nav_menu_locations', 10, 1 );
		self::add_hook( 'sidebars_widgets', 'localize_sidebar_locations', 10, 1 );
		self::add_hook( 'wp_get_nav_menu_items', 'localize_menu_items', 10, 3 );
		self::add_hook( 'wp_nav_menu_objects', 'handle_language_links', 10, 1 );

		// General fitlering
		self::add_hook( 'body_class', 'add_body_classes', 10, 1 );
		self::add_hook( 'option_page_on_front', 'current_language_post', 10, 1 );
		self::add_hook( 'option_page_for_posts', 'current_language_post', 10, 1 );
		self::add_hook( 'option_sticky_posts', 'current_language_post_list', 10, 1 );
		self::add_hook( 'option_date_format', 'localize_date_format', 10, 1 );

		// Frontend-Only Query Rewrites
		self::add_hook( 'get_previous_post_join', 'add_adjacent_translation_join_clause', 10, 1 );
		self::add_hook( 'get_next_post_join', 'add_adjacent_translation_join_clause', 10, 1 );
		self::add_hook( 'get_previous_post_where', 'add_adjacent_translation_where_clause', 10, 1 );
		self::add_hook( 'get_next_post_where', 'add_adjacent_translation_where_clause', 10, 1 );
		self::add_hook( 'getarchives_join', 'add_archives_translation_join_clause', 10, 2 );
		self::add_hook( 'getarchives_where', 'add_archives_translation_where_clause', 10, 2 );

		// Locale & GetText Rewrites
		self::add_hook( 'wp', 'maybe_patch_wp_locale', 10, 0 );
		self::add_hook( 'gettext_with_context', 'handle_text_direction', 10, 4 );

		// Frontend-Only URL Rewrites
		self::add_hook( 'site_url', 'localize_uri', 10, 1 );
		self::add_hook( 'stylesheet_directory_uri', 'localize_uri', 10, 1 );
		self::add_hook( 'template_directory_uri', 'localize_uri', 10, 1 );
		self::add_hook( 'upload_dir', 'localize_dir', 10, 1 );
		self::add_hook( 'the_content', 'localize_attachment_urls', 10, 1 );

		// Script/Style Enqueues
		self::add_hook( 'wp_enqueue_scripts', 'enqueue_assets', 10, 0 );

		// Admin Bar Additions
		self::add_hook( 'admin_bar_menu', 'add_translate_menu', 81, 1 ); // should occur after Edit menu item
	}

	// =========================
	// ! Language Rewriting
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

	// =========================
	// ! Language Redirection
	// =========================

	/**
	 * Check if a language redirection is needed.
	 *
	 * For example, if the language requested doesn't match that of the queried object,
	 * or if the language requested is inactive.
	 *
	 * @since 2.9.2 Specify redirected-by for wp_redirect().
	 * @since 2.9.1 Added check for sitemap requests.
	 * @since 2.6.0 Allow inactive language if user is logged in.
	 * @since 2.2.0 Regigged post language redirecting to account for untranslated homepage.
	 * @since 2.0.0
	 *
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

		// Don't do anything on 404s or sitemap stuff either
		if ( is_404() || get_query_var( 'sitemap' ) || get_query_var( 'sitemap-stylesheet' ) ) {
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
						// Check if post_language_override is set
						if ( Registry::get( 'post_language_override', false ) ) {
							// Finally, check if the homepage is being requested when there's no translation
							if ( ! ( is_front_page() && ! Translator::get_post_translation( $queried_object ) ) ) {
								$redirect_language = $post_language;
							}
						}
					}
				}
			}
		}

		// If the language isn't active (and they're not logged in), fallback to the accepted or default language
		if ( ! $redirect_language->active && ! is_user_logged_in() ) {
			$redirect_language = Registry::accepted_language() ?: Registry::default_language();
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

		// Redirect, but don't allow a loop; make sure they're different (trailing slash agnostic)
		if ( $redirect_url && untrailingslashit( NL_ORIGINAL_URL ) != untrailingslashit( $redirect_url ) ) {
			// Exit if redirect was successful
			header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
			if ( wp_redirect( $redirect_url, $status, 'nLingual' ) ) {
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
	 * @since 2.9.2 Ensure $_wp_registered_nav_menus is populated.
	 * @since 2.0.0
	 *
	 * @see Frontend::localize_locations()
	 *
	 * @global array $_wp_registered_nav_menus The registered nav menus list.
	 */
	public static function localize_nav_menu_locations( $locations ) {
		global $_wp_registered_nav_menus;

		if ( $_wp_registered_nav_menus ) {
			$locations = self::localize_locations( 'nav_menu', $locations, $_wp_registered_nav_menus );
		}

		return $locations;
	}

	/**
	 * Replaces the registered sidebars with versions for each active language.
	 *
	 * @since 2.9.2 Ensure $wp_registered_sidebars is populated.
	 * @since 2.0.0
	 *
	 * @see Frontend::localize_locations()
	 *
	 * @global array $wp_registered_sidebars The registered sidebars list.
	 */
	public static function localize_sidebar_locations( $locations ) {
		global $wp_registered_sidebars;

		if ( $wp_registered_sidebars ) {
			$locations = self::localize_locations( 'sidebar', $locations, $wp_registered_sidebars );
		}

		return $locations;
	}

	// =========================
	// ! Menu Item Handling
	// =========================

	/**
	 * Localize the menu items if applicable.
	 *
	 * @since 2.6.0 Add check to make sure an item's post type is supported.
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

		// If the location is set but not already localizable, localize the items
		$localize_items = $theme_location && ! Registry::is_location_supported( 'nav_menu', $theme_location );

		/**
		 * Filters the result.
		 *
		 * @since 2.9.2
		 *
		 * @param bool   $localize_items Wether or not to localize menu items.
		 * @param object $menu           The menu being considered.
		 * @param array  $items          The items for the menu.
		 */
		$localize_items = apply_filters( 'nlingual_localize_menu_items', $localize_items, $menu, $items );

		// Don't bother if the location wasn't found or is already localizable
		if ( $localize_items ) {
			// Loop through each item, attempt to localize
			foreach ( $items as $item ) {
				// If it's for a (supported) post that has a translation (that's not itself),
				// update the title/link with that of the translation
				if ( $item->type == 'post_type'
				&& Registry::is_post_type_supported( $item->object )
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
				// Language link, set URL to the localized version of the current location,
				// Delete the item if it's for a language that doesn't exist or is inactive
				if ( ( $language = Registry::get_language( $item->object ) )
				&& ( $language->active || is_user_logged_in() ) ) {
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
	 * @since 2.9.0 Fallback to original ID if no default language version is found.
	 * @since 2.6.0 Add check to make sure post's type is supported.
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
		if ( Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			$default_language = Registry::default_language();

			$post_id = Translator::get_post_translation( $post_id, $default_language, 'return self' );
		}

		return $post_id;
	}

	/**
	 * Replace a post ID with it's translation for the current language.
	 *
	 * @since 2.9.0 Add check to make sure the post's status is usable.
	 * @since 2.6.0 Add check to make sure post's type is supported.
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
		if ( Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			$current_language = Registry::current_language();

			$translation_id = Translator::get_post_translation( $post_id, $current_language );

			// If translation is found, check status
			if ( $translation_id ) {
				$translation_status = get_post_status( $translation_id );

				// If the translation is published, or the same as the original, use it
				if ( $translation_status == 'publish' || $translation_status == get_post_status( $post_id ) ) {
					return $translation_id;
				}
			}
		}

		return $post_id;
	}

	/**
	 * Replace entries in a post ID list with their translations for the current language.
	 *
	 * @since 2.8.0
	 *
	 * @api
	 *
	 * @uses Frontend::current_language_post_list() on each entry in the array.
	 *
	 * @param array $post_ids The post IDs to be replaced.
	 *
	 * @return array The IDs of the translations.
	 */
	public static function current_language_post_list( $post_ids ) {
		$post_ids = array_map( array( __NAMESPACE__ . '\Frontend', 'current_language_post' ), $post_ids );

		return $post_ids;
	}

	/**
	 * Localizes the date_format option.
	 *
	 * @since 2.10.0 Use translate to avoid gettext extraction issues.
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

		// phpcs:ignore WordPress.WP.I18n
		$format = translate( $format, $domain );

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
	 * @global \WP_Locale $wp_locale The original Date/Time Locale object.
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

	// =========================
	// ! Script/Style Enqueues
	// =========================

	/**
	 * Enqueue necessary styles and scripts.
	 *
	 * @since 2.6.0
	 */
	public static function enqueue_assets() {
		// Abort if not showing the admin bar
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		// Admin styling
		wp_enqueue_style( 'nlingual-public', plugins_url( 'css/public.css', NL_PLUGIN_FILE ), NL_PLUGIN_VERSION, 'screen' );
	}

	// =========================
	// ! Admin Bar Additions
	// =========================

	/**
	 * Add a Translate This node/menu to the Admin Bar.
	 *
	 * @since 2.9.2 Check create_posts rather than edit_post, ensure
	 *              links are added before registering menu.
	 * @since 2.6.0
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar object.
	 */
	public static function add_translate_menu( \WP_Admin_Bar $wp_admin_bar ) {
		global $wp_the_query;

		$current_object = $wp_the_query->get_queried_object();

		if ( ! empty( $current_object->post_type )
		&& Registry::is_post_type_supported( $current_object->post_type )
		&& Translator::get_post_language( $current_object->ID, 'true value' )
		&& ( $post_type_object = get_post_type_object( $current_object->post_type ) )
		&& current_user_can( $post_type_object->cap->create_posts )
		&& $post_type_object->show_in_admin_bar ) {
			// Compile a list of missing language translations
			$translations = Translator::get_post_translations( $current_object->ID, 'include self' );
			$missing_translations = array();
			foreach ( Registry::languages() as $language ) {
				if ( ! isset( $translations[ $language->id ] ) ) {
					$missing_translations[] = $language;
				}
			}

			if ( ! $missing_translations ) {
				return;
			}

			// Add links for each missing language
			$has_links = false;
			foreach ( $missing_translations as $language ) {
				$translate_link = get_translate_post_link( $current_object->ID, $language->id );
				if ( $translate_link ) {
					$has_links = true;
					$wp_admin_bar->add_node( array(
						'parent' => 'nlingual',
						'id' => 'nlingual-' . $language->slug,
						'title' => _f( 'Translate to %s', 'nlingual', $language->system_name ),
						'href' => $translate_link,
					) );
				}
			}

			// Add the menu item if we have links
			if ( $has_links ) {
				$label = property_exists( $post_type_object->labels, 'translate_item' ) ? $post_type_object->labels->translate_item : __( 'Translate This', 'nlingual' );
				$wp_admin_bar->add_menu( array(
					'id' => 'nlingual',
					'title' => $label,
				) );
			}
		}
	}
}
