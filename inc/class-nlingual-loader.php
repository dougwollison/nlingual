<?php
/**
 * nLingual Logic
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class nLingual_Loader {
	/**
	 * Register the plugin hooks
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		register_activation_hook( NL_SELF, array( $this, 'plugin_activate' ) );
		register_deactivation_hook( NL_SELF, array( $this, 'plugin_deactivate' ) );
		register_uninstall_hook( NL_SELF, array( $this, 'plugin_uninstall' ) );
	}

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

	/**
	 * Create database tables and add default options.
	 *
	 * @since 2.0.0
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
				// Export the contents of the old languages table
				$languages = $wpdb->get_results("
					SELECT
						lang_id AS id,
						active,
						system_name,
						native_name,
						short_name,
						slug,
						iso_code,
						mo_file,
					FROM {$wpdb->prefix}nL_languages
					ORDER BY list_order
				");

				// Store as an option
				update_option( 'nlingual_languages', $languages );

				// Delete the table
				$wpdb->query( "DROP TABLE {$wpdb->prefix}nL_languages" );

				// We need to alter the translations table to the new format
				$wpdb->query(
					// Rename to new lowercase naming scheme
					"ALTER TABLE {$wpdb->prefix}nL_translations RENAME TO $wpdb->nl_translations;".
					// Start by removing the old unique key
					"ALTER TABLE $wpdb->nl_translations DROP KEY `post_id`;".
					// Now add the new object_type column
					"ALTER TABLE $wpdb->nl_translations ADD `object_type` varchar(20) COLLATE $charset_collate NOT NULL DEFAULT 'post';".
					// Rename post_id to object_id, placing it after object_type
					"ALTER TABLE $wpdb->nl_translations CHANGE `post_id` `object_id` bigint(20) unsigned NOT NULL AFTER `object_type`;".
					// Add the new unique key for object type/id pairs
					"ALTER TABLE $wpdb->nl_translations ADD UNIQUE KEY object_type_id (object_id, object_type);"
				);

				// Just in case, mark them down as being at least up to 2.0.0
				update_option( 'nlingual_databse_version', '2.0.0' );
			}

			// Just install/update the translations table as normal
			$sql_languages = "CREATE TABLE $wpdb->nl_translations (
				group_id bigint(20) unsigned NOT NULL,
				lang_id bigint(20) unsigned NOT NULL,
				object_type varchar(20) DEFAULT 'post' NOT NULL,
				object_id bigint(20) unsigned NOT NULL,
				UNIQUE KEY object_type_id (object_id, object_type),
				UNIQUE KEY group_lang (group_id, lang_id)
			) $charset_collate;";
			dbDelta( $sql_languages );

			// The string translations table
			$sql_languages = "CREATE TABLE $wpdb->nl_strings (
				string_id bigint(20) unsigned NOT NULL,
				string_key varchar(255) DEFAULT '' NOT NULL,
				string_text longtext NOT NULL,
				lang_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY  (string_id),
				UNIQUE KEY string_lang (string_key, lang_id)
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
}