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
	 *
	 * @return int The existing or new group ID.
	 */
	protected static function _translation_group_id( $type = null, $id = null ) {
		global $wpdb;

		// Attempt to retrieve the group ID for the object if present
		$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d", $type, $id ) );

		// Create a new one otherwise
		if ( ! $group_id ) {
			$group_id = $wpdb->get_var( "SELECT MAX(group_id) + 1 FROM $wpdb->nl_translations" );
		}

		return $group_id;
	}

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
			return false;
		}

		// Delete the original translation entry
		delete_object_language( $type, $id );

		// Insert a new one
		$wpdb->insert( $wpdb->nl_translations, array(
			'group_id'    => static::_translation_group_id(),
			'object_type' => $type,
			'object_id'   => $id,
		) );

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
	public static function delete_object_language( $type, $id, $lang ) {
		global $wpdb;

		// Ensure $lang is a Language
		if ( ! static::_lang( $lang ) ) {
			return false;
		}

		// Insert a new one
		$wpdb->delete( $wpdb->nl_translations, array(
			'object_type' => $type,
			'object_id'   => $id,
		) );

		// Remove it from the cache
		Registry::cache_delete( "{$type}_language", $id );

		return true;
	}

	/**
	 * Get the translation for an object.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 * @param mixed  $lang The language to get the translation for.
	 *
	 * @return int The ID of the objects counterpart in that language (false on failure).
	 */
	public static function get_object_translation( $type, $id, $lang ) {

	}
}