<?php
namespace nLingual;

/**
 * nLingual Logic
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class API {
	// =========================
	// ! Properties
	// =========================

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
		$class = get_called_class();

		// Register the database tables (with backwards compatability for nL_ version)
		$wpdb->nl_languages = $wpdb->nL_languages = $wpdb->prefix . 'nl_languages';
		$wpdb->nl_strings = $wpdb->prefix . 'nl_strings';

		// Register the loader hooks
		Loader::register_hooks();

		// Load options
		static::load_options();

		// Setup action/filter hooks
		static::setup_global_hooks();
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			static::setup_backend_hooks();
		} else {
			static::setup_frontend_hooks();
		}

		// Add general actions
		add_action( 'plugins_loaded', array( $class, 'ready' ) );
	}

	// =========================
	// ! Propert Access Methods
	// =========================

	/**
	 * Get all languages.
	 *
	 * @since 2.0.0
	 *
	 * @uses static::$languages
	 *
	 * @return nLingual_Languages The languages collection.
	 */
	public static function languages() {
		return static::$language;
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
	// ! Utility/Callback Methods
	// =========================

	/**
	 * Load the relevant options
	 *
	 * @since 2.0.0
	 */
	protected static function load_options() {
		// Load languages
		static::$languages = get_option( 'nlingual_languages', new Languages );
	}

	/**
	 * Load text domain for localizations
	 *
	 * @since 2.0.0
	 */
	public static function ready() {
		load_plugin_textdomain( NL_TXTDMN, false, NL_DIR . '/lang' );
	}
}