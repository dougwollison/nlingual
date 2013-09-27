<?php
// ====================================== //
//	Hooks for Processing Exteral Options  //
// ====================================== //

/*
 * Handle the erasing of the translations table
 * Handle the rewriting of the languages table
 */
add_action('admin_init', 'nLingual_process_options');
function nLingual_process_options(){
	global $wpdb;
	
	// Handle erasing of translation table.
	if(isset($_GET['_nL_nonce']) && !wp_verify_nonce($_GET['_nL_nonce'], 'nLingual_erase_translations')){
		// Truncate  the translations table
		$wpdb->query("TRUNCATE TABLE $wpdb->nL_translations");

		// Redirect back to the nLingual page with the notice that the table was cleared.
		$goback = add_query_arg('nLingual-erase', 'true',  wp_get_referer());
		wp_redirect($goback);
		exit;
	}
	
	if(isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'nLingual-options') && isset($_POST['nLingual-languages'])){
		// First, run through and process the deletes
		if(isset($_POST['nLingual-delete']) && is_array($_POST['nLingual-delete'])){
			foreach($_POST['nLingual-delete'] as $lang_id){
				unset($_POST['nLingual-languages'][$lang_id]);
				if($lang_id > 0){
					$wpdb->delete($wpdb->nL_languages, array('lang_id' => $lang_id));
					$wpdb->delete($wpdb->nL_translations, array('lang_id' => $lang_id));
				}
			}
		}
		
		// Now, update/insert the remaining languages
		foreach($_POST['nLingual-languages'] as $lang_id => $data){
			// Make sure $lang_id is an integer
			$lang_id = intval($lang_id);
		
			// Build the $values array	
			$values = array(
				'system_name' => stripslashes($data['system_name']),
				'native_name' => stripslashes($data['native_name']),
				'short_name' => stripslashes($data['short_name']),
				'mo' => preg_replace('/[^\w-]/', '', $data['mo']),
				'slug' => substr(preg_replace('/[^a-z]/', '', strtolower($data['slug'])), 0, 2),
				'iso' => substr(preg_replace('/[^a-z]/', '', strtolower($data['iso'])), 0, 2),
				'list_order' => intval($data['list_order'])
			);
			
			// Build the $formats array
			$formats = array('%s', '%s', '%s', '%s', '%s', '%s', '%d');
				
			if($lang_id > 0){
				// Exisiting language, update
				$wpdb->update($wpdb->nL_languages, $values, array('lang_id' => $lang_id), $formats, array('%d'));
			}else{
				// New language, insert
				$wpdb->replace($wpdb->nL_languages, $values, $formats);
			}
		}
	}
}