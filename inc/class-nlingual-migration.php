<?php
/**
 * nLingual Migration Utility
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class nLingual_Migration {
	/**
	 * Upgrade database structure from < 2.0.0
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function upgrade_database() {
		global $wpdb;

		static::export_languages();

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
		update_option( 'nlingual_database_version', '2.0.0' );
	}

	/**
	 * Export languages from database into options table.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function export_languages() {
		global $wpdb;

		// Export the contents of the old languages table
		$data = $wpdb->get_results("
			SELECT
				lang_id AS id,
				active,
				system_name,
				native_name,
				short_name,
				slug,
				iso,
				mo,
			FROM {$wpdb->prefix}nL_languages
			ORDER BY list_order
		");

		// Convert to new structure
		$languages = new nLingual_Languages;
		foreach ( $data as $i => $entry ) {
			$languages->add( array(
				'id'          => $entry->lang_id,
				'active'      => (bool) $entry->active,
				'slug'        => $entry->slug,
				'system_name' => $entry->system_name,
				'native_name' => $entry->native_name,
				'short_name'  => $entry->short_name,
				'iso_code'    => $entry->iso_code,
				'locale_name' => $entry->mo,
				'list_order'  => $i,
			) );
		}

		// Store in the options table
		update_option( 'nlingual_languages', $languages );
	}
}