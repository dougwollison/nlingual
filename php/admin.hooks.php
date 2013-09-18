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
		if($lang == nL_get_post_lang()) continue;

		$translation = nL_get_translation($post->ID, $lang, false);

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
				<?php _ef('Create a new %1$s %2$s', NL_TXTDMN, $lang['name'], get_post_type_object($post->post_type)->labels->singular_name)?>
			</a>
		</p>
		<?php
	}
}

/*
 * Save post hook for saving and updating translation links
 */
add_action('save_post', 'nLingual_save_post', 999, 2);
function nLingual_save_post($post_id, $post){
	global $wpdb;

	// Abort if they don't have permission to edit posts/pages
	if($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;
	elseif($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;

	// Abort if nonce verification fails
	if(!isset($_POST['nLingual_translations']) || !wp_verify_nonce($_POST['nLingual_translations'], __FILE__))return;

	// Set the language
	if($_POST['language']){
		wp_set_object_terms($post_id, $_POST['language'], 'language');
		nL_set_post_lang($post_id, $_POST['language']);
	}

	if(isset($_POST['translations'])){
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
		$args['post_parent'] = nL_get_translation($orig['post_parent'], $lang->slug);

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
	if(in_array($typenow, nL_post_types())):
		$selected = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';
		?>
		<select name="language" class="postform">
			<option value=""><?php _e('Show all languages', NL_TXTDMN)?></option>
			<?php
			$langs = get_terms('language', array(
				'orderby' => 'name',
				'hide_empty' => false,
				'parent' => $parent
			));
			foreach($langs as $lang){
			    echo '<option value="'.$lang->slug.'"'.($_GET['language'] == $lang->slug ? ' selected' : '').'>'.$lang->name.'</option>';
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
	$columns['language'] = __('Language', nL_domain());

	return $columns;
}
function nL_do_language_column($column, $post_id){
	if($column == 'language'){
		// do language info
	}
}

/*
 * Enqueue admin styles/scripts
 */
add_action('admin_enqueue_scripts', 'nLingual_enqueue_scripts');
function nLingual_enqueue_scripts(){
	wp_enqueue_style('nLingual-admin', plugins_url('css/admin.css', NL_DIR), '1.0', 'screen');
	wp_enqueue_script('nLingual-admin-js', plugins_url('js/admin.js', NL_DIR), array('jquery-ui-sortable'), '1.0');
}