<?php
// ==================== //
// QuickStart Hooks     //
// ==================== //

if ( class_exists( 'QuickStart' ) ) {
	// Add hooks to support the index page feature
	if ( current_theme_supports( 'quickstart-index_page' ) ) {
		add_filter('qs_helper_get_index', 'nL_get_translation');

		function nL_qs_index_localize( $link, $lang ) {
			if ( ! is_post_type_archive() ) return $link;

			$post_type = get_query_var( 'post_type' );
			if ( $index = get_option( "page_for_{$post_type}_posts" ) ){
				$index = nL_get_translation( $index, $lang );
				$link = get_permalink( $index );
				if ( $_SERVER['QUERY_STRING'] ) {
					$link .= '?'.$_SERVER['QUERY_STRING'];
				}
			}
			return $link;
		}

		add_filter( 'nLingual_localize_here', 'nL_qs_index_localize', 10, 2 );
	}
}