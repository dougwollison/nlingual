<?php
// ======================= //
//	Query Rewriting Hooks  //
// ======================= //

/**
 * Query variables filter
 * Register language variable
 *
 * @since 1.0.0
 */
add_filter('query_vars', 'nLingual_language_var');
function nLingual_language_var($vars){
	$vars[] = 'language';
	return $vars;
}

/**
 * Set the language query_var if on the front end and requesting a language supporting post type
 *
 * @since 1.0.0
 */
add_action('parse_query', 'nLingual_set_language_query_var');
function nLingual_set_language_query_var(&$wp_query){
	if(!is_admin() && (is_home() || is_archive() || is_search())
	&& nL_post_type_supported($wp_query->query_vars['post_type'])
	&& !isset($wp_query->query_vars['language'])){
		$wp_query->query_vars['language'] = nL_current_lang();
	}
}

/**
 * Posts query JOIN filter
 * Adds join statement for the translations table (if language query var is present)
 *
 * @since 1.0.0
 */
function nLingual_posts_join_request($join, &$query){
	global $wpdb;
	if(!nL_post_type_supported($query->query_vars['post_type'])
	|| !isset($query->query_vars['language'])
	|| !$query->query_vars['language']) return $join;

	$join .= " INNER JOIN $wpdb->nL_translations AS nL ON $wpdb->posts.ID = nL.post_id";

	return $join;
}
add_filter('posts_join_request', 'nLingual_posts_join_request', 10, 2);


/**
 * Posts query WHERE filter
 * Adds fitler to return only posts in the desired langauge (if language query var is present)
 *
 * @since 1.0.0
 */
function nLingual_posts_where_request($where, &$query){
	if(!nL_post_type_supported($query->query_vars['post_type'])
	|| !isset($query->query_vars['language'])
	|| !$query->query_vars['language']) return $where;

	$lang_id = nL_lang_id($query->query_vars['language']);

	$where .= " AND nL.lang_id = '$lang_id'";

	return $where;
}
add_filter('posts_where_request', 'nLingual_posts_where_request', 10, 2);

/**
 * Next/previous post where/join filters
 * Adds filters for the the join and where compontents of the ajacent post queries,
 * altering the query to join with the translations table and filter by the current
 * language.
 *
 * Currently is only added when the post post type is registered with nLingaul
 *
 * @since 1.0.0
 */
if(nL_post_type_supported('post')){
	function nLingual_adjacent_post_join($join){
		global $wpdb;
		$join .= " INNER JOIN $wpdb->nL_translations AS nL ON p.ID = nL.post_id";

		return $join;
	}
	add_filter('get_previous_post_join', 'nLingual_adjacent_post_join');
	add_filter('get_next_post_join', 'nLingual_adjacent_post_join');

	function nLingual_adjacent_post_where($where){
		$lang = nL_lang_id();

		$where .= " AND nL.lang_id = '$lang_id'";

		return $where;
	}
	add_filter('get_previous_post_where', 'nLingual_adjacent_post_where');
	add_filter('get_next_post_where', 'nLingual_adjacent_post_where');
}