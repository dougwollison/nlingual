<?php
// ==================== //
// QuickStart Hooks     //
// ==================== //

function nL_qs_hooks_setup(){
	if ( function_exists( 'QuickStart' ) ) {
		// Add hooks to support the index page feature
		if ( current_theme_supports( 'quickstart-index_page' ) ) {
			// Setup translation of the get_index helper function
			add_filter( 'qs_helper_get_index', 'nL_get_translation' );
	
			// Add handling of custom index pages for URL localizing
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
		
		// Add language filtering to queries made by QuickStart (i.e. order manager and parent filtering)
		function nL_qs_admin_parse_query( $query ) {
			if ( ! is_admin() || ! isset( $query->query_vars['qs-context'] ) ) {
				return;
			}
		
			$qvar = nL_query_var();
			$pt = isset( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : null;
		
			if ( nL_post_type_supported( $pt )
			&& ! isset( $query->query_vars[ $qvar ] ) ) {
				$query->query_vars[ $qvar ] = nL_current_lang();
			}
		}
		add_filter( 'parse_query', 'nL_qs_admin_parse_query' );
	}
}
add_action( 'after_setup_theme', 'nL_qs_hooks_setup', 999 );