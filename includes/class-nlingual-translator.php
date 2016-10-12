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
final class Translator {
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
	 * @param int $object_type  The type of the object to flush from the cache.
	 * @param int $object_id    The ID of the object to flush from the cache.
	 * @param int $group_id     Optional The ID of the group (will get using type/id if not provided).
	 * @param int $new_group_id Optional The ID of the new group.
	 */
	private static function flush_cache( $object_type, $object_id, $group_id = null, $new_group_id = null ) {
		// Get the group if not provided
		if ( is_null( $group_id ) ) {
			$group_id = self::get_group_id( $object_type, $object_id );
		}

		// Delete the cached group data
		if ( $group_id ) {
			wp_cache_delete( $group_id, 'nlingual:group' );
		}

		// If a new group is provided, update it, otherwise delete it
		if ( ! is_null( $new_group_id ) ) {
			wp_cache_set( "{$object_type}/{$object_id}", $new_group_id, 'nlingual:group_id' );
		} else {
			wp_cache_delete( "$object_type/$object_id", 'nlingual:group_id' );
		}
	}

	/**
	 * Get a new group ID from the database.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @return int The new group ID.
	 */
	private static function new_group_id() {
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
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $object_type The type of object.
	 * @param int    $object_id   The ID of the object.
	 *
	 * @return int The existing or new group ID.
	 */
	private static function get_group_id( $object_type, $object_id ) {
		global $wpdb;

		$cache_id = "{$object_type}/{$object_id}";

		// Check if it's cached, return if so
		$cached = wp_cache_get( $cache_id, 'nlingual:group_id', false, $found );
		if ( $found ) {
			return $cached;
		}

		// Attempt to retrieve the group ID for the object if present
		$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d LIMIT 1", $object_type, $object_id ) );

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
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $object_type The type of object.
	 * @param int    $object_id   The ID of the object.
	 *
	 * @return bool|array The list of entries in the group, with object_id and language_id indexes (false if not found).
	 */
	private static function get_group( $object_type, $object_id ) {
		global $wpdb;

		$cache_id = "{$object_type}/{$object_id}";

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
			$query = $wpdb->prepare( "SELECT * FROM $wpdb->nl_translations WHERE group_id = (SELECT group_id FROM $wpdb->nl_translations WHERE object_type = %s AND object_id = %d)", $object_type, $object_id );
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
	 * @since 2.1.0 Added $true_value argument.
	 * @since 2.0.0
	 *
	 * @uses Translator::get_translation_group() to retrieve the object's translation group.
	 * @uses Registry::languages() to retrieve the Language object by ID.
	 * @uses Registry::get() to get the language_is_required option.
	 *
	 * @param string $object_type The type of object.
	 * @param int    $object_id   The ID of the object.
	 * @param bool   $true_value  Wether or not to bypass language_is_requried fallback.
	 *
	 * @return bool|Language The language of the object (false if not found).
	 */
	public static function get_object_language( $object_type, $object_id, $true_value = false ) {
		// Get the translation group for the object
		$group = self::get_group( $object_type, $object_id );

		// If translation group exists, get the language
		if ( $group ) {
			return Registry::get_language( $group['language_by_object'][ $object_id ] );
		}
		// If language was not found, and language_is_required is enabled, use default unless bypassing
		elseif ( Registry::get( 'language_is_required' ) && ! $true_value ) {
			return Registry::default_language();
		}

		return false;
	}

	/**
	 * Set an object's language.
	 *
	 * @since 2.1.0 Added use of new $true_value argument on get_object_language().
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Translator::delete_object_language() to allow for replacement.
	 * @uses validate_language() to ensure $language is a Language object.
	 * @uses Translator::get_group_id() to get the object's group ID.
	 * @uses Translator::new_group_id() to generate a new group ID if needed.
	 *
	 * @param string $object_type The type of object.
	 * @param int    $object_id   The ID of the object.
	 * @param mixed  $language    The language of the object.
	 *
	 * @throws Exception If the language specified does not exist.
	 *
	 * @return bool If the assignment worked or not.
	 */
	public static function set_object_language( $object_type, $object_id, $language ) {
		global $wpdb;

		// Redirect to delete_object_language() if $language is false-ish
		if ( ! $language ) {
			return self::delete_object_language( $object_type, $object_id );
		}

		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Check the old language (bypass language_is_required fallback)
		$old_language = self::get_object_language( $object_type, $object_id, 'true_value' );
		// If it has one and is the same, abort
		if ( $old_language && $old_language->id == $language->id ) {
			return true;
		}

		// Get a group ID for the object, a new on if necessary
		$group_id = self::get_group_id( $object_type, $object_id );

		// Save the old group ID
		$old_group_id = $group_id;

		if ( $group_id ) {
			// Get an object of that type for the language in this group exists
			$current_object = $wpdb->get_var( $wpdb->prepare( "
				SELECT object_id FROM $wpdb->nl_translations
				WHERE group_id = %d AND language_id = %d AND object_type = %s
			", $group_id, $language->id, $object_type ) );

			// Check if it exists (and isn't the same object; it shouldn't)
			if ( $current_object && $current_object != $object_id ) {
				// Get a new group ID if so
				$group_id = self::new_group_id();
			}
		} else {
			$group_id = self::new_group_id();
		}

		// Insert a new one
		$wpdb->replace( $wpdb->nl_translations, array(
			'group_id'    => $group_id,
			'object_type' => $object_type,
			'object_id'   => $object_id,
			'language_id' => $language->id,
		), array( '%d', '%s', '%d', '%d' ) );

		// Flush and update the relevant cache
		self::flush_cache( $object_type, $object_id, $old_group_id, $group_id );

		return true;
	}

	/**
	 * Delete an object's language.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $object_type The type of object.
	 * @param int    $object_id   The ID of the object.
	 *
	 * @return bool If the deletion worked or not.
	 */
	public static function delete_object_language( $object_type, $object_id ) {
		global $wpdb;

		// Insert a new one
		$wpdb->delete( $wpdb->nl_translations, array(
			'object_type' => $object_type,
			'object_id'   => $object_id,
		) );

		// Flush the relevant cache
		self::flush_cache( $object_type, $object_id );

		return true;
	}

	/**
	 * Deletes all translation entries for the specified language.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses validate_language() to validate the language and get the Language object.
	 *
	 * @param mixed  $language The language to remove the association for.
	 *
	 * @throws Exception If the language specified does not exist.
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
	 * @param string $object_type The type of object.
	 * @param int    $object_id   The ID of the object.
	 * @param mixed  $language    Optional. The language to retrieve for (defaults to current).
	 * @param bool   $return_self Optional. Return $object_id if nothing is found (default false).
	 *
	 * @return bool|int The id of the translation.
	 */
	public static function get_object_translation( $object_type, $object_id, $language = null, $return_self = false ) {
		// Ensure $language is a Language, defaulting to current
		if ( ! validate_language( $language, true ) ) {
			// Trigger warning error if not found, return false
			trigger_error( '[nLingual] Language does not exist: ' . maybe_serialize( $language ) . '; cannot get translation.', E_USER_WARNING );
			return false;
		}

		// Get the translation group for the object
		$group = self::get_group( $object_type, $object_id );

		// If no translation group exists, or the language entry doesn't,
		// return false or the original object id
		if ( ! $group || ! isset( $group['object_by_language'][ $language->id ] ) ) {
			return $return_self ? $object_id : false;
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
	 * @param string $object_type  The type of object.
	 * @param int    $object_id    The ID of the object.
	 * @param bool   $include_self Optional. Include this object in the list (default false).
	 *
	 * @return array An associative array of objects in language_id => object_id format.
	 */
	public static function get_object_translations( $object_type, $object_id, $include_self = false ) {
		// Get the translation group for the object
		$group = self::get_group( $object_type, $object_id );

		// If no translation group exists, return empty array
		if ( ! $group ) {
			return array();
		}

		// Use the by_language index as the translations list
		$translations = $group['object_by_language'];

		// Remove the target posts ID if desired
		if ( ! $include_self ) {
			$language_id = array_search( $object_id, $translations );
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
	 * @uses validate_language() to validate the language and get the Language object.
	 * @uses Translator::new_group_id() to get the existing group ID.
	 * @uses Translator::get_object_language() to get the current language of the object.
	 * @uses Translator::delete_object_translation() if a translation is to be unset.
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $object_type The type of the objects.
	 * @param int    $object_id   The ID of the primary object.
	 * @param array  $objects     A list of objects to associate (id => language_id format).
	 *
	 * @throws Exception If the language specified does not exist.
	 *
	 * @param bool Wether or not the association could be done (false if aborted).
	 */
	public static function set_object_translations( $object_type, $object_id, $translations ) {
		global $wpdb;

		// Get the group ID for this object
		$group_id = self::get_group_id( $object_type, $object_id );

		// If none was found, abort
		if ( ! $group_id ) {
			return null;
		}

		// Also get the language for this object
		$the_language = self::get_object_language( $object_type, $object_id );

		// Start the query
		$query = "REPLACE INTO $wpdb->nl_translations (group_id, object_type, object_id, language_id) VALUES ";

		// Go through the $objects and handle accordingly
		$values = array();
		foreach ( $translations as $language => $translation_id ) {
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
			if ( intval( $translation_id ) <= 0 ) {
				self::delete_object_translation( $object_type, $object_id, $language );
			} else {
				// Build the row data for the query
				$values[] = $wpdb->prepare( "(%d, %s, %d, %d)", $group_id, $object_type, $translation_id, $language->id );
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
		self::flush_cache( $object_type, $object_id, $group_id );

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
	 * @param string $object_type The type of the objects.
	 * @param int    $object_id   The ID of the primary object.
	 * @param mixed  $language    The language to add a translation for.
	 * @param int    $object      The object to add as the translation.
	 *
	 * @throws Exception If the language specified does not exist.
	 *
	 * @param bool Wether or not the association could be done.
	 */
	public static function set_object_translation( $object_type, $object_id, $language, $object ) {
		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Alias to set_object_translations method
		return self::set_object_translations( $object_type, $object_id, array( $language->id => $object ) );
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
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @param string $object_type The type of object.
	 * @param int    $object_id   The ID of the object.
	 * @param mixed  $language    The language to remove the association for.
	 *
	 * @throws Exception If the language specified does not exist.
	 *
	 * @return bool Wether or not a deletion was performed (false = nothing to delete).
	 */
	public static function delete_object_translation( $object_type, $object_id, $language ) {
		global $wpdb;

		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Get the group ID for this object
		$group_id = self::get_group_id( $object_type, $object_id );

		// If none was found, abort
		if ( ! $group_id ) {
			return false;
		}

		// Get a new group ID for the sister object
		$new_group_id = self::new_group_id();

		// Update the group ID for the translation
		$wpdb->update(
			$wpdb->nl_translations,
			array(
				'group_id'    => $new_group_id,
			),
			array(
				'group_id'    => $group_id,
				'language_id' => $language->id,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);

		// Flush and update the relevant cache
		self::flush_cache( $object_type, $object_id, $group_id, $new_group_id );

		return true;
	}

	// =========================
	// ! Overloading
	// =========================

	/**
	 * Overload to the various _object_ methods.
	 *
	 * @since 2.2.0 Added NOTICE error trigger when calling invalid get_* method.
	 * @since 2.0.0
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $args The list of arguments for the method.
	 *
	 * @throws Exception If the method alias cannot be determined.
	 * @throws Exception If a post is called belonging to an unsupported post type.
	 *
	 * @return mixed The result of the target method.
	 */
	public static function __callStatic( $name, $args ) {
		// Check if $name matches an existing method with $object_type instead of object
		if ( preg_match( '/^(get|set|delete)_(\w+?)_(language|translations?)$/', $name, $matches ) ) {
			// Get the parts
			list( , $action, $object_type, $meta ) = $matches;

			// Build target method name
			$method = $action . '_object_' . $meta;

			// If the method does not exist, throw exception
			if ( ! method_exists( __CLASS__, $method ) ) {
				/* Translators: %s = The full name of the method being called. (Low priority translation) */
				throw new Exception( _f( 'Call to unrecognized method alias %s', 'nlingual', __CLASS__ . '::' . $name . '()' ), NL_ERR_UNSUPPORTED );
			}

			// Add the $object_type argument
			array_unshift( $args, $object_type );

			// If the second argument ($object_id in all cases) is an object, get the ID appropriately if possible.
			$object_id = $args[1];
			if ( is_object( $object_id ) ) {
				// Get the property list, find the ID field and get it
				$id_fields = array( 'id', 'ID', "{$object_type}_id", "{$object_type}_ID" );
				foreach ( $id_fields as $id_field ) {
					if ( property_exists( $object_id, $id_field ) ) {
						$object_id = $object_id->$id_field;
						break;
					}
				}
				// Update it in the arguments list
				$args[1] = $object_id;
			}

			// If it's a post and we can get the type, check if it's supported
			if ( $object_type == 'post' && ( $post_type = get_post_type( $object_id ) )
			&& ! Registry::is_post_type_supported( $post_type ) ) {
				// If this was the set method, throw exception
				if ( $action == 'set' ) {
					/* Translators: %d = The ID number of the object. */
					throw new Exception( _f( 'The requested post (ID: %d) does not belong to a supported post type.', 'nlingual', $object_id ), NL_ERR_UNSUPPORTED );
				}
				// Otherwise, return false
				else {
					/* Translators: %d = The ID number of the object. */
					trigger_error( '[nLingual] ' . _f( 'The requested post (ID: %d) does not belong to a supported post type.', 'nlingual', $object_id ), E_USER_NOTICE );
					return false;
				}
			}

			return call_user_func_array( array( __CLASS__, $method ), $args );
		}

		return null;
	}
}
