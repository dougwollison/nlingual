<?php
// ============================== //
//	Settings Hooks and Callbacks  //
// ============================== //

/*
 * Add settings page
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
			if(in_array('post_date', $ruleset['data'])) $ruleset['data'][] = 'post_date_gmt';
			if(in_array('post_modified', $ruleset['data'])) $ruleset['data'][] = 'post_modified_gmt';
		}

		return $data;
	});

	add_settings_field('redirection-method', __('Language redirection method', NL_TXTDMN), function(){
		$compare = nL_get_option('method');
		$options = array(
			NL_REDIRECT_USING_DOMAIN	=> __('Subdomain (e.g. <code>%1$s.%2$s</code>)', NL_TXTDMN),
			NL_REDIRECT_USING_PATH		=> __('Path prefix (e.g. <code>%2$s/%1$s/</code>)', NL_TXTDMN),
			NL_REDIRECT_USING_ACCEPT	=> __('None, use visitors native language, if applicable', NL_TXTDMN)
		);

		foreach($options as $value => $label){
			$label = sprintf($label, nL_default_lang(), parse_url(get_bloginfo('home'), PHP_URL_HOST));
			printf(
				'<label><input type="radio" name="nLingual-options[method]" value="%s" %s> %s</label><br/>',
				$value,
				$value == $compare ? 'checked' : '',
				$label
			);
		}

		$bool = nL_get_option('skip_default_l10n');

		printf(
			'<label><input id="skip_default_l10n" type="checkbox" name="nLingual-options[skip_default_l10n]" value="1" %s> %s</label>',
			$bool ? 'checked' : '',
			__('Do not use a subdomain or path prefix for the default langauge', NL_TXTDMN)
		);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('request-vars', __('POST & GET variable names', NL_TXTDMN), function(){
		$get = nL_get_option('get_var');
		$post = nL_get_option('post_var');

		printf(
			'<label>%s</label><br>',
			__('If these parameters are passed, they will override the language set via the redirection method.', NL_TXTDMN)
		);
		printf(
			'<label><code>GET&nbsp;</code> <input type="text" name="nLingual-options[get_var]" value="%s"></label><br>',
			$get
		);
		printf(
			'<label><code>POST</code> <input type="text" name="nLingual-options[post_var]" value="%s"></label><br>',
			$post
		);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('post_types', __('Supported post types', NL_TXTDMN), function(){
		$post_types = nL_post_types();

		$available = get_post_types(array('show_ui' => true), 'objects');

		foreach($available as $slug => $data){
			if($slug == 'attachment') continue; // Attachements are excluded; they have show_ui but they should not be translated.
			printf(
				'<label><input type="checkbox" name="nLingual-options[post_types][]" value="%s" %s> %s</label><br/>',
				$slug,
				in_array($slug, $post_types) ? 'checked' : '',
				$data->labels->menu_name
			);
		}
	}, 'nLingual', 'nLingual-options');

	add_settings_field('split_separator', __('Split language separator', NL_TXTDMN), function(){
		$separator = nL_get_option('separator');

		printf(
			'<input id="split_separator" type="text" class="small-text" name="nLingual-options[separator]" value="%s">',
			$separator
		);
		printf(
			'<p>%s</p>',
			__('Used for splitting things like the blog title and extracting the title for the appropriate language.', NL_TXTDMN)
		);
		printf(
			'<p class="description">'.__('Example: <strong>English Title %1$s French Title %1$s Spanish Title</strong> (if the language order is English, French, Spanish)', NL_TXTDMN).'</p>', $separator
		);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('l10n_dateformat', __('Localize date format?', NL_TXTDMN), function(){
		$bool = nL_get_option('l10n_dateformat');

		printf(
			'<label><input id="l10n_dateformat" type="checkbox" name="nLingual-options[l10n_dateformat]" value="1" %s> %s</label>',
			$bool ? 'checked' : '',
			__('Run localization on the date format string', NL_TXTDMN)
		);
		printf(
			'<p class="description">%s</p>',
			__('Use if any of your languages use custom date formats.', NL_TXTDMN)
		);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('delete_sisters', __('Delete sister posts?', NL_TXTDMN), function(){
		$bool = nL_get_option('delete_sisters');

		printf(
			'<label><input id="delete_sisters" type="checkbox" name="nLingual-options[delete_sisters]" value="1" %s> %s</label>',
			$bool ? 'checked' : '',
			__('When permanently deleting a post, delete it’s sister translations.', NL_TXTDMN)
		);
	}, 'nLingual', 'nLingual-options');

	add_settings_field('erase_translations', __('Erase translation data?', NL_TXTDMN), function(){
		$erase_url = admin_url(sprintf('?_nL_nonce=%s', wp_create_nonce('nLingual_erase_translations')));
		printf(
			'<label><a href="%s" id="erase_translations" class="button-primary">%s</a></label>',
			$erase_url,
			__('Clear the translations table for this site?', NL_TXTDMN)
		);
		printf(
			'<p class="description">%s</p>',
			__('This will erase all language information, and translation links, for all posts (actual posts will be unaffected).', NL_TXTDMN)
		);
	}, 'nLingual', 'nLingual-options');

	// Add Syncornization Rule managers for each post type
	foreach(nL_post_types() as $post_type){
		$post_type = get_post_type_object($post_type);
		add_settings_field($post_type->name.'-sync_rules', $post_type->labels->name, function() use ($post_type, $sync_rules){
			$pt = $post_type->name;
			$sync_data_rules = nL_sync_rules($pt, 'data');
			printf(
				'<label>%s</label><br>',
				_f('Check off the fields that should be synchronized between sister %s.', NL_TXTDMN,
					strtolower($post_type->labels->name))
			);
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
				printf(
					'<br/><label>%s</label><br>',
					__('Check off the taxonomies that should be synchronized.', NL_TXTDMN)
				);
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
			printf(
				'<br/><label for="%s-meta-sync_rules">%s</label><br>',
				$pt,
				__('List the meta field that should be synchronized, one per line.', NL_TXTDMN)
			);
			printf(
				'<textarea id="%s-meta-sync_rules" name="nLingual-sync_rules[%s][meta]" class="large-text code" rows="5">%s</textarea>',
				$pt,
				$pt,
				implode("\n", $sync_meta_rules)
			);
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
	global $nLingual_preset_languages;
	$languages = nL_languages();
	?>
	<select id="nLingual_language_preset">
		<option value=""><?php _e('Custom', NL_TXTDMN)?></option>
		<?php
		foreach($nLingual_preset_languages as $lang => $data)
			printf('<option value="%s" title="%s">%s</option>', $lang, $data['native_name'], $data['system_name']);
		?>
	</select>
	<button id="nLingual_add_language" type="button" class="button-secondary"><?php _e('Add Language', NL_TXTDMN)?></button>

	<table id="nLingual_languages" class="widefat">
		<thead>
			<tr>
				<th class="language-default">Default?</th>
				<th class="language-system_name">System Name</th>
				<th class="language-native_name">Native Name</th>
				<th class="language-short_name" title="<?php _e('A shorthand name for the language.', NL_TXTDMN)?>">Short Name</th>
				<th class="language-mo" title="<?php _e('The name (minus extension) of the .MO file use for localization.', NL_TXTDMN)?>">.MO File</th>
				<th class="language-slug" title="<?php _e('A unique identifier for this language.', NL_TXTDMN)?>">Slug</th>
				<th class="language-iso" title="<?php _e('The official 2 letter code identifying this language.', NL_TXTDMN)?>">ISO</th>
				<th class="language-delete">Delete?</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($languages as $language) _nLingual_language_editor(get_object_vars($language))?>
		</tbody>
	</table>

	<script id="nLingual_preset_languages" type="text/javascript">var nLingual_preset_languages = <?php echo json_encode($nLingual_preset_languages)?>;</script>

	<script id="nLingual_language_template" type="text/template">
		<?php _nLingual_language_editor()?>
	</script>
	<?php
}

function _nLingual_language_editor($language = array()){
	$language = array_map('esc_textarea', $language);

	extract(array_merge(array(
		'lang_id'=>'-1',
		'system_name'=>'',
		'native_name'=>'',
		'short_name'=>'',
		'mo'=>'',
		'slug'=>'',
		'iso'=>'',
		'list_order'=>''
	), $language));

	$default = nL_get_option('default_lang');
	?>
	<tr>
		<td class="language-default">
			<input type="radio" name="nLingual-options[default_lang]" value="<?php echo $lang_id?>" <?php if($lang_id && $default == $lang_id) echo 'checked'?>>
			<input type="hidden" name="nLingual-languages[<?php echo $lang_id?>][list_order]" value="<?php echo $list_order?>" class="list_order">
		</td>
		<td class="language-system_name">
			<input type="text" name="nLingual-languages[<?php echo $lang_id?>][system_name]" value="<?php echo $system_name?>">
		</td>
		<td class="language-native_name">
			<input type="text" name="nLingual-languages[<?php echo $lang_id?>][native_name]" value="<?php echo $native_name?>">
		</td>
		<td class="language-short_name" title="<?php _e('A shorthand name for the language.', NL_TXTDMN)?>">
			<input type="text" name="nLingual-languages[<?php echo $lang_id?>][short_name]" value="<?php echo $short_name?>">
		</td>
		<td class="language-mo" title="<?php _e('The name (minus extension) of the .MO file use for localization.', NL_TXTDMN)?>">
			<input type="text" name="nLingual-languages[<?php echo $lang_id?>][mo]" value="<?php echo $mo?>">
		</td>
		<td class="language-slug" title="<?php _e('A unique identifier for this language.', NL_TXTDMN)?>">
			<input type="text" name="nLingual-languages[<?php echo $lang_id?>][slug]" value="<?php echo $slug?>">
		</td>
		<td class="language-iso" title="<?php _e('The official 2 letter code identifying this language.', NL_TXTDMN)?>">
			<input type="text" name="nLingual-languages[<?php echo $lang_id?>][iso]" value="<?php echo $iso?>">
		</td>
		<td class="language-delete">
			<input type="checkbox" name="nLingual-delete[]" value="<?php echo $lang_id?>">
		</td>
	</tr>
	<?php
}