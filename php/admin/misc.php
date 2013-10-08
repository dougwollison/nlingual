<?php
// ===================== //
//	Miscellaneous Hooks  //
// ===================== //

/**
 * admin_enqueue_scripts action.
 *
 * Enqueues plugin specific JavasScript and CSS, and localize the admin js.
 *
 * @since 1.0.0
 */
function nLingual_enqueue_scripts(){
	// Admin styling
	wp_register_style('nLingual-admin', plugins_url('css/admin.css', NL_SELF), '1.0', 'screen');

	// Admin javascript
	wp_register_script('nLingual-admin-js', plugins_url('js/admin.js', NL_SELF), array('jquery-ui-sortable'), '1.0');
	wp_localize_script('nLingual-admin-js', 'nLingual_l10n', array(
		'NoPostSelected' => __('No post selected to edit.', NL_TXTDMN),
		'EraseDataConfirm' => __('Are you sure you wish to erase all language and translation data?', NL_TXTDMN)
	));

	// Quick-Edit javascript
	wp_register_script('nLingual-quickedit-js', plugins_url('js/quickedit.js', NL_SELF), array('inline-edit-post'), '1.0', true);

	wp_enqueue_style('nLingual-admin');
	wp_enqueue_script('nLingual-admin-js');
	wp_enqueue_script('nLingual-quickedit-js');
}
add_action('admin_enqueue_scripts', 'nLingual_enqueue_scripts');

/**
 * plugin_action_links filter.
 *
 * Add settings page link to the plugin's entry on the Plugins page.
 *
 * @since 1.0.0
 *
 * @param array  $links The list of links for the plugin.
 * @param string $file  The plugin basename (to see which plugin we're editing links for).
 *
 * @return array The modified links list.
 */
function nLingual_plugin_links($links, $file){
	global $this_plugin;
	if(!$this_plugin) $this_plugin = plugin_basename(NL_SELF);

	if($file == $this_plugin){
		$settings_link = '<a href="options-general.php?page=nLingual">'.__('Settings', NL_TXTDMN).'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}
add_filter('plugin_action_links', 'nLingual_plugin_links', 10, 2);

/**
 * admin_notices filter.
 *
 * Adds notices for any nLingual related actions.
 *
 * @since 1.0.0
 */
function nLingual_admin_notices(){
	if(isset($_GET['nLingual-erase']) && $_GET['nLingual-erase']){
		printf('<div class="updated"><p>%s</p></div>', __('The translation table has been successfully erased.', NL_TXTDMN));
	}
}
add_action('admin_notices', 'nLingual_admin_notices');