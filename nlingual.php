<?php
/*
Plugin Name: nLingual
Plugin URI: https://github.com/dougwollison/nlingual
Description: Easy to manage Multilingual system, with theme development utilities and post data synchronization.
Version: 2.3.1
Author: Doug Wollison
Author URI: http://dougw.me
Tags: multilingual, multi, language, admin, bilingual, switcher, translation, nlingual
License: GPL2
Text Domain: nLingual
Domain Path: /languages
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
define( 'NL_PLUGIN_FILE', __FILE__ );

/**
 * Reference to the plugin directory.
 *
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_PLUGIN_DIR', dirname( NL_PLUGIN_FILE ) );

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

// =========================
// ! Includes
// =========================

require( NL_PLUGIN_DIR . '/includes/autoloader.php' );
require( NL_PLUGIN_DIR . '/includes/functions-nlingual.php' );
require( NL_PLUGIN_DIR . '/includes/functions-gettext.php' );

// =========================
// ! Setup
// =========================

nLingual\System::setup();
