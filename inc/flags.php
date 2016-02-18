<?php
/**
 * nLingual Flag Constants
 *
 * @package nLingual
 * @subpackage Utilities
 *
 * @api
 *
 * @since 2.0.0
 */

// =========================
// ! Functionality Flags
// =========================

/**
 * Flag the URL as needing to be unlocalized.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_UNLOCALIZED', 'NL_UNLOCALIZED' );

// =========================
// ! Error Codes
// =========================

/**
 * Error code for missing language.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_MISSING_LANGUAGE', 404 );

/**
 * Error code for an unrecognized method alias.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_UNSUPPORTED_METHOD', 405 );