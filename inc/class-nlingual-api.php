<?php
namespace nLingual;

/**
 * nLingual Primary Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class API extends Functional {
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
	// ! Master Setup Method
	// =========================

	/**
	 * Register hooks and load options.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function setup() {
		global $wpdb;

		// Register the database tables (with backwards compatability for nL_ version)
		$wpdb->nl_translations = $wpdb->nL_translations = $wpdb->prefix . 'nl_translations';
		$wpdb->nl_strings = $wpdb->prefix . 'nl_strings';

		// Register the loader hooks
		Loader::register_hooks();

		// Setup the registry
		Registry::load();

		// Register the action/filter hooks
		static::register_hooks();
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			Backend::register_hooks();
		} else {
			Frontend::register_hooks();
		}

		// Add general actions
		static::add_action( 'plugins_loaded', 'ready' );
	}

	// =========================
	// ! Setup Utility Methods
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Query Filters
		static::add_filter( 'query_vars', 'add_language_var' );
		static::add_filter( 'posts_join_request', 'add_translations_join_clause', 10, 2 );
		static::add_filter( 'posts_where_request', 'add_translations_where_clause', 10, 2 );

		// Theme Setup Actions
		static::add_action( 'after_theme_setup', 'add_nav_menu_variations', 999 );
		static::add_action( 'after_theme_setup', 'add_sidebar_variations', 999 );
	}

	/**
	 * Load text domain for localizations
	 *
	 * @since 2.0.0
	 */
	public static function ready() {
		load_plugin_textdomain( NL_TXTDMN, false, NL_DIR . '/lang' );
	}

	// =========================
	// ! Query Filter Methods
	// =========================

	/**
	 * Register the language query var.
	 *
	 * @since 2.0.0
	 *
	 * @param array $vars The whitelist of query vars.
	 *
	 * @return array The updated whitelist.
	 */
	public static function add_language_var( array $vars ) {
		if ( Registry::$query_var ) {
			$vars[] = Registry::$query_var;
		}
		return $vars;
	}

	/**
	 * Adds JOIN clause for the translations table if needed.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string   $clause The JOIN clause.
	 * @param WP_Query $query  The WP_Query instance.
	 *
	 * @return string The updated JOIN clauses.
	 */
	public static function add_post_translations_join_clause( $clause, WP_Query $query ) {
		global $wpdb;

		// Check if the post type in question supports translation
		// and that the language is specified in the query
		if ( Registry::is_post_type_supported( $query->get('post_type') )
		&& $query->get( Registry::$query_var ) ) {
			$clause .= " INNER JOIN $wpdb->nl_translations ON ($wpdb->posts.ID = $wpdb->nl_translations.object_id AND $wpdb->nl_translations.object_type = 'post')";
		}

		return $clause;
	}

	/**
	 * Adds WHERE clause for the translation language id if needed.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string   $clause The WHERE clause.
	 * @param WP_Query $query  The WP_Query instance.
	 *
	 * @return string The updated WHERE clauses.
	 */
	public static function add_post_translations_where_clause( $clause, WP_Query $query ) {
		global $wpdb;

		// Check if the post type in question supports translation,
		// that the language is specified in the query,
		// and that a registered langauge can be found.
		if ( Registry::is_post_type_supported( $query->get('post_type') )
		&& ( $lang = $query->get( Registry::$query_var ) )
		&& ( $language = Registry::languages()->get( $lang ) ) ) {
			$clause .= $wpdb->prepare( " AND $wpdb->nl_translations.lang_id = %d", $language->lang_id );
		}

		return $clause;
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

		// Cancel if this feature isn't enabled
		$localizables = Registry::get_option( 'localizeables' );
		if ( ! in_array( 'nav_menus', $localizables ) ) {
			return;
		}

		// Abort if no menus are present
		if ( ! $_wp_registered_nav_menus ) {
			return;
		}

		// Build a new nav menu list; with copies of each menu for each language
		$localized_menus = array();
		foreach ( $_wp_registered_nav_menus as $slug => $name ) {
			foreach ( Registry::languages() as $lang ) {
				$new_slug = $slug . '--' . $lang->slug;
				$new_name = $name . ' (' . $lang->system_name . ')';
				$localized_menus[ $new_slug ] = $new_name;
			}
		}

		// Cache the old version of the menus for refernce
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
	public static function add_sidebars_variations() {
		global $wp_registered_sidebars;

		// Cancel if this feature isn't enabled
		$localizables = Registry::get_option( 'localizeables' );
		if ( ! in_array( 'sidebars', $localizables ) ) {
			return;
		}

		// Abort if no menus are present
		if ( ! $localized_sidebars ) {
			return;
		}

		// Build a new nav menu list; with copies of each menu for each language
		$localized_sidebars = array();
		foreach ( $localized_sidebars as $id => $args ) {
			foreach ( Registry::languages() as $lang ) {
				$new_id = $id . '--' . $lang->slug;
				$args['name'] .= ' (' . $lang->system_name . ')';
				$localized_sidebars[ $new_id ] = $args;
			}
		}

		// Cache the old version of the menus for refernce
		Registry::cache_set( 'vars', 'wp_registered_sidebars', $wp_registered_sidebars );

		// Replace the registered nav menu array with the new one
		$wp_registered_sidebars = $localized_sidebars;
	}
}

// Initialize
API::init();