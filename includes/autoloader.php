<?php
/**
 * nLingual Autoloading System
 *
 * @package nLingual
 *
 * @internal
 *
 * @since 2.0.0
 */

namespace nLingual_Autoloader;

/**
 * Handle file locating and loading.
 *
 * @internal
 *
 * @since 2.0.0
 *
 * @param string $type The type to try and look under (class, trait, etc.)
 * @param string $name The symbol name of the asset being requested.
 *
 * @return bool Wether or not the file was found and loaded.
 */
function find( $type, $name ) {
	// Just in case, trim off beginning backslash
	$name = ltrim( $name, '\\' );

	// Reformat to wordpress standards
	$file = $type . '-' . strtolower( str_replace( array( '\\', '_' ), '-', $name ) ) . '.php';

	// Build the full path
	$path = plugin_dir_path( __FILE__ ) . '/' . $file;

	// Make sure the file exists before loading it
	if ( file_exists ( $path ) ){
		require( $path );
		return true;
	}

	return false;
}

/**
 * Find/load an nLingual class.
 *
 * Will automatically initailize if it's a Functional sub-class.
 *
 * @internal
 *
 * @since 2.0.0
 *
 * @uses find() to find and load the class if it exists.
 *
 * @param string $class The name of the class being requested.
 */
function find_class( $class ) {
	find( 'class', $class );
}

/**
 * Find/load an nLingual abstract class.
 *
 * @internal
 *
 * @since 2.0.0
 *
 * @uses find() to find and load the class if it exists.
 *
 * @param string $class The name of the class being requested.
 */
function find_abstract( $class ) {
	find( 'abstract', $class );
}

// Register the find
spl_autoload_register( __NAMESPACE__ . '\\find_class' );
spl_autoload_register( __NAMESPACE__ . '\\find_abstract' );
