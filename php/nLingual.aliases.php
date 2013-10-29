<?php
/*
 * Aliases to nLingual methods.
 */

use nLingual as nL;

/**
 * @see nLingual::log_textdomain()
 */
function nL_log_textdomain($domain, $mofile, $force = false){
	return nl::log_textdomains($domain, $mofile, $force);
}

/**
 * @see nLingual::get_option()
 */
function nL_get_option($name){
	return nL::get_option($name);
}

/**
 * @see nLingual::sync_rules()
 */
function nL_sync_rules($post_type, $rule_type = null){
	return nL::sync_rules($post_type, $rule_type);
}

/**
 * @see nLingual::languages()
 */
function nL_languages(){
	return nL::languages();
}

/**
 * @see nLingual::langs_by_id()
 */
function nL_langs_by_id(){
	return nL::langs_by_id();
}

/**
 * @see nLingual::langs_by_slug()
 */
function nL_langs_by_slug(){
	return nL::langs_by_slug();
}

/**
 * @see nLingual::post_types()
 */
function nL_post_types(){
	return nL::post_types();
}

/**
 * @see nLingual::default_lang()
 */
function nL_default_lang(){
	return nL::default_lang();
}

/**
 * @see nLingual::current_lang()
 */
function nL_current_lang(){
	return nL::current_lang();
}

/**
 * @see nLingual::other_lang()
 */
function nL_other_lang(){
	return nL::other_lang();
}

/**
 * @see nLingual::is_lang()
 */
function nL_is_lang($lang){
	return nL::is_lang($lang);
}

/**
 * @see nLingual::is_default()
 */
function nL_is_default(){
	return nL::is_default();
}

/**
 * @see nLingual::cache_get()
 */
function nL_cache_get($id, $section){
	return nL::cache_get($id, $section);
}

/**
 * @see nLingual::cache_set()
 */
function nL_cache_set($id, $lang, $section){
	return nL::cache_set($id, $lang, $section);
}

/**
 * @see nLingual::lang_exists()
 */
function nL_lang_exists($lang){
	return nL::lang_exists($lang);
}

/**
 * @see nLingual::post_type_supported()
 */
function nL_post_type_supported($type, $all = true){
	return nL::post_type_supported($type, $all);
}

/**
 * @see nLingual::admin_only()
 */
function nL_admin_only(){
	return nL::admin_only();
}

/**
 * @see nLingual::get_lang()
 */
function nL_get_lang($field = null, $lang = null){
	return nL::get_lang($field, $lang);
}

/**
 * @see nLingual::lang_id()
 */
function nL_lang_id($slug = null){
	return nL::lang_id($slug);
}

/**
 * @see nLingual::lang_slug()
 */
function nL_lang_slug($lang_id = null){
	return nL::lang_slug($lang_id);
}

/**
 * @see nLingual::set_lang()
 */
function nL_set_lang($lang, $lock = true){
	return nL::set_lang($lang, $lock);
}

/**
 * @see nLingual::switch_lang()
 */
function nL_switch_lang($lang){
	return nL::switch_lang($lang);
}

/**
 * @see nLingual::restore_lang()
 */
function nL_restore_lang(){
	return nL::restore_lang();
}

/**
 * @see nLingual::query_var()
 */
function nL_query_var(){
	return nL::query_var();
}

/**
 * @see nLingual::get_post_lang()
 */
function nL_get_post_lang($id = null){
	return nL::get_post_lang($id);
}

/**
 * @see nLingual::set_post_lang()
 */
function nL_set_post_lang($id = null, $lang = null){
	return nL::set_post_lang($id, $lang);
}

/**
 * @see nLingual::delete_post_lang()
 */
function nL_delete_post_lang($id = null, $lang = null){
	return nL::delete_post_lang($id, $lang);
}

/**
 * @see nLingual::in_this_lang()
 */
function nL_in_this_lang($id = null, $lang){
	return nL::in_this_lang($id, $lang);
}

/**
 * @see nLingual::in_default_lang()
 */
function nL_in_default_lang($id = null){
	return nL::in_default_lang($id);
}

/**
 * @see nLingual::in_current_lang()
 */
function nL_in_current_lang($id = null){
	return nL::in_current_lang($id);
}

/**
 * @see nLingual::in_other_lang()
 */
function nL_in_other_lang($id = null){
	return nL::in_other_lang($id);
}

/**
 * @see nLingual::get_translation()
 */
function nL_get_translation($id, $lang = null, $return_self = true){
	return nL::get_translation($id, $lang, $return_self);
}

/**
 * @see nLingual::get_translation()
 */
function nL_unlink_translation($id, $lang){
	return nL::get_translation($id, $lang);
}

/**
 * @see nLingual::associate_posts()
 */
function nL_associate_posts($post_id, $posts){
	return nL::associate_posts($post_id, $posts);
}

/**
 * @see nLingual::associated_posts()
 */
function nL_associated_posts($post_id, $include_self = false){
	return nL::associated_posts($post_id, $include_self);
}

/**
 * @see nLingual::build_url()
 */
function nL_build_url($data){
	return nL::build_url($data);
}

/**
 * @see nLingual::process_domain()
 */
function nL_process_domain($host, &$lang = null){
	return nL::process_domain($host, $lang);
}

/**
 * @see nLingual::process_path()
 */
function nL_process_path($uri, &$lang = null){
	return nL::process_path($uri, $lang);
}

/**
 * @see nLingual::process_url()
 */
function nL_process_url($url_data = null){
	return nL::process_url($url_data);
}

/**
 * @see nLingual::localize_url()
 */
function nL_localize_url($url = null, $lang = null, $relocalize = false){
	return nL::localize_url($url, $lang, $relocalize);
}

/**
 * @see nLingual::delocalize_url()
 */
function nL_delocalize_url($url){
	return nL::delocalize_url($url);
}

/**
 * @see nLingual::get_permalink()
 */
function nL_get_permalink($id = null, $lang = null){
	return nL::get_permalink($id, $lang);
}

/**
 * @see nLingual::the_permalink()
 */
function nL_the_permalink($id = null, $lang = null){
	return nL::the_permalink($id, $lang);
}

/**
 * @see nLingual::translate_link()
 */
function nL_translate_link($path, $post_type = null, $lang = null, $echo = true){
	return nL::translate_link($path, $post_type, $lang, $echo);
}

/**
 * @see nLingual::localize_here()
 */
function nL_localize_here($lang = null){
	return nL::localize_here($lang);
}

/**
 * @see nLingual::maybe_redirect()
 */
function nL_maybe_redirect(){
	return nL::maybe_redirect();
}

/**
 * @see nLingual::get_lang_links()
 */
function nL_get_lang_links($skip_current = false){
	return nL::get_lang_links($skip_current);
}

/**
 * @see nLingual::print_lang_links()
 */
function nL_print_lang_links($prefix = '', $sep = ' ', $skip_current = false){
	return nL::print_lang_links($prefix, $sep, $skip_current);
}

/**
 * @see nLingual::split_langs()
 */
function nL_split_langs($text, $lang = null, $sep = null, $force = false){
	return nL::split_langs($text, $lang, $sep, $force);
}

/**
 * @see nLingual::reload_textdomains()
 */
function nL_reload_textdomains($old_locale, $new_locale){
	return nL::reload_textdomains($old_locale, $new_locale);
}

// Depreciated methods

/**
 * @see nLingual::lang_links()
 */
function nL_lang_links($echo = false, $prefix = '', $sep = ' ', $skip_current = false){
	return nL::lang_links($echo, $prefix, $sep, $skip_current);
}