<?php
/**
 * nLingual Shared Filters
 *
 * @package nLingual
 * @subpackage Utilities
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * Shared filters for Handler-based classes.
 *
 * Just a collection of methods that could be used
 * as hook methods by multiple Handlers.
 *
 * @package nLingual
 * @subpackage Utilities
 *
 * @internal
 *
 * @since 2.0.0
 */

trait Shared_Filters {
	/**
	 * Replace a post ID with it's translation for the current language.
	 *
	 * @since 2.0.0
	 *
	 * @api
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
