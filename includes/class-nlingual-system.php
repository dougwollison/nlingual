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

class System extends Handler {
	// =========================
	// ! Properties
	// =========================

	/**
	 * Language switching log.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $language_stack = array();

	/**
	 * The internal text domain cache.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $textdomain_cache = array();

	/**
	 * The internal text domain log.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected static $textdomain_log = array();

	/**
	 * The last locale text domains were reloaded for.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected static $last_locale_reloaded = '';

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Cache the text domains and re-load them.
	 *
	 * @since 2.0.0
	 *
	 * @global array $l10n The index of loaded text domain files.
	 *
	 * @param string $old_local The previous locale.
	 */
	public static function reload_textdomains( $old_local ) {
		global $l10n;

		// Current locale
		$new_locale = get_locale();

		// Abort if $new_locale matches the previous reload locale
		if ( $new_locale == static::$last_locale_reloaded ) {
			return;
		}

		// Record the new locale
		static::$last_locale_reloaded = $new_locale;

		// Cache the current list
		static::$textdomain_cache[ $old_local ] = $l10n;

		// If a list exists for the new local, us it
		if ( isset( static::$textdomain_cache[ $new_locale ] ) ) {
			$l10n = static::$textdomain_cache[ $new_locale ];
		}
		// Otherwise, go through each one and reload it
		else {
			// Backup and clear $l10n
			$old_textdomains = $l10n;
			$l10n = array();

			// Loop through all old domains
			foreach ( $old_textdomains as $domain => $mo ) {
				// If it wasn't logged, skip it (99% chance it's a filler NOOP)
				if ( ! isset( static::$textdomain_log[ $domain ] ) ) {
					continue;
				}

				// "default" is easy
				if ( $domain == 'default' ) {
					load_default_textdomain( $new_locale );
					continue;
				}

				// Get the type and path(s) from the log
				$type = static::$textdomain_log[ $domain ]['type'];
				$paths = static::$textdomain_log[ $domain ]['paths'];

				// The new mo file name
				$mofile = $the_locale . '.mo';

				// Filter it if needed
				$the_locale = $new_locale;
				if ( $type ) {
					$the_locale = apply_filters( "{$type}_locale", $new_locale, $domain );
				}

				// The new mo file name
				$mofile = $the_locale . '.mo';

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

		// Get the old locale for text domain reloading
		$old_local = get_locale();

		// Log the current language
		static::$language_stack[] = Registry::current_language( 'id' );

		// Set to the desired language
		Registry::set_language( $language->id, false, 'override' );

		if ( $reload_textdomains ) {
			// Reload the text domains
			static::reload_textdomains( $old_local );
		}
	}

	/**
	 * Switch back to the previous language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::$previous_languages to get the previous language.
	 * @uses Registry::get() to get the default language id.
	 * @uses Registry::$current_language to update the current language.
	 */
	public static function restore_language() {
		$last_language = array_pop( static::$language_stack );

		// If no previous language, go with default
		if ( ! $last_language ) {
			$last_language = Registry::default_language( 'id' );
		}

		// Get the old locale for text domain reloading
		$old_local = get_locale();

		// Set to the last language
		Registry::set_language( $last_language, false, 'override' );

		// Reload the text domains
		static::reload_textdomains( $old_local );
	}

	// =========================
	// ! Master Setup Method
	// =========================

	/**
	 * Register hooks and load options.
	 *
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

		// Register the database tables (with backwards compatibility for their old nL_ version)
		$wpdb->nl_translations = $wpdb->nL_translations = $wpdb->prefix . 'nl_translations';
		$wpdb->nl_localizations = $wpdb->prefix . 'nl_localizations';

		// Setup the registry
		Registry::load();

		// Register the Installer stuff
		Installer::register_hooks();

		// Register global hooks
		static::register_hooks();

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
	// ! Setup Utilities
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Setup Stuff
		static::add_action( 'plugins_loaded', 'setup_localizable_fields', 10, 0 );

		// Text Domain Manipulation
		static::add_filter( 'theme_locale', 'log_textdomain_type', 10, 2 );
		static::add_filter( 'plugin_locale', 'log_textdomain_type', 10, 2 );
		static::add_action( 'load_textdomain', 'log_textdomain_path', 10, 2 );

		// Post Changes
		static::add_action( 'edit_post', 'synchronize_posts', 20, 1 );
		static::add_filter( 'trashed_post', 'trash_or_untrash_sister_posts', 10, 1 );
		static::add_filter( 'untrashed_post', 'trash_or_untrash_sister_posts', 10, 1 );
		static::add_filter( 'deleted_post', 'delete_sister_posts', 10, 1 );
		static::add_filter( 'deleted_post', 'delete_post_language', 11, 1 );

		// URL Rewriting
		static::add_filter( 'home_url', 'localize_home_url', 10, 3 );
		static::add_filter( 'page_link', 'localize_post_link', 10, 2 );
		static::add_filter( 'post_link', 'localize_post_link', 10, 2 );
		static::add_filter( 'post_type_link', 'localize_post_link', 10, 2 );
		static::add_filter( 'mod_rewrite_rules', 'fix_mod_rewrite_rules', 0, 1 );

		// Query Manipulation
		static::add_action( 'parse_query', 'set_queried_language', 10, 1 );
		static::add_action( 'parse_comment_query', 'set_queried_language', 10, 1 );
		static::add_filter( 'posts_clauses', 'add_translation_clauses', 10, 2 );
		static::add_filter( 'comments_clauses', 'add_translation_clauses', 10, 2 );
		//static::add_filter( 'posts_join_request', 'add_post_translations_join_clause', 10, 2 );
		//static::add_filter( 'posts_where_request', 'add_post_translations_where_clause', 10, 2 );
		static::add_filter( 'get_pages', 'filter_pages', 10, 2 );

		// Miscellaneous Changes
		static::add_action( 'wp_print_scripts', 'patch_font_stack', 10, 0 );
		static::add_action( 'admin_print_scripts', 'patch_font_stack', 10, 0 );
	}

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
		Localizer::register_option_field( 'blogname', 'options-general', array(
			'title'  => 'Site Title'
		) );
		Localizer::register_option_field( 'blogdescription', 'options-general', array(
			'title'  => 'Tagline'
		) );

		// Register supported taxonomies for localization
		$taxonomies = Registry::get( 'taxonomies' );
		foreach ( $taxonomies as $taxonomy ) {
			Localizer::register_taxonomy( $taxonomy );
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
		if ( ! isset( static::$textdomain_log[ $domain ] ) ) {
			static::$textdomain_log[ $domain ] = array();
		}
		static::$textdomain_log[ $domain ]['type'] = $type;

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
		if ( ! isset( static::$textdomain_log[ $domain ] ) ) {
			static::$textdomain_log[ $domain ] = array();
		}
		static::$textdomain_log[ $domain ]['paths'][] = $dir;
	}

	// =========================
	// ! Post Changes
	// =========================

	/**
	 * Handle any synchronization with sister posts.
	 *
	 * @since 2.0.0
	 * @uses Synchronizer::sync_post_with_sister() to handle post synchronizing.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public static function synchronize_posts( $post_id ) {
		// Abort if doing auto save or it's a revision
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

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
		$priority = static::remove_action( 'edit_post', __FUNCTION__ );

		// Now synchronize the post's translations
		Synchronizer::sync_post_with_sisters( $post_id, $skip_ids );

		// Rehook now that we're done
		static::add_action( 'edit_post', __FUNCTION__, $priority, 1 );
	}

	/**
	 * Delete the language for a post being deleted.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::delete_post_language() to handle the deletion.
	 *
	 * @param int $post_id The ID of the post that was deleted.
	 */
	public static function delete_post_language( $post_id ) {
		// Delete the language
		Translator::delete_post_language( $post_id );
	}

	/**
	 * (Un)trash a post's sister translations when it's deleted.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to check the trash_sister_posts option.
	 *
	 * @param int $post_id The ID of the post that was deleted.
	 */
	public static function trash_or_untrash_sister_posts( $post_id ) {
		// Abort if option isn't enabled
		if ( ! Registry::get( 'trash_sister_posts' ) ) {
			return;
		}

		// Get the current action
		$action = current_filter();

		// Determine the function to use
		$function = 'wp_' . str_replace( 'ed_post', '', $action ) . '_post';

		// Unhook to prevent loop
		$priority = static::remove_action( $action, __FUNCTION__ );

		// Get the translations of the post
		$translations = Translator::get_post_translations( $post_id );
		foreach ( $translations as $translation ) {
			// (Un)trash it
			call_user_func( $function, $translation );
		}

		// Rehook now that we're done
		static::add_action( $action, __FUNCTION__, $priority, 1 );
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

		// Unhook to prevent loop
		$priority = static::remove_action( 'deleted_post', __FUNCTION__ );

		// Get the translations of the post
		$translations = Translator::get_post_translations( $post_id );
		foreach ( $translations as $translation ) {
			// Delete it (if it's also in the trash)
			if ( get_post_status( $translation ) == 'trash' ) {
				wp_delete_post( $translation, true );
			}
		}

		// Rehook now that we're done
		static::add_action( 'deleted_post', __FUNCTION__, $priority, 1 );
	}

	// =========================
	// ! URL Rewriting
	// =========================

	/**
	 * Localize the home URL.
	 *
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
		// Only localize for http/https and scheme-agnostic
		if ( ! in_array( $scheme, array( null, 'http', 'https' ) ) ) {
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
	 * @since 2.0.0
	 *
	 * @uses Translator::get_post_language() to get the post's language.
	 * @uses Registry::default_language() to get the default language.
	 * @uses Translator::get_post_translation() to get the post for that language.
	 * @uses Rewriter::localize_url() to localize the URL into the post's language.
	 *
	 * @param string $permalink The permalink of the post.
	 * @param int    $post_id   The ID of the post.
	 *
	 * @return string The localized permalink.
	 */
	public static function localize_post_link( $permalink, $post_id ) {
		// Check if it has a language
		if ( $language = Translator::get_post_language( $post_id ) ) {
			// If it's a page, check if it's a home page translation
			if ( current_filter() == 'page_link' ) {
				// Get the default language translation
				$translation = Translator::get_post_translation( $post_id, Registry::default_language(), true );

				// If it's a home page translation, replace with unlocalized home url
				if ( $translation == get_option( 'page_on_front' ) ) {
					$permalink = get_home_url( null, '', 'unlocalized' );
				}
			}

			// Just ensure the URL is localized for it's language and return it
			return Rewriter::localize_url( $permalink, $language, true );
		}

		return $permalink;
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
	public function fix_mod_rewrite_rules( $rules ) {
		global $wp_rewrite;

		// Only bother if using the path rewrite method
		// (shouldn't need fixing otherwise)
		if ( Registry::get( 'url_rewrite_method' ) != 'path' ) {
			return $rules;
		}

		// Unhook to prevent infinite loop
		$priority = static::remove_filter( 'mod_rewrite_rules', __FUNCTION__ );

		// Turn off URL localization, getting the old setting
		$status = Rewriter::disable_localization();

		// Now retry generating the rules without interference from nLingual
		$rules = $wp_rewrite->mod_rewrite_rules();

		// Restore URL localization to it's former setting
		Rewriter::enable_localization( $status );

		// Rehook now that we're done
		static::add_filter( 'mod_rewrite_rules', __FUNCTION__, $priority, 1 );

		return $rules;
	}

	// =========================
	// ! Query Filters
	// =========================

	/**
	 * Set the queried language to the current one if applicable
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the query_var, show_all_languages options.
	 * @uses Registry::current_language() to get the current language.
	 * @uses Translator::get_post_language() to check if the parent has a language set.
	 * @uses is_backend() to check if the query is for wp-admin.
	 *
	 * @param object $query The query object.
	 */
	function set_queried_language( $query ) {
		// Get the language query_var name, and the queries variables (by reference)
		$query_var = Registry::get( 'query_var' );
		$query_vars = &$query->query_vars;

		// Abort if no query var name is set or if it's already declared
		if ( ! $query_var || isset( $query_vars[ $query_var ] ) ) {
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
			$query_vars[ $query_var ] = $pre_value;
			return;
		}

		// If not the admin or some kind of posts/comments feed, abort
		if ( ! ( is_admin() || $query->is_home() || $query->is_archive() || $query->is_search() || is_a( $query, 'WP_Comment_Query' ) ) ) {
			return;
		}

		// If it's a post type archive, check if the post type is supported
		if ( $query->is_post_type_archive() && ! Registry::is_post_type_supported( $query->get( 'post_type' ) ) ) {
			return;
		}

		// If it's a taxonomy, check if the object types are supported
		if ( $query->is_tax() && $query->queried_object->taxonomy ) {
			$object_types = get_taxonomy( $query->queried_object->taxonomy )->object_type;

			if ( ! Registry::is_post_type_supported( $object_types ) ) {
				return;
			}
		}

		// If the parent is specified, and has a language itself, don't bother
		if ( ( $parent = $query->get( 'post_parent' ) ) && Translator::get_post_language( $parent ) ) {
			return;
		}

		// Assume current language; build the list (1 value; the current language)
		$value = array( Registry::current_language()->slug );

		// If in the backend and the show_all_languages option is enabled, set filter for all active languages
		if ( is_backend() && Registry::get( 'show_all_languages' ) ) {
			$value = Registry::languages( 'active' )->pluck( 'id', false );
		}

		// If in the backend, also add 0 to retreive language-less posts too
		if ( is_backend() ) {
			$value[] = '0';
		}

		// Now set the language to the current one
		$query_vars[ $query_var ] = $value;
	}

	/**
	 * Add the translations join clause and language where clause for a query.
	 *
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

		// Get the language query_var name, and the queries variables
		$query_var = Registry::get( 'query_var' );
		$query_vars = &$query->query_vars;

		// Abort if no language was set
		if ( ! isset( $query_vars[ $query_var ] ) || empty( $query_vars[ $query_var ] ) ) {
			return $clause;
		}

		// Get the language(s) specified
		$requested_languages = $query_vars[ $query_var ];

		// Ensure it's an array
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
			if ( $language === '0' ) {
				$where_clauses[] = "$nl.language_id IS NULL";
			}
			// Otherwise check if the language exists
			elseif ( $language = $all_languages->get( $language ) ) {
				$where_clauses[] = $wpdb->prepare( "$nl.language_id = %d", $language->id );
			}
		}

		// If any where clauses were made, add them
		if ( $where_clauses ) {
			// Also add the join for the translations table
			$clauses['join'] .= " LEFT JOIN $nl ON ($wpdb->posts.ID = $nl.object_id AND $nl.object_type = 'post')";

			// Add the new clause
			$clauses['where'] .= " AND (" . implode( ' OR ', $where_clauses ) . ")";
		}

		return $clauses;
	}

	/**
	 * Adds JOIN clause for the translations table if needed.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check if clause editing is needed.
	 * @uses Registry::get() to get the query var option.
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string   $clause The JOIN clause.
	 * @param WP_Query $query  The WP_Query instance.
	 *
	 * @return string The updated JOIN clauses.
	 */
	public static function add_post_translations_join_clause( $clause, \WP_Query $query ) {
		global $wpdb;

		// Abort if language isn't specified
		if ( $query->get( Registry::get( 'query_var' ) ) === '' ) {
			return $clause;
		}

		// Add the join for the translations table
		$clause .= " LEFT JOIN $wpdb->nl_translations ON ($wpdb->posts.ID = $wpdb->nl_translations.object_id AND $wpdb->nl_translations.object_type = 'post')";

		return $clause;
	}

	/**
	 * Adds WHERE clause for the translation language id if needed.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check if clause editing is needed.
	 * @uses Registry::get() to get the query var option.
	 * @uses Registry::languages() to get the available languages.
	 * @uses Languages::get() to get a specific language by slug/ID.
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string   $clause The WHERE clause.
	 * @param WP_Query $query  The WP_Query instance.
	 *
	 * @return string The updated WHERE clauses.
	 */
	public static function add_post_translations_where_clause( $clause, \WP_Query $query ) {
		global $wpdb;

		// Get the language(s) specified
		$languages = $query->get( Registry::get( 'query_var' ) );

		// Abort if not set
		if ( $languages === '' ) {
			return $clause;
		}

		// Ensure it's an array
		$languages = (array) $languages;

		// Get the available languages for valiation purposes
		$all_languages = Registry::languages();

		// Loop through each language specified and build the subclause
		$subclauses = array();
		foreach ( $languages as $language ) {
			// Skip if blank
			if ( $language === '' ) {
				continue;
			}

			// Check if the language specified is "None"
			if ( $language === '0' ) {
				$subclauses[] = "$wpdb->nl_translations.language_id IS NULL";
			}
			// Otherwise check if the language exists
			elseif ( $language = $all_languages->get( $language ) ) {
				$subclauses[] = $wpdb->prepare( "$wpdb->nl_translations.language_id = %d", $language->id );
			}
		}

		// If any subclauses were made, add them
		if ( $subclauses ) {
			// Add the new clause
			$clause .= " AND (" . implode( ' OR ', $subclauses ) . ")";
		}

		return $clause;
	}

	/**
	 * Filter the results of get_pages, removing those not in the current language.
	 *
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
		// Abort if $pages is empty or show_all_languages is set
		if ( ! $pages || Registry::get( 'show_all_languages' ) ) {
			return $pages;
		}

		// Get the id of the current language or the requested one
		if ( isset( $args['language'] ) ) {
			$filter_language = Registry::get_language( $args['language'] );

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
			if ( ! $language || $language->id == $filter_language ) {
				$filtered_pages[] = $page;
			}
		}

		return $filtered_pages;
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
}
