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
	 * @access protected
	 * @var string
	 */
	protected static $name;

	/**
	 * The language query var.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var string
	 */
	protected static $query_var;

	/**
	 * The default language id.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var string
	 */
	protected static $default_lang;

	/**
	 * The language directory.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var nLingual_Languages
	 */
	protected static $languages;

	/**
	 * The synchronization rules.
	 *
	 * @since 2.0.0
	 *
	 * @access protected
	 * @var array
	 */
	protected static $sync_rules = array();

	// =========================
	// ! Propert Access Methods
	// =========================

	/**
	 * Retrieve a property value.
	 *
	 * @since 2.0.0
	 *
	 * @param string $property The property name.
	 *
	 * @return mixed The property value.
	 */
	public static function get_option( $property ) {
		if ( property_exists( static::$name, $property ) ) {
			return static::$property;
		}
		return null;
	}

	/**
	 * Get an array of langauges by a certain key.
	 *
	 * @since 2.0.0
	 *
	 * @uses static::$languages
	 * @uses nLingual_Languages::as_array()
	 *
	 * @return array An array of nLingual_Language objects.
	 */
	public static function languages_by( $key ) {
		return static::$languages->as_array( $key );
	}

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

		// Load options
		static::load_options();

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
	 * Load the relevant options.
	 *
	 * @since 2.0.0
	 */
	protected static function load_options() {
		// Load simple options
		static::$query_var = get_option( 'nlingual_query_var', '' );
		static::$default_lang = get_option( 'nlingual_default_language', 0 );

		// Load languages
		static::$languages = get_option( 'nlingual_languages', new Languages );

		// Load sync rules
		static::$sync_rules = get_option( 'nlingual_sync_rules', array() );
	}

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
		if ( static::$query_var ) {
			$vars[] = static::$query_var;
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
		if ( static::is_post_type_supported( $query->get('post_type') )
		&& $query->get( static::$query_var ) ) {
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
		if ( static::is_post_type_supported( $query->get('post_type') )
		&& ( $lang = $query->get( static::$query_var ) )
		&& ( $language = static::$languages->get( $lang ) ) ) {
			$clause .= $wpdb->prepare( " AND $wpdb->nl_translations.lang_id = %d", $language->lang_id );
		}

		return $clause;
	}
}

// Initialize
API::init();