<?php
namespace nLingual;

/**
 * nLingual Loader Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Loader extends Functional {
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
	// ! Methods
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

	/**
	 * Security check logic.
	 *
	 * @since 2.0.0
	 */
	protected static function plugin_security_check( $check_referer = null ) {
		return true;
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
	 *
	 * @uses Loader::plugin_security_check() to check for activation nonce.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function plugin_activate() {
		global $wpdb;

		if ( ! static::plugin_security_check( 'activate' ) ) {
			return;
		}

		// Get database version and upgrade if needed.
		$db_version = get_option( 'nlingual_database_version' );
		$charset_collate = $wpdb->get_charset_collate();

		// Load dbDelta utility
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Install/update the tables
		if ( version_compare( $db_version, NL_DB_VERSION, '<' ) ) {
			// Check if upgrading from before 2.0.0
			if ( version_compare( $db_version, '2.0.0', '<' ) ) {
				Migrator::upgrade_database();
			}

			// Just install/update the translations table as normal
			$sql_languages = "CREATE TABLE $wpdb->nl_translations (
				group_id bigint(20) unsigned NOT NULL,
				lang_id bigint(20) unsigned NOT NULL,
				object_type varchar(20) DEFAULT 'post' NOT NULL,
				object_id bigint(20) unsigned NOT NULL,
				UNIQUE KEY object_type_id (object_id,object_type),
				UNIQUE KEY group_lang (group_id,lang_id)
			) $charset_collate;";
			dbDelta( $sql_languages );

			// The string translations table
			$sql_languages = "CREATE TABLE $wpdb->nl_strings (
				string_id bigint(20) unsigned NOT NULL,
				string_key varchar(64) DEFAULT '' NOT NULL,
				string_text longtext NOT NULL,
				PRIMARY KEY  (string_id),
				UNIQUE KEY string_key (string_key)
			) $charset_collate;";
			dbDelta( $sql_languages );

			// Log the current database version
			update_option( 'nlingual_database_version', NL_DB_VERSION );
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
		$wpdb->query( "DROP TABLE IF EXISTS $wpdb->nl_translations" );
		$wpdb->query( "DROP TABLE IF EXISTS $wpdb->nl_strings" );

		// And delete the options
		$wpdb->query( "DELETE FORM $wpdb->options WHERE option_name like 'nlingual\_%'" );
	}
}

// Initialize
Loader::init();