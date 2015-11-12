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

function nLingual_get_curlang_version( $id ) {
	if ( !is_admin() ) {
		$id = nL_get_translation( $id );
	}
	return $id;
}