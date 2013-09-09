<?php
add_action('add_meta_boxes', function(){
	foreach(theme_post_types() as $type){
		add_meta_box(
			'translations',
			'Translations',
			'translations_metabox',
			$type
		);
	}
});

function translations_metabox($post){
	global $wpdb;
	wp_nonce_field(__FILE__, 'foresite_translations');

	if(in_default_language($post)){
		global $languages;
		foreach($languages as $lang => $data){
			if($lang == DEFAULT_LANG) continue;

			$tp = get_post_meta($post->ID, "_translated_$lang", true);

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
			<p>Or... <a href="<?php echo admin_url()?>?new_translation=<?php echo $post->ID?>&language=<?php echo $lang?>&_fsnonce=<?php echo wp_create_nonce(__FILE__)?>">Create a new translated <?php echo $post->post_type?></a></p>
			<?php
		}
	}else{
		$lang = get_language($post->ID);
		$lang_posts = get_posts(array(
			'post_type' => $post->post_type,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'language' => DEFAULT_LANG,
			'orderby' => 'post_title',
			'order' => 'ASC',
		));
		?>
		<p>
			<?php if($op = get_original_post($post->ID, false)):?>
			<a href="<?php echo admin_url('/post.php')?>?post_type=<?php echo get_post_type($op)?>&post=<?php echo $op?>&action=edit" class="button" target="_blank">Edit "<?php echo $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = $op")?>"</a>
			Or...
			<?php endif;?>
		</p>
		<p><label>
			Make this a translation of:
			<select name="translation_of">
				<option value="-1">Select <?php echo get_curlang('name', DEFAULT_LANG)?> <?php echo $post->post_type?></option>
			<?php foreach($lang_posts as $lang_post):?>
				<option value="<?php echo $lang_post->ID?>" <?php if(get_translated_post($lang_post->ID, $lang) == $post->ID) echo 'selected'?>><?php echo $lang_post->post_title?></option>
			<?php endforeach;?>
			</select>
		</label></p>
		<?php
	}
}

add_action('restrict_manage_posts', function(){
	global $typenow;
	if(in_array($typenow, theme_post_types())):
		$selected = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';
		?>
		<select name="language" class="postform">
			<option value="">Show all languages</option>
			<?php select_category_options('language');?>
		</select>
	<?php endif;
});
	function select_category_options($tax, $parent = 0, $level = 0){
		$cats = get_terms($tax, array(
			'orderby' => 'name',
			'hide_empty' => false,
			'parent' => $parent
		));
		foreach($cats as $cat){
		    echo '<option class="level-'.$level.'" value="'.$cat->slug.'"'.($_GET[$tax] == $cat->slug ? ' selected' : '').'>'.str_repeat('&nbsp;&nbsp;&nbsp;', $level).$cat->name.'</option>';
		    select_category_options($tax, $cat->term_id, $level+1);
		}
	}

add_filter('parse_query', function(&$query){
	if(is_admin() && isset($_REQUEST['lang'])){
		$query->query_vars['tax_query'][] = array(
			'taxonomy' => 'language',
			'terms' => $_REQUEST['lang'],
		);
	}
});

add_action('save_post', function($post_id, $post){
	global $wpdb;

	if($post->post_status == 'publish'){
		$terms = wp_get_object_terms($post_id, 'language');

		if(is_array($terms) && empty($terms)){
			wp_set_object_terms($post_id, 'en', 'language');
		}
	}

	if($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;
	elseif($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) return;

	if(!isset($_POST['foresite_translations']) || !wp_verify_nonce($_POST['foresite_translations'], __FILE__))return;

	if(isset($_POST['translations'])){
		foreach($_POST['translations'] as $lang => $id){
			update_post_meta($post_id, "_translated_$lang", $id);
		}
	}

	if(isset($_POST['translation_of'])){
		$target = $_POST['translation_of'];

		$lang = get_language($post_id);
		if($lang == DEFAULT_LANG) return;

		if($target == -1){
			$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", "_translated_$lang", $post_id));
		}else{
			update_post_meta($target, "_translated_$lang", $post_id);
		}
	}
}, 999, 2);

add_action('admin_init', function(){
	global $wpdb;
	if(isset($_GET['new_translation'])){
		$post_id = $_GET['new_translation'];

		if(!isset($_GET['_fsnonce']) || !wp_verify_nonce($_GET['_fsnonce'], __FILE__))
			wp_die('You do not have permission to do that');

		if(!in_default_language($post_id))
			wp_die('This object is not in the default language');

		if(!isset($_GET['language']) || !($lang = get_term_by('slug', $_GET['language'], 'language')))
			wp_die('Invalid language');

		$orig = $wpdb->get_row($wpdb->prepare("SELECT post_title, post_type, post_content, post_excerpt, post_parent, menu_order FROM $wpdb->posts WHERE ID = %d", $post_id), ARRAY_A);
		$orig_meta = get_post_meta($post_id);
		$orig_taxs = get_object_taxonomies($orig['post_type']);
		$tax_query = array();

		foreach($orig_taxs as $tax){
			if($tax == 'language') continue;
			$terms = wp_get_post_terms($post_id, $tax, array('fields' => 'ids'));
			$tax_query[$tax] = $terms;
		}

		$tax_query['language'] = $lang->term_id;

		$args = $orig;
		$args['tax_input'] = $tax_query;
		$args['post_status'] = 'draft';
		$args['post_title'] = "Translate to $lang->name: {$args['post_title']}";
		$args['post_parent'] = get_translated_post($orig['post_parent'], $lang->slug);

		$new = wp_insert_post($args);

		foreach($orig_meta as $key => $value){
			if(strpos($key, '_translated_') === false){
				foreach($value as $val){
					add_post_meta($new, $key, maybe_unserialize($val));
				}
			}
		}

		update_post_meta($post_id, "_translated_$lang->slug", $new);

		header('Location: '.admin_url("/post.php?post=$new&action=edit"));
		exit;
	}
});