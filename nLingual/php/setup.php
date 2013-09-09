<?php
global $languages, $curlang, $curlang_cache, $langcache;
$curlang = $curlang_cache = DEFAULT_LANG;

add_action('init', function(){
	register_taxonomy(
		'language',
		theme_post_types(),
		array(
		    'hierarchical' => true,
		    'labels' => array(
				'name' => 'Language',
				'singular_name' => 'Language',
				'search_items' => 'Search Languages',
				'popular_items' => 'Popular Languages',
				'all_items' => 'All Languages',
				'parent_item' => null,
				'parent_item_colon' => null,
				'edit_item' => 'Edit Language',
				'update_item' => 'Update Language',
				'add_new_item' => 'Add New Language',
				'new_item_name' => 'New Language Name',
				'separate_items_with_commas' => 'Separate languages with commas',
				'add_or_remove_items' => 'Add or remove languages',
				'choose_from_most_used' => 'Choose from the most used languages',
				'not_found' => 'No languages found.',
				'menu_name' => 'Languages'
		    ),
		    'show_ui' => true,
		    'show_admin_column' => true,
		    'update_count_callback' => '_update_post_term_count',
		)
	);

	global $languages;
	foreach($languages as $lang => $data){
		if(!term_exists($lang, 'language')){
			wp_insert_term(
				$data['name'],
				'language',
				array(
					'slug' => $lang,
				)
			);
		}
	}
});