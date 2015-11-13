<?php
/**
 * nLingual Compatibilty Hooks
 *
 * @package nLingual
 * @subpackage Backwards_Compatibilty
 *
 * @since 2.0.0
 */

// =========================
// ! Language Splitting
// =========================

add_filter( 'option_blogname', 'nl_split_langs', 10, 1 );
add_filter( 'option_blogdescription', 'nl_split_langs', 10, 1 );
add_filter( 'the_title', 'nl_split_langs', 10, 1 );

// =========================
// ! Old Callbacks
// =========================

/**
 * Get the translation of the post in the current language.
 *
 * @since 2.0.0
 *
 * @param int $id The post ID.
 *
 * @return int The translation ID if found/applicable.
 */
function nLingual_get_curlang_version( $id ) {
	if ( !is_admin() ) {
		$id = nL_get_translation( $id );
	}
	return $id;
}

/**
 * Enables support for nLingual_localize_here_array hook.
 *
 * @since 2.0.0
 *
 * @param string   $url      The URL to filter.
 * @param array    $url_data The raw URL data array.
 * @param Language $language The language requested.
 *
 * @return string The filtered URL.
 */
function nLingual_localize_here_array( $url, $url_data, $language ) {
	// Check if callbacks exist for the old array filter
	if ( has_filter( 'nLingual_localize_here_array' ) ) {
		/**
		 * Filter the URL data.
		 *
		 * @since 2.0.0
		 *
		 * @param array  $url_data The parsed URL data.
		 * @param string $language The language slug.
		 */
		$url_data = apply_filters( 'nLingual_localize_here_array', $url_data, $language->slug );

		$url = Rewriter::build_url( $url_data );
	}

	return $url;
}