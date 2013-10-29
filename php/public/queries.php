<?php
// ==================== //
//	Public Query Hooks  //
// ==================== //

/**
 * parse_query action.
 *
 * Set the language query_var if on the front end and requesting a language supporting post type.
 *
 * @since 1.0.0
 *
 * @uses nL_query_var()
 * @uses nL_post_type_supported()
 * @uses nL_current_lang()
 *
 * @param WP_Query $wp_query The WP_Query instance (by reference). 
 */
function nLingual_set_language_query_var(&$wp_query){
	// Get the query var we should use
	$qvar = nL_query_var();
	$pt = isset($wp_query->query_vars['post_type']) ? $wp_query->query_vars['post_type'] : null;
	
	if(!is_admin() && (is_home() || is_archive() || is_search())
	&& nL_post_type_supported($pt)
	&& !isset($wp_query->query_vars[$qvar])){
		$wp_query->query_vars[$qvar] = nL_current_lang();
	}
}
add_action('parse_query', 'nLingual_set_language_query_var');

if(nL_post_type_supported('post')){
	/**
	 * get_(next/previous)_post_join filters.
	 *
	 * Modifies the JOIN clause to include the nL_translations table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $join The JOIN clause.
	 *
	 * @return string The modified JOIN clause.
	 */
	function nLingual_adjacent_post_join($join){
		global $wpdb;
		$join .= " INNER JOIN $wpdb->nL_translations AS nL ON p.ID = nL.post_id";

		return $join;
	}
	add_filter('get_previous_post_join', 'nLingual_adjacent_post_join');
	add_filter('get_next_post_join', 'nLingual_adjacent_post_join');

	/**
	 * get_(next/previous)_post_where filters.
	 *
	 * Modifies the WHERE clause to filter by language.
	 *
	 * @since 1.0.0
	 *
	 * @uses nL_lang_id()
	 *
	 * @param string $where The WHERE clause.
	 *
	 * @return string The modified WHERE clause.
	 */
	function nLingual_adjacent_post_where($where){
		$lang_id = nL_lang_id();

		$where .= " AND nL.lang_id = '$lang_id'";

		return $where;
	}
	add_filter('get_previous_post_where', 'nLingual_adjacent_post_where');
	add_filter('get_next_post_where', 'nLingual_adjacent_post_where');
}