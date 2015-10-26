<?php
/**
 * nLingual Loader Functionality
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

class Loader extends Functional {
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
		add_option( 'nlingual_skip_default_l10n', 0 );
		add_option( 'nlingual_query_var', 'nl_language' );
		add_option( 'nlingual_redirection_method', NL_REDIRECT_USING_GET );
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
		$db_version = get_option( 'nlingual_database_version' );
		$charset_collate = $wpdb->get_charset_collate();

		// Load dbDelta utility
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Install/update the tables
		if ( version_compare( $db_version, NL_DB_VERSION, '<' ) ) {
			// Check if upgrading from before 2.0.0
			if ( version_compare( $db_version, '2.0.0', '<' ) ) {
				static::convert_options();
				static::upgrade_database();
			}

			// Just install/update the languages table as normal
			$sql_languages = "CREATE TABLE $wpdb->nl_languages (
				language_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				system_name varchar(200) DEFAULT '' NOT NULL,
				native_name varchar(200) DEFAULT '' NOT NULL,
				short_name varchar(200) DEFAULT '' NOT NULL,
				locale_name varchar(100) DEFAULT '' NOT NULL,
				iso_code varchar(2) DEFAULT '' NOT NULL,
				slug varchar(100) DEFAULT '' NOT NULL,
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
				UNIQUE KEY object_type_id (object_id,object_type),
				UNIQUE KEY group_language (group_id,language_id)
			) $charset_collate;";
			dbDelta( $sql_translations );

			// The string localization table
			$sql_strings = "CREATE TABLE $wpdb->nl_strings (
				language_id bigint(20) unsigned NOT NULL,
				object_id bigint(20) unsigned NOT NULL,
				string_key varchar(128) DEFAULT '' NOT NULL,
				string_value longtext NOT NULL,
				UNIQUE KEY language_object_string (language_id,object_id,string_key)
			) $charset_collate;";
			dbDelta( $sql_strings );

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
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_languages" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_translations" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_strings" );

		// And delete the options
		$wpdb->query( "DELETE FORM $wpdb->options WHERE option_name like 'nlingual\_%'" );
	}

	// =========================
	// ! Migration Utilities
	// =========================

	/**
	 * Convert old options to new format.
	 *
	 * @since 2.0.0
	 */
	protected static function convert_options() {
		// Get the old nLingual-options
		$options = get_option( 'nLingual-options', array() );

		// Reassign to new options
		$renamed_options = array(
			'admin_only'        => 'nlingual_backend_only',
			'default_lang'      => 'nlingual_default_language',
			'delete_sisters'    => 'nlingual_delete_sisters',
			'get_var'           => 'nlingual_query_var',
			'l10n_dateformat'   => 'nlingual_localize_date',
			'method'            => 'nlingual_redirection_method',
			'post_types'        => 'nlingual_post_types',
			'skip_default_l10n' => 'nlingual_skip_default_l10n',
			'separator'         => 'nlingual-old_separator',
		);
		foreach ( $renamed_options as $oldname => $newname ) {
			if ( isset( $options[ $oldname ] ) ) {
				update_option( $newname, $options[ $oldname ] );
			}
		}

		// Get the old nLingual-sync_rules
		$old_sync_rules = get_option( 'nLingual-sync_rules', array() );
		$new_sync_rules = array( 'post_type' => array() );
		foreach ( $old_sync_rules as $post_type => $rules ) {
			$new_sync_rules['post_type'][ $post_type ] = array(
				'post_fields' => $rules['data'],
				'post_terms' => $rules['tax'],
				'post_meta' => $rules['meta'],
			);
		}

		// Save the new sync rules
		update_option( 'nlingual_sync_rules', $new_sync_rules );

		// Automatically set options that weren't present in old version
		update_option( 'nlingual_post_language_override', 1 );
	}

	/**
	 * Upgrade database structure from < 2.0.0.
	 *
	 * Converts old languages and translations tables to new formats.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	protected static function upgrade_database() {
		global $wpdb;

		// Get the collation if applicable
		$collate = '';
		if ( ! empty( $wpdb->collate ) ) {
			$collate = "COLLATE $wpdb->collate";
		}

		// We need to alter the languages table to the new format

		// Rename to lowercase
		$wpdb->query("ALTER TABLE {$wpdb->prefix}nL_languages RENAME TO {$wpdb->prefix}nl_languages2");
		$wpdb->query("ALTER TABLE {$wpdb->prefix}nl_languages2 RENAME TO $wpdb->nl_languages");
		// Rename lang_id to language_id, keeping it at the beginning
		$wpdb->query("ALTER TABLE $wpdb->nl_languages CHANGE `lang_id` `language_id` bigint(20) unsigned NOT NULL FIRST");
		// Rename mo to locale_name, placing it after short_name
		$wpdb->query("ALTER TABLE $wpdb->nl_languages CHANGE `mo` `locale_name` varchar(10) DEFAULT '' NOT NULL AFTER `short_name`");
		// Rename iso to iso_code, placing it after locale_name
		$wpdb->query("ALTER TABLE $wpdb->nl_languages CHANGE `iso` `iso_code` varchar(2) DEFAULT '' NOT NULL AFTER `locale_name`");
		// Relocate slug to after iso_code
		$wpdb->query("ALTER TABLE $wpdb->nl_languages MODIFY `slug` varchar(10) DEFAULT '' NOT NULL AFTER `iso_code`");

		// We need to alter the translations table to the new format

		// Rename to lowercase
		$wpdb->query("ALTER TABLE {$wpdb->prefix}nL_translations RENAME TO {$wpdb->prefix}nl_translations2");
		$wpdb->query("ALTER TABLE {$wpdb->prefix}nl_translations2 RENAME TO $wpdb->nl_translations");
		// Start by removing the old unique keys
		$wpdb->query("ALTER TABLE $wpdb->nl_translations DROP KEY post");
		$wpdb->query("ALTER TABLE $wpdb->nl_translations DROP KEY translation");
		// Rename lang_id to language_id, keeping it after group_id
		$wpdb->query("ALTER TABLE $wpdb->nl_translations CHANGE `lang_id` `language_id` bigint(20) unsigned NOT NULL AFTER `group_id`");
		// Now add the new object_type column
		$wpdb->query("ALTER TABLE $wpdb->nl_translations ADD `object_type` varchar(20) $collate NOT NULL DEFAULT 'post'");
		// Rename post_id to object_id, placing it after object_type
		$wpdb->query("ALTER TABLE $wpdb->nl_translations CHANGE `post_id` `object_id` bigint(20) unsigned NOT NULL AFTER `object_type`");
		// Add the new keys
		$wpdb->query("ALTER TABLE $wpdb->nl_translations ADD UNIQUE KEY group_lang (group_id, language_id)");
		$wpdb->query("ALTER TABLE $wpdb->nl_translations ADD UNIQUE KEY group_object (group_id, object_id)");
		$wpdb->query("ALTER TABLE $wpdb->nl_translations ADD UNIQUE KEY object_type (object_type, object_id)");

		// Just in case, mark them down as being at least up to 2.0.0
		update_option( 'nlingual_database_version', '2.0.0' );
	}
}