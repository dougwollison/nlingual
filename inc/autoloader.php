<?php
/**
 * nLingual Autoloading System
 *
 * @package nLingual
 *
 * @since 2.0.0
 */

namespace nLingual_Autoloader;

/**
 * Handle file locating and loading.
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
 * @since 2.0.0
 *
 * @see find() to find and load the class if it exists.
 *
 * @param string $class The name of the class being requested.
 */
function find_class( $class ) {
	// Make sure the file exists before loading it
	if ( find( 'class', $class ) ){
		// Initialize it if it's a Handler-based class
		if ( is_subclass_of( $class, 'nLingual\\Handler' ) ) {
			call_user_func( array( $class, 'init' ) );
		}
	}
}

/**
 * Find/load an nLingual abstract class.
 *
 * @since 2.0.0
 *
 * @see find() to find and load the class if it exists.
 *
 * @param string $class The name of the class being requested.
 */
function find_abstract( $class ) {
	find( 'abstract', $class );
}

/**
 * Find/load an nLingual trait.
 *
 * @since 2.0.0
 *
 * @see find() to find and load the trait if it exists.
 *
 * @param string $trait The name of the trait being requested.
 */
function find_trait( $trait ) {
	find( 'trait', $trait );
}

// Register the find
spl_autoload_register( __NAMESPACE__ . '\\find_class' );
spl_autoload_register( __NAMESPACE__ . '\\find_abstract' );
spl_autoload_register( __NAMESPACE__ . '\\find_trait' );
