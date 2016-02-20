<?php
/**
 * nLingual Installation Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Plugin Installer
 *
 * Registers activate/deactivate/uninstall hooks, and handle
 * any necessary upgrading from an existing install.
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */

class Installer extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register the plugin hooks
	 *
	 * @since 2.0.0
	 *
	 * @uses NL_PLUGIN_FILE to identify the plugin file.
	 * @uses Installer::plugin_activate() as the activation hook.
	 * @uses Installer::plugin_deactivate() as the deactivation hook.
	 * @uses Installer::plugin_uninstall() as the uninstall hook.
	 */
	public static function register_hooks() {
		// Plugin hooks
		register_activation_hook( NL_PLUGIN_FILE, array( __CLASS__, 'plugin_activate' ) );
		register_deactivation_hook( NL_PLUGIN_FILE, array( __CLASS__, 'plugin_deactivate' ) );
		register_uninstall_hook( NL_PLUGIN_FILE, array( __CLASS__, 'plugin_uninstall' ) );

		// Upgrade logic
		static::add_action( 'plugins_loaded', 'upgrade', 10, 0 );
	}

	// =========================
	// ! Internal Utilities
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
			// Check if this is the intended plugin for uninstalling
			if ( ! isset( $_REQUEST['checked'] )
			|| ! in_array( plugin_basename( NL_PLUGIN_FILE ), $_REQUEST['checked'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Test if the site previously used nLingual 1.
	 *
	 * Tests for presense of old nLingual-options array.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Wether or not we're upgrading.
	 */
	protected static function is_upgrading() {
		$old_options = get_option( 'nLingual-options' );
		return is_array( $old_options );
	}

	/**
	 * Get an old option value, return it, while archiving it under a prefixed name.
	 *
	 * @since 2.0.0
	 *
	 * @param string $option  The name of the old option to retrieve.
	 * @param mixed  $default The default value for the option.
	 *
	 * @return mixed The value of the option.
	 */
	protected static function get_old_option( $option, $default = null ) {
		$value = get_option( $option, $default );

		// Store under __old prefix and delete original
		update_option( "__old-$option", $value );
		delete_option( $option );

		return $value;
	}

	// =========================
	// ! Public Utilities
	// =========================

	/**
	 * Convert a split-language string for use with the Localization API.
	 *
	 * @internal Used by Installer::convert_options() and the terms converter.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::languages() to get the languages.
	 * @uses Localizer::save_string_value() to save the localized values of the string.
	 *
	 * @param string $value      The value to convert.
	 * @param string $string_key The key to save the localized values under.
	 * @param int    $object_id  Optional. The object ID to save under.
	 *
	 * @return string The unlocalized version found.
	 */
	public static function convert_split_string( $value, $string_key, $object_id = 0 ) {
		global $wpdb;

		// If no separator can be found, send back value
		$separator = get_option( 'nlingual-old_separator' );
		if ( ! $separator ) {
			return $value;
		}

		// Prep the separator for regex use
		$separator_regex = preg_quote( $separator, '/' );

		// Split
		$values = preg_split( "/\s*$separator_regex\s*/", $value );

		// Get the languages
		$languages = Registry::languages();

		// Loop through each value found and store it.
		foreach ( $values as $i => $val ) {
			// Get the language at the matching index, Break if not found
			if ( ! $language = $languages->nth( $i ) ) {
				break;
			}

			// Skip if value is empty
			if ( ! $val ) {
				continue;
			}

			// Save the value (ignoring string registration status)
			Localizer::save_field_value( $string_key, $language->id, $object_id, $val, false );
		}

		// Return the first value as the unlocalized one
		return $values[0];
	}

	// =========================
	// ! Plugin Hooks
	// =========================

	/**
	 * Create the default options.
	 *
	 * @since 2.0.0
	 *
	 * @uses Installer::plugin_security_check() to check for activation nonce.
	 * @uses Installer::upgrade() to check for and perform a system upgrade.
	 * @uses Installer::install() to proceed with regular installation otherwise.
	 *
	 * @todo Set default language to the current one of the install.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function plugin_activate() {
		if ( ! static::plugin_security_check( 'activate' ) ) {
			return;
		}

		// Attempt to upgrade, in case we're activating after an plugin update
		if ( ! static::upgrade() ) {
			// Otherwise just install the options/tables
			static::install();
		}
	}

	/**
	 * Empty deactivation hook for now.
	 *
	 * @since 2.0.0
	 *
	 * @uses Installer::plugin_security_check() to check for deactivation nonce.
	 */
	public static function plugin_deactivate() {
		if ( ! static::plugin_security_check( 'deactivate' ) ) {
			return;
		}

		// To be written
	}

	/**
	 * Delete database tables and any options.
	 *
	 * @since 2.0.0
	 *
	 * @uses Installer::plugin_security_check() to check for WP_UNINSTALL_PLUGIN.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function plugin_uninstall() {
		global $wpdb;

		if ( ! static::plugin_security_check() ) {
			return;
		}

		// Delete the object and string translation tables
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_languages" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_translations" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nl_localizations" );

		// And delete the options
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name like 'nlingual\_%'" );
	}

	// =========================
	// ! Install Logic
	// =========================

	/**
	 * Install tables the tables.
	 *
	 * @since 2.0.0
	 */
	public static function install() {
		// Default options
		add_option( 'nlingual_show_all_languages', 1 );
		add_option( 'nlingual_localize_date', 0 );
		add_option( 'nlingual_skip_default_l10n', 0 );
		add_option( 'nlingual_redirection_permanent', 0 );
		add_option( 'nlingual_patch_wp_locale', 0 );
		add_option( 'nlingual_post_language_override', 0 );
		add_option( 'nlingual_backwards_compatible', 0 );
		add_option( 'nlingual_trash_sister_posts', 0 );
		add_option( 'nlingual_delete_sister_posts', 0 );
		add_option( 'nlingual_default_language', 1 );
		add_option( 'nlingual_query_var', 'nl_language' );
		add_option( 'nlingual_url_rewrite_method', 'get' );
		add_option( 'nlingual_post_types', array() );
		add_option( 'nlingual_taxonomies', array() );
		add_option( 'nlingual_localizables', array(
			'nav_menu_locations' => array(),
			'sidebar_locations' => array(),
		) );
		add_option( 'nlingual_sync_rules', array(
			'post_types' => array(),
		)  );
		add_option( 'nlingual_clone_rules', array(
			'post_types' => array(),
		) );

		// Load dbDelta utility
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();

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
			UNIQUE KEY translation (group_id,language_id,object_type,object_id),
			KEY group_id (group_id),
			KEY object_id (object_id)
		) $charset_collate;";
		dbDelta( $sql_translations );

		// The localizer fields table
		$sql_localizer = "CREATE TABLE $wpdb->nl_localizations (
			language_id bigint(20) unsigned NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			field_key varchar(128) DEFAULT '' NOT NULL,
			localized_value longtext NOT NULL,
			UNIQUE KEY localizerdata (language_id,object_id,field_key),
			KEY language_id (language_id),
			KEY object_id (object_id)
		) $charset_collate;";
		dbDelta( $sql_localizer );
	}

	// =========================
	// ! Upgrade Logic
	// =========================

	/**
	 * Install/Upgrade the database tables, converting them if needed.
	 *
	 * @since 2.0.0
	 *
	 * @uses Installer::is_upgrading() to check if upgrading from nLingual 1.
	 * @uses Installer::convert_tables() to convert tables to 2.0.0 standards.
	 * @uses Installer::install_tables() to install/upgrade database tables.
	 * @uses Installer::convert_options() to convert relevant plugin and blog options.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @return bool Wether or not an upgrade was performed.
	 */
	public static function upgrade() {
		global $wpdb;

		// Abort if the site was previously using nLingual 2 or higher
		if ( version_compare( get_option( 'nlingual_database_version', '1.0.0' ), NL_DB_VERSION, '>=' ) ) {
			return false;
		}

		// If upgrading from nLingual 1, convert tables before updating them
		if ( static::is_upgrading() ) {
			static::convert_tables();
		}

		// Perform regular install
		static::install();

		// If upgrading from nLingual 1, convert options
		if ( static::is_upgrading() ) {
			static::convert_options();

			// Flag as having been upgraded
			add_option( 'nlingual_upgraded', 1 );

			// Also auto-enable backwards compatibility
			Registry::set( 'backwards_compatible', 1, 'save' );
		}

		// Log the current database version
		update_option( 'nlingual_database_version', NL_DB_VERSION );

		return true;
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
	protected static function convert_tables() {
		global $wpdb;

		// Abort if already flagged as converted
		if ( get_option( '_nlingual_tables_converted' ) ) {
			return;
		}

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
		$wpdb->query("ALTER TABLE $wpdb->nl_languages CHANGE `lang_id` `language_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT FIRST");
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

		// Flag as having been upgraded
		update_option( '_nlingual_tables_converted', 1 );
	}

	/**
	 * Convert various options, both plugin and blog, to new formats.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Installer::get_old_option() to fetch/archive general/sync options.
	 * @uses Installer::convert_split_string() to convert blog name/description.
	 */
	protected static function convert_options() {
		global $wpdb;

		// Abort if already flagged as converted
		if ( get_option( '_nlingual_options_converted' ) ) {
			return;
		}

		/**
		 * Convert General Options
		 */

		// Get the old nLingual-options, and drop it
		$options = static::get_old_option( 'nLingual-options', array() );

		// Reassign to new options
		$renamed_options = array(
			'admin_only'        => 'nlingual_backend_only',
			'default_lang'      => 'nlingual_default_language',
			'delete_sisters'    => 'nlingual_delete_sister_posts',
			'delete_sisters'    => 'nlingual_trash_sister_posts',
			'get_var'           => 'nlingual_query_var',
			'l10n_dateformat'   => 'nlingual_localize_date',
			'post_types'        => 'nlingual_post_types',
			'skip_default_l10n' => 'nlingual_skip_default_l10n',
		);
		foreach ( $renamed_options as $oldname => $newname ) {
			if ( isset( $options[ $oldname ] ) ) {
				update_option( $newname, $options[ $oldname ] );
			}
		}

		// Convert the redirection method
		if ( isset( $options['method'] ) ) {
			$method = strtolower( str_replace( 'NL_REDIRECT_USING_', '', $options['method'] ) );
			update_option( 'nlingual_url_rewrite_method', $method );
		}

		// Automatically set options that weren't present in old version
		update_option( 'nlingual_post_language_override', 1 );
		update_option( 'nlingual_patch_wp_locale', 1 );

		/**
		 * Convert Sync Rules
		 */

		// Get the old nLingual-sync_rules
		$old_sync_rules = static::get_old_option( 'nLingual-sync_rules', array() );
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

		// Save empty new clone rules
		update_option( 'nlingual_clone_rules', array() );

		/**
		 * Convert Navigation Menu Locations
		 */

		// Get the assigned menus
		$old_menus = get_theme_mod( 'nav_menu_locations' );

		// Loop through all menus and convert to new scheme if applicable
		$new_menus = array();
		$menu_locations = array();
		foreach ( $old_menus as $location => $menu ) {
			if ( preg_match( '/(.+?)--(\w{2})$/', $location, $matches ) ) {
				list( , $location, $slug ) = $matches;
				// Find a language matching the slug
				if ( $language_id = $wpdb->get_var( "SELECT language_id FROM {$wpdb->prefix}nL_languages WHERE slug = '$slug'" ) ) {
					// Add the location to the list of localizable ones
					$menu_locations[] = $location;

					// Create the new localized slug for the location
					$location .= '-language' . $language_id;
				}
			}

			$new_menus[ $location ] = $menu;
		}

		// Update the assigned menus
		set_theme_mod( 'nav_menu_locations', $new_menus );

		// Add the nav menu locations to the localizables list
		$localizables = array();
		$localizables['nav_menu_locations'] = $menu_locations;
		update_option( 'nlingual_localizables', $localizables );

		/**
		 * Final cleanup
		 */

		// Reload the registry
		Registry::load( true );

		// Get the blog name and description values
		$name = get_option( 'blogname' );
		$description = get_option( 'blogdescription' );

		// Convert the name and description (if using language splitting), get the unlocalized versions
		$unlocalized_name = static::convert_split_string( $name, 'option:blogname' );
		$unlocalized_description = static::convert_split_string( $description, 'option:blogdescription' );

		// Update values
		update_option( 'blogname', $unlocalized_name );
		update_option( 'blogdescription', $unlocalized_description );

		// Flag as having been upgraded
		update_option( '_nlingual_options_converted', 1 );
	}
}
