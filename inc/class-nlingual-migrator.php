<?php
/**
 * nLingual Migrator API
 *
 * @package nLingual
 * @subpackage Tools
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Migration System
 *
 * Provides internal-use tools for converting from
 * the old nLingual 1.* system.
 *
 * @package nLingual
 * @subpackage Tools
 *
 * @since 2.0.0
 */

class Migrator {
	// =========================
	// ! Utilities
	// =========================

	/**
	 * Test if the site previously used nLingual 1.
	 *
	 * Tests for presense of old nLingual-options array.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Wether or not we're upgrading.
	 */
	public static function is_upgrading() {
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
	public static function get_old_option( $option, $default = null ) {
		$value = get_option( $option, $default );

		// Store under __old prefix and delete original
		update_option( "__old-$option", $value );
		delete_option( $option );

		return $value;
	}

	/**
	 * Convert a split-language string for use with the Localization API.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
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

		// Get the language IDs
		$language_ids = $wpdb->get_col( "SELECT language_id FROM $wpdb->nl_languages ORDER BY list_order ASC" );

		// Loop through each value found and store it.
		foreach ( $values as $i => $val ) {
			// Break if empty or no corresponding language is found
			if ( ! isset( $language_ids[ $i ] ) ) {
				break;
			}

			// Skip if value is empty
			if ( ! $val ) {
				continue;
			}

			// Save the value (ignoring string registration status)
			Localizer::save_string_value( $string_key, $language_ids[ $i ], $object_id, $val, false );
		}

		// Return the first value as the unlocalized one
		return $values[0];
	}

	// =========================
	// ! Main Methods
	// =========================

	/**
	 * Upgrade database structure from < 2.0.0.
	 *
	 * Converts old languages and translations tables to new formats.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function convert_tables() {
		global $wpdb;

		// Abort if already flagged as upgraded
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

		// Flag as having been upgraded
		update_option( '_nlingual_tables_converted', 1 );
	}

	/**
	 * Convert various options, both plugin and blog, to new formats.
	 *
	 * @since 2.0.0
	 *
	 * @uses Migrator::get_old_option() to fetch/archive general/sync options.
	 * @uses Migrator::convert_split_strings() to convert blog name/description.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function convert_options() {
		global $wpdb;

		// Abort if already flagged as upgraded
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

		// Update the nav menu locations for localizables
		$localizables = array();
		$localizables['nav_menu_locations'] = $menu_locations;
		update_option( 'nlingual_localizables', $localizables );

		// Update the assigned menus
		set_theme_mod( 'nav_menu_locations', $new_menus );

		/**
		 * Convert site name/description if using language splitting
		 */

		// Get the blog name and description values
		$name = get_option( 'blogname' );
		$description = get_option( 'blogdescription' );

		// Convert the name and description, get the unlocalized versions
		$unlocalized_name = static::convert_split_string( $name, 'option:blogname' );
		$unlocalized_description = static::convert_split_string( $description, 'option:blogdescription' );

		// Update values
		update_option( 'blogname', $unlocalized_name );
		update_option( 'blogdescription', $unlocalized_description );

		/**
		 * Flag as having converted the options.
		 */

		// Flag as having been upgraded
		update_option( '_nlingual_options_converted', 1 );
	}
}
