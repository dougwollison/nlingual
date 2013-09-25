<?php
/*
 * Add options pages
 */

add_action('admin_menu', 'nLingual_options_menu');
function nLingual_options_menu(){
	add_options_page(
		__('Language Settings', NL_TXTDMN),
		__('Languages', NL_TXTDMN),
		'manage_options',
		'nLingual',
		'nLingual_settings_page'
	);
}

function nLingual_settings_page(){
	?>
	<div class="wrap">
		<?php screen_icon('options-general')?>
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
	add_settings_section('nLingual-options', __('Options', NL_TXTDMN), 'nLingual_manage_options', 'nLingual');
	add_settings_section('nLingual-sync_rules', __('Synchronization Rules', NL_TXTDMN), 'nLingual_manage_sync', 'nLingual');
	add_settings_section('nLingual-languages', __('Languages', NL_TXTDMN), 'nLingual_manage_languages', 'nLingual');

	register_setting('nLingual', 'nLingual-options');
	register_setting('nLingual', 'nLingual-sync_rules', function($data){
		foreach($data as &$ruleset){
			$ruleset['meta'] = explode("\n", $ruleset['meta']);
		}

		return $data;
	});
	register_setting('nLingual', 'nLingual-languages', function($data){
		$languages = array();

		// In case santizing was already called, check if it's proper and skip if so.
		if($data === array_values($data)) return $data;

		foreach($data as $field => $values)
			foreach($values as $index => $value)
				$languages[$index][$field] = $value;

		return $languages;
	});

	add_settings_field('redirection-method', __('Language redirection method', NL_TXTDMN), function(){
		$compare = nL_get_option('method');
		$options = array(
			'Subdomain (e.g. <code>%1$s.%2$s</code>)' => NL_REDIRECT_USING_DOMAIN,
			'Path prefix (e.g. <code>%2$s/%1$s</code>)' => NL_REDIRECT_USING_PATH,
			'None, use visitors native language, if applicable' => NL_REDIRECT_USING_ACCEPT
		);

		foreach($options as $label => $value){
			$label = sprintf($label, nL_default_lang(), parse_url(get_bloginfo('home'), PHP_URL_HOST));
			printf('<label><input type="radio" name="nLingual-options[method]" value="%s" %s> %s</label><br/>', $value, $value == $compare ? 'checked' : '', __($label, 'NL_TXTDMN'));
		}

		$bool = nL_get_option('skip_default_l10n');

		printf('<label><input id="skip_default_l10n" type="checkbox" name="nLingual-options[skip_default_l10n]" value="1" %s> %s</label>', $bool ? 'checked' : '', __('Skip on urls in the default language', NL_TXTDMN));
	}, 'nLingual', 'nLingual-options');

	add_settings_field('request-vars', __('POST & GET variable names', NL_TXTDMN), function(){
		$get = nL_get_option('get_var');
		$post = nL_get_option('post_var');

		printf('<label>%s</label><br>', __('If these parameters are passed, they will override the language set via the redirection method.', NL_TXTDMN));

		printf('<label><code>GET&nbsp;</code> <input type="text" name="nLingual-options[get_var]" value="%s"></label><br>', $get);
		printf('<label><code>POST</code> <input type="text" name="nLingual-options[post_var]" value="%s"></label><br>', $post);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('post_types', __('Supported post types', NL_TXTDMN), function(){
		$post_types = nL_post_types();

		$available = get_post_types(array('show_ui' => true, 'show_in_nav_menus' => true), 'objects');

		foreach($available as $slug => $data){
			printf('<label><input type="checkbox" name="nLingual-options[post_types][]" value="%s" %s> %s</label><br/>', $slug, in_array($slug, $post_types) ? 'checked' : '', $data->labels->menu_name);
		}
	}, 'nLingual', 'nLingual-options');

	add_settings_field('split_separator', __('Split language separator', NL_TXTDMN), function(){
		$separator = nL_get_option('separator');

		printf('<input id="split_separator" type="text" class="small-text" name="nLingual-options[separator]" value="%s">', $separator);
		printf('<p>%s</p>', __('Used for splitting things like the blog title and extracting the title for the appropriate language.', NL_TXTDMN));
		printf('<p class="description">'.__('Example: <strong>English Title %1$s French Title %1$s Spanish Title</strong> (if the language order is English, French, Spanish)', NL_TXTDMN).'</p>', $separator);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('l10n_dateformat', __('Localize date format?', NL_TXTDMN), function(){
		$bool = nL_get_option('l10n_dateformat');

		printf('<label><input id="l10n_dateformat" type="checkbox" name="nLingual-options[l10n_dateformat]" value="1" %s> %s</label>', $bool ? 'checked' : '', __('Run localization on the date format string', NL_TXTDMN));
		printf('<p class="description">%s</p>', __('Use if any of your languages use custom date formats.', NL_TXTDMN));
	}, 'nLingual', 'nLingual-options');

	// Add Syncornization Rule managers for each post type
	foreach(nL_post_types() as $post_type){
		$post_type = get_post_type_object($post_type);
		add_settings_field($post_type->name.'-sync_rules', _f('%s', NL_TXTDMN, $post_type->labels->name), function() use ($post_type, $sync_rules){
			$pt = $post_type->name;
			$sync_data_rules = nL_sync_rules($pt, 'data');
			printf('<label>%s</label><br>', _f('Check off the fields that should be synchronized between sister %s.', NL_TXTDMN, strtolower($post_type->labels->name)));
			foreach(array(
				'post_author',
				'post_parent',
				'post_date',
				'post_modified',
				'menu_order',
				'post_status',
				'comment_status',
				'ping_status',
			) as $field){
				printf(
					'<label><input type="checkbox" name="nLingual-sync_rules[%s][data][]" value="%s" %s> %s</label><br/>',
					$pt,
					$field,
					in_array($field, $sync_data_rules) ? 'checked' : '',
					$field
				);
			}

			$sync_tax_rules = nL_sync_rules($pt, 'tax');
			$taxonomies = get_object_taxonomies($pt, 'objects');
			if($taxonomies){
				printf('<br/><label>%s</label><br>', __('Check off the taxonomies that should be synchronized.', NL_TXTDMN));
				foreach($taxonomies as $taxonomy => $data){
					printf(
						'<label><input type="checkbox" name="nLingual-sync_rules[%s][tax][]" value="%s" %s> %s</label><br/>',
						$pt,
						$taxonomy,
						in_array($taxonomy, $sync_tax_rules) ? 'checked' : '',
						$data->label
					);
				}
			}

			$sync_meta_rules = nL_sync_rules($pt, 'meta');
			printf('<br/><label for="%s-meta-sync_rules">%s</label><br>', $pt, __('List the meta field that should be synchronized, one per line.', NL_TXTDMN));
			printf('<textarea id="%s-meta-sync_rules" name="nLingual-sync_rules[%s][meta]" class="large-text code" rows="5">%s</textarea>', $pt, $pt, implode("\n", $sync_meta_rules));
		}, 'nLingual', 'nLingual-sync_rules');
	}
}

function nLingual_manage_options(){
	do_settings_fields('nLingual', 'options');
}

function nLingual_manage_sync(){
	do_settings_fields('nLingual', 'sync');
}

function nLingual_manage_languages(){
	$languages = nL_languages();
	?>
	<button id="nLingual_add_language" type="button" class="button-secondary"><?php _e('Add Language', NL_TXTDMN)?></button>

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
			<button type="button" class="delete button-secondary"><?php _e('Delete', NL_TXTDMN)?></button>
			<br class="clearfix">
		</div>
		<div class="info">
			<label class="name">
				<?php _e('System Name', NL_TXTDMN)?>
				<input type="text" name="nLingual-languages[name][]" value="<?php echo $name?>">
			</label>
			<label class="native">
				<?php _e('Native Name', NL_TXTDMN)?>
				<input type="text" name="nLingual-languages[native][]" value="<?php echo $native?>">
			</label>
			<label class="iso" title="<?php _e('The 2 letter code to use for identifying the langauge.', NL_TXTDMN)?>">
				<?php _e('ISO', NL_TXTDMN)?>
				<input type="text" name="nLingual-languages[iso][]" value="<?php echo $iso?>" maxlength="2">
			</label>
			<label class="mo" title="<?php _e('The name (minus extension) of the .MO file use for localization.', NL_TXTDMN)?>">
				<?php _e('.MO file', NL_TXTDMN)?>
				<input type="text" name="nLingual-languages[mo][]" value="<?php echo $mo?>">
			</label>
			<label class="tag" title="<?php _e('A shorthand name for the language.', NL_TXTDMN)?>">
				<?php _e('Short name', NL_TXTDMN)?>
				<input type="text" name="nLingual-languages[tag][]" value="<?php echo $tag?>">
			</label>
			<br class="clearfix">
		</div>
	</div>
	<?php
}