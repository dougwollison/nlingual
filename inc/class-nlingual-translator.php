<?php
namespace nLingual;

/**
 * nLingual Translator API
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Translator {
	/**
	 * Utility; convert $language passed into proper object format.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed &$language The language to be converted.
	 *
	 * @return bool If the language was successfully converted.
	 */
	protected static function _lang( &$language ) {
		if ( is_a( $language, __NAMESPACE__ . '\Language' ) ) {
			return;
		} else {
			$language = Registry::languages()->get( $language );
		}

		return (bool) $language;
	}

	/**
	 * Utility; get a translation group ID for an object.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type Optional The type of object.
	 * @param int    $id   Optional The ID of the object.
	 * @param bool   $inc  Optional Get new group ID if not found? (default true)
	 *
	 * @return int The existing or new group ID.
	 */
	protected static function _translation_group_id( $type = null, $id = null, $inc = true ) {
		global $wpdb;

		// Attempt to retrieve the group ID for the object if present
		$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d", $type, $id ) );

		// Create a new one otherwise
		if ( ! $group_id && $inc ) {
			$group_id = $wpdb->get_var( "SELECT MAX(group_id) + 1 FROM $wpdb->nl_translations" );
		}

		return $group_id;
	}

	// =========================
	// ! Language Methods
	// =========================

	/**
	 * Get an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 *
	 * @return Language The language of the object.
	 */
	public static function get_object_language( $type, $id ) {
		global $wpdb;

		// Check if it's cached, return if so
		if ( $language = Registry::cache_get( "{$type}_language", $id ) ) {
			return $language;
		}

		// Query the translations table for the language of the object in question
		$lang_id = $wpdb->get_var( $wpdb->prepare( "SELECT lang_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d", $type, $id ) );
		$language = Registry::languages()->get( $lang_id );

		// Add it to the cache
		Registry::cache_set( "{$type}_language", $id, $language );

		return $language;
	}

	/**
	 * Set an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 * @param mixed  $lang The language of the object.
	 *
	 * @return bool If the assignment worked or not.
	 */
	public static function set_object_language( $type, $id, $lang ) {
		global $wpdb;

		// Ensure $lang is a Language
		if ( ! static::_lang( $lang ) ) {
			return false; // Does not exist
		}

		// Delete the original translation entry
		delete_object_language( $type, $id );

		// Insert a new one
		$wpdb->replace( $wpdb->nl_translations, array(
			'group_id'    => static::_translation_group_id(),
			'object_type' => $type,
			'object_id'   => $id,
			'lang_id'     => $lang->id,
		), array( '%d', '%s', '%d', '%d' ) );

		// Add it to the cache
		Registry::cache_set( "{$type}_language", $id, $language );

		return true;
	}

	/**
	 * Delete an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 *
	 * @return bool If the deletion worked or not.
	 */
	public static function delete_object_language( $type, $id ) {
		global $wpdb;

		// Insert a new one
		$wpdb->delete( $wpdb->nl_translations, array(
			'object_type' => $type,
			'object_id'   => $id,
		) );

		// Remove it from the cache
		Registry::cache_delete( "{$type}_language", $id );

		return true;
	}

	// =========================
	// ! Translation Methods
	// =========================

	/**
	 * Get all translations for an object.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type         The type of object.
	 * @param int    $id           The ID of the object.
	 * @param bool   $include_self Optional Include this object in the list (default false).
	 *
	 * @return array An associative array of objects in lang_id => object_id format.
	 */
	public static function get_object_translations( $type, $id, $include_self = false ) {
		global $wpdb;

		$query = "
			SELECT
				t2.lang_id,
				t2.object_id
			FROM
				$wpdb->nl_translations AS t1
				LEFT JOIN
					$wpdb->nl_translations AS t2
					ON (t1.group_id = t2.group_id)
			WHERE 1=1
				AND t1.object_type = %s
				AND t1.object_id = %1\$d
		";

		// Add the additional where clause if $include_self is false
		if ( ! $include_self ) {
			$query .= "AND t2.object_id != %1\$d";
		}

		// Get the results of the query
		$results = $wpdb->get_results( $wpdb->prepare( $query, $post_id ) );

		// Loop through the results and build the lang_id => object_id list
		$objects = array();
		foreach ( $results as $row ) {
			$objects[ $row->lang_id ] = $row->object_id;
		}

		return $objects;
	}

	/**
	 * Get specific translation for an object.
	 *
	 * @since 2.0.0
	 *
	 * @see Translator::get_object_translations()
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type        The type of object.
	 * @param int    $id          The ID of the object.
	 * @param mixed  $lang        The language to get the translation for.
	 * @param bool   $return_self Optional Return $id if nothing found? (default false).
	 *
	 * @return int The ID of the object's counterpart in that language (false on failure).
	 */
	public static function get_object_translation( $type, $id, $lang, $return_self = false ) {
		// Ensure $lang is a Language
		if ( ! static::_lang( $lang ) ) {
			return false; // Does not exist
		}

		$translations = get_object_translations( $type, $id );

		// Check if translation exists
		if ( isset( $translations[ $lang->id ] ) ) {
			return $translations[ $lang->id ];
		}

		// Otherwise, return the original id or false, depending on $return_self
		return $return_self ? $id : false;
	}

	/**
	 * Set the translations for an object in 1 or more languages.
	 *
	 * Will fail if the primary isn't already in the database or if
	 * any of the languages listed aren't valid.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type    The type of the objects.
	 * @param int    $id      The ID of the primary object.
	 * @param array  $objects A list of objects to associate (id => lang_id format).
	 *
	 * @param bool Wether or not the association could be done.
	 */
	public static function set_object_translations( $type, $id, $objects ) {
		global $wpdb;

		// Get the group ID for this object
		$group_id = static::_translation_group_id( $type, $id, false );

		// If none was found, fail
		if ( ! $group_id ) {
			return false;
		}

		// Start the query
		$query = "REPLACE INTO $wpdb->nl_translations (group_id, object_type, object_id, lang_id) VALUES ";

		// Go through the $objects and handle accordingly
		$values = array();
		foreach ( $objects as $object_id => $lang ) {
			// Ensure $lang is a Language
			if ( ! static::_lang( $lang ) ) {
				return false; // Does not exist
			}

			// If $object_id isn't valid, assume we want to unlink it
			if ( $object_id <= 0 ) {
				static::unlink_object_translation( $type, $id, $lang );
			} else {
				// Build the row data for the query
				$values[] = $wpdb->prepare( "(%d, %s, %d, %d)", $group_id, $type, $object_id, $lang_id );
			}
		}

		// Add the values to the query
		$query .= implode( ',', $values );

		// Run the query
		$wpdb->query( $query );

		return true;
	}

	/**
	 * Set a translation for an object in a specific language.
	 *
	 * @since 2.0.0
	 *
	 * @see Translator::set_object_translations()
	 *
	 * @param string $type   The type of the objects.
	 * @param int    $id     The ID of the primary object.
	 * @param mixed  $lang   The language to add a translation for.
	 * @param int    $object The object to add as the translation.
	 *
	 * @param bool Wether or not the association could be done.
	 */
	public static function set_object_translation( $type, $id, $lang, $object ) {
		// Ensure $lang is a Language
		if ( ! static::_lang( $lang ) ) {
			return false; // Does not exist
		}

		// Alias to set_object_translations method
		return static::set_object_translations( $type, $id, array( $lang->id => $object ) );
	}

	/**
	 * Delete the association between two objects.
	 *
	 * This moves the object's translation in the target language to a new group.
	 * An entry of the matching object and it's language is preserved.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 * @param mixed  $lang The language to remove the association for.
	 */
	public static function delete_object_translation( $type, $id, $lang ) {
		global $wpdb;

		// Ensure $lang is a Language
		if ( ! static::_lang( $lang ) ) {
			return false; // Does not exist
		}

		// Get the group ID for this object
		$group_id = static::_translation_group_id( $type, $id );

		// Get a new group ID for the sister object
		$new_group_id = static::_translation_group_id();

		// Update the group ID for the translation
		$wpdb->update(
			$wpdb->nl_translations,
			array(
				'group_id' => $new_group_id,
			),
			array(
				'group_id' => $group_id,
				'lang_id' => $lang->id
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}
}