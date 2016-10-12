<?php
/**
 * nLingual AJAX Functionality
 *
 * @package nLingual
 * @subpackage Handlers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The AJAX Request Handler
 *
 * Add necessary wp_ajax_* hooks to fullfill any
 * custom AJAX requests.
 *
 * @internal Used by the System.
 *
 * @since 2.0.0
 */
final class AJAX extends Handler {
	// =========================
	// ! Hook Registration
	// =========================

	/**
	 * Register hooks.
	 *
	 * @since 2.0.0
	 */
	public static function register_hooks() {
		// Don't do anything if not doing an AJAX request
		if ( ! defined( 'DOING_AJAX' ) || DOING_AJAX !== true ) {
			return;
		}

		// Translation creation
		self::add_action( 'wp_ajax_nl_new_translation', 'new_translation', 10, 0 );
	}

	// =========================
	// ! Translation Creation
	// =========================

	/**
	 * Create a clone of the requested post in the requested language.
	 *
	 * @since 2.2.0 Add capabiltiy to set the post language before cloning.
	 * @since 2.0.0
	 *
	 * @uses Registry::languages() to validate the language requested.
	 * @uses Synchronizer::clone_post() to create the cloned post.
	 */
	public static function new_translation() {
		// Fail if no post/language id or title is passed
		if ( ! isset( $_REQUEST['post_id'] ) || ! isset( $_REQUEST['post_language_id'] )
		|| ! isset( $_REQUEST['translation_language_id'] ) || ! isset( $_REQUEST['title'] ) ) {
			return;
		}

		// Fail if post does not exist
		$post = get_post( $_REQUEST['post_id'] );
		if ( ! $post ) {
			return;
		}

		// Fail if post language does not exist
		$post_language = Registry::get_language( $_REQUEST['post_language_id'] );
		if ( ! $post_language ) {
			return;
		}

		// Fail if translation language does not exist
		$translation_language = Registry::get_language( $_REQUEST['translation_language_id'] );
		if ( ! $translation_language ) {
			return;
		}

		// Ensure the post is in the correct language
		Translator::set_post_language( $post, $post_language );

		// Create the translated clone
		$translation = Synchronizer::clone_post( $post, $translation_language, $_REQUEST['title'], isset( $_REQUEST['custom_title'] ) && $_REQUEST['custom_title'] );

		// Fail if error creating translation
		if ( ! $translation ) {
			return;
		}

		/**
		 * Fires when a translation clone is successfully created.
		 *
		 * @since 2.2.0
		 *
		 * @param WP_Post  $translation          The translation clone of the post.
		 * @param WP_Post  $post                 The original post.
		 * @param Language $translation_language The language the clone is for.
		 */
		do_action( 'nlingual_new_translation', $translation, $post, $translation_language );

		// Return the details
		echo json_encode( array(
			'id'    => $translation->ID,
			'title' => $translation->post_title,
		) );
		wp_die();
	}
}
