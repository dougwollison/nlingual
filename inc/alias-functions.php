<?php
/**
 * nLingual Alias Functions
 *
 * @package nLingual
 * @subpackage Aliases
 * @since 2.0.0
 */

use nLingual\Registry as Registry;
use nLingual\Translator as Translator;
use nLingual\Rewriter as Rewriter;

/**
 * @see Registry::get() for details.
 */
function nl_get_option( $option ) {
	return Registry::get( $option );
}