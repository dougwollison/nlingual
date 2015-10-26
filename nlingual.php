<?php
/*
Plugin Name: nLingual
Plugin URI: https://github.com/dougwollison/nLingual
Description: Easy to manage Multilingual system, with theme development utilities and post data synchronization.
Version: 1.3.1
Author: Doug Wollison
Author URI: http://dougw.me
Tags: multilingual, multi, language, admin, bilingual, switcher, translation, nlingual
License: GPL2
*/

define( 'NL_SELF', __FILE__ );
define( 'NL_TXTDMN', 'nLingual' );
define( 'NL_DB_VERSION', '1.2.3' );

require( __DIR__ . '/inc/load.php' );

// Check for update notices
if ( is_admin() ) {
	add_action( 'in_plugin_update_message-' . basename( __DIR__ ) . '/' . basename( __FILE__ ), 'nlingual_update_notice_check' );
	function nlingual_update_notice_check( $plugin ) {
		// get the version number
		$version = $plugin['new_version'];

		// check for a notice on the SVN repo
		$key = "nlingual_update_notice_$version";
		$data = get_transient( $key );
		if ( $data === false ) {
			// fetch the data and save it
			$data = file_get_contents( "http://plugins.svn.wordpress.org/nlingual/assets/notice-$version.txt" );
			if ( ! $data ) {
				$data = '';
			}
			set_transient( $key, $data, YEAR_IN_SECONDS );
		}

		// if there's a notice, print it out
		if ( $data ) {
			echo apply_filters( 'the_content', $data );
		}
	}
}