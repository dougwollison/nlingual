<?php
// Class autoloader
spl_autoload_register( function( $class ) {
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
});