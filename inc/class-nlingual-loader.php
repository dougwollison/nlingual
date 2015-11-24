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
	// ! Properties
	// =========================

	/**
	 * The name of the class.
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected static $name;

	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register the plugin hooks
	 *
	 * @since 2.0.0
	 *
	 * @uses NL_SELF to identify the plugin file.
	 * @uses Loader::plugin_activate() as the activation hook.
	 * @uses Loader::plugin_deactivate() as the deactivation hook.
	 * @uses Loader::plugin_uninstall() as the uninstall hook.
	 */
	public static function register_hooks() {
		register_activation_hook( NL_SELF, array( static::$name, 'plugin_activate' ) );
		register_deactivation_hook( NL_SELF, array( static::$name, 'plugin_deactivate' ) );
		register_uninstall_hook( NL_SELF, array( static::$name, 'plugin_uninstall' ) );
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
	 * Create database tables and add default options.
	 *
	 * @since 2.0.0
	 *
	 * @uses Loader::plugin_security_check() to check for activation nonce.
	 * @uses Migrator::is_upgrading() to check if upgrading from nLingual 1.
	 * @uses Migrator::convert_tables() to convert database structure.
	 * @uses Migrator::convert_options() to convert plugin/blog options.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function plugin_activate() {
		global $wpdb;

		if ( ! static::plugin_security_check( 'activate' ) ) {
			return;
		}

		// Default options
		add_option( 'nlingual_default_language', 1 );
		add_option( 'nlingual_show_all_languages', 1 );
		add_option( 'nlingual_localize_date', 0 );
		add_option( 'nlingual_skip_default_l10n', 0 );
		add_option( 'nlingual_query_var', 'nl_language' );
		add_option( 'nlingual_redirection_method', 'get' );
		add_option( 'nlingual_patch_wp_locale', 0 );
		add_option( 'nlingual_post_language_override', 0 );
		add_option( 'nlingual_post_types', array() );
		add_option( 'nlingual_taxonomies', array() );
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

		// Get database version and upgrade if needed.
		$db_version = get_option( 'nlingual_database_version', '1.0' );
		$charset_collate = $wpdb->get_charset_collate();

		// Test if we're upgrading from nLingual 1, by checking for the old options array
		$upgrade_from_v1 = Migrator::is_upgrading();

		// Load dbDelta utility
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Install/update the tables
		if ( version_compare( $db_version, NL_DB_VERSION, '<' ) ) {
			// [Upgrading] convert the database tables and flag as having been upgraded
			if ( $upgrade_from_v1 ) {
				Migrator::convert_tables();

				// Flag as having been upgraded and needing backwards compatability
				add_option( 'nlingual_upgraded', 1 );
				add_option( 'nlingual_backwards_compatible', 1 );
			}

			// Just install/update the languages table as normal
			$sql_languages = "CREATE TABLE $wpdb->nl_languages (
				language_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				system_name varchar(200) DEFAULT '' NOT NULL,
				native_name varchar(200) DEFAULT '' NOT NULL,
				short_name varchar(200) DEFAULT '' NOT NULL,
				locale_name varchar(100) DEFAULT '' NOT NULL,
				iso_code char(2) DEFAULT '' NOT NULL,
				slug varchar(100) DEFAULT '' NOT NULL,
				direction enum('ltr', 'rtl') DEFAULT 'ltr' NOT NULL,
				list_order int(11) unsigned NOT NULL,
				active tinyint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY  (language_id),
				UNIQUE KEY slug (slug)
			) $charset_collate;";
			dbDelta( $sql_languages );

			// Just install/update the translations table as normal
			$sql_translations = "CREATE TABLE $wpdb->nl_translations (
				group_id bigint(20) unsigned NOT NULL,
				language_id bigint(20) unsigned NOT NULL,
				object_type varchar(20) DEFAULT 'post' NOT NULL,
				object_id bigint(20) unsigned NOT NULL,
				UNIQUE KEY translation (group_id,language_id,object_type,object_id)
				KEY group_id (group_id)
				KEY object_id (object_id)
			) $charset_collate;";
			dbDelta( $sql_translations );

			// The localizer data table
			$sql_strings = "CREATE TABLE $wpdb->nl_localizerdata (
				language_id bigint(20) unsigned NOT NULL,
				object_id bigint(20) unsigned NOT NULL,
				string_key varchar(128) DEFAULT '' NOT NULL,
				localized_value longtext NOT NULL,
				UNIQUE KEY localizerdata (language_id,object_id,string_key)
				KEY language_id (language_id)
				KEY object_id (object_id)
			) $charset_collate;";
			dbDelta( $sql_strings );

			// Log the current database version
			update_option( 'nlingual_database_version', NL_DB_VERSION );

			// [Upgrading] Now that the tables are setup, convert the options
			if ( $upgrade_from_v1 ) {
				Migrator::convert_options();
			}
		}
	}

	/**
	 * Empty deactivation hook for now.
	 *
	 * @since 2.0.0
	 *
	 * @uses Loader::plugin_security_check() to check for deactivation nonce.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function plugin_deactivate() {
		global $wpdb;

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
