<?php
/**
 * nLingual Shared Filters
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual;

trait Shared_Filters {
	/**
	 * Replace a post ID with it's translation for the current language.
	 *
	 * @since 2.0.0
	 *
	 * @uses Registry::current_language() to get the current language.
	 * @uses Translator::get_object_translation() to get the translated post ID.
	 *
	 * @param int|string $post_id The post ID to be replaced.
	 *
	 * @return int The ID of the translation.
	 */
	public static function current_language_post( $post_id ) {
		$current_language = Registry::current_language();

		$post_id = Translator::get_post_translation( $post_id, $current_language, true );

		return $post_id;
	}
}
