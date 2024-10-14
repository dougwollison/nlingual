<?php
/*
Plugin Name: nLingual
Plugin URI: https://github.com/dougwollison/nlingual
Description: Easy to manage Multilingual system, with theme development utilities and post data synchronization.
Version: 2.10.0
Author: Doug Wollison
Author URI: https://dougw.me
Tags: multilingual, multi, language, admin, bilingual, switcher, translation, nlingual
License: GPL2
Text Domain: nlingual
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
 * Reference to the plugin slug.
 *
 * @since 2.8.0
 *
 * @var string
 */
define( 'NL_PLUGIN_SLUG', basename( NL_PLUGIN_DIR ) . '/' . basename( NL_PLUGIN_FILE ) );

/**
 * Identifies the current plugin version.
 *
 * @since 2.6.0
 *
 * @var string
 */
define( 'NL_PLUGIN_VERSION', '2.10.0' );

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
 * @since 2.10.0 Handle possibly undefined REQUEST_URI.
 * @since 2.6.0 Won't generate if HTTP_HOST isn't present.
 * @since 2.0.0
 *
 * @var string
 */
define( 'NL_ORIGINAL_URL', isset( $_SERVER['HTTP_HOST'] ) ? ( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . ( $_SERVER['REQUEST_URI'] ?? '' ) ) : '' );

// =========================
// ! Includes
// =========================

require NL_PLUGIN_DIR . '/includes/autoloader.php';
require NL_PLUGIN_DIR . '/includes/functions-nlingual.php';
require NL_PLUGIN_DIR . '/includes/functions-gettext.php';

// =========================
// ! Setup
// =========================

nLingual\System::setup();
