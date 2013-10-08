<?php
// ========================= //
//	Metabox Hooks/Callbacks  //
// ========================= //

/**
 * add_meta_boxes action.
 *
 * Adds the language and translations meta boxes
 * to the post types registered with nLingual.
 *
 * @since 1.0.0
 *
 * @uses nL_post_types()
 */
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
add_action('add_meta_boxes', 'nLingual_add_meta_box');

/**
 * nLingual_language metabox callback.
 *
 * Prints out select input for choosing post language
 *
 * @since 1.2.0 Updated nonce/field names.
 * @since 1.0.0
 */
function nLingual_language_metabox($post){
	wp_nonce_field('nLingual_set_language', 'nL_lang_nonce');
	?>
	<select name="nL_language" style="width:100%">
	<?php foreach(nL_languages() as $lang):?>
		<option value="<?php echo $lang->slug?>" <?php if(nL_in_this_lang($post->ID, $lang->slug)) echo 'selected'?>><?php echo $lang->system_name?></option>
	<?php endforeach;?>
	</select>
	<?php
}

/**
 * nLingual_translations metabox callback.
 *
 * Prints out select inputs for choosing translations for each language.
 *
 * @since 1.2.0 Updated nonce/field names.
 * @since 1.0.0
 *
 * @uses nL_languages()
 * @uses nL_in_this_lang()
 * @uses nL_get_translation()
 *
 * @param WP_Post $post The post object.
 */
function nLingual_translations_metabox($post){
	wp_nonce_field('nLingual_set_translations', 'nL_link_nonce');

	// Loop through each language and present controls for each translation
	foreach(nL_languages() as $lang){
		if(nL_in_this_lang($post->ID, $lang->slug)) continue;

		$translation = nL_get_translation($post->ID, $lang->slug);

		// Get a list of available posts in the selected language
		$lang_posts = new WP_Query(array(
			'post_type' => $post->post_type,
			'posts_per_page' => -1,
			'language' => $lang->slug,
			'orderby' => 'post_title',
			'order' => 'ASC',
		));
		?>
		<p>
			<strong><?php echo $lang->system_name?>:</strong>
			<select name="nL_translations[<?php echo $lang->slug?>]" class="nL-translations">
				<option value="-1"><?php _e('None', NL_TXTDMN)?></option>
			<?php foreach($lang_posts->posts as $lang_post):?>
				<option value="<?php echo $lang_post->ID?>" <?php if($lang_post->ID == $translation) echo 'selected'?>><?php echo $lang_post->post_title?></option>
			<?php endforeach;?>
			</select>
			<a href="<?php echo admin_url("/post.php?post=%d&action=edit")?>" class="button-primary edit-translation"><?php _e('Edit', NL_TXTDMN)?></a>
			or <a href="<?php echo admin_url(sprintf('?nL_new_translation=%d&nL_language=%s&_nL_nonce=%s', $post->ID, $lang->slug, wp_create_nonce('nLingual_new_translation')))?>" class="button-secondary" target="_blank">
				<?php _ef('Create a new %1$s %2$s', NL_TXTDMN, strtolower($lang->system_name), strtolower(get_post_type_object($post->post_type)->labels->singular_name))?>
			</a>
		</p>
		<?php
	}
}