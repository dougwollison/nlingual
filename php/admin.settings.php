<?php
/*
 * Add options pages
 */

add_action('admin_menu', 'nLingual_options_menu');
function nLingual_options_menu(){
	add_menu_page('nLingual Settings', 'nLingual', 'manage_options', 'nLingual', 'nLingual_settings_page');
}

function nLingual_settings_page(){
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
	add_settings_section('nLingual-options', 'Options', 'nLingual_manage_options', 'nLingual');
	add_settings_section('nLingual-languages', 'Languages', 'nLingual_manage_languages', 'nLingual');

	register_setting('nLingual', 'nLingual-options');
	register_setting('nLingual', 'nLingual-languages', function($data){
		$languages = array();

		// In case santizing was already called, check if it's proper and skip if so.
		if($data === array_values($data)) return $data;

		foreach($data as $field => $values)
			foreach($values as $index => $value)
				$languages[$index][$field] = $value;

		return $languages;
	});

	add_settings_field('redirection-method', 'Language redirection method', function(){
		$compare = nL_get_option('method');
		$options = array(
			'Subdomain (e.g. <code>%1$s.%2$s</code>)' => NL_REDIRECT_USING_DOMAIN,
			'Path prefix (e.g. <code>%2$s/%1$s</code>)' => NL_REDIRECT_USING_PATH,
			'None, use visitors native language, if applicable' => NL_REDIRECT_USING_ACCEPT
		);

		foreach($options as $label => $value){
			$label = sprintf($label, nL_default_lang(), parse_url(get_bloginfo('home'), PHP_URL_HOST));
			printf('<label><input type="radio" name="nLingual-options[method]" value="%s" %s> %s</label><br/>', $value, $value == $compare ? 'checked' : '', $label);
		}
	}, 'nLingual', 'nLingual-options');

	add_settings_field('request-vars', 'POST & GET variable names', function(){
		$get = nL_get_option('get_var');
		$post = nL_get_option('post_var');

		echo '<label>If these parameters are passed, they will override the language set via the redirection method.</label><br>';

		printf('<label><code>GET&nbsp;</code> <input type="text" name="nLingual-options[get_var]" value="%s"></label><br>', $get);
		printf('<label><code>POST</code> <input type="text" name="nLingual-options[post_var]" value="%s"></label><br>', $post);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('post_types', 'Supported post types', function(){
		$post_types = nL_post_types();

		$available = get_post_types(array('show_ui' => true, 'show_in_nav_menus' => true), 'objects');

		foreach($available as $slug => $data){
			printf('<label><input type="checkbox" name="nLingual-options[post_types][]" value="%s" %s> %s</label><br/>', $slug, in_array($slug, $post_types) ? 'checked' : '', $data->labels->menu_name);
		}
	}, 'nLingual', 'nLingual-options');

	add_settings_field('split_separator', 'Split language separator', function(){
		$separator = nL_get_option('separator');

		printf('<input id="split_separator" type="text" class="small-text" name="nLingual-options[separator]" value="%s">', $separator);
		echo '<p class="description">Used for splitting things like the blog title and extracting the title for the appropriate language.</p>';
		printf('<p class="description">Example: <strong>English Title %1$s French Title %1$s Spanish Title</strong> (if the language order is English, French, Spanish)</p>', $separator);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('l10n_dateformat', 'Localize date format?', function(){
		$bool = get_option('l10n_dateformat');

		printf('<label><input id="l10n_dateformat" type="checkbox" name="nLingual-options[l10n_dateformat]" value="1" %s> Run localization on the date format string</label>', $bool ? 'checked' : 'checked');
		echo '<p class="description">Use if any of your languages use custom date formats.</p>';
	}, 'nLingual', 'nLingual-options');
}

function nLingual_manage_options(){
	do_settings_fields('nLingual', 'options');
}

function nLingual_manage_languages(){
	$languages = nL_languages();
	?>
	<button id="nLingual_add_language" type="button" class="button-secondary">Add Language</button>

	<div id="nLingual_languages">
	<?php foreach($languages as $language) _nLingual_language_editor($language)?>
	</div>

	<script id="nLingual_language_template" type="text/template">
		<?php _nLingual_language_editor()?>
	</script>
	<?php
}

function _nLingual_language_editor($language = array()){
	extract(array_merge(array('iso'=>'', 'mo'=>'', 'tag'=>'', 'name'=>'', 'native'=>''), $language));
	$default = nL_default_lang();
	?>
	<div class="language">
		<div class="controls">
			<label class="default"><input type="radio" name="nLingual-options[default_lang]" value="<?php echo $iso?>" <?php if($default == $iso) echo 'checked'?>> Default</label>
			<button type="button" class="delete button-secondary">Delete</button>
			<br class="clearfix">
		</div>
		<div class="info">
			<label class="name">System Name <input type="text" name="nLingual-languages[name][]" value="<?php echo $name?>"></label>
			<label class="native">Native Name <input type="text" name="nLingual-languages[native][]" value="<?php echo $native?>"></label>
			<label class="iso" title="The code to use for selecting the langauge.">ISO (2 letter) code <input type="text" name="nLingual-languages[iso][]" value="<?php echo $iso?>" maxlength="2"></label>
			<label class="mo" title="The name (sans extension) of the .MO file use for localization.">.mo filename <input type="text" name="nLingual-languages[mo][]" value="<?php echo $mo?>"></label>
			<label class="tag">Shorthand name <input type="text" name="nLingual-languages[tag][]" value="<?php echo $tag?>"></label>
			<br class="clearfix">
		</div>
	</div>
	<?php
}