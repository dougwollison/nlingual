<?php
/*
 * Add traslations metabox
 */
add_action('add_meta_boxes', 'nLingual_add_meta_box');
function nLingual_add_meta_box(){
	foreach(nL_post_types() as $type){
		add_meta_box(
			'nLingual_language',
			__('Language', NL_TXTDMN),
			'nLingual_language_metabox',
			$type
		);
		add_meta_box(
			'nLingual_translations',
			__('Translations', NL_TXTDMN),
			'nLingual_translations_metabox',
			$type
		);
	}
}

function nLingual_language_metabox($post){
	?>
	<select name="language" style="width:100%">
	<?php foreach(nL_languages() as $slug => $lang):?>
		<option value="<?php echo $slug?>" <?php if(nL_in_this_lang($post->ID, $slug)) echo 'selected'?>><?php echo $lang['name']?></option>
	<?php endforeach;?>
	</select>
	<?php
}

function nLingual_translations_metabox($post){
	global $wpdb;
	wp_nonce_field(__FILE__, 'nLingual_translations');

	// Loop through each language and present controls for each translation
	foreach(nL_languages() as $lang => $data){
		if(nL_in_this_lang($post->ID, $lang)) continue;

		$translation = nL_get_translation($post->ID, $lang);

		// Get a list of available posts in the selected language
		$lang_posts = new WP_Query(array(
			'post_type' => $post->post_type,
			'posts_per_page' => -1,
			'language' => $lang,
			'orderby' => 'post_title',
			'order' => 'ASC',
		));
		?>
		<p>
			<strong><?php echo $data['name']?>:</strong>
			<select name="translations[<?php echo $lang?>]">
				<option value="-1"><?php _ex('None', 'no translation', NL_TXTDMN)?></option>
			<?php foreach($lang_posts->posts as $lang_post):?>
				<option value="<?php echo $lang_post->ID?>" <?php if($lang_post->ID == $translation) echo 'selected'?>><?php echo $lang_post->post_title?></option>
			<?php endforeach;?>
			</select>
			or <a href="<?php echo admin_url()?>?nL_new_translation=<?php echo $post->ID?>&language=<?php echo $lang?>&_nL_nonce=<?php echo wp_create_nonce(__FILE__)?>" class="button-secondary">
				<?php _ef('Create a new %1$s %2$s', NL_TXTDMN, strtolower(nL_get_lang('name', $lang)), strtolower(get_post_type_object($post->post_type)->labels->singular_name))?>
			</a>
		</p>
		<?php
	}
}

/*
 * Save post hook for saving and updating translation links
 */
add_action('save_post', 'nLingual_save_post', 999);
function nLingual_save_post($post_id){
	global $wpdb;

	// Abort if they don't have permission to edit posts/pages
	if($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;
	elseif($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;

	// Set the language if nLingual_language nonce is verified
	if(isset($_POST['nLingual_language']) && wp_verify_nonce($_POST['nLingual_language'], __FILE__) && isset($_POST['language'])){
		nL_set_post_lang($post_id, $_POST['language']);
	}

	// Update translations if nLingual_translations nonce is verified
	if(isset($_POST['nLingual_translations']) && wp_verify_nonce($_POST['nLingual_translations'], __FILE__) && isset($_POST['translations'])){
		nL_associate_posts($post_id, $_POST['translations']);
	}

	// Loop through the sync options, and syncronize the fields with it's associated posts
	$associated = nL_associated_posts($post_id);
	if($post_fields = nL_get_option('sync_post_fields')){

	}
	if($meta_fields = nL_get_option('sync_meta_fields')){

	}
	if($taxonomies = nL_get_option('sync_taxonomies')){

	}
}

/*
 * Delete post hook for updating translation links
 */
add_action('delete_post', 'nL_delete_translation', 999);

/*
 * Handle request for a new translation of a post
 * Will create a new post with the data copied over,
 * and direct you to the edit page of the new post
 */
add_action('admin_init', 'nLingual_new_translation');
function nLingual_new_translation(){
	global $wpdb;
	if(isset($_GET['nL_new_translation'])){
		$post_id = $_GET['nL_new_translation'];

		if(!isset($_GET['_nL_nonce']) || !wp_verify_nonce($_GET['_nL_nonce'], __FILE__))
			wp_die(__('You do not have permission to do that.', NL_TXTDMN));

		if(!isset($_GET['language']) || !nL_lang_exists($_GET['language']))
			wp_die(__('Invalid language.', NL_TXTDMN));

		$lang = $_GET['language'];

		// Load the original posts post/meta/tax data
		$orig = $wpdb->get_row($wpdb->prepare("SELECT post_title, post_type, post_content, post_excerpt, post_parent, menu_order FROM $wpdb->posts WHERE ID = %d", $post_id), ARRAY_A);
		$orig_meta = get_post_meta($post_id);
		$orig_taxs = get_object_taxonomies($orig['post_type']);
		$tax_query = array();

		// Loop through the taxonomies for this post, and get the terms (execpt for language)
		foreach($orig_taxs as $tax){
			if($tax == 'language') continue;
			$terms = wp_get_post_terms($post_id, $tax, array('fields' => 'ids'));
			$tax_query[$tax] = $terms;
		}

		// Set the language term to the requested language
		$tax_query['language'] = $lang;

		// Build the arguments for wp_insert_args
		$args = $orig;
		$args['tax_input'] = $tax_query;

		// Set the status to draft and update the title to flag it as needing translation
		$args['post_status'] = 'draft';
		$args['post_title'] = sprintf('Translate to %s: %s', nL_get_lang('name', $lang), $args['post_title']);

		// Set the post parent to be the translated parent if available
		$args['post_parent'] = nL_get_translation($orig['post_parent'], $lang);

		// Inser the new post
		$new = wp_insert_post($args);

		// Loop through the metadata and apply it to the new post (except the _translated_[lang] field, not that that should exist anyway)
		foreach($orig_meta as $key => $value){
			foreach($value as $val){
				add_post_meta($new, $key, maybe_unserialize($val));
			}
		}

		// Set the translation status
		nL_associate_posts($post_id, array($lang => $new));

		// Redirect them to the edit screen for the new post
		header('Location: '.admin_url("/post.php?post=$new&action=edit"));
		exit;
	}
}

/*
 * Add posts filter for language
 */
add_action('restrict_manage_posts', 'nLingual_manage_post_language_filter');
function nLingual_manage_post_language_filter(){
	global $typenow;
	if(nL_post_type_supported($typenow)):
		$selected = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';
		?>
		<select name="language" class="postform">
			<option value=""><?php _e('Show all languages', NL_TXTDMN)?></option>
			<?php
			foreach(nL_languages() as $lang){
			    echo '<option value="'.$lang['iso'].'"'.($_GET['language'] == $lang['iso'] ? ' selected' : '').'>'.$lang['name'].'</option>';
			}
			?>
		</select>
	<?php endif;
}

/*
 * Add language column to editor tables
 */
foreach(nL_post_types() as $post_type){
	if(in_array($post_type, array('post', 'page'))) $prefix = "manage_{$post_type}s";
	else $prefix = "manage_{$post_type}_posts";

	add_action($prefix.'_columns', 'nL_add_language_column');
	add_action($prefix.'_custom_column', 'nL_do_language_column', 10, 2);
}
function nL_add_language_column($columns){
	$columns['language'] = __('Language', NL_TXTDMN);

	return $columns;
}
function nL_do_language_column($column, $post_id){
	if($column == 'language'){
		// Print out the language it's in
		$lang = nL_get_post_lang($post_id);

		printf('<input type="hidden" value="%s">', $lang);

		if(!$lang){
			_ex('None', 'no language', NL_TXTDMN);
			return;
		}

		printf('<strong>%s</strong>', nL_get_lang('name', $lang));

		if($associated = nL_associated_posts($post_id)){
			foreach($associated as $lang => $pid){
				printf('<br>%s: <a href="%s">%s</a> | <a href="%s">View</a>',
					nL_get_lang('name', $lang),
					admin_url("/post.php?post=$pid&action=edit"),
					get_the_title($pid),
					get_permalink($pid)
				);
			}
		}
	}
}

/*
 * Quick edit field for language
 */
add_action('quick_edit_custom_box', 'nLingual_quick_edit_box');
function nLingual_quick_edit_box($column, $post_type){
	static $printNonce = TRUE;
    if($printNonce){
        $printNonce = FALSE;
        wp_nonce_field(__FILE__, 'nLingual_language');
    }
    ?>
    <?php if($column == 'language'):?>
    <fieldset class="inline-edit-col-right inline-edit-book">
      <div class="inline-edit-col column-<?php echo $column ?>">
        <label class="inline-edit-group">
        	<?php _e('Language', NL_TXTDMN)?>
        	<select name="language">
			<?php foreach(nL_languages() as $slug => $lang):?>
				<option value="<?php echo $slug?>"><?php echo $lang['name']?></option>
			<?php endforeach;?>
			</select>
        </label>
      </div>
    </fieldset>
    <?php endif;?>
    <?php
}

/*
 * Enqueue admin styles/scripts
 */
add_action('admin_enqueue_scripts', 'nLingual_enqueue_scripts');
function nLingual_enqueue_scripts(){
	// Settings styling
	wp_enqueue_style('nLingual-settings', plugins_url('css/settings.css', NL_SELF), '1.0', 'screen');

	// Settings javascript
	wp_enqueue_script('nLingual-settings-js', plugins_url('js/settings.js', NL_SELF), array('jquery-ui-sortable'), '1.0');

	// Quick-Edit javascript
	wp_enqueue_script('nLingual-quickedit-js', plugins_url('js/quickedit.js', NL_SELF), array('inline-edit-post'), '1.0', true);
}

/*
 * Langlinks for nav menus
 */
add_action('admin_head', 'nLingual_special_metaboxes');
function nLingual_special_metaboxes(){
	add_meta_box(
		'add-langlink',
		'Language Links',
		'nLingual_add_langlinks',
		'nav-menus',
		'side'
	);
}

function nLingual_add_langlinks(){
	global $_nav_menu_placeholder, $nav_menu_selected_id;

	?>
	<div class="posttypediv" id="langlink">
		<p>These links will go to the respective language versions of the current URL.</p>
		<div id="tabs-panel-langlink-all" class="tabs-panel tabs-panel-active">
			<ul id="pagechecklist-most-recent" class="categorychecklist form-no-clear">
			<?php $i = -1; foreach(nL_languages() as $lang):?>
				<li>
					<label class="menu-item-title">
						<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo $i?>][menu-item-object-id]" value="-1">
						<?php echo $lang['name']?>
					</label>
					<input type="hidden" class="menu-item-type" name="menu-item[<?php echo $i?>][menu-item-type]" value="langlink">
					<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $i?>][menu-item-title]" value="<?php echo $lang['native']?>">
					<input type="hidden" class="menu-item-url" name="menu-item[<?php echo $i?>][menu-item-object]" value="<?php echo $lang['iso']?>">
				</li>
			<?php $i--; endforeach;?>
			</ul>
		</div>

		<p class="button-controls">
			<span class="list-controls">
				<a href="/wp-admin/nav-menus.php?langlink-tab=all&amp;selectall=1#langlink" class="select-all">Select All</a>
			</span>

			<span class="add-to-menu">
				<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( __( 'Add to Menu' ) ); ?>" name="add-post-type-menu-item" id="submit-langlink" />
				<span class="spinner"></span>
			</span>
		</p>
	</div>
	<?php
}