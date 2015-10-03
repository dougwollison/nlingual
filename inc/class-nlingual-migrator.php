<?php
namespace nLingual;

/**
 * nLingual Migrator Utility
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Migrator {
	/**
	 * Upgrade database structure from < 2.0.0
	 *
	 * @since 2.0.0
	 *
	 * @uses Migrator::export_languages() for converting the languages table to an option.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 */
	public static function upgrade_database() {
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
		// Rename mo to locale_name, placing it after short_name
		$wpdb->query("ALTER TABLE $wpdb->nL_languages CHANGE `mo` `locale_name` varchar(10) DEFAULT '' NOT NULL AFTER `short_name`");
		// Rename iso to iso_code, placing it after locale_name
		$wpdb->query("ALTER TABLE $wpdb->nL_languages CHANGE `iso` `iso_code` varchar(2) DEFAULT '' NOT NULL AFTER `locale_name`");
		// Relocate slug to after iso_code
		$wpdb->query("ALTER TABLE $wpdb->nL_languages MODIFY `slug` varchar(10) DEFAULT '' NOT NULL AFTER `iso_code`");

		// We need to alter the translations table to the new format

		// Rename to lowercase
		$wpdb->query("ALTER TABLE {$wpdb->prefix}nL_translations RENAME TO {$wpdb->prefix}nl_translations2");
		$wpdb->query("ALTER TABLE {$wpdb->prefix}nl_translations2 RENAME TO $wpdb->nl_translations");
		// Start by removing the old unique key
		$wpdb->query("ALTER TABLE $wpdb->nl_translations DROP KEY `post_id`");
		// Now add the new object_type column
		$wpdb->query("ALTER TABLE $wpdb->nl_translations ADD `object_type` varchar(20) $collate NOT NULL DEFAULT 'post'");
		// Rename post_id to object_id, placing it after object_type
		$wpdb->query("ALTER TABLE $wpdb->nl_translations CHANGE `post_id` `object_id` bigint(20) unsigned NOT NULL AFTER `object_type`");
		// Add the new unique key for object type/id pairs
		$wpdb->query("ALTER TABLE $wpdb->nl_translations ADD UNIQUE KEY object_type_id (object_id, object_type)");

		// Just in case, mark them down as being at least up to 2.0.0
		update_option( 'nlingual_database_version', '2.0.0' );
	}
}