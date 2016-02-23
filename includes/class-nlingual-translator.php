<?php
/**
 * nLingual Translation API
 *
 * @package nLingual
 * @subpackage Tools
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Translator System
 *
 * A toolkit for accessing and managing the language
 * and translations of objects.
 *
 * @api
 *
 * @since 2.0.0
 *
 * @method bool|Language get_*_language() get an object's language.
 * @method bool          set_*_language() set an object's language.
 * @method bool          delete_*_language() delete an object's language.
 * @method bool|int      get_*_translation() get an object's translation.
 * @method bool          set_*_translation() set an object's translation.
 * @method bool          delete_*_translation() delete an object's translation.
 * @method bool|array    get_*_translations() get an object's translations.
 * @method bool          set_*_translations() set an object's translations.
 */

class Translator {
	// =========================
	// ! Utilities
	// =========================

	/**
	 * Flush the cache for the relevant group and object, optionally updating the group.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @param int $type      The type of the object to flush from the cache.
	 * @param int $id        The ID of the object to flush from the cache.
	 * @param int $group     Optional The ID of the group (will get using type/id if not provided).
	 * @param int $new_group Optional The ID of the new group.
	 */
	protected static function flush_cache( $type, $id, $group = null, $new_group = null ) {
		// Get the group if not provided
		if ( is_null( $group ) ) {
			$group = static::get_group( $type, $id );
		}

		// Delete the cached group data
		wp_cache_delete( $group, 'nlingual:group' );

		// If a new group is provided, update it, otherwise delete it
		if ( ! is_null( $new_group ) ) {
			wp_cache_set( "{$type}/{$id}", $new_group, 'nlingual:group_id' );
		} else {
			wp_cache_delete( "$type/$id", 'nlingual:group_id' );
		}
	}

	/**
	 * Get a new group ID from the database.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @return int The new group ID.
	 */
	protected static function new_group_id() {
		global $wpdb;

		$group_id = $wpdb->get_var( "SELECT MAX(group_id) FROM $wpdb->nl_translations" );
		$group_id = intval( $group_id ) + 1;

		return $group_id;
	}

	/**
	 * Get a translation group ID for an object.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 *
	 * @return int The existing or new group ID.
	 */
	protected static function get_group_id( $type, $id ) {
		global $wpdb;

		$cache_id = "{$type}/{$id}";

		// Check if it's cached, return if so
		$cached = wp_cache_get( $cache_id, 'nlingual:group_id', false, $found );
		if ( $found ) {
			return $cached;
		}

		// Attempt to retrieve the group ID for the object if present
		$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d LIMIT 1", $type, $id ) );

		// Add it to the cache
		wp_cache_set( $cache_id, $group_id, 'nlingual:group_id' );

		return $group_id;
	}

	/**
	 * Get the translation group an object is in.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 *
	 * @return bool|array The list of entries in the group, with object_id and language_id indexes (false if not found).
	 */
	protected static function get_group( $type, $id ) {
		global $wpdb;

		$cache_id = "{$type}/{$id}";

		// Check if the group id has already been determined
		$group_id = wp_cache_get( $cache_id, 'nlingual:group_id', false, $group_id_found );
		if ( $group_id_found && ! $group_id ) {
			// If it was determined to have no group id, return false.
			return false;
		}

		// If there was a group ID, check if it's cached, return if so
		if ( $group_id ) {
			$cached = wp_cache_get( $group_id, 'nlingual:group', false, $group_found );
			if ( $group_found ) {
				return $cached;
			}
		}

		// Build the query based on available information
		if ( $group_id_found ) {
			// Group ID already known, query directly
			$query = $wpdb->prepare( "SELECT * FROM $wpdb->nl_translations WHERE group_id = %d", $group_id );
		} else {
			// Group ID unknown, use nested query
			$query = $wpdb->prepare( "SELECT * FROM $wpdb->nl_translations WHERE group_id = (SELECT group_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d)", $type, $id );
		}

		// Get the results
		$group = false;
		if ( $result = $wpdb->get_results( $query, ARRAY_A ) ) {
			// Get the group ID
			$group_id = $result[0]['group_id'];

			// Build the group index
			$group = array();
			foreach ( $result as $row ) {
				$group['language_by_object'][ $row['object_id'] ] = $row['language_id'];
				$group['object_by_language'][ $row['language_id'] ] = $row['object_id'];

				// Cache the group ID for each object
				wp_cache_set( "{$row['object_type']}/{$row['object_id']}", $group_id, 'nlingual:group_id' );
			}
		}

		// Cache the group's data
		wp_cache_set( $group_id, $group, 'nlingual:group' );

		return $group;
	}

	// =========================
	// ! Language Handling
	// =========================

	/**
	 * Get an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::get_translation_gorup() to retrieve the object's translation group.
	 * @uses Registry::languages() to retrieve the Language object by ID.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 *
	 * @return bool|Language The language of the object (false if not found).
	 */
	public static function get_object_language( $type, $id ) {
		// Get the translation group for the object
		$group = static::get_group( $type, $id );

		// If no translation group exists, return false
		if ( ! $group ) {
			return false;
		}

		return Registry::get_language( $group['language_by_object'][ $id ] );
	}

	/**
	 * Set an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Translator::delete_object_language() to allow for replacement.
	 * @uses validate_language() to ensure $language is a Language object.
	 * @uses Translator::get_group_id() to get the object's group ID.
	 * @uses Translator::new_group_id() to generate a new group ID if needed.
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
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Check the old language
		$old_language = static::get_object_language( $type, $id );
		// If it has one and is the same, abort
		if ( $old_language && $old_language->id == $language->id ) {
			return true;
		}

		// Get a group ID for the object, a new on if necessary
		$group_id = static::get_group_id( $type, $id );

		if ( $group_id ) {
			// Get an object of that type for the language in this group exists
			$current_object = $wpdb->get_var( $wpdb->prepare( "
				SELECT object_id FROM $wpdb->nl_translations
				WHERE group_id = %d AND language_id = %d AND object_type = %s
			", $group_id, $language->id, $type ) );

			// Check if it exists (and isn't the same object; it shouldn't)
			if ( $current_object && $current_object != $id ) {
				// Get a new group ID if so
				$new_group_id = static::new_group_id();
			} else {
				$new_group_id = $group_id;
			}
		} else {
			$new_group_id = static::new_group_id();
		}

		// Insert a new one
		$wpdb->replace( $wpdb->nl_translations, array(
			'group_id'    => $new_group_id,
			'object_type' => $type,
			'object_id'   => $id,
			'language_id' => $language->id,
		), array( '%d', '%s', '%d', '%d' ) );

		// Flush and update the relevant cache
		static::flush_cache( $type, $id, $group_id, $new_group_id );

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

		// Flush the relevant cache
		static::flush_cache( $type, $id );

		return true;
	}

	/**
	 * Deletes all translation entries for the specified language.
	 *
	 * @since 2.0.0
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param mixed  $language The language to remove the association for.
	 */
	public static function delete_language( $language ) {
		global $wpdb;

		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		$wpdb->delete(
			$wpdb->nl_translations,
			array(
				'language_id' => $language->id,
			)
		);
	}

	// =========================
	// ! Translation Handling
	// =========================

	/**
	 * Get a translation for an object.
	 *
	 * @since 2.0.0
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 * @uses Translator::get_group() to get the object's translation group.
	 *
	 * @param string $type        The type of object.
	 * @param int    $id          The ID of the object.
	 * @param mixed  $language    Optional. The language to retrieve for (defaults to current).
	 * @param bool   $return_self Optional. Return $id if nothing is found (default false).
	 *
	 * @return bool|int The id of the translation.
	 */
	public static function get_object_translation( $type, $id, $language = null, $return_self = false ) {
		// Ensure $language is a Language, defaulting to current
		if ( ! validate_language( $language, true ) ) {
			// Throw exception if not found
			throw new Exception( 'The language requested does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Get the translation group for the object
		$group = static::get_group( $type, $id );

		// If no translation group exists, return false
		if ( ! $group ) {
			return $return_self ? $id : false;
		}

		return $group['object_by_language'][ $language->id ];
	}

	/**
	 * Get all translations for an object.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::get_group() to get the object's translation group.
	 *
	 * @param string $type         The type of object.
	 * @param int    $id           The ID of the object.
	 * @param bool   $include_self Optional. Include this object in the list (default false).
	 *
	 * @return array An associative array of objects in language_id => object_id format.
	 */
	public static function get_object_translations( $type, $id, $include_self = false ) {
		// Get the translation group for the object
		$group = static::get_group( $type, $id );

		// If no translation group exists, return empty array
		if ( ! $group ) {
			return array();
		}

		// Use the by_language index as the translations list
		$translations = $group['object_by_language'];

		// Remove the target posts ID if desired
		if ( ! $include_self ) {
			$language_id = array_search( $id, $translations );
			unset( $translations[ $language_id ] );
		}

		return $translations;
	}

	/**
	 * Simply check wether or not an object has translations.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::get_group() to get the object's translation group.
	 *
	 * @param string $type The type of object.
	 * @param int    $id   The ID of the object.
	 *
	 * @return bool Wether or not the object has translations assigned.
	 */
	public static function does_object_have_translations( $type, $id ) {
		// Get the translation group for the object
		$group = static::get_group( $type, $id );

		// If no translation group exists, return false
		if ( ! $group ) {
			return false;
		}

		// Return wether or not there are multiple objects in the group
		return count( $group['object_by_language'] ) > 1;
	}

	/**
	 * Set the translations for an object in 1 or more languages.
	 *
	 * Will fail if the primary isn't already in the database or if
	 * any of the languages listed aren't valid.
	 *
	 * @since 2.0.0
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 * @uses Translator::new_group_id() to get the existing group ID.
	 * @uses Translator::get_object_language() to get the current language of the object.
	 * @uses Translator::delete_object_translation() if a translation is to be unset.
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
		$group_id = static::get_group_id( $type, $id );

		// If none was found, abort
		if ( ! $group_id ) {
			return null;
		}

		// Also get the language for this object
		$the_language = static::get_object_language( $type, $id );

		// Start the query
		$query = "REPLACE INTO $wpdb->nl_translations (group_id, object_type, object_id, language_id) VALUES ";

		// Go through the $objects and handle accordingly
		$values = array();
		foreach ( $objects as $language => $object_id ) {
			// Ensure $language is a Language
			if ( ! validate_language( $language ) ) {
				// Throw exception if not found
				throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
			}

			// Skip if we're trying assign a translation for the object's language
			if ( $language->id == $the_language->id ) {
				continue;
			}

			// If $object_id isn't valid, assume we want to unlink it
			if ( intval( $object_id ) <= 0 ) {
				static::delete_object_translation( $type, $id, $language );
			} else {
				// Build the row data for the query
				$values[] = $wpdb->prepare( "(%d, %s, %d, %d)", $group_id, $type, $object_id, $language->id );
			}
		}

		// Check if we have values to update
		if ( $values ) {
			// Add the values to the query
			$query .= implode( ',', $values );

			// Run the query
			$wpdb->query( $query );
		}

		// Flush the relevant cache
		static::flush_cache( $type, $id, $group_id );

		return true;
	}

	/**
	 * Set a translation for an object in a specific language.
	 *
	 * @since 2.0.0
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 * @uses Translator::set_object_translations() to handle the details.
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
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Alias to set_object_translations method
		return static::set_object_translations( $type, $id, array( $language->id => $object ) );
	}

	/**
	 * Delete the translation association between two objects.
	 *
	 * This moves the object's translation in the target language to a new group.
	 * An entry of the matching object and it's language is preserved.
	 *
	 * @since 2.0.0
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 * @uses Translator::new_group_id() to get the group ID for the object
	 *                                     as well a new one for it's sister.
	 *
	 * @global wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $type     The type of object.
	 * @param int    $id       The ID of the object.
	 * @param mixed  $language The language to remove the association for.
	 *
	 * @return bool Wether or not a deletion was performed (false = nothing to delete).
	 */
	public static function delete_object_translation( $type, $id, $language ) {
		global $wpdb;

		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Get the group ID for this object
		$group_id = static::get_group_id( $type, $id );

		// If none was found, abort
		if ( ! $group_id ) {
			return false;
		}

		// Get a new group ID for the sister object
		$new_group_id = static::new_group_id();

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

		// Flush and update the relevant cache
		static::flush_cache( $type, $id, $group_id, $new_group_id );

		return true;
	}

	// =========================
	// ! URL Translation
	// =========================

	/**
	 * Get the permalink for a post in the desired language.
	 *
	 * @since 2.0.0
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 * @uses Translator::get_object_translation() to get the post's translation.
	 *
	 * @param int   $post_id  The ID of the post.
	 * @param mixed $language Optional. The desired language (defaults to current).
	 *
	 * @return string The translation's permalink.
	 */
	public static function get_permalink( $post_id, $language = null ) {
		// Ensure $language is a Language, defaulting to current
		if ( ! validate_language( $language, true ) ) {
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
		if ( preg_match( '/^(get|set|delete|does)_(\w+?)_((?:have_)?(?:language|translations?))$/', $name, $matches ) ) {
			// Get the parts
			list(, $action, $type, $meta ) = $matches;

			// Build target method name
			$method = $action . '_object_' . $meta;

			// If the method does not exist, throw an error
			if ( ! method_exists( __CLASS__, $method ) ) {
				throw new Exception( sprintf( 'Call to unrecognized method alias %s::%s()', __CLASS__, $name ), NL_ERR_UNSUPPORTED );
			}

			// Add the $type argument
			array_unshift( $args, $type );

			// If the second argument ($id in all cases) is an object, get the ID appropriately if possible.
			$id = $args[1];
			if ( is_object( $id ) ) {
				switch ( $type ) {
					case 'post' :
					case 'user' :
						$id = $id->ID;
						break;
					case 'term' :
						$id = $id->term_id;
						break;
				}
				$args[1] = $id;
			}

			return call_user_func_array( array( __CLASS__, $method ), $args );
		}

		return null;
	}
}