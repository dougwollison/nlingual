<?php
// ======================= //
//	Query Rewriting Hooks  //
// ======================= //

/*
 * Register langauge query_var
 */
add_filter('query_vars', 'nLingual_langauge_var');
function nLingual_langauge_var($vars){
	$vars[] = 'language';
	return $vars;
}

/*
 * Set the language query_var if on the front end and requesting a language supporting post type
 */
add_action('parse_query', 'nLingual_set_language_query_var');
function nLingual_set_language_query_var(&$wp_query){
	if(!is_admin() && (is_home() || is_archive() || is_search())
	&& nL_post_type_supported($wp_query->query_vars['post_type'])
	&& !isset($wp_query->query_vars['language'])){
		$wp_query->query_vars['language'] = nL_current_lang();
	}
}

/*
 * Filters for join/where parts of WP_Query statements to match languages
 *
 * Will skip if not a supported post type, or language is blank/not set
 */
add_filter('posts_join_request', 'nLingual_posts_join_request', 10, 2);
function nLingual_posts_join_request($join, &$query){
	global $wpdb;
	if(!nL_post_type_supported($query->query_vars['post_type'])
	|| !isset($query->query_vars['language'])
	|| !$query->query_vars['language']) return $join;

	$join .= " INNER JOIN $wpdb->nL_translations AS nL ON $wpdb->posts.ID = nL.post_id";

	return $join;
}

add_filter('posts_where_request', 'nLingual_posts_where_request', 10, 2);
function nLingual_posts_where_request($where, &$query){
	if(!nL_post_type_supported($query->query_vars['post_type'])
	|| !isset($query->query_vars['language'])
	|| !$query->query_vars['language']) return $where;

	$lang_id = nL_lang_id($query->query_vars['language']);

	$where .= " AND nL.lang_id = '$lang_id'";

	return $where;
}

/*
 * Fitlers for adjusting the next/previous posts query parts to return only those in the current language
 *
 * Unfortunately, this will run indiscriminately so long as the "post" post type is supported
 */
if(nL_post_type_supported('post')){
	add_filter('get_previous_post_join', 'nLingual_adjacent_post_join');
	add_filter('get_next_post_join', 'nLingual_adjacent_post_join');
	function nLingual_adjacent_post_join($join){
		global $wpdb;
		$join .= " INNER JOIN $wpdb->nL_translations AS nL ON p.ID = nL.post_id";

		return $join;
	}

	add_filter('get_previous_post_where', 'nLingual_adjacent_post_where');
	add_filter('get_next_post_where', 'nLingual_adjacent_post_where');
	function nLingual_adjacent_post_where($where){
		$lang = nL_lang_id();

		$where .= " AND nL.lang_id = '$lang_id'";

		return $where;
	}
}