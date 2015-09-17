<?php
/**
 * nLingual Logic
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class nLingual {
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
		nLingual_Loader::register_hooks();

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

	/**
	 * Load text domain for localizations
	 *
	 * @since 2.0.0
	 */
	public static function ready() {
		load_plugin_textdomain( NL_TXTDMN, false, NL_DIR . '/lang' );
	}
}