<?php
/*
 * Add options pages
 */
add_action('admin_menu', 'nLingual_options_menu');
function nLingual_options_menu(){
	add_menu_page('nLingual Options', 'nLingual', 'manage_options', 'nLingual', 'nLingual_options_page');
}

function nLingual_options_page(){
	?>
	<div class="wrap">
		<?php screen_icon('generic')?>
		<h2><?php echo get_admin_page_title()?></h2>

		<br>

		<form method="post" action="options.php">
			<?php settings_fields('nLingual')?>
			<?php do_settings_sections('nLingual')?>
			<?php submit_button()?>
		</form>
	</div>
	<?php
}

/*
 * Register settings for options page
 */
add_action('admin_init', 'nLingual_register_settings');
function nLingual_register_settings(){
	add_settings_section('languages', 'Manage Settings', 'nLingual_manage_settings', 'nLingual');
	add_settings_section('languages', 'Manage Languages', 'nLingual_manage_languages', 'nLingual');
}

function nLingual_manage_settings(){

}

function nLingual_manage_languages(){

}