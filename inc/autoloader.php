<?php
/**
 * nLingual Class Autoloader
 *
 * @package nLingual
 * @subpackage Autoloader
 * @since 2.0.0
 */

/**
 * Handle autoloading of nLingual classes.
 *
 * Will automatically initailize any Functional classes.
 *
 * @since 2.0.0
 *
 * @param string $name The name of the class being requested.
 */
function nlingual_autoloader( $class ) {
	// Remove any backslashes on the ends, just in case
	$name = trim( $class, '\\' );

	// Reformat to wordpress standards
	$file = 'class-' . strtolower( str_replace( array( '\\', '_' ), '-', $name ) ) . '.php';

	// Make sure the file exists before loading it
	if ( file_exists ( plugin_dir_path( __FILE__ ) . '/' . $file ) ){
		require( plugin_dir_path( __FILE__ ) . '/' . $file );

		// Initialize it if it's a Functional-based class
		if ( is_subclass_of( $class, 'nLingual\\Functional' ) ) {
			call_user_func( array( $class, 'init' ) );
		}
	}
}
spl_autoload_register( 'nlingual_autoloader' );