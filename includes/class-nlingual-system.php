<?php
/**
 * nLingual System
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Main System
 *
 * Sets up the database table aliases, the Registry,
 * and all the Handler classes.
 *
 * @api
 *
 * @since 2.0.0
 */
final class System extends Handler {
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

	/**
	 * Language switching log.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $language_stack = array();

	/**
	 * Blog language map.
	 *
	 * @internal
	 *
	 * @since 2.9.0
	 *
	 * @var array
	 */
	private static $blog_languages = array();

	/**
	 * The internal text domain cache.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $textdomain_cache = array();

	/**
	 * The internal text domain log.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $textdomain_log = array();

	/**
	 * The last locale text domains were reloaded for.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	private static $last_locale_reloaded = '';

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Cache the text domains and re-load them.
	 *
	 * @since 2.6.0 Removed erroneous uses of $the_locale instead of $new_locale.
	 * @since 2.0.0
	 *
	 * @global array $l10n The index of loaded text domain files.
	 *
	 * @param string $old_local The previous locale.
	 */
	public static function reload_textdomains( $old_local ) {
		global $l10n;
		if ( ! is_array( $l10n ) ) {
			$l10n = array();
		}

		// Current locale
		$new_locale = get_locale();

		// Abort if $new_locale matches the previous reload locale
		if ( $new_locale == self::$last_locale_reloaded ) {
			return;
		}

		// Record the new locale
		self::$last_locale_reloaded = $new_locale;

		// Cache the current list
		self::$textdomain_cache[ $old_local ] = $l10n;

		// If a list exists for the new local, us it
		if ( isset( self::$textdomain_cache[ $new_locale ] ) ) {
			$l10n = self::$textdomain_cache[ $new_locale ];
		}
		// Otherwise, go through each one and reload it
		else {
			// Backup and clear $l10n
			$old_textdomains = $l10n;
			$l10n = array();

			// Loop through all old domains
			foreach ( $old_textdomains as $domain => $mo ) {
				// If it wasn't logged, skip it (99% chance it's a filler NOOP)
				if ( ! isset( self::$textdomain_log[ $domain ] ) ) {
					continue;
				}

				// "default" is easy
				if ( $domain == 'default' ) {
					load_default_textdomain( $new_locale );
					continue;
				}

				// Get the type and path(s) from the log
				$type = self::$textdomain_log[ $domain ]['type'];
				$paths = self::$textdomain_log[ $domain ]['paths'];

				// The new mo file name
				$mofile = $new_locale . '.mo';

				// Filter it if needed
				if ( $type ) {
					$new_locale = apply_filters( "{$type}_locale", $new_locale, $domain );
				}

				// The new mo file name
				$mofile = $new_locale . '.mo';

				// In the case of a plugin, prefix with domain
				if ( $type == 'plugin' ) {
					$mofile = $domain . '-' . $mofile;
				}

				// Load it for each path
				foreach ( $paths as $dir ) {
					load_textdomain( $domain, $dir . '/' . $mofile );
				}
			}
		}
	}

	/**
	 * Switch to a different language.
	 *
	 * @since 2.8.0 Store the $reload_textdomains option when adding to stack.
	 *              Added check if already in requested language.
	 * @since 2.0.0
	 *
	 * @uses validate_language() to ensure $language is a Language object.
	 * @uses Registry::$current_language to get/update the current language.
	 * @uses Registry::$previous_languages to log the current language.
	 *
	 * @param mixed $language           The language object, slug or id.
	 * @param bool  $reload_textdomains Wether or not to reload text domains.
	 */
	public static function switch_language( $language, $reload_textdomains = false ) {
		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			return false; // Does not exist
		}

		// Log the current language and reload status
		self::$language_stack[] = array( Registry::current_language( 'id' ), $reload_textdomains );

		// If already in this language, finish
		if ( Registry::is_language_current( $language ) ) {
			return;
		}

		// Get the old locale for text domain reloading
		$old_local = get_locale();

		// Set to the desired language
		Registry::set_language( $language->id, false, 'override' );

		if ( $reload_textdomains ) {
			// Reload the text domains
			self::reload_textdomains( $old_local );
		}
	}

	/**
	 * Switch back to the previous language.
	 *
	 * @since 2.8.0 Added handling of re-reloading text domains.
	 *              Added check if already in previous language.
	 * @since 2.0.0
	 *
	 * @uses Registry::$previous_languages to get the previous language.
	 * @uses Registry::get() to get the default language id.
	 * @uses Registry::$current_language to update the current language.
	 */
	public static function restore_language() {
		$last_change = array_pop( self::$language_stack );

		// If no previous language, go with default
		if ( ! $last_change ) {
			$last_change = array( Registry::default_language( 'id' ), false ); // default to not reloading text domains
		}

		// Get the language and reload option
		list( $language, $reload_textdomains ) = $last_change;

		// If already in this language, finish
		if ( Registry::is_language_current( $language ) ) {
			return;
		}

		// Get the old locale for text domain reloading
		$old_local = get_locale();

		// Set to the last language
		Registry::set_language( $language, false, 'override' );

		if ( $reload_textdomains ) {
			// Reload the text domains
			self::reload_textdomains( $old_local );
		}
	}

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
	// ! Master Setup Method
	// =========================

	/**
	 * Register hooks and load options.
	 *
	 * @since 2.3.3 Redid table registration for multisite support,
	 *              dropped support for old nL_translations alias.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Loader::register_hooks() to setup plugin management.
	 * @uses Registry::load() to load the options.
	 * @uses System::register_hooks() to setup global functionality.
	 * @uses Backend::register_hooks() to setup backend functionality.
	 * @uses Manager::register_hooks() to setup admin screens.
	 * @uses Localizer::register_hooks() to setup Localize This widget.
	 * @uses Documenter::register_hooks() to setup admin documentation.
	 * @uses Frontend::register_hooks() to setup frontend functionality.
	 * @uses Liaison::register_hooks() to setup plugin cross-compatibility.
	 * @uses is_backend() to check if the query is for wp-admin.
	 */
	public static function setup() {
		global $wpdb;

		// Register the database tables, regenerate
		$wpdb->tables[] = 'nl_translations';
		$wpdb->tables[] = 'nl_localizations';
		$wpdb->set_blog_id( $wpdb->blogid );

		// Setup the registry
		Registry::load();

		// Register the Installer stuff
		Installer::register_hooks();

		// Register global hooks
		self::register_hooks();

		// Register the hooks of the subsystems
		Frontend::register_hooks();
		Backend::register_hooks();
		AJAX::register_hooks();
		Manager::register_hooks();
		Documenter::register_hooks();
		Localizer::register_hooks();
		Liaison::register_hooks();
	}

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.9.0 Added nl_is_translated query handling, enqueue_assets,
	 *              unique post slug and posts result filters.
	 * @since 2.8.0 Moved rewrite_locale to Frontend.
	 * @since 2.6.0 Added transition (un)flagging.
	 * @since 2.4.0 Only add patch_font_stack hook if before 4.6.
	 * @since 2.2.0 Reassigned synchronize_posts to wp_insert_post (better hook to use).
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Setup Stuff
		self::add_hook( 'plugins_loaded', 'setup_localizable_fields', 10, 0 );

		// Language Detection
		self::add_hook( 'plugins_loaded', 'detect_language', 10, 0 );

		// Text Domain Manipulation
		self::add_hook( 'theme_locale', 'log_textdomain_type', 10, 2 );
		self::add_hook( 'plugin_locale', 'log_textdomain_type', 10, 2 );
		self::add_hook( 'load_textdomain', 'log_textdomain_path', 10, 2 );

		// Post Changes
		self::add_hook( 'wp_insert_post', 'synchronize_posts', 10, 3 );
		self::add_hook( 'wp_trash_post', 'flag_transitioning_post', 10, 1 );
		self::add_hook( 'untrash_post', 'flag_transitioning_post', 10, 1 );
		self::add_hook( 'trashed_post', 'unflag_transitioning_post', 10, 1 );
		self::add_hook( 'untrashed_post', 'unflag_transitioning_post', 10, 1 );
		self::add_hook( 'trashed_post', 'trash_or_untrash_sister_posts', 10, 1 );
		self::add_hook( 'untrashed_post', 'trash_or_untrash_sister_posts', 10, 1 );
		self::add_hook( 'deleted_post', 'delete_sister_posts', 10, 1 );
		self::add_hook( 'deleted_post', 'delete_post_language', 11, 1 );
		self::add_hook( 'wp_unique_post_slug', 'unique_slug_for_language', 10, 6 );
		self::add_hook( 'nlingual_sync_post_field-post_parent', 'use_translated_post', 10, 2 );

		// URL Rewriting
		self::add_hook( 'home_url', 'localize_home_url', 10, 3 );
		self::add_hook( 'page_link', 'localize_post_link', 10, 3 );
		self::add_hook( 'post_link', 'localize_post_link', 10, 3 );
		self::add_hook( 'post_type_link', 'localize_post_link', 10, 3 );
		self::add_hook( 'url_to_postid', 'unlocalize_url', 10, 1 );
		self::add_hook( 'mod_rewrite_rules', 'fix_mod_rewrite_rules', 0, 1 );
		self::add_hook( 'wp_loaded', 'rest_query_var_setup', 10, 1 );

		// Query Manipulation
		self::add_hook( 'parse_query', 'set_queried_language', 10, 1 );
		self::add_hook( 'parse_comment_query', 'set_queried_language', 10, 1 );
		self::add_hook( 'pre_get_posts', 'translate_excluded_posts', 20, 1 );
		self::add_hook( 'posts_clauses', 'add_translation_clauses', 10, 2 );
		self::add_hook( 'comments_clauses', 'add_translation_clauses', 10, 2 );
		self::add_hook( 'posts_clauses', 'add_istranslated_clauses', 10, 2 );
		self::add_hook( 'get_pages', 'filter_pages', 10, 2 );
		self::add_hook( 'posts_results', 'find_appropriate_translation', 10, 2 );

		// Apply font patching (if needed)
		if ( is_patch_font_stack_needed() ) {
			self::add_hook( 'wp_print_scripts', 'patch_font_stack', 10, 0 );
			self::add_hook( 'admin_print_scripts', 'patch_font_stack', 10, 0 );
		}

		// Script/Style Enqueues
		self::add_hook( 'wp_enqueue_scripts', 'enqueue_assets', 10, 0 );
		self::add_hook( 'admin_enqueue_scripts', 'enqueue_assets', 10, 0 );

		// Finally, add the blog switching handler
		add_action( 'switch_blog', array( __CLASS__, 'check_blog_for_support' ), 10, 2 );
	}

	// =========================
	// ! Setup Utilities
	// =========================

	/**
	 * Setup default localizable fields like title/tagline and supported taxonomies.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve a list of enabled taxonomies.
	 * @uses Localizer::register_option() to register the site title and tagline for localization.
	 * @uses Localizer::register_taxonomy() to register the enabled taxonomies for localization.
	 */
	public static function setup_localizable_fields() {
		// Register the blogname and blogdescription for localization
		Localizer::register_option_field( 'blogname', 'options-general' );
		Localizer::register_option_field( 'blogdescription', 'options-general' );

		// Register supported taxonomies for localization
		$taxonomies = Registry::get( 'taxonomies' );
		foreach ( $taxonomies as $taxonomy ) {
			Localizer::register_taxonomy( $taxonomy );
		}
	}

	/**
	 * Check if the new blog supports nLingual; disable querying if not.
	 *
	 * @since 2.9.0 Add tracking of the current language in each blog.
	 * @since 2.8.0 Added use of is_nlingual_active() to properly test for plugin status.
	 * @since 2.5.0
	 *
	 * @param int $new_blog_id  The ID of the blog being switched to.
	 * @param int $prev_blog_id The ID of the blog being switched from.
	 */
	public static function check_blog_for_support( $new_blog_id, $prev_blog_id ) {
		// First, log the current language for the previous blog
		$previous_language = self::$blog_languages[ $prev_blog_id ] = Registry::current_language();

		// Reload the registry
		Registry::load( 'reload' );

		// Check if the plugin runs on this site and isn't outdated
		if ( is_nlingual_active() && version_compare( get_option( 'nlingual_database_version', '0.0.0' ), NL_DB_VERSION, '>=' ) ) {
			// Re-enable the various handlers' hooks
			self::restore_all_hooks();
			Frontend::restore_all_hooks();
			Backend::restore_all_hooks();
			AJAX::restore_all_hooks();
			Manager::restore_all_hooks();
			Documenter::restore_all_hooks();
			Localizer::restore_all_hooks();
			Liaison::restore_all_hooks();

			/**
			 * @todo figure out handling of switching langauge within switching blog
			 */

			// If switching back, retrieve the previous language
			if ( isset( self::$blog_languages[ $new_blog_id ] ) ) {
				$current_language = self::$blog_languages[ $new_blog_id ];
			}
			// Otherwise, find this blog's version of the previous blog's current language
			else {
				$current_language = Registry::get_language( $previous_language->slug );
				// Fallback to this blog's default language otherwise
				if ( ! $current_language ) {
					$current_language = Registry::default_language();
				}
			}

			// Set the current language
			Registry::set_language( $current_language, false, 'override' );
		} else {
			// Disable the various handlers' hooks
			self::remove_all_hooks();
			Frontend::remove_all_hooks();
			Backend::remove_all_hooks();
			AJAX::remove_all_hooks();
			Manager::remove_all_hooks();
			Documenter::remove_all_hooks();
			Localizer::remove_all_hooks();
			Liaison::remove_all_hooks();
		}
	}

	// =========================
	// ! Language Detection
	// =========================

	/**
	 * Detect the language based on the request or browser info.
	 *
	 * @since 2.9.0 Add detection of user language and backend language override.
	 * @since 2.7.0 Checked for skip_default_l10n option before getting accepted language.
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

			// If in the backend and nl_switch is set, save it to a cookie
			if ( is_admin() && isset( $_REQUEST['nl_switch'] ) ) {
				setcookie( 'nlingual_language', $language->id, 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			}
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
		// Failing that, get the language overrided for their session, but only in the backend
		elseif ( is_admin() && isset( $_COOKIE['nlingual_language'] ) && $language = Registry::get_language( $_COOKIE['nlingual_language'] ) ) {
			$mode = 'REQUESTED';
		}
		// If the user is logged in and has a preferred language, fallback to that, assuming skip is not enabled or we're in the backend
		elseif ( ( is_backend() || ! Registry::get( 'skip_default_l10n' ) ) && $language = Registry::languages( 'active' )->match_tag( get_user_locale() ) ) {
			$mode = 'ACCEPTED';
		}
		// Fallback to finding the first match in the accepted languages list, assuming skip is not enabled
		elseif ( ! Registry::get( 'skip_default_l10n' ) && $language = self::get_accepted_language() ) {
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

	// =========================
	// ! Text Domain Caching
	// =========================

	/**
	 * Log the type ("theme" or "plugin") of a text domain.
	 *
	 * @since 2.0.0
	 *
	 * @param string $locale The locale being loaded for.
	 * @param string $domain The text domain being loaded.
	 *
	 * @return string The locale, untouched.
	 */
	public static function log_textdomain_type( $locale, $domain ) {
		// Get the type
		$type = str_replace( '_locale', '', current_filter() );

		// Add/update it in the list
		if ( ! isset( self::$textdomain_log[ $domain ] ) ) {
			self::$textdomain_log[ $domain ] = array(
				'type' => '',
				'paths' => array(),
			);
		}
		self::$textdomain_log[ $domain ]['type'] = $type;

		return $locale;
	}

	/**
	 * Log the directory a text domain file was stored in.
	 *
	 * Since we can't be 100% sure what directories are valid and
	 * usable, we'll log every one attempted and try each one
	 * when reloading the domain.
	 *
	 * @since 2.0.0
	 *
	 * @param string $domain The text domain being loaded.
	 * @param string $mofile The file being loaded.
	 */
	public static function log_textdomain_path( $domain, $mofile ) {
		// Get the directory
		$dir = dirname( $mofile );

		// Add/update it in the list
		if ( ! isset( self::$textdomain_log[ $domain ] ) ) {
			self::$textdomain_log[ $domain ] = array(
				'type' => '',
				'paths' => array(),
			);
		}
		self::$textdomain_log[ $domain ]['paths'][] = $dir;
	}

	// =========================
	// ! Post Changes
	// =========================

	/**
	 * Handle any synchronization with sister posts.
	 *
	 * @since 2.6.0 Added check to see if post is in the middle of transitioning to/from trashed.
	 * @since 2.3.1 Fixed number of accepted arguments for rehooking.
	 * @since 2.2.0 Added $post & $update args; moved to wp_insert_post hook.
	 * @since 2.0.0
	 *
	 * @uses Synchronizer::sync_post_with_sister() to handle post synchronizing.
	 *
	 * @param int     $post_id The ID of the post being updated.
	 * @param WP_Post $post    The post being updated.
	 * @param bool    $update  Whether this is an existing post being updated or not.
	 */
	public static function synchronize_posts( $post_id, $post, $update ) {
		// Abort if doing auto save, a revision, or not an update
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! $update ) {
			return;
		}

		// Abort if the post has been marked as in the middle of (un)trashing
		$transitioning = wp_cache_get( 'transitioning_posts', 'nlingual:vars' ) ?: array();
		if ( in_array( $post_id, $transitioning ) ) {
			return;
		}

		// Abort if the post's post type isn't supported
		if ( ! Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return;
		}

		// Get the current action
		$action = current_filter();

		$skip_ids = array();
		// If doing a bulk edit, and this is one of the intended posts,
		// make sure to skip all listed posts
		if ( isset( $_REQUEST['bulk_edit'] )
		&& isset( $_REQUEST['_nl_nonce'] )
		&& wp_verify_nonce( $_REQUEST['_nl_nonce'], 'bulk-posts' )
		&& isset( $_REQUEST['post'] )
		&& in_array( $post_id, (array) $_REQUEST['post'] ) ) {
			$skip_ids = (array) $_REQUEST['post'];
		}

		// Unhook this hook to prevent an infinite loop
		self::remove_hook( $action, __FUNCTION__ );

		// Now synchronize the post's translations
		Synchronizer::sync_post_with_sisters( $post_id, $skip_ids );

		// Rehook now that we're done
		self::restore_hook( $action, __FUNCTION__ );
	}

	/**
	 * Delete the language for a post being deleted.
	 *
	 * @since 2.8.0 Add check for post's type being supported.
	 * @since 2.0.0
	 *
	 * @uses Translator::delete_post_language() to handle the deletion.
	 *
	 * @param int $post_id The ID of the post that was deleted.
	 */
	public static function delete_post_language( $post_id ) {
		// Abort if the post's post type isn't supported
		if ( ! Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return;
		}

		// Delete the language
		Translator::delete_post_language( $post_id );
	}

	/**
	 * Flag a post being (un)trashed.
	 *
	 * This is to prevent syncronize_post() from proceeding.
	 *
	 * @since 2.6.0
	 *
	 * @param int $post_id The ID of the post being (un)trashed.
	 */
	public static function flag_transitioning_post( $post_id ) {
		$transitioning = wp_cache_get( 'transitioning_posts', 'nlingual:vars' ) ?: array();

		$transitioning[] = $post_id;

		wp_cache_set( 'transitioning_posts', $transitioning, 'nlingual:vars' );
	}

	/**
	 * Unflag a post being (un)trashed.
	 *
	 * @since 2.6.0
	 *
	 * @param int $post_id The ID of the post being (un)trashed.
	 */
	public static function unflag_transitioning_post( $post_id ) {
		$transitioning = wp_cache_get( 'transitioning_posts', 'nlingual:vars' ) ?: array();

		$index = array_search( $post_id, $transitioning );
		if ( $index !== false ) {
			unset( $transitioning[ $index ] );
			wp_cache_set( 'transitioning_posts', $transitioning, 'nlingual:vars' );
		}
	}

	/**
	 * (Un)trash a post's sister translations when it's deleted.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to check the trash_sister_posts option.
	 *
	 * @param int $post_id The ID of the post being trashed/untrashed.
	 */
	public static function trash_or_untrash_sister_posts( $post_id ) {
		// Abort if option isn't enabled
		if ( ! Registry::get( 'trash_sister_posts' ) ) {
			return;
		}

		// Abort if the post's post type isn't supported
		if ( ! Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return;
		}

		// Get the current action
		$action = current_filter();

		// Determine the function to use
		$function = 'wp_' . str_replace( 'ed_post', '', $action ) . '_post';

		// Unhook to prevent loop
		self::remove_hook( $action, __FUNCTION__ );

		// Get the translations of the post
		$translations = Translator::get_post_translations( $post_id );
		foreach ( $translations as $translation ) {
			// (Un)trash it
			call_user_func( $function, $translation );
		}

		// Rehook now that we're done
		self::restore_hook( $action, __FUNCTION__ );
	}

	/**
	 * Delete a post's sister translations when it's deleted.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to check the delete_sister_posts option.
	 *
	 * @param int $post_id The ID of the post that was deleted.
	 */
	public static function delete_sister_posts( $post_id ) {
		// Abort if option isn't enabled
		if ( ! Registry::get( 'delete_sister_posts' ) ) {
			return;
		}

		// Abort if the post's post type isn't supported
		if ( ! Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return;
		}

		// Get the current action
		$action = current_filter();

		// Unhook to prevent loop
		self::remove_hook( $action, __FUNCTION__ );

		// Get the translations of the post
		$translations = Translator::get_post_translations( $post_id );
		foreach ( $translations as $translation ) {
			// Delete it (if it's also in the trash)
			if ( get_post_status( $translation ) == 'trash' ) {
				wp_delete_post( $translation, 'force delete' );
			}
		}

		// Rehook now that we're done
		self::restore_hook( $action, __FUNCTION__ );
	}

	/**
	 * Replace the post ID with that of it's translation.
	 *
	 * @since 2.6.0 Add check to make sure $post_id isn't 0 and that post's type is supported.
	 * @since 2.1.0
	 *
	 * @uses Translator::get_post_translation() To get the translation's ID.
	 *
	 * @param int      $post_id  The ID of the post to find a translation of.
	 * @param Language $language The language to find a translation for.
	 *
	 * @return int The original ID or it's translation's if found.
	 */
	public static function use_translated_post( $post_id, Language $language ) {
		// Make sure this post's type is supported
		if ( $post_id && Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return Translator::get_post_translation( $post_id, $language, 'return_self' );
		}

		return $post_id;
	}

	/**
	 * Filter the slug to use the original if unique for that language.
	 *
	 * @since 2.9.0
	 *
	 * @uses Registry::is_post_type_supported() To check if the post uses translations.
	 * @uses Translator::get_post_language() To get the language of a post.
	 * @uses $wpdb To fetch and compare matching slugs.
	 *
	 * @param string $slug          The post slug.
	 * @param int    $post_ID       Post ID.
	 * @param string $post_status   The post status.
	 * @param string $post_type     Post type.
	 * @param int    $post_parent   Post parent ID
	 * @param string $original_slug The original post slug.
	 *
	 * @return string The unique slug for the post.
     */
	public static function unique_slug_for_language( $slug, $post_id, $post_status, $post_type, $post_parent, $original_slug ) {
		global $wpdb;

		// Only bother if the slug was changed and the post's type is supported
		if ( $slug != $original_slug && Registry::is_post_type_supported( $post_type ) ) {
			// Get the language for the target post
			$language = Translator::get_post_language( $post_id );

			// Get all posts of the same type with the same slug to compare
			$existing = $wpdb->get_col( $wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d", $original_slug, $post_type, $post_id ) );

			// If any share the same language, use the suffixed one
			foreach ( $existing as $id ) {
				if ( Translator::get_post_language( $id ) == $language ) {
					return $slug;
				}
			}

			// Otherwise, the original is allowed
			return $original_slug;
		}

		return $slug;
	}

	// =========================
	// ! URL Rewriting
	// =========================

	/**
	 * Localize the home URL.
	 *
	 * @since 2.8.3 Added check if being called before parse_request.
	 * @since 2.2.0 No longer localizes draft URLs while in the admin.
	 * @since 2.0.0
	 *
	 * @uses Rewriter::localize_url() to create the new url.
	 *
	 * @param string      $url     The complete home URL including scheme and path.
	 * @param string      $path    Path relative to the home URL.
	 * @param string|null $scheme  Scheme to give the home URL context.
	 *
	 * @return string The localized home URL.
	 */
	public static function localize_home_url( $url, $path, $scheme ) {
		// If the language wasn't specified and we haven't parsed the request yet, abort
		if ( ! defined( 'NL_REQUESTED_LANGUAGE' ) && ! did_action( 'send_headers' ) ) {
			// This is mostly to prevent WP::parse_request() from getting the wrong home path to work with
			// but, in case do_parse_request returns false, we have to check if we've sent headers or not
			return $url;
		}

		// Only localize for http/https and scheme-agnostic
		if ( ! in_array( $scheme, array( null, 'http', 'https' ) ) ) {
			return $url;
		}

		// Don't localize draft paths in the admin
		if ( is_backend() && preg_match( '/^\?(p|page_id|post_type)=/', $path ) ) {
			return $url;
		}

		// Return the localized version of the URL
		return Rewriter::localize_url( $url );
	}

	/**
	 * Localize a post's URL.
	 *
	 * Namely, localize it for it's assigned language.
	 * Also checks for localizing a home page translation.
	 *
	 * @since 2.9.0 Use current language unless post's language overrides.
	 * @since 2.2.0 Modified to explicitly handle post object vs ID.
	 *              Will no longer localize for draft/pending posts.
	 * @since 2.0.0
	 *
	 * @uses Translator::get_post_language() to get the post's language.
	 * @uses Registry::default_language() to get the default language.
	 * @uses Translator::get_post_translation() to get the post for that language.
	 * @uses Rewriter::localize_url() to localize the URL into the post's language.
	 *
	 * @param string      $permalink The permalink of the post.
	 * @param int|WP_Post $post      The post ID or object.
	 * @param bool        $sample    Is this a sample permalink? (Defaults to FALSE).
	 *
	 * @return string The localized permalink.
	 */
	public static function localize_post_link( $permalink, $post, $sample = false ) {
		// If $post is an object, get the ID
		if ( is_object( $post ) ) {
			$post_id = $post->ID;
		} else {
			$post_id = $post;
		}

		// If it doesn't belong to a supported post type, abort
		if ( ! Registry::is_post_type_supported( get_post_type( $post_id ) ) ) {
			return $permalink;
		}

		// If the post is non published, don't bother
		$status = get_post_status( $post_id );
		$draft_or_pending = in_array( $status, array( 'draft', 'pending', 'auto-draft', 'future' ) );
		if ( $draft_or_pending && ! $sample ) {
			return $permalink;
		}

		// Get the language to use
		$language = Registry::current_language();
		// Use the post's langauge if desired or otherwise in the backend
		if ( Registry::get( 'post_language_override', false ) || is_backend() ) {
			$language = Translator::get_post_language( $post_id );

			if ( ! $language ) {
				return $permalink;
			}
		}

		// If it's a page, check if it's a home page translation
		if ( current_filter() == 'page_link' ) {
			// Get the default language translation
			$translation = Translator::get_post_translation( $post_id, Registry::default_language(), 'return self' );

			// If it's a home page translation, replace with unlocalized home url
			if ( $translation == get_option( 'page_on_front' ) ) {
				$permalink = get_home_url( null, '', 'unlocalized' );
			}
		}

		// Just ensure the URL is localized for it's language and return it
		return Rewriter::localize_url( $permalink, $language, 'relocalize' );
	}

	/**
	 * Unlocalize the URL before resolving.
	 *
	 * @since 2.9.0
	 *
	 * @param string $url The URL being resolved.
	 *
	 * @return string The unlocalized URL.
	 */
	public static function unlocalize_url( $url ) {
		if ( strpos( $url, home_url() ) === 0 ) {
			$url = Rewriter::delocalize_url( $url );
		}

		return $url;
	}

	/**
	 * Fixes possible bugs with mod_rewrite rules.
	 *
	 * If skip_default_l10n is disabled, the mod_rewrite_rules
	 * will use the path prefix in instead of the true home path.
	 *
	 * @since 2.0.0
	 *
	 * @global \WP_Rewrite $wp_rewrite The WordPress rewrite API.
	 *
	 * @param string $rules The mod_rewrite block.
	 *
	 * @return string The filtered rewrite block.
	 */
	public static function fix_mod_rewrite_rules( $rules ) {
		global $wp_rewrite;

		// Only bother if using the path rewrite method
		// (shouldn't need fixing otherwise)
		if ( Registry::get( 'url_rewrite_method' ) != 'path' ) {
			return $rules;
		}

		// Get the current action
		$action = current_filter();

		// Unhook to prevent infinite loop
		self::remove_hook( $action, __FUNCTION__ );

		// Turn off URL localization, getting the old setting
		$status = Rewriter::disable_localization();

		// Now retry generating the rules without interference from nLingual
		$rules = $wp_rewrite->mod_rewrite_rules();

		// Restore URL localization to it's former setting
		Rewriter::enable_localization( $status );

		// Rehook now that we're done
		self::restore_hook( $action, __FUNCTION__ );

		return $rules;
	}

	/**
	 * Setup hook to add query_var option for all REST Posts Controller instances.
	 *
	 * @since 2.6.0
	 */
	public static function rest_query_var_setup() {
		foreach ( get_post_types( array( 'show_in_rest' => true ), 'names' ) as $post_type ) {
			if ( Registry::is_post_type_supported( $post_type ) ) {
				self::add_hook( "rest_{$post_type}_collection_params", 'rest_register_query_var', 10, 1 );
				self::add_hook( "rest_{$post_type}_query", 'rest_handle_query_var', 10, 2 );
			}
		}
	}

	/**
	 * Filter the query params to add the query_var option as a
	 * possible parameter for collection requests.
	 *
	 * @since 2.9.0 Add 0 as language option/default if language is not required.
	 * @since 2.6.0
	 *
	 * @param array $query_params The list of params to add to.
	 *
	 * @return array The filtered query parameters.
	 */
	public static function rest_register_query_var( $query_params ) {
		$query_var = Registry::get( 'query_var' );

		$language_slugs = $language_ids = array();
		foreach ( Registry::languages( 'active' ) as $language ) {
			$language_slugs[] = $language->slug;
			$language_ids[] = $language->id;
		}

		$default = array( Registry::current_language()->slug );
		$whitelist = array_merge( $language_slugs, $language_ids );

		if ( ! Registry::get( 'language_is_required' ) ) {
			$default[] = '0';
			$whitelist[] = '0';
		}

		$query_params[ $query_var ] = array(
			'default'           => $default,
			'description'       => __( 'Limit result set to posts assigned one or more registered languages.', 'nlingual' ),
			'type'              => 'array',
			'items'             => array(
				'enum'          => $whitelist,
				'type'          => 'string',
			),
		);

		return $query_params;
	}

	/**
	 * Filter the query arguments, adding the the query_var if requested.
	 *
	 * @since 2.6.0
	 *
	 * @param array           $args    Key value array of query var to query value.
	 * @param WP_REST_Request $request The request used.
	 *
	 * @return array The filtered args.
	 */
	public static function rest_handle_query_var( $args, $request ) {
		$query_var = Registry::get( 'query_var' );

		if ( $languages = $request->get_param( $query_var ) ) {
			$args[ $query_var ] = $languages;
		}

		return $args;
	}

	// =========================
	// ! Query Filters
	// =========================

	/**
	 * Set the queried language to the current one if applicable
	 *
	 * @since 2.9.0 Don't blindly set when in the admin,
	 *              Check for both default and custom query_var,
	 *              Skip when viewing trash, rewrite post type checking
	 *              to handle search and mixed types.
	 * @since 2.8.0 Added check for parent's post type being supported.
	 * @since 2.7.0 Revised support checks for post type archives.
	 * @since 2.6.0 Perform tax query handling first, then post type archive.
	 * @since 2.1.1 Fixed post type and taxonomy checks to be more less picky.
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the query_var, show_all_languages options.
	 * @uses Registry::current_language() to get the current language.
	 * @uses Translator::get_post_language() to check if the parent has a language set.
	 * @uses is_backend() to check if the query is for wp-admin.
	 *
	 * @param object $query The query object.
	 */
	public static function set_queried_language( $query ) {
		// Get the language query_var name, and the query's variables (by reference)
		$query_var = Registry::get( 'query_var', 'nl_language' );

		// Get the language(s) if already declared
		$requested_languages = $query->get( $query_var ) ?: $query->get( 'nl_language' );

		// Abort if already declared
		if ( ! empty( $requested_languages ) || (string) $requested_languages === '0' ) {
			// But convert to array if applicable
			if ( is_string( $requested_languages ) ) {
				$requested_languages = explode( '|', $requested_languages );
				$query->set( $query_var, false );
				$query->set( 'nl_language', $requested_languages );
			}
			return;
		}

		/**
		 * Short circuit the language assignment; setting the language manually.
		 *
		 * @since 2.0.0
		 *
		 * @param mixed  $pre_value The value to set for the query (NULL to proceed).
		 * @param object $query     The query being modified.
		 */
		$pre_value = apply_filters( 'nlingual_pre_set_queried_language', null, $query );
		if ( ! is_null( $pre_value ) ) {
			$query->set( $query_var, $pre_value );
			return;
		}

		// If viewing posts in the trash, abort
		if ( $query->get( 'post_status' ) == 'trash' ) {
			return;
		}

		// If not a non-singular posts query, nor a comment query, abort
		if ( ! ( ( is_a( $query, 'WP_Query' ) && ! $query->is_singular() ) || is_a( $query, 'WP_Comment_Query' ) ) ) {
			return;
		}

		// If it's the home feed, check if posts are supported
		if ( $query->is_home() && ! Registry::is_post_type_supported( 'post' ) ) {
			return;
		}

		// If we're querying by taxonomy, check if it's object type is supported
		if ( property_exists( $query, 'tax_query' ) && $query->tax_query ) {
			foreach ( $query->tax_query->queries as $tax_query ) {
				if ( is_array( $tax_query ) ) {
					$taxonomy = get_taxonomy( $tax_query['taxonomy'] );
					if ( $taxonomy && ! Registry::is_post_type_supported( $taxonomy->object_type ) ) {
						return;
					}
				}
			}
		}

		// If we're querying by post type, check if ANY are supported
		if ( $query->get( 'post_type' ) && ! Registry::is_post_type_supported( $query->get( 'post_type' ) ) ) {
			return;
		}

		// If the parent is specified, is of a supported type, and has a language itself, don't bother
		if ( ( $parent = $query->get( 'post_parent' ) ) && Registry::is_post_type_supported( get_post_type( $parent ) ) && Translator::get_post_language( $parent ) ) {
			return;
		}

		// Assume current language; build the list (1 value; the current language)
		$value = array( Registry::current_language()->slug );

		// If in the backend and the show_all_languages option is enabled, set filter for all active languages
		if ( is_backend() && Registry::get( 'show_all_languages' ) ) {
			$value = Registry::languages( 'active' )->pluck( 'id', false );
		}

		// If in the backend, or language is not required, or non-supported post types are involved, add 0 to retreive language-less posts too
		if ( is_backend() || ! Registry::get( 'language_is_required' ) || ! Registry::is_post_type_supported( $query->get( 'post_type' ), 'require all' ) ) {
			$value[] = '0';
		}

		// Now set the language to the current one
		$query->set( $query_var, $value );
	}

	/**
	 * Translated the post__not_in IDs to those for the queried language(s) if needed.
	 *
	 * @since 2.8.8 Use WP_Query::get() and set(), check for default query var.
	 * @since 2.6.0 Added check to see if current query is for an unsupported post type.
	 * @since 2.0.0
	 *
	 * @param object $query The query object.
	 */
	public static function translate_excluded_posts( $query ) {
		// Get the language query_var name, and the query's variables (by reference)
		$query_var = Registry::get( 'query_var' );

		// Abort if a query for an unsupported post type
		if ( ! Registry::is_post_type_supported( $query->get( 'post_type' ) ) ) {
			return;
		}

		$post__not_in = $query->get( 'post__not_in' );

		// Abort if no exclusions were set
		if ( ! $post__not_in ) {
			return;
		}

		// Get the language(s) specified
		$requested_languages = $query->get( $query_var ) ?: $query->get( 'nl_language' );

		// Abort if no language(s) was set
		if ( is_null( $requested_languages ) || $requested_languages === '' ) {
			return;
		}

		// Ensure languages is an array, filtered
		$requested_languages = array_filter( (array) $requested_languages );

		// Loop through the IDs
		$exclude_ids = array();
		foreach ( $post__not_in as $i => $id ) {
			// Check if it has a language
			if ( Translator::get_post_language( $id ) ) {
				// Add it's translations in each requested language
				foreach ( $requested_languages as $language ) {
					$exclude_ids[] = Translator::get_post_translation( $id, $language, 'return self' );
				}
			}
			// Preserve it
			else {
				$exclude_ids[] = $id;
			}
		}

		$query->set( 'post__not_in', array_filter( $exclude_ids ) );
	}

	/**
	 * Add the translations join clause and language where clause for a query.
	 *
	 * @since 2.8.8 Use WP_Query::get(), accept default query var.
	 * @since 2.6.0 Fixed handling of 0 value for filtering by no language.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::is_post_type_supported() to check for post type support.
	 * @uses Registry::current_language() to get the current language ID.
	 *
	 * @param array  $clauses The clauses to modify.
	 * @param object $query   The query object this is for.
	 *
	 * @return array The modified clauses.
	 */
	public static function add_translation_clauses( $clauses, $query ) {
		global $wpdb;

		// Get the language query_var name, and the query's variables
		$query_var = Registry::get( 'query_var' );

		// Get the language(s) specified
		$requested_languages = $query->get( $query_var ) ?: $query->get( 'nl_language' );

		// Abort if no language(s) was set
		if ( is_null( $requested_languages ) || $requested_languages === '' ) {
			return $clauses;
		}

		// Ensure languages is an array
		$requested_languages = (array) $requested_languages;

		// Get the available languages for valiation purposes
		$all_languages = Registry::languages();

		// Alias for the translations table
		$nl = $wpdb->nl_translations;

		// Loop through each language specified and build the subclause
		$where_clauses = array();
		foreach ( $requested_languages as $language ) {
			// Skip if blank
			if ( $language === '' ) {
				continue;
			}

			// Check if the language specified is "None"
			if ( $language === '0' || $language === 0 ) {
				$where_clauses[] = "$nl.language_id IS NULL";
			}
			// Otherwise check if the language exists
			elseif ( $language = $all_languages->get( $language ) ) {
				$where_clauses[] = $wpdb->prepare( "$nl.language_id = %d", $language->id );
			}
		}

		// If any where clauses were made, add them
		if ( $where_clauses ) {
			// Determine the ID field to use
			$id_field = "{$wpdb->posts}.ID";
			if ( is_a( $query, 'WP_Comment_Query' ) ) {
				$id_field = "{$wpdb->comments}.comment_post_ID";
			}

			// Also add the join for the translations table
			$clauses['join'] .= " LEFT JOIN $nl ON ($id_field = $nl.object_id AND $nl.object_type = 'post')";

			// Add the new clause
			$clauses['where'] .= " AND (" . implode( ' OR ', $where_clauses ) . ")";
		}

		return $clauses;
	}

	/**
	 * Add the join/where clauses for handling the nl_is_translated argument.
	 *
	 * Will join the translations table twice to look for posts that have
	 * belong to groups of more than 1 or only 1 depending on value.
	 *
	 * @since 2.9.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param array  $clauses The clauses to modify.
	 * @param object $query   The query object this is for.
	 *
	 * @return array The modified clauses.
	 */
	public static function add_istranslated_clauses( $clauses, $query ) {
		global $wpdb;

		$query_vars = $query->query_vars;

		// Abort if nl_is_translated is absent/empty
		$query_var = 'nl_is_translated';
		if ( ! isset( $query_vars[ $query_var ] ) || is_null( $query_vars[ $query_var ] ) || $query_vars[ $query_var ] === '' ) {
			return $clauses;
		}

		// True for IS NOT NULL (has translation), False for IS NULL (has no translations)
		$test = $query_vars[ $query_var ] ? 'IS NOT NULL' : 'IS NULL';

		// Default shortcuts for translations table
		$nl = $wpdb->nl_translations;
		$nl1 = "{$nl}_istranslated1";
		$nl2 = "{$nl}_istranslated2";

		// Determine the ID field to use
		$id_field = "{$wpdb->posts}.ID";
		if ( is_a( $query, 'WP_Comment_Query' ) ) {
			$id_field = "{$wpdb->comments}.comment_post_ID";
		}

		// Use existing nl_translations table join if present
		$query_var = Registry::get( 'query_var' );
		if ( isset( $query_vars[ $query_var ] ) && ! empty( $query_vars[ $query_var ] ) ) {
			$nl1 = $wpdb->nl_translations;
		} else {
			// Add first join
			$clauses['join'] .= " LEFT JOIN $nl AS $nl1 ON ($id_field = $nl1.object_id AND $nl1.object_type = 'post')";
		}

		// Add second join
		$clauses['join'] .= " LEFT JOIN $nl AS $nl2 ON ($nl1.group_id = $nl2.group_id AND $nl1.object_id != $nl2.object_id)";

		// Add where clause
		$clauses['where'] .= " AND ($nl1.language_id IS NOT NULL AND $nl2.language_id $test)";

		return $clauses;
	}

	/**
	 * Filter the results of get_pages, removing those not in the current language.
	 *
	 * @since 2.8.0 Add handling for an array of languages being requested.
	 * @since 2.6.0 Add check to make sure the Page post type is supported.
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to retrieve the show_all_languages option.
	 * @uses Registry::languages() to check/get the matching language object.
	 * @uses Registry::current_language() as the default language to fitler by.
	 * @uses Translator::get_post_language() to get the translation in that language.
	 *
	 * @param array $pages The list of pages to filter.
	 * @param array $args  The arugments passed to get_pages().
	 *
	 * @return array The filtered list of pages.
	 */
	public static function filter_pages( $pages, $args ) {
		// Abort if $pages is empty, show_all_languages is set, or pages are not localizable
		if ( ! $pages || Registry::get( 'show_all_languages' ) || ! Registry::is_post_type_supported( 'page' ) ) {
			return $pages;
		}

		// Get the id of the current language or the requested one
		if ( isset( $args['language'] ) && $args['language'] ) {
			// Check only the first real language
			$requested_languages = array_filter( (array) $args['language'] );
			$filter_language = Registry::get_language( $requested_languages[0] );

			// If it's not a valid language, return the original list
			if ( ! $filter_language ) {
				return $pages;
			}
		} else {
			$filter_language = Registry::current_language();
		}

		$filtered_pages = array();
		foreach ( $pages as $page ) {
			// If the language isn't set or is the current one, include it
			$language = Translator::get_post_language( $page->ID );
			if ( ! $language || $language->id == $filter_language->id ) {
				$filtered_pages[] = $page;
			}
		}

		return $filtered_pages;
	}

	/**
	 * Filter the post results to find the appopriate translation.
	 *
	 * In cases where multiple posts are matched for a slug,
	 * find the one for the current language.
	 *
	 * @since 2.9.0
	 *
	 * @uses Registry::is_post_type_supported() to check for support of the requested post type.
	 * @uses Registry::current_language() as the language to filter by.
	 * @uses Translator::get_post_language() to find the appropriate translation to use.
	 *
	 * @param array    $posts The fetched posts to be filtered.
	 * @param WP_Query $query The Query the results are for.
	 *
	 * @return array The filtered list of posts.
	 */
	public static function find_appropriate_translation( $posts, $query ) {
		// If not mulitple posts, or not a singular query, or the post type isn't supported, abort
		if ( count( $posts ) <= 1 || ! $query->is_singular() || ! Registry::is_post_type_supported( $query->get( 'post_type' ) ) ) {
			return $posts;
		}

		// Get the current language
		$current_language = Registry::current_language();

		// Loop through and find the post for the current language
		foreach ( $posts as $post ) {
			if ( Translator::get_post_language( $post ) == $current_language ) {
				return array( $post );
			}
		}

		// Otherwise give up
		return $posts;
	}

	// =========================
	// ! Miscellaneous
	// =========================

	/**
	 * Patch the font stack in the admin.
	 *
	 * Replaces the font stack to use Helvetica and Tahoma
	 * instead of Open Sans, which has rendering issues in Chrome.
	 *
	 * Helvetica because it's the closes websafe match, and Tahoma
	 * for the sake of less ugly Arabic characters.
	 *
	 * @since 2.0.0
	 */
	public static function patch_font_stack() {
		// Only proceed if enabled and Open Sans is in use
		if ( Registry::get( 'patch_font_stack' )
		&& wp_style_is( 'open-sans', 'enqueued' ) ) {
			?>
			<style id="nlingual-open-sans-fix" type="text/css" media="all">
				body.wp-admin,
				#wpadminbar,
				#wpadminbar * {
					font-family: 'Helvetica Neueu', Helvetica, Tahoma, Arial, sans-serif;
				}
			</style>
			<?php
		}
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
		wp_enqueue_style( 'nlingual-adminbar', plugins_url( 'css/adminbar.css', NL_PLUGIN_FILE ), NL_PLUGIN_VERSION, 'screen' );
	}
}
