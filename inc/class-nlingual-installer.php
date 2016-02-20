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

		// Get the old options, check for a separator, abort if none found
		$old_options = get_option( '__old-nLingual-options', array() );
		if ( ! isset( $old_options['separator'] ) || ! $old_options['separator'] ) {
			return $value;
		}

		// Prep the separator for regex use
		$separator_regex = preg_quote( $old_options['separator'], '/' );

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
		// Default option(s)
		add_option( 'nlingual_options', array() );
		add_option( 'nlingual_languages', array() );

		// Load dbDelta utility
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();

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
			//return false;
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
			Registry::set( 'backwards_compatible', 1 );
			// Save the change
			Registry::save();
		}

		// Log the current database version
		update_option( 'nlingual_database_version', NL_DB_VERSION );

		return true;
	}

	/**
	 * Upgrade database structure from < 2.0.0.
	 *
	 * Converts old translations tables to new formats.
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

		// Flag as having been converted
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
		 * Convert Languages
		 */

		// Grab the language entries, run them through the framework, and save them
		$entries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}nL_languages ORDER BY list_order ASC", ARRAY_A );
		foreach ( $entries as $entry ) {
			Registry::languages()->add( $entry );
		}

		/**
		 * Convert General Options
		 */

		// Get the old nLingual-options, and drop it
		$options = static::get_old_option( 'nLingual-options', array() );

		// Reassign to new options
		$renamed_options = array(
			'default_lang'      => 'default_language',
			'delete_sisters'    => 'delete_sister_posts',
			'delete_sisters'    => 'trash_sister_posts',
			'get_var'           => 'query_var',
			'l10n_dateformat'   => 'localize_date',
			'post_types'        => 'post_types',
			'skip_default_l10n' => 'skip_default_l10n',
		);
		foreach ( $renamed_options as $oldname => $newname ) {
			if ( isset( $options[ $oldname ] ) ) {
				Registry::set( $newname, $options[ $oldname ] );
			}
		}

		// Convert the redirection method
		if ( isset( $options['method'] ) ) {
			$method = strtolower( str_replace( 'NL_REDIRECT_USING_', '', $options['method'] ) );
			Registry::set( 'url_rewrite_method', $method );
		}

		/**
		 * Convert Sync Rules
		 */

		// Get the old nLingual-sync_rules
		$old_sync_rules = get_option( 'nLingual-sync_rules' );
		$new_sync_rules = array( 'post_type' => array() );
		foreach ( $old_sync_rules as $post_type => $rules ) {
			$new_sync_rules['post_type'][ $post_type ] = array(
				'post_fields' => $rules['data'],
				'post_terms' => $rules['tax'],
				'post_meta' => $rules['meta'],
			);
		}

		// Save the new sync rules
		Registry::set( 'sync_rules', $new_sync_rules );

		// Save empty new clone rules
		Registry::set( 'clone_rules', array() );

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
				if ( $language = Registry::languages()->get( $slug ) ) {
					// Add the location to the list of localizable ones
					$menu_locations[] = $location;

					// Create the new localized slug for the location
					$location .= '-language' . $language->id;
				}
			}

			$new_menus[ $location ] = $menu;
		}

		// Update the assigned menus
		set_theme_mod( 'nav_menu_locations', $new_menus );

		// Add the nav menu locations to the localizables list
		$localizables = array();
		$localizables['nav_menu_locations'] = $menu_locations;
		Registry::set( 'localizables', $localizables );

		// Save changes
		Registry::save();

		/**
		 * Final cleanup
		 */

		// Get the blog name and description values
		$name = get_option( 'blogname' );
		$description = get_option( 'blogdescription' );

		// Convert the name and description (if using language splitting), get the unlocalized versions
		$unlocalized_name = static::convert_split_string( $name, 'option:blogname' );
		$unlocalized_description = static::convert_split_string( $description, 'option:blogdescription' );

		// Update values
		update_option( 'blogname', $unlocalized_name );
		update_option( 'blogdescription', $unlocalized_description );

		// Now, drop the languages table
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nL_languages" );

		// Flag as having been converted
		update_option( '_nlingual_options_converted', 1 );
	}
}
