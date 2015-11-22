<?php
/*
Plugin Name: nLingual
Plugin URI: https://github.com/dougwollison/nLingual
Description: Easy to manage Multilingual system, with theme development utilities and post data synchronization.
Version: 2.0.0
Author: Doug Wollison
Author URI: http://dougw.me
Tags: multilingual, multi, language, admin, bilingual, switcher, translation, nlingual
License: GPL2
Text Domain: nLingual
Domain Path: /lang
*/


// =========================
// ! Constants
// =========================

/**
 * Reference to the plugin file.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_SELF', __FILE__ );

/**
 * Reference to the plugin directory.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_DIR', __DIR__ );

/**
 * Shortcut for the TextDomain.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_TXTDMN', 'nLingual' );

/**
 * Identifies the current database version.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_DB_VERSION', '2.0.0' );

/**
 * Stores the (assumed) undoctored URL requested.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ORIGINAL_URL', ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

/**
 * Flag the URL as needing to be unlocalized.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_UNLOCALIZED', 'NL_UNLOCALIZED' );

// =========================
// ! Includes
// =========================

require( NL_DIR . '/inc/autoloader.php' );
require( NL_DIR . '/inc/functions-nlingual.php' );
require( NL_DIR . '/inc/functions-gettext.php' );
require( NL_DIR . '/inc/functions-alias.php' );

// =========================
// ! Setup
// =========================

nLingual\System::setup();
