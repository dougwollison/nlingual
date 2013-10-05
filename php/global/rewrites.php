<?php
// ======================== //
//	Global Rewrite Filters  //
// ======================== //

/**
 * Add fitlers for running split_langs on the following:
 * - blog name
 * - blog description
 * - post title
 *
 * @since 1.2.0 Moved to global hooks folder
 * @since 1.0.0
 */
add_filter('option_blogname', 'nL_split_langs');
add_filter('option_blogdescription', 'nL_split_langs');
add_filter('the_title', 'nL_split_langs');