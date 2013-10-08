<?php
// ==================== //
//	Global Query Hooks  //
// ==================== //

/**
 * query_vars filter.
 *
 * Register language variable
 *
 * @since 1.2.0 Moved to global hooks folder.
 * @since 1.0.0
 *
 * @uses nL_query_var()
 *
 * @param array $vars The public query variables whitelist.
 *
 * @return array The modified variables list.
 */
function nLingual_language_var($vars){
	$vars[] = nL_query_var();
	return $vars;
}
add_filter('query_vars', 'nLingual_language_var');

/**
 * posts_join_request filter.
 *
 * Adds nL_translations table to JOIN clause (if language query var is present).
 *
 * @since 1.2.0 Moved to global hooks folder.
 * @since 1.0.0
 *
 * @uses nL_query_var()
 * @uses nL_post_type_supported()
 *
 * @param string   $join  The original JOIN clause.
 * @param WP_Query $query The WP_Query instance.
 *
 * @return string The modified JOIN clause.
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
 * posts_where_request filter.
 *
 * Adds nL_translations table to WHERE clause (if language query var is present).
 *
 * @since 1.2.0 Moved to global hooks folder.
 * @since 1.0.0
 *
 * @uses nL_query_var()
 * @uses nL_post_type_supported()
 * @uses nL_lang_id()
 *
 * @param string   $join  The original WHERE clause.
 * @param WP_Query $query The WP_Query instance.
 *
 * @return string The modified WHERE clause.
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