<?php
/**
 * nLingual Alias Functions
 *
 * Namely for use by templates and other plugins.
 *
 * @package nLingual
 * @subpackage Template
 *
 * @api
 *
 * @since 2.0.0
 */

use nLingual\Exception  as Exception;
use nLingual\Registry   as Registry;
use nLingual\Translator as Translator;
use nLingual\Rewriter   as Rewriter;

/**
 * Alias of Registry::get(), with error handling.
 *
 * @since 2.0.0
 *
 * @param string $option The name of the option to retrieve.
 *
 * @return mixed The value of that option (null if not found).
 */
function nl_get_option( $option ) {
	// Attempt to retrieve the desired option.
	try {
		return Registry::get( $option );
	} catch ( Exception $e ) {
		// get_var and post_var have been merged into query_var
		if ( $option == 'get_var' || $option == 'post_var' ) {
			return nl_get_option( 'query_var' );
		}
	}

	// Not found
	return null;
}
