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
 * Error code for a forbidden action.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_FORBIDDEN', 403 );

/**
 * Error code for missing thing.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_NOTFOUND', 404 );

/**
 * Error code for an unsupported action.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ERR_UNSUPPORTED', 405 );