<?php
// ==================== //
//	Global Query Hooks  //
// ==================== //

/**
 * Query variables filter
 * Register language variable
 *
 * @since 1.2.0 Moved to global hooks folder
 * @since 1.0.0
 */
add_filter('query_vars', 'nLingual_language_var');
function nLingual_language_var($vars){
	$vars[] = nL_query_var();
	return $vars;
}

/**
 * Posts query JOIN filter
 * Adds join statement for the translations table (if language query var is present)
 *
 * @since 1.2.0 Moved to global hooks folder
 * @since 1.0.0
 */
function nLingual_posts_join_request($join, $query){
	global $wpdb;
	
	// Get the query var we should use
	$qvar = nL_query_var();

	if(!nL_post_type_supported($query->query_vars['post_type'])
	|| !isset($query->query_vars[$qvar])
	|| !$query->query_vars[$qvar]) return $join;

	$join .= " INNER JOIN $wpdb->nL_translations AS nL ON $wpdb->posts.ID = nL.post_id";

	return $join;
}
add_filter('posts_join_request', 'nLingual_posts_join_request', 10, 2);


/**
 * Posts query WHERE filter
 * Adds fitler to return only posts in the desired langauge (if language query var is present)
 *
 * @since 1.2.0 Moved to global hooks folder
 * @since 1.0.0
 */
function nLingual_posts_where_request($where, $query){
	// Get the query var we should use
	$qvar = nL_query_var();
	
	if(!nL_post_type_supported($query->query_vars['post_type'])
	|| !isset($query->query_vars[$qvar])
	|| !$query->query_vars[$qvar]) return $where;

	$lang_id = nL_lang_id($query->query_vars[$qvar]);

	$where .= " AND nL.lang_id = '$lang_id'";

	return $where;
}
add_filter('posts_where_request', 'nLingual_posts_where_request', 10, 2);