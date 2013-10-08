<?php
// ======================== //
//	Global Rewrite Filters  //
// ======================== //

/**
 * option_(blogname/blogdescription) & the_title filter.
 *
 * Run values through nL_split_langs().
 *
 * @since 1.2.0 Moved to global hooks folder
 * @since 1.0.0
 *
 * @see nLingual::split_langs()
 */
add_filter('option_blogname', 'nL_split_langs');
add_filter('option_blogdescription', 'nL_split_langs');
add_filter('the_title', 'nL_split_langs');