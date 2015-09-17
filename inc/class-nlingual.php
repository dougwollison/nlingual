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
	 */
	public static function setup() {
		$class = get_called_class();

		// Register plugin hooks
		register_activation_hook( NL_SELF, array( $class, 'plugin_activate' ) );
		register_deactivation_hook( NL_SELF, array( $class, 'plugin_deactivate' ) );
		register_uninstall_hook( NL_SELF, array( $class, 'plugin_uninstall' ) );

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
	// ! Plugin Hooks/Actions
	// =========================

	/**
	 * Activation/Deactivation/Uninstall security check logic.
	 *
	 * @since 2.0.0
	 */
	protected static function plugin_security_check( $check_referer = null ) {
		// Make sure they have permisson
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return false;
		}

		if ( $check_referer ) {
			$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
			check_admin_referer( "{$check_referer}-plugin_{$plugin}" );
		} else {
			// Check if this is the intended file for uninstalling
			if ( __FILE__ != WP_UNINSTALL_PLUGIN ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Create database tables and add default options.
	 *
	 * @since 2.0.0
	 */
	public static function plugin_activate() {
		if ( ! static::plugin_security_check( 'activate' ) ) {
			return;
		}
	}

	/**
	 * Empty deactivation hook for now.
	 *
	 * @since 2.0.0
	 */
	public static function plugin_deactivate() {
		if ( ! static::plugin_security_check( 'deactivate' ) ) {
			return;
		}
	}

	/**
	 * Delete database tables and add options.
	 *
	 * @since 2.0.0
	 */
	public static function plugin_uninstall() {
		if ( ! static::plugin_security_check() ) {
			return;
		}
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