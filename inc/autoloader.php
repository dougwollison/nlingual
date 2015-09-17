<?php
// Class autoloader
spl_autoload_register( function( $class ) {
	// Reformat to wordpress standards
	$file = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

	// Make sure the file exists before loading it
	if ( file_exists ( plugin_dir_path( __FILE__ ) . '/' . $file ) ){
        require( plugin_dir_path( __FILE__ ) . '/' . $file );
    }
});