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
	 * @uses Loader::register_hooks() to setup plugin management.
	 * @uses Registry::load() to load the options.
	 * @uses API::register_hooks() globally.
	 * @uses Backend::register_hooks() if in the admin (and not AJAX).
	 * @uses Frontend::register_hooks() if otherwise.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function setup() {
		global $wpdb;

		// Register the database tables (with backwards compatability for nL_ version)
		$wpdb->nl_languages = $wpdb->nL_languages = $wpdb->prefix . 'nl_languages';
		$wpdb->nl_translations = $wpdb->nL_translations = $wpdb->prefix . 'nl_translations';
		$wpdb->nl_strings = $wpdb->prefix . 'nl_strings';

		// Register the loader hooks
		Loader::register_hooks();

		// Setup the registry
		Registry::load();

		// Register global hooks
		static::register_hooks();

		// Register admin or public hooks
		if ( is_admin() ) {
			Backend::register_hooks();
			Manager::register_hooks();
			Localizer::register_hooks();
			Documenter::register_hooks();
		} else {
			Frontend::register_hooks();
		}

		// Register ajax hooks
		if ( defined( 'DOING_AJAX' ) ) {
			AJAX::register_hooks();
		}
	}

	// =========================
	// ! Controls
	// =========================

	/**
	 * Set the current language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::set() to store the new current language.
	 *
	 * @param Language $lang The desired language id.
	 * @param bool     $lock Wether or not to lock the selection.
	 */
	public static function set_language( Language $lang, $lock = false ) {
		if ( defined( 'NL_LANGUAGE_LOCKED' ) ) {
			return;
		}

		Registry::set( 'current_lang', $lang->lang_id );

		if ( $lock ) {
			// Lock the language from being changed again
			define( 'NL_LANGUAGE_LOCKED', true );
		}
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
		static::add_action( 'plugins_loaded', 'ready' );

		// Query Filters
		static::add_filter( 'query_vars', 'add_language_var' );
		static::add_filter( 'posts_join_request', 'add_post_translations_join_clause', 10, 2 );
		static::add_filter( 'posts_where_request', 'add_post_translations_where_clause', 10, 2 );
	}

	/**
	 * Load text domain for localization, and register options for localization.
	 *
	 * @since 2.0.0
	 */
	public static function ready() {
		// Load the textdomain
		load_plugin_textdomain( NL_TXTDMN, false, NL_DIR . '/lang' );

		// Register the blogname and blogdescription for localization
		Localizer::register_option( 'blogname', array(
			'page'   => 'options-general',
			'title'  => 'Site Title'
		) );
		Localizer::register_option( 'blogdescription', array(
			'page'   => 'options-general',
			'title'  => 'Tagline'
		) );
	}

	// =========================
	// ! Query Filters
	// =========================

	/**
	 * Register the language query var.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::get() to get the query var option.
	 *
	 * @param array $vars The whitelist of query vars.
	 *
	 * @return array The updated whitelist.
	 */
	public static function add_language_var( array $vars ) {
		if ( $query_var = Registry::get( 'query_var' ) ) {
			$vars[] = $query_var;
		}
		return $vars;
	}

	/**
	 * Adds JOIN clause for the translations table if needed.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::is_post_type_supported()
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

		// Abort if post type is not supported
		if ( ! Registry::is_post_type_supported( $query->get('post_type') ) ) {
			return $clause;
		}

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
	 * @uses Registry::is_post_type_supported()
	 * @uses Registry::get() to get the query var option.
	 * @uses Registry::languages() to validate and retrieve the language.
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

		// Abort if post type is not supported
		if ( ! Registry::is_post_type_supported( $query->get('post_type') ) ) {
			return $clause;
		}

		// Get the language
		$lang = $query->get( Registry::get( 'query_var' ) );

		// Abort if language isn't specified
		if ( $lang === '' ) {
			return $clause;
		}

		// Check if the language specified is "None"
		if ( $lang === '0' ) {
			$clause .= " AND $wpdb->nl_translations.lang_id IS NULL";
		} else
		// Otherwise check if the language exists
		if ( $language = Registry::languages()->get( $lang ) ) {
			$clause .= $wpdb->prepare( " AND $wpdb->nl_translations.lang_id = %d", $language->lang_id );
		}

		return $clause;
	}
}

// Initialize
API::init();