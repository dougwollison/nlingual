<?php
// Abort if WordPress isn't actually uninstalling anything
if(!defined('WP_UNINSTALL_PLUGIN')) exit;

// Or if the user can't activate plugins
if(!current_user_can('activate_plugins'))
	return;

// Or if this isn't the plugin we're supposed to uninstall
if(WP_UNINSTALL_PLUGIN != 'nLingual/nLingual.php')
	return;

global $wpdb, $table_prefix;

// Now, delete the language and translation table if it exists
$wpdb->query("DROP TABLE IF EXISTS `{$table_prefix}nL_languages`");
$wpdb->query("DROP TABLE IF EXISTS `{$table_prefix}nL_translations`");

// Oh, and delete the options
$wpdb->query("DELETE FORM $wpdb->options WHERE option_name like 'nLingual-%'");
