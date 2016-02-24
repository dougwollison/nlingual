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
	// ! Master Setup Method
	// =========================

	/**
	 * Register hooks and load options.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
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

		// Register the Installer stuff
		Installer::register_hooks();

		// Setup the registry
		Registry::load();

		// Register own hooks
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
		// Post-setup stuff
		static::add_action( 'plugins_loaded', 'setup_localizable_fields', 10, 0 );

		// Post Changes
		static::add_action( 'save_post', 'synchronize_posts', 20, 1 );
		static::add_filter( 'deleted_post', 'delete_sister_posts', 10, 1 );
		static::add_filter( 'deleted_post', 'delete_post_language', 11, 1 );

		// URL Rewriting
		static::add_filter( 'home_url', 'localize_home_url', 10, 3 );
		static::add_filter( 'page_link', 'localize_post_link', 10, 2 );
		static::add_filter( 'post_link', 'localize_post_link', 10, 2 );
		static::add_filter( 'post_type_link', 'localize_post_link', 10, 2 );
		static::add_filter( 'mod_rewrite_rules', 'fix_mod_rewrite_rules', 0, 1 );

		// Query Manipulation
		static::add_action( 'parse_query', 'maybe_set_queried_language', 10, 1 );
		static::add_filter( 'posts_join_request', 'add_post_translations_join_clause', 10, 2 );
		static::add_filter( 'posts_where_request', 'add_post_translations_where_clause', 10, 2 );
		static::add_filter( 'get_pages', 'filter_pages', 10, 2 );
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
		$priority = static::remove_action( 'save_post', __FUNCTION__ );

		// Now synchronize the post's translations
		Synchronizer::sync_post_with_sisters( $post_id, $skip_ids );

		// Rehook now that we're done
		static::add_action( 'save_post', __FUNCTION__, $priority, 1 );
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
	 * Localize a post's URL.
	 *
	 * Namely, localize it for it's assigned language.
	 * Also checks for localizing a home page translation.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Translator::get_post_translation() to get the post for that language.
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
				$translation = Translator::get_post_translation( $post_id, null, true );

				// If it's a home page translation, return the localized home url
				if ( $translation == get_option( 'page_on_front' ) ) {
					return Rewriter::localize_url( home_url( '', NL_UNLOCALIZED ), $language );
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
	 * @global WP_Rewrite $wp_rewrite The WordPress rewrite API.
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
	 * @param WP_Query $wp_query The WP_Query instance.
	 */
	function maybe_set_queried_language( \WP_Query $query ) {
		// Get the query var name and current language slug
		$query_var = Registry::get( 'query_var' );

		// Abort if no query var name is set or if it's already declared
		if ( ! $query_var || $query->get( $query_var ) !== '' ) {
			return;
		}

		/**
		 * Short circuit the language assignment; setting the language manually.
		 *
		 * @since 2.0.0
		 *
		 * @param mixed    $pre_value The value to set for the query (NULL to proceed).
		 * @param WP_Query $query     The query being modified.
		 */
		$pre_value = apply_filters( 'nlingual_pre_set_queried_language', null, $query );
		if ( ! is_null( $pre_value ) ) {
			$query->set( $query_var, $pre_value );
			return;
		}

		// If not the admin or some kind of posts feed, abort
		if ( ! ( is_admin() || $query->is_home() || $query->is_archive() || $query->is_search() ) ) {
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
		$query->set( $query_var, $value );
	}

	/**
	 * Adds JOIN clause for the translations table if needed.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported() to check if clause editing is needed.
	 * @uses Registry::get() to get the query var option.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
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
	 * @global wpdb $wpdb The database abstraction class instance.
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
}
