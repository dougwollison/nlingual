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
*/

// Constants
define( 'NL_SELF', __FILE__ );
define( 'NL_DIR', __DIR__ );
define( 'NL_TXTDMN', 'nLingual' );
define( 'NL_DB_VERSION', '2.0.0' );
define( 'NL_ORIGINAL_URL', ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

// Flags
define( 'NL_UNLOCALIZED', 'NL_UNLOCALIZED' );
define( 'NL_REDIRECT_USING_GET', 'NL_REDIRECT_USING_GET' );
define( 'NL_REDIRECT_USING_PATH', 'NL_REDIRECT_USING_PATH' );
define( 'NL_REDIRECT_USING_DOMAIN', 'NL_REDIRECT_USING_DOMAIN' );

// Load includes
require( NL_DIR . '/inc/autoloader.php' );
require( NL_DIR . '/inc/functions-nlingual.php' );
require( NL_DIR . '/inc/functions-gettext.php' );
require( NL_DIR . '/inc/functions-alias.php' );

// Setup
nLingual\System::setup();
