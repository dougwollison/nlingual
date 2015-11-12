<?php
/**
 * nLingual Translation API
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

class Translator {
	use Utilities;

	// =========================
	// ! Utilities
	// =========================

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

		$cache_id = "{$type}/{$id}";

		// Check if it's cached, return if so
		if ( $language = Registry::cache_get( 'language', $cache_id ) ) {
			return $language;
		}

		// Query the translations table for the language of the object in question
		$language_id = $wpdb->get_var( $wpdb->prepare( "SELECT language_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d", $type, $id ) );
		$language = Registry::languages()->get( $language_id );

		// Add it to the cache
		Registry::cache_set( 'language', $cache_id, $language );

		return $language;
	}

	/**
	 * Set an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::delete_object_language() to allow for replacement.
	 * @uses Utilities::_language() to ensure $language is a Language object.
	 * @uses Translator::_translation_group_id() to generate a new group ID.
	 * @uses Registry::cache_set() to update the object's cached language result.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type     The type of object.
	 * @param int    $id       The ID of the object.
	 * @param mixed  $language The language of the object.
	 *
	 * @return bool If the assignment worked or not.
	 */
	public static function set_object_language( $type, $id, $language ) {
		global $wpdb;

		// Redirect to delete_object_language() if $language is false-ish
		if ( ! $language ) {
			return static::delete_object_language( $type, $id );
		}

		// Ensure $language is a Language
		if ( ! static::_language( $language ) ) {
			return false; // Does not exist
		}

		// Get a group ID for the object
		$group_id = static::_translation_group_id( $type, $id );

		// Check if a counterpart for the language in this group exists
		$language_exists = $wpdb->get_var( $wpdb->prepare( "
			SELECT object_id FROM $wpdb->nl_translations
			WHERE group_id = %d AND language_id = %
		", $group_id, $language->id ) );
		if ( $language_exists ) {
			// Get a new group ID if so
			$group_id = static::_translation_group_id();
		}

		// Insert a new one
		$wpdb->replace( $wpdb->nl_translations, array(
			'group_id'    => $group_id,
			'object_type' => $type,
			'object_id'   => $id,
			'language_id' => $language->id,
		), array( '%d', '%s', '%d', '%d' ) );

		// Add it to the cache
		Registry::cache_delete( 'language', "{$type}/{$id}", $language );

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
		Registry::cache_delete( 'language', "{$type}/{$id}" );

		return true;
	}

	// =========================
	// ! Translation Handling
	// =========================

	/**
	 * Get a translation for an object.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type        The type of object.
	 * @param int    $id          The ID of the object.
	 * @param bool   $return_self Optional Return $id if nothing is found (default false).
	 *
	 * @return bool|int The id of the translation.
	 */
	public static function get_object_translation( $type, $id, $language, $return_self = false ) {
		global $wpdb;

		// Ensure $language is a Language
		if ( ! static::_language( $language ) ) {
			return false; // Does not exist
		}

		$cache_id = "{$type}/{$id}/{$language->id}";

		// Check if it's cached, fetch if not
		if ( ! ( $translation = Registry::cache_get( 'translation', $cache_id ) ) ) {
			$query = "
				SELECT
					t2.object_id
				FROM
					$wpdb->nl_translations AS t1
					LEFT JOIN
						$wpdb->nl_translations AS t2
						ON (t1.group_id = t2.group_id)
				WHERE 1=1
					AND t1.object_type = %s
					AND t1.object_id = %d
					AND t2.language_id = %d
			";

			// Get the results of the query
			$translation = $wpdb->get_var( $wpdb->prepare( $query, $type, $id, $language->id ) );

			// Add it to the cache
			Registry::cache_set( 'translation', $cache_id, $translation );
		}

		// If a translation is found, return it
		if ( $translation ) {
			return $translation;
		}

		// Otherwise, return the original ID or false, depending on $return_self
		return $return_self ? $id : false;
	}

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
	 * @return array An associative array of objects in language_id => object_id format.
	 */
	public static function get_object_translations( $type, $id, $include_self = false ) {
		global $wpdb;

		$cache_id = "{$type}/{$id}";

		// Check if it's cached, fetch if not
		if ( ! ( $translations = Registry::cache_get( 'translations', $cache_id ) ) ) {
			$query = "
				SELECT
					t2.language_id,
					t2.object_id
				FROM
					$wpdb->nl_translations AS t1
					LEFT JOIN
						$wpdb->nl_translations AS t2
						ON (t1.group_id = t2.group_id)
				WHERE 1=1
					AND t1.object_type = %s
					AND t1.object_id = %d
			";

			// Get the results of the query
			$results = $wpdb->get_results( $wpdb->prepare( $query, $type, $id ) );

			// Loop through the results and build the language_id => object_id list
			$translations = array();
			foreach ( $results as $row ) {
				$translations[ $row->language_id ] = $row->object_id;
			}

			// Add it to the cache
			Registry::cache_set( 'translations', $cache_id, $translations );
		}

		// Remove the target posts ID if desired
		if ( ! $include_self ) {
			$language_id = array_search( $id, $translations );
			unset( $translations[ $language_id ] );
		}

		return $translations;
	}

	/**
	 * Set the translations for an object in 1 or more languages.
	 *
	 * Will fail if the primary isn't already in the database or if
	 * any of the languages listed aren't valid.
	 *
	 * @since 2.0.0
	 *
	 * @uses Utilities::_language() to ensure $language is a Language object.
	 * @uses Translator::unlink_object_translation() if a translation is to be unset.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type    The type of the objects.
	 * @param int    $id      The ID of the primary object.
	 * @param array  $objects A list of objects to associate (id => language_id format).
	 *
	 * @param bool Wether or not the association could be done (false if aborted).
	 */
	public static function set_object_translations( $type, $id, $objects ) {
		global $wpdb;

		// Get the group ID for this object
		$group_id = static::_translation_group_id( $type, $id, false );

		// Also get the language for this object
		$the_language = static::get_object_language( $type, $id );

		// If none was found, abort
		if ( ! $group_id ) {
			return null;
		}

		// Start the query
		$query = "REPLACE INTO $wpdb->nl_translations (group_id, object_type, object_id, language_id) VALUES ";

		// Go through the $objects and handle accordingly
		$values = array();
		foreach ( $objects as $object_language => $object_id ) {
			// Ensure $language is a Language
			if ( ! static::_language( $object_language ) ) {
				return false; // Does not exist
			}

			// Skip if we're trying assign a translation for the object's language
			if ( $object_language->id == $the_language->id ) {
				continue;
			}

			// If $object_id isn't valid, assume we want to unlink it
			if ( $object_id <= 0 ) {
				static::unlink_object_translation( $type, $id, $object_language );
			} else {
				// Build the row data for the query
				$values[] = $wpdb->prepare( "(%d, %s, %d, %d)", $group_id, $type, $object_id, $object_language->id );
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
	 * @uses Utilities::_language() to ensure $language is a Language object.
	 *
	 * @see Translator::set_object_translations() for how it's done.
	 *
	 * @param string $type     The type of the objects.
	 * @param int    $id       The ID of the primary object.
	 * @param mixed  $language The language to add a translation for.
	 * @param int    $object   The object to add as the translation.
	 *
	 * @param bool Wether or not the association could be done.
	 */
	public static function set_object_translation( $type, $id, $language, $object ) {
		// Ensure $language is a Language
		if ( ! static::_language( $language ) ) {
			return false; // Does not exist
		}

		// Alias to set_object_translations method
		return static::set_object_translations( $type, $id, array( $language->id => $object ) );
	}

	/**
	 * Delete the association between two objects.
	 *
	 * This moves the object's translation in the target language to a new group.
	 * An entry of the matching object and it's language is preserved.
	 *
	 * @since 2.0.0
	 *
	 * @uses Utilities::_language() to ensure $language is a Language object.
	 * @uses Translator::_translation_group_id() to get the group ID for the object
	 *                                     as well a new one for it's sister.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type     The type of object.
	 * @param int    $id       The ID of the object.
	 * @param mixed  $language The language to remove the association for.
	 */
	public static function delete_object_translation( $type, $id, $language ) {
		global $wpdb;

		// Ensure $language is a Language
		if ( ! static::_language( $language ) ) {
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
				'group_id'    => $new_group_id,
			),
			array(
				'group_id'    => $group_id,
				'language_id' => $language->id
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
	 * @uses Utilities::_language() to ensure $language is a Language object.
	 * @uses Translator::get_object_translation() to get the post's translation.
	 *
	 * @param int   $post_id  The ID of the post.
	 * @param mixed $language Optional The desired language.
	 *
	 * @return string The translation's permalink.
	 */
	public static function get_permalink( $post_id, $language = null ) {
		// Ensure $language is a Language
		if ( ! static::_language( $language ) ) {
			// Doesn't exit; resort to original permalink
			return get_permalink( $post_id );
		}

		// Get the translation counterpart
		$translation_id = static::get_post_translation( $post_id, $language );

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
	 * @param mixed  $language  The slug of the language requested (defaults to current language).
	 *
	 * @return string The translated permalink.
	 */
	public static function translate_link( $path, $post_type = null, $language = null ) {
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
		return static::get_permalink( $post->ID, $language );
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
	 * @param mixed  $language_id The id of the language to get the count for.
	 * @param string $post_type   The post type to filter by.
	 * @param string $post_status The post status to filter by.
	 *
	 * @return int The number of posts found.
	 */
	public static function language_posts_count( $language_id, $post_type = null, $post_status = null ) {
		global $wpdb;

		$query = "
		SELECT COUNT(p.ID)
		FROM $wpdb->posts AS p
			LEFT JOIN $wpdb->nl_translations AS t
				ON p.ID = t.object_id AND t.object_type = 'post'
		";

		// Add language filter appropriately
		if ( $language_id ) {
			$query .= $wpdb->prepare( "WHERE t.language_id = %d", $language_id );
		} else {
			$query .= "WHERE t.language_id IS NULL";
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
