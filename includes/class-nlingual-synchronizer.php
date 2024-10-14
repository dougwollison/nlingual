<?php
/**
 * nLingual Synchronization Tool
 *
 * @package nLingual
 * @subpackage Helpers
 *
 * @internal
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Synchronizer System
 *
 * Internal-use utility kit for handling post
 * synchronization and cloning.
 *
 * @internal Used by the Backend.
 *
 * @since 2.0.0
 */
final class Synchronizer {
	// =========================
	// ! Properties
	// =========================

	/**
	 * The list of allowed post_fields for syncing.
	 *
	 * @since 2.8.0
	 *
	 * @var array
	 */
	protected static $allowed_post_fields = array(
		'sync' => array( 'post_author', 'post_date', 'post_status', 'post_parent', 'menu_order', 'post_password', 'comment_status' ),
		'clone' => array( 'post_content', 'post_author', 'post_date', 'post_parent', 'menu_order', 'post_password', 'comment_status' ),
	);

	// =========================
	// ! Utilities
	// =========================

	/**
	 * Get the whitelist of allowed post fields for a mode.
	 *
	 * @since 2.8.0
	 *
	 * @param string $context The requested mode to get the list for.
	 *
	 * @return array The requested whitelist.
	 */
	final public static function get_allowed_post_fields( $context ) {
		return self::$allowed_post_fields[ $context ];
	}

	/**
	 * Handle the post sync/clone rules for the Synchronizer.
	 *
	 * Ensures fields/terms/meta are present, and handle
	 * the aliases dictated by existing values.
	 *
	 * @uses Documenter::post_field_names() To get list of possible post_field options.
	 * @uses Synchronizer::get_allowed_post_fields() To get whitelist of post_field options.
	 *
	 * @since 2.8.6 Fixed post_fields whitelisting.
	 * @since 2.8.0 Added $context parameter, post_fields expanding/whitelisting.
	 * @since 2.1.0 Made sure all entries were array|bool, including
	 *              splitting up post_meta at the line breaks.
	 * @since 2.0.0
	 *
	 * @param array  $rules   The rules to prepare.
	 * @param string $context The context the rules are for ("sync" or "clone").
	 *
	 * @return array The prepared rules.
	 */
	private static function prepare_post_rules( array $rules, $context = 'sync' ) {
		// Ensure the rule sets are present
		$rules = wp_parse_args( $rules, array(
			'post_fields' => array(),
			'post_terms'  => array(),
			'post_meta'   => array(),
		) );

		// Ensure each is an array or boolean
		if ( ! is_array( $rules['post_fields'] ) && ! is_bool( $rules['post_fields'] ) ) {
			$rules['post_fields'] = (array) $rules['post_fields'];
		}
		if ( ! is_array( $rules['post_terms'] ) && ! is_bool( $rules['post_terms'] ) ) {
			$rules['post_terms'] = (array) $rules['post_terms'];
		}
		if ( ! is_array( $rules['post_meta'] ) && ! is_bool( $rules['post_meta'] ) ) {
			$rules['post_meta'] = preg_split( '/[\r\n]+/', trim( $rules['post_meta'] ), 0, PREG_SPLIT_NO_EMPTY );
		}

		// Auto-expand post_fields if true
		if ( $rules['post_fields'] === true ) {
			$rules['post_fields'] = array_keys( Documenter::post_field_names() );
		}

		// Whitelist the $post_fields list
		$rules['post_fields'] = array_intersect( $rules['post_fields'], self::get_allowed_post_fields( $context ) );

		// Handle the post_field aliases
		if ( in_array( 'post_content', $rules['post_fields'] ) ) {
			$rules['post_fields'][] = 'post_content_filtered';
			$rules['post_fields'][] = 'post_excerpt';
		}
		if ( in_array( 'post_date', $rules['post_fields'] ) ) {
			$rules['post_fields'][] = 'post_date_gmt';
			$rules['post_fields'][] = 'post_modified';
			$rules['post_fields'][] = 'post_modified_gmt';
		}
		if ( in_array( 'comment_status', $rules['post_fields'] ) ) {
			$rules['post_fields'][] = 'ping_status';
		}

		// Ensure lists are unique
		$rules['post_fields'] = array_unique( $rules['post_fields'] );
		if ( is_array( $rules['post_terms'] ) ) {
			$rules['post_terms'] = array_unique( $rules['post_terms'] );
		}
		if ( is_array( $rules['post_meta'] ) ) {
			$rules['post_meta'] = array_unique( $rules['post_meta'] );
		}

		return $rules;
	}

	// =========================
	// ! Object Synchronizing
	// =========================

	/**
	 * Copy desired post fields, meta data, and terms from the original to target.
	 *
	 * @since 2.9.2 Need to slash data for sync; blocks break otherwise.
	 * @since 2.8.7 Fix date sync to bypass date clearing on draft targets.
	 * @since 2.8.5 Fixed get_rules() call to specify post_type.
	 * @since 2.8.0 Moved post_fields=true expanding to prepare_post_rules().
	 * @since 2.6.0 Fixed typo preventing fields from being synced, also modified
	 *              all-meta handling to skip _edit_* metadata.
	 * @since 2.3.2 Fixed typo causing terms to be erased when trying to sync.
	 * @since 2.1.0 Fixed various bugs causing sync to fail, added filters for
	 *              each post field, term list, and meta value list,
	 *              moving post_parent localizing to System filter.
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::get_rules() to retrieve the synchronization rules.
	 * @uses Translator::get_object_language() to get the target's language.
	 * @uses Translator::get_object_translation() to get the original's parent's translation.
	 *
	 * @param int|\WP_Post $original The original post ID/object.
	 * @param int|\WP_Post $target   The target post ID/object.
	 * @param array        $rules    Optional. The rules to use for syncing.
	 *     @option array      "post_fields" A whitelist of fields to copy over.
	 *     @option bool|array "post_meta"   A whitelist of meta fields (TRUE for all).
	 *     @option bool|array "post_terms"  A whitelist of taxonomies (TRUE for all).
	 * @param string       $context  Optional. The context for preparing the rules ("sync" or "clone"),
	 *                              also dictates what rules are fetched if they aren't provided.
	 *
	 * @throws Exception If the requested posts aren't of the same type.
	 *
	 * @return bool TRUE if successful, FALSE if errors occurred.
	 */
	public static function sync_posts( $original, $target, $rules = null, $context = 'sync' ) {
		global $wpdb;

		// Get post objects if not already passed
		if ( ! is_a( $original, 'WP_Post' ) ) {
			$original = get_post( $original );
		}
		if ( ! is_a( $target, 'WP_Post' ) ) {
			$target = get_post( $target );
		}

		// Fail if either post is invalid
		if ( ! $original || ! $target ) {
			return false;
		}

		// Throw exception if the post types don't match
		if ( $original->post_type != $target->post_type ) {
			$message = esc_html( sprintf( 'The requested posts (%1$d & %2$d) cannot be synchronized because they are of different types.', $original->ID, $target->ID ) );
			throw new Exception( $message, NL_ERR_BADREQUEST );
		}

		// Load general sync rules by default
		if ( is_null( $rules ) ) {
			$rules = Registry::get_rules( $context, 'post_type', $original->post_type );

			/**
			 * Filter the post rules.
			 *
			 * @since 2.8.0 Now applies to nlingual_post_clone_rules too.
			 * @since 2.0.0
			 *
			 * @param array    $rules    The sync rules for this post's type.
			 * @param \WP_Post $original The post being synchronized from.
			 * @param \WP_Post $target   The post being synchronized to.
			 */
			$rules = apply_filters( "nlingual_post_{$context}_rules", $rules, $original, $target );
		}

		// Prepare the rules
		$rules = self::prepare_post_rules( $rules, $context );

		// Get the target's language
		$language = Translator::get_post_language( $target->ID );

		// Post Fields
		if ( isset( $rules['post_fields'] ) && $rules['post_fields'] ) {
			// Build the list of fields to change
			$changes = array();
			foreach ( $rules['post_fields'] as $field_name ) {
				$field_value = $original->$field_name;

				/**
				 * Filter the meta values for the translation.
				 *
				 * Namely for replacing with translated counterparts.
				 *
				 * @since 2.1.0
				 *
				 * @param string|int $field_value The field value.
				 * @param Language   $language    The language of the post this will be assigned to.
				 * @param int        $target_id   The ID of the post this will be assigned to.
				 * @param int        $original_id The ID of the post the value is from.
				 */
				$field_value = apply_filters( "nlingual_sync_post_field-{$field_name}", $field_value, $language, $target->ID, $original->ID );

				$changes[ $field_name ] = $field_value;
			}

			// Include edit_date to prevent date clearing on the target
			if ( isset( $changes['post_date_gmt'] )  ) {
				$changes['edit_date'] = $changes['post_date_gmt'];
			}

			// Apply the updates
			$changes[ 'ID' ] = $target->ID;
			wp_update_post( wp_slash( $changes ) );
		}

		// Post Terms
		if ( isset( $rules['post_terms'] ) && $rules['post_terms'] ) {
			// If TRUE, use all taxonomies for the post's type
			if ( $rules['post_terms'] === true ) {
				$rules['post_terms'] = get_object_taxonomies( $original, 'names' );
			}

			// Assign all the same terms
			foreach ( $rules['post_terms'] as $taxonomy ) {
				// If taxonomy is not currently registered, skip
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				// Get the terms of the original
				$term_ids = wp_get_object_terms( $original->ID, $taxonomy, array( 'fields' => 'ids' ) );

				/**
				 * Filter the meta values for the translation.
				 *
				 * Namely for replacing with translated counterparts.
				 *
				 * @since 2.1.0
				 *
				 * @param array    $term_ids    The list of terms.
				 * @param Language $language    The language of the post this will be assigned to.
				 * @param int      $target_id   The ID of the post this will be assigned to.
				 * @param int      $original_id The ID of the post the value is from.
				 */
				$term_ids = apply_filters( "nlingual_sync_post_terms-{$taxonomy}", $term_ids, $language, $target->ID, $original->ID );

				// Ensure they're integers
				$term_ids = array_map( 'intval', $term_ids );

				wp_set_object_terms( $target->ID, $term_ids, $taxonomy );
			}
		}

		// Meta Data
		if ( isset( $rules['post_meta'] ) && $rules['post_meta'] ) {
			// If TRUE or wildcard exists, get all possible meta_key values from the original
			if ( $rules['post_meta'] === true || in_array( '*', $rules['post_meta'] ) ) {
				$edit_like = $wpdb->esc_like( '_edit_' ) . '%';
				$rules['post_meta'] = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM $wpdb->postmeta WHERE post_id = %d AND meta_key NOT LIKE %s", $original->ID, $edit_like ) );
			}

			// Assign all the same meta values
			foreach ( $rules['post_meta'] as $meta_key ) {
				// Delete the target's value(s)
				delete_post_meta( $target->ID, $meta_key );

				// Get the original's value(s)
				$meta_values = get_post_meta( $original->ID, $meta_key );

				/**
				 * Filter the meta values for the translation.
				 *
				 * Namely for replacing with translated counterparts.
				 *
				 * @since 2.1.0
				 *
				 * @param array    $meta_values The values for the meta key (individual entries are unserialized).
				 * @param Language $language    The language of the post this will be assigned to.
				 * @param int      $target_id   The ID of the post this will be assigned to.
				 * @param int      $original_id The ID of the post the value is from.
				 */
				$meta_values = apply_filters( "nlingual_sync_post_meta-{$meta_key}", $meta_values, $language, $target->ID, $original->ID );

				// Re-add each value
				foreach ( $meta_values as $meta_value ) {
					add_post_meta( $target->ID, $meta_key, maybe_unserialize( $meta_value ) );
				}
			}
		}

		return true;
	}

	/**
	 * Call sync_posts() for the desired post and each of it's sisters.
	 *
	 * @since 2.0.0
	 *
	 * @uses Translator::get_object_translations() to get the posts sisters.
	 * @uses Synchronizer::sync_posts() to sync the post with each sister.
	 *
	 * @param int   $post_id  The ID of the post to synchronize with.
	 * @param array $skip_ids A blacklist of IDs to not sync with.
	 */
	public static function sync_post_with_sisters( $post_id, $skip_ids = array() ) {
		// Get the translations
		$translations = Translator::get_post_translations( $post_id );

		foreach ( $translations as $translation ) {
			if ( ! in_array( $translation, $skip_ids ) ) {
				self::sync_posts( $post_id, $translation );
			}
		}
	}

	// =========================
	// ! Translation Cloning
	// =========================

	/**
	 * Clone a post object for translation.
	 *
	 * All fields, meta data and terms are copied.
	 *
	 * @since 2.9.2 Need to slash data for cloning; blocks break otherwise.
	 * @since 2.9.0 Drop custom title support.
	 * @since 2.8.0 Copy only mandatory post fields, leave the rest to sync_posts().
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate/retrieve the desired language.
	 * @uses Registry::get() to retrieve the cloning rules.
	 * @uses Synchronizer::sync_posts() to handle meta data and term copying.
	 * @uses Translator::get_post_translation() to get the parent post's translation.
	 * @uses Translator::set_post_language() to assign the language to the clone.
	 * @uses Translator::set_post_translation() to link the clone to the original.
	 *
	 * @param int|\WP_Post  $post     The ID/object of the post to clone.
	 * @param int|Language  $language The language to assign the clone to.
	 *
	 * @throws Exception If the post specified does not exist.
	 * @throws Exception If the language specified does not exist.
	 *
	 * @return \WP_Post|false The cloned post or false on failure.
	 */
	public static function clone_post( $post, $language ) {
		// Validate $post if an ID
		if ( ! is_a( $post, 'WP_Post' ) ) {
			$requested_post = $post;
			$post = get_post( $post );
			if ( ! $post ) {
				if ( is_object( $requested_post ) ) {
					$requested_post = $requested_post->ID ?? '[object]';
				} else if ( ! is_scalar( $requested_post ) ) {
					$requested_post = '[non-scalar value]';
				}
				throw new Exception( 'The post specified does not exist: ' . esc_html( $requested_post ), NL_ERR_NOTFOUND );
			}
		}

		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			if ( is_object( $language ) ) {
				$language = $language->id ?? '[object]';
			} else if ( ! is_scalar( $language ) ) {
				$language = '[non-scalar value]';
			}
			throw new Exception( 'The language specified does not exist: ' . esc_html( $language ), NL_ERR_NOTFOUND );
		}

		// Since this is a draft, prefix the title with a note about translation being needed

		/* translators: %1$s = The name of the language, %2$s = The post title */
		$title = _f( '[Needs %1$s Translation]: %2$s', 'nlingual', $language->system_name, $post->post_title );

		// And default the post_name to the original + the language slug
		$slug = $post->post_name . '-' . $language->slug;

		// Create the new post
		$post_data = array(
			'post_title'     => $title,
			'post_name'      => $slug,
			'post_status'    => 'draft',
			'post_type'      => $post->post_type,
			'post_mime_type' => $post->post_mime_type,
		);

		// Insert and get the ID
		$translation = wp_insert_post( wp_slash( $post_data ) );

		// Check if it worked
		if ( ! $translation ) {
			return false;
		}

		// Get the post object
		$translation = get_post( $translation );

		// Set the language of the translation
		Translator::set_post_language( $translation->ID, $language );

		// Synchronize the two posts
		self::sync_posts( $post->ID, $translation->ID, null, 'clone' );

		// Now associate it with the original
		Translator::set_post_translation( $post->ID, $language, $translation->ID );

		return $translation;
	}
}
