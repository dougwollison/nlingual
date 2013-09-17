<?php
/*
 * Add traslations metabox
 */
add_action('add_meta_boxes', 'nLingual_add_meta_box');
function nLingual_add_meta_box(){
	foreach(nL_post_types() as $type){
		add_meta_box(
			'language',
			'Language',
			'nLingual_language_metabox',
			$type
		);
		add_meta_box(
			'translations',
			'Translations',
			'nLingual_translations_metabox',
			$type
		);
	}
}

function nLingual_language_metabox($post){
	?>
	<select name="language" style="width:100%">
	<?php foreach(nL_languages() as $slug => $lang): $lang = get_term_by('slug', $slug, 'language');?>
		<option value="<?php echo $lang->term_id?>" <?php if(nL_in_this_lang($post->ID, $slug)) echo 'selected'?>><?php echo $lang->name?></option>
	<?php endforeach;?>
	</select>
	<?php
}

function nLingual_translations_metabox($post){
	global $wpdb;
	wp_nonce_field(__FILE__, 'nLingual_translations');

	if(nL_in_default_lang($post)){
		// If in the default language (the original) offer a list of posts in each language to select as the translated version
		foreach(nL_languages() as $lang => $data){
			if($lang == nL_default_lang()) continue;

			$tp = get_post_meta($post->ID, "_translated_$lang", true);

			// Get a list of available posts in the selected language
			$lang_posts = get_posts(array(
				'post_type' => $post->post_type,
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'language' => $lang,
				'orderby' => 'post_title',
				'order' => 'ASC',
			));

			?>
			<p>
				<?php if($tp > 0):?>
				<a href="<?php echo admin_url('/post.php')?>?post_type=<?php echo get_post_type($tp)?>&post=<?php echo $tp?>&action=edit" class="button" target="_blank">Edit "<?php echo $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = $tp")?>"</a>
				Or...
				<?php endif;?>
			</p>
			<p><label>

				Assign <?php echo $data['name']?> post:
				<select name="translations[<?php echo $lang?>]">
					<option value="-1">Select <?php echo $post->post_type?></option>
				<?php foreach($lang_posts as $lang_post): ?>
					<option value="<?php echo $lang_post->ID?>" <?php if($lang_post->ID == $tp) echo 'selected'?>><?php echo $lang_post->post_title?></option>
				<?php endforeach;?>
				</select>
			</label></p>
			<p>Or... <a href="<?php echo admin_url()?>?nL_new_translation=<?php echo $post->ID?>&language=<?php echo $lang?>&_nL_nonce=<?php echo wp_create_nonce(__FILE__)?>">Create a new translated <?php echo $post->post_type?></a></p>
			<?php
		}
	}else{
		// Otherwise, provide a list of posts in the default language to select as the original version
		$lang = nL_get_post_lang($post->ID);
		$lang_posts = get_posts(array(
			'post_type' => $post->post_type,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'language' => nL_default_lang(),
			'orderby' => 'post_title',
			'order' => 'ASC',
		));
		?>
		<p>
			<?php if($op = nL_get_original_post($post->ID, false)):?>
			<a href="<?php echo admin_url('/post.php')?>?post_type=<?php echo get_post_type($op)?>&post=<?php echo $op?>&action=edit" class="button" target="_blank">Edit "<?php echo $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = $op")?>"</a>
			Or...
			<?php endif;?>
		</p>
		<p><label>
			Make this a translation of:
			<select name="translation_of">
				<option value="-1">Select <?php echo get_curlang('name', DEFAULT_LANG)?> <?php echo $post->post_type?></option>
			<?php foreach($lang_posts as $lang_post):?>
				<option value="<?php echo $lang_post->ID?>" <?php if(nL_get_translated_post($lang_post->ID, $lang) == $post->ID) echo 'selected'?>><?php echo $lang_post->post_title?></option>
			<?php endforeach;?>
			</select>
		</label></p>
		<?php
	}
}

/*
 * Save post hook for saving and updating translation links
 */
add_action('save_post', 'nLingual_save_post', 999, 2);
function nLingual_save_post($post_id, $post){
	global $wpdb;

	if($post->post_status == 'publish'){
		$terms = wp_get_object_terms($post_id, 'language');

		if(is_array($terms) && empty($terms)){
			wp_set_object_terms($post_id, nL_default_lang(), 'language');
		}
	}

	if($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;
	elseif($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;

	if(!isset($_POST['nLingual_translations']) || !wp_verify_nonce($_POST['nLingual_translations'], __FILE__))return;

	if(isset($_POST['translations'])){
		// Update the translation status of each post selected
		foreach($_POST['translations'] as $lang => $id){
			update_post_meta($post_id, "_translated_$lang", $id);
		}
	}

	if(isset($_POST['translation_of'])){
		// Update the translation status of this post
		$target = $_POST['translation_of'];

		$lang = nL_get_post_lang($post_id);
		if($lang == nL_default_lang()) return;

		if($target == -1){
			$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", "_translated_$lang", $post_id));
		}else{
			update_post_meta($target, "_translated_$lang", $post_id);
		}
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
	if(isset($_GET['new_translation'])){
		$post_id = $_GET['new_translation'];

		if(!isset($_GET['_nL_nonce']) || !wp_verify_nonce($_GET['_nL_nonce'], __FILE__))
			wp_die('You do not have permission to do that.');

		if(!nL_in_default_language($post_id))
			wp_die('This object is not in the default language.');

		if(!isset($_GET['language']) || !($lang = get_term_by('slug', $_GET['language'], 'language')))
			wp_die('Invalid language.');

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
		$tax_query['language'] = $lang->term_id;

		// Build the arguments for wp_insert_args
		$args = $orig;
		$args['tax_input'] = $tax_query;

		// Set the status to draft and update the title to flag it as needing translation
		$args['post_status'] = 'draft';
		$args['post_title'] = "Translate to $lang->name: {$args['post_title']}";

		// Set the post parent to be the translated parent if available
		$args['post_parent'] = nL_get_translated_post($orig['post_parent'], $lang->slug);

		// Inser the new post
		$new = wp_insert_post($args);

		// Loop through the metadata and apply it to the new post (except the _translated_[lang] field, not that that should exist anyway)
		foreach($orig_meta as $key => $value){
			if(strpos($key, '_translated_') === false){
				foreach($value as $val){
					add_post_meta($new, $key, maybe_unserialize($val));
				}
			}
		}

		// Set the translation status
		update_post_meta($post_id, "_translated_$lang->slug", $new);

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
			<option value="">Show all languages</option>
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
 * Enqueue admin styles/scripts
 */
add_action('admin_enqueue_scripts', 'nLingual_enqueue_scripts');
function nLingual_enqueue_scripts(){
	wp_enqueue_style('nLingual-admin', plugins_url('/nLingual/css/admin.css'), '1.0', 'screen');
	wp_enqueue_script('nLingual-admin-js', plugins_url('/nLingual/js/admin.js'), array('jquery-ui-sortable'), '1.0');
}