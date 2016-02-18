<?php
/**
 * nLingual Loader Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Plugin Loader
 *
 * Registers the necessary hooks to handle the nLingual plugin's
 * (un)installation and (de)activation.
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */

class Loader extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register the plugin hooks
	 *
	 * @since 2.0.0
	 *
	 * @uses NL_PLUGIN_FILE to identify the plugin file.
	 * @uses Loader::plugin_activate() as the activation hook.
	 * @uses Loader::plugin_deactivate() as the deactivation hook.
	 * @uses Loader::plugin_uninstall() as the uninstall hook.
	 */
	public static function register_hooks() {
		register_activation_hook( NL_PLUGIN_FILE, array( get_called_class(), 'plugin_activate' ) );
		register_deactivation_hook( NL_PLUGIN_FILE, array( get_called_class(), 'plugin_deactivate' ) );
		register_uninstall_hook( NL_PLUGIN_FILE, array( get_called_class(), 'plugin_uninstall' ) );
	}

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Security check logic.
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

	// =========================
	// ! Hook Handlers
	// =========================

	/**
	 * Create the default options.
	 *
	 * @since 2.0.0
	 *
	 * @uses Loader::plugin_security_check() to check for activation nonce.
	 * @uses inc/presets.php To get the details for the default first language.
	 *
	 * @todo Set default language to the current one of the install.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function plugin_activate() {
		if ( ! static::plugin_security_check( 'activate' ) ) {
			return;
		}

		// Default options
		add_option( 'nlingual_default_language', 1 );
		add_option( 'nlingual_show_all_languages', 1 );
		add_option( 'nlingual_localize_date', 0 );
		add_option( 'nlingual_skip_default_l10n', 0 );
		add_option( 'nlingual_query_var', 'nl_language' );
		add_option( 'nlingual_url_rewrite_method', 'get' );
		add_option( 'nlingual_redirection_permanent', 0 );
		add_option( 'nlingual_patch_wp_locale', 0 );
		add_option( 'nlingual_post_language_override', 0 );
		add_option( 'nlingual_backwards_compatible', 1 );
		add_option( 'nlingual_post_types', array() );
		add_option( 'nlingual_taxonomies', array() );

		// Default rules/lists: empty must-have entries
		add_option( 'nlingual_sync_rules', array(
			'post_types' => array(),
		)  );
		add_option( 'nlingual_clone_rules', array(
			'post_types' => array(),
		) );
		add_option( 'nlingual_localizables', array(
			'nav_menu_locations' => array(),
			'sidebar_locations' => array(),
		) );

		// Default languages: English
		$presets = require( NL_PLUGIN_DIR . '/inc/presets-languages.php' );
		add_option( 'nlingual_languages', new Languages( array( $presets['en'] ) ) );
	}

	/**
	 * Empty deactivation hook for now.
	 *
	 * @since 2.0.0
	 *
	 * @uses Loader::plugin_security_check() to check for deactivation nonce.
	 */
	public static function plugin_deactivate() {
		if ( ! static::plugin_security_check( 'deactivate' ) ) {
			return;
		}
	}

	/**
	 * Delete database tables and any options.
	 *
	 * @since 2.0.0
	 *
	 * @uses Loader::plugin_security_check() to check for WP_UNINSTALL_PLUGIN.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function plugin_uninstall() {
		if ( ! static::plugin_security_check() ) {
			return;
		}

		// Delete the object and string translation tables
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_languages" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_translations" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_localizerdata" );

		// And delete the options
		$wpdb->query( "DELETE FORM $wpdb->options WHERE option_name like 'nlingual\_%'" );
	}
}
