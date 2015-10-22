<?php
namespace nLingual;

/**
 * nLingual Translation API
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

class Translator {
	// =========================
	// ! Utilities
	// =========================

	/**
	 * Utility; convert $language passed into proper object format.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate and retrieve the passed language.
	 *
	 * @param mixed &$language The language to be converted.
	 *
	 * @return bool If the language was successfully converted.
	 */
	protected static function _lang( &$language ) {
		if ( is_a( $language, __NAMESPACE__ . '\Language' ) ) {
			return true;
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
			$group_id = $wpdb->get_var( "SELECT MAX(group_id) FROM $wpdb->nl_translations" );
			$group_id = intval( $group_id ) + 1;
		}

		return $group_id;
	}

	// =========================
	// ! Language Handling
	// =========================

	/**
	 * Get an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::cache_get() to check if this object's language has already been determined.
	 * @uses Registry::languages() to retrieve the Language object by ID.
	 * @uses Registry::cache_set() to store the result for future reuse.
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
	 * @uses Translator::delete_object_language() to allow for replacement.
	 * @uses Translator::_lang() to ensure $lang is a Language object.
	 * @uses Translator::_translation_group_id() to generate a new group ID.
	 * @uses Registry::cache_set() to update the object's cached language result.
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

		// Redirect to delete_object_language() if $lang is false-ish
		if ( ! $lang ) {
			return static::delete_object_language( $type, $id );
		}

		// Ensure $lang is a Language
		if ( ! static::_lang( $lang ) ) {
			return false; // Does not exist
		}

		// Get a group ID for the object
		$group_id = static::_translation_group_id( $type, $id );

		// Check if a counterpart for the language in this group exists
		$lang_exists = $wpdb->get_var( $wpdb->prepare( "
			SELECT object_id FROM $wpdb->nl_translations
			WHERE group_id = %d AND lang_id = %
		", $group_id, $lang->lang_id ) );
		if ( $lang_exists ) {
			// Get a new group ID if so
			$group_id = static::_translation_group_id();
		}

		// Insert a new one
		$wpdb->replace( $wpdb->nl_translations, array(
			'group_id'    => $group_id,
			'object_type' => $type,
			'object_id'   => $id,
			'lang_id'     => $lang->lang_id,
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
	 * @uses Registry::cache_delete() to clear the result from storage.
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
	// ! Translation Handling
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
				AND t1.object_id = %2\$d
		";

		// Add the additional where clause if $include_self is false
		if ( ! $include_self ) {
			$query .= "AND t2.object_id != %2\$d";
		}

		// Get the results of the query
		$results = $wpdb->get_results( $wpdb->prepare( $query, $type, $id ) );

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
	 * @uses Translator::_lang() to ensure $lang is a Language object.
	 *
	 * @see Translator::get_object_translations() For how the list is retrieved.
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

		$translations = static::get_object_translations( $type, $id );

		// Check if translation exists
		if ( isset( $translations[ $lang->lang_id ] ) ) {
			return $translations[ $lang->lang_id ];
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
	 * @uses Translator::_lang() to ensure $lang is a Language object.
	 * @uses Translator::unlink_object_translation() if a translation is to be unset.
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

		// Also get the language for this object
		$language = static::get_object_language( $type, $id );

		// If none was found, fail
		if ( ! $group_id ) {
			return false;
		}

		// Start the query
		$query = "REPLACE INTO $wpdb->nl_translations (group_id, object_type, object_id, lang_id) VALUES ";

		// Go through the $objects and handle accordingly
		$values = array();
		foreach ( $objects as $lang => $object_id ) {
			// Ensure $lang is a Language
			if ( ! static::_lang( $lang ) ) {
				return false; // Does not exist
			}

			// Skip if we're trying assign a translation for the object's language
			if ( $language->lang_id == $lang->lang_id ) {
				continue;
			}

			// If $object_id isn't valid, assume we want to unlink it
			if ( $object_id <= 0 ) {
				static::unlink_object_translation( $type, $id, $lang );
			} else {
				// Build the row data for the query
				$values[] = $wpdb->prepare( "(%d, %s, %d, %d)", $group_id, $type, $object_id, $lang->lang_id );
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
	 * @uses Translator::_lang() to ensure $lang is a Language object.
	 *
	 * @see Translator::set_object_translations() for how it's done.
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
		return static::set_object_translations( $type, $id, array( $lang->lang_id => $object ) );
	}

	/**
	 * Delete the association between two objects.
	 *
	 * This moves the object's translation in the target language to a new group.
	 * An entry of the matching object and it's language is preserved.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::_lang() to ensure $lang is a Language object.
	 * @uses Translator::_translation_group_id() to get the group ID for the object
	 *                                     as well a new one for it's sister.
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
				'lang_id' => $lang->lang_id
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	// =========================
	// ! URL Translation
	// =========================

	/**
	 * Get the permalink for a post in the desired language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::_lang() to ensure $lang is a Language object.
	 * @uses Translator::get_object_translation() to get the post's translation.
	 *
	 * @param int   $post_id The ID of the post.
	 * @param mixed $lang    Optional The desired language.
	 *
	 * @return string The translation's permalink.
	 */
	public static function get_permalink( $post_id, $lang = null ) {
		// Ensure $lang is a Language
		if ( ! static::_lang( $lang ) ) {
			// Doesn't exit; resort to original permalink
			return get_permalink( $post_id );
		}

		// Get the translation counterpart
		$translation_id = static::get_post_translation( $post_id, $lang );

		// Return the translations permalink
		return get_permalink( $translation_id );
	}

	/**
	 * Get the translated version of the post based on the path.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::get_permalink() to get the permalink for the matched post's translation.
	 *
	 * @param string $path      The path (in /parent/child/ or /page/ form) of the page to find.
	 * @param string $post_type The post type it should be looking for (defaults to page).
	 * @param mixed  $lang      The slug of the language requested (defaults to current language).
	 *
	 * @return string The translated permalink.
	 */
	public static function translate_link( $path, $post_type = null, $lang = null ) {
		// Default to page for post type
		if ( ! $post_type ) {
			$post_type = 'page';
		}

		// Get the ID based on the path provided
		$post = get_page_by_path( trim( $path, '/' ), OBJECT, $post_type );

		// Abort if not found
		if ( ! $post ) {
			return null;
		}

		// Get the translation's permalink
		return static::get_permalink( $post->ID, $lang );
	}

	// =========================
	// ! Statistics
	// =========================

	/**
	 * Get the number of posts in a specific language.
	 *
	 * Filter by post type and status.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param mixed  $lang_id     The id of the language to get the count for.
	 * @param string $post_type   The post type to filter by.
	 * @param string $post_status The post status to filter by.
	 *
	 * @return int The number of posts found.
	 */
	public static function language_posts_count( $lang_id, $post_type = null, $post_status = null ) {
		global $wpdb;

		$query = "
		SELECT COUNT(p.ID)
		FROM $wpdb->posts AS p
			LEFT JOIN $wpdb->nl_translations AS t
				ON p.ID = t.object_id AND t.object_type = 'post'
		";

		// Add language filter appropriately
		if ( $lang_id ) {
			$query .= $wpdb->prepare( "WHERE t.lang_id = %d", $lang_id );
		} else {
			$query .= "WHERE t.lang_id IS NULL";
		}

		// Add post_type filter if applicable
		if ( ! is_null( $post_type ) ) {
			$query .= $wpdb->prepare( " AND p.post_type = %s", $post_type );
		}

		// Add post_status filter if applicable
		if ( ! is_null( $post_status ) ) {
			$query .= $wpdb->prepare( " AND p.post_status = %s", $post_status );
		}

		// Run the query and return the results
		$count = $wpdb->get_var( $query );

		return intval( $count );
	}

	// =========================
	// ! Overloading
	// =========================

	/**
	 * Overload to the various _object_ methods.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $args The list of arguments for the method.
	 *
	 * @return mixed The result of the target method.
	 */
	public static function __callStatic( $name, $args ) {
		// Check if $name matches an existing method with $type instead of object
		if ( preg_match( '/^(get|set|delete)_(\w+?)_(language|translations?)$/', $name, $matches ) ) {
			// Get the parts
			list(, $action, $type, $meta ) = $matches;

			// Build target method name
			$method = $action . '_object_' . $meta;

			// If it exists, call it and return the result
			$class = get_called_class();
			if ( method_exists( $class, $method ) ) {
				// Add the $type argument
				array_unshift( $args, $type );
				return call_user_func_array( array( $class, $method ), $args );
			}
		}

		return null;
	}
}