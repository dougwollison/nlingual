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
	// ! Utilities
	// =========================

	/**
	 * Handle the post sync/clone rules for the Synchronizer.
	 *
	 * Ensures fields/terms/meta are present, and handle
	 * the aliases dictated by existing values.
	 *
	 * @param array $rules The rules to prepare.
	 *
	 * @return array The prepared rules.
	 */
	final private static function prepare_post_rules( array $rules ) {
		// Ensure the rule sets are present
		$rules = wp_parse_args( array(
			'post_fields' => array(),
			'post_terms'  => array(),
			'post_meta'   => array(),
		), $rules );

		// Handle the post_field aliases
		if ( in_array( 'post_date', $rules['post_fields'] ) ) {
			$rules['post_fields'][] = 'post_date_gmt';
			$rules['post_fields'][] = 'post_modified';
			$rules['post_fields'][] = 'post_modified_gmt';
		}
		if ( in_array( 'comment_status', $rules['post_fields'] ) ) {
			$rules['post_fields'][] = 'ping_status';
		}
		$rules['post_fields'] = array_unique( $rules['post_fields'] );

		return $rules;
	}

	// =========================
	// ! Object Synchronizing
	// =========================

	/**
	 * Copy desired post fields, meta data, and terms from the original to target.
	 *
	 * @since 2.0.0
	 *
	 * @global \wpdb $wpdb The database abstraction class instance.
	 *
	 * @uses Registry::get_rules() to retrieve the synchronization rules.
	 * @uses Translator::get_object_language() to get the target's language.
	 * @uses Translator::get_object_translation() to get the original's parent's translation.
	 *
	 * @param int|WP_Post $original The original post ID/object.
	 * @param int|WP_Post $target   The target post ID/object.
	 * @param array       $rules    Optional. The rules to use for syncing.
	 *		@option array      "post_fields" A whitelist of fields to copy over.
	 *		@option bool|array "post_meta"   A whitelist of meta fields (TRUE for all).
	 *		@option bool|array "post_terms"  A whitelist of taxonomies (TRUE for all).
	 *
	 * @return bool TRUE if successful, FALSE if errors occurred.
	 */
	final public static function sync_posts( $original, $target, $rules = null ) {
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

		// Load general sync rules by default
		if ( is_null( $rules ) ) {
			$rules = Registry::get_post_sync_rules( $original->post_type );
		}

		// Prepare the rules
		$rules = static::prepare_post_rules( $rules );

		/**
		 * Filter the post sync rules.
		 *
		 * @since 2.0.0
		 *
		 * @param array    $rules    The sync rules for this post's type.
		 * @param \WP_Post $original The post being synchronized from.
		 * @param \WP_Post $target   The post being synchronized to.
		 */
		$rules = apply_filters( 'nlingual_post_sync_rules', $rules, $original, $target );

		// Post Fields
		if ( isset( $rules['post_field'] ) && $rules['post_fields'] ) {
			// Build the list of fields to change
			$changes = array();
			foreach ( $rules['post_fields'] as $field ) {
				if ( $field == 'post_parent' ) {
					// In the case of the parent, try the parent's translation
					$language = Translator::get_post_language( $target->ID );
					$changes[ $field ] = Translator::get_post_translation( $original->$field, $language, true );
				} else {
					$changes[ $field ] = $original->$field;
				}
			}

			// Apply the updates
			$changes[ 'ID' ] = $target->ID;
			wp_update_post( $changes );
		}

		// Post Terms
		if ( isset( $rules['post_terms'] ) && $rules['post_terms'] ) {
			// Assign to all the same terms
			$taxonomies = get_object_taxonomies( $post->post_type );
			foreach ( $taxonomies as $taxonomy ) {
				// Skip if not a whilelisted taxonomy
				if ( is_array( $rules['post_terms'] ) && ! in_array( $taxonomy, $rules['post_terms'] ) ) {
					continue;
				}

				$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
				$term_ids = array();
				foreach ( $terms as $term ) {
					$term_ids[] = Translator::get_term_translation( $term->term_id, $language, true );
				}

				wp_set_object_terms( $translation->ID, $term_ids, $taxonomy );
			}
		}

		// Meta Data
		if ( isset( $rules['post_meta'] ) && $rules['post_meta'] ) {
			// Copy over all meta data found
			$meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post->ID ) );

			// Loop through and add to the translation
			foreach ( $meta_data as $meta ) {
				// Skip if not a whilelisted field
				if ( is_array( $rules['post_meta'] ) && ! in_array( $meta->meta_key, $rules['post_meta'] ) ) {
					continue;
				}

				add_post_meta( $translation->ID, $meta->meta_key, $meta->meta_value );
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
	final public static function sync_post_with_sisters( $post_id, $skip_ids = array() ) {
		// Get the translations
		$translations = Translator::get_post_translations( $post_id );

		foreach ( $translations as $translation ) {
			if ( ! in_array( $translation, $skip_ids ) ) {
				static::sync_posts( $post_id, $translation );
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
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate/retrieve the desired language.
	 * @uses Registry::get() to retrieve the cloning rules.
	 * @uses Synchronizer::sync_posts() to handle meta data and term copying.
	 * @uses Translator::get_post_translation() to get the parent post's translation.
	 * @uses Translator::set_post_language() to assign the language to the clone.
	 * @uses Translator::set_post_translation() to link the clone to the original.
	 *
	 * @param int|WP_Post  $post             The ID/object of the post to clone.
	 * @param int|Language $language          The language to assign the clone to.
	 * @param string       $title             Optional. The custom title for the clone.
	 * @param bool         $_title_is_default Optional. Was $title the default "Translate to..."?
	 *                                        Internal use only by Backend::ajax_new_translation()
	 *
	 * @return WP_Post|false The cloned post or false on failure.
	 */
	final public static function clone_post( $post, $language, $title = null, $_title_is_default = false ) {
		// Validate $post if an ID
		if ( ! is_a( $post, 'WP_Post' ) ) {
			$post = get_post( $post );
			if ( ! $post ) {
				throw new Exception( 'The post specified does not exist: ' . func_get_arg( 0 ), NL_ERR_NOTFOUND );
			}
		}

		// Ensure $language is a Language
		if ( ! validate_language( $language ) ) {
			// Throw exception if not found
			throw new Exception( 'The language specified does not exist: ' . maybe_serialize( $language ), NL_ERR_NOTFOUND );
		}

		// Default title if not passed
		if ( is_null( $title ) ) {
			/* Translators: %1$s = The name of the language, %2$s = The post title */
			$title = _f( 'Translate to %1$s: %2$s', 'nlingual', $language->system_name, $post->post_title );
			$_title_is_default = true;
		}

		// Create the new post
		$post_data = array(
			'post_author'    => $post->post_author,
			'post_date'      => $post->post_date,
			'post_content'   => $post->post_content,
			'post_title'     => $title,
			'post_excerpt'   => $post->post_excerpt,
			'post_status'    => 'draft',
			'comment_status' => $post->comment_status,
			'post_password'  => $post->post_password,
			'to_ping'        => $post->to_ping,
			'pinged'         => $post->pinged,
			'post_parent'    => Translator::get_post_translation( $post->post_parent, $language, true ),
			'menu_order'     => $post->menu_order,
			'post_type'      => $post->post_type,
			'comment_count'  => $post->comment_count
		);

		// If using default title, create a default post_name
		$post_data['post_name'] = $post->post_name . '-' . $language->slug;

		// Insert and get the ID
		$translation = wp_insert_post( $post_data );

		// Check if it worked
		if ( ! $translation ) {
			return false;
		}

		// Get the cloning rules
		$rules = Registry::get_post_clone_rules();

		// Prepare the rules
		$rules = static::prepare_post_rules( $rules );

		/**
		 * Filter the post sync rules.
		 *
		 * @since 2.0.0
		 *
		 * @param array    $rules    The clone rules for this post's type.
		 * @param \WP_Post $post     The post being cloned.
		 * @param Language $language The language being cloned for.
		 */
		$rules = apply_filters( 'nlingual_post_clone_rules', $rules, $post, $language );

		// Get the post object
		$translation = get_post( $translation );

		// Set the language of the translation and it's associate it with the original
		Translator::set_post_language( $translation->ID, $language );
		Translator::set_post_translation( $post->ID, $language, $translation->ID );

		// Synchronize the two posts
		static::sync_posts( $post->ID, $translation->ID, $rules );

		return $translation;
	}
}
