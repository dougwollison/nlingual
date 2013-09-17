<?php
/*
 * Aliases to nLingual methods
 */

use nLingual as nL;

function nL_domain($theme = false){
	return nL::domain($theme);
}

function nL_get_option($name){
	return nL::get_option($name);
}

function nL_languages(){
	return nL::languages();
}

function nL_post_types(){
	return nL::post_types();
}

function nL_default_lang(){
	return nL::default_lang();
}

function nL_cacheGet($id){
	return nL::cacheGet($id);
}

function nL_cacheSet($id, $lang){
	return nL::cacheSet($id, $lang);
}

function nL_lang_exists($lang){
	return nL::lang_exists($lang);
}

function nL_get_lang($field = null, $lang = null){
	return nL::get_lang($field, $lang);
}

function nL_set_lang($lang, $lock = true){
	return nL::set_lang($lang, $lock);
}

function nL_switch_lang($lang){
	return nL::switch_lang($lang);
}

function nL_restore_lang(){
	return nL::restore_lang();
}

function nL_get_post_lang($id = null, $default = null){
	return nL::get_post_lang($id, $default);
}

function nL_in_default_lang($id = null){
	return nL::in_default_lang($id);
}

function nL_in_current_lang($id){
	return nL::in_current_lang($id);
}

function nL_get_original_post($id = null, $return_self = true){
	return nL::get_original_post($id, $return_self);
}

function nL_get_translated_post($id, $lang = null, $return_self = true){
	return nL::get_translated_post($id, $lang, $return_self);
}

function nL_associated_posts($post_id, $include_self = true){
	return nL::associated_posts($post_id, $include_self);
}

function nL_get_permalink($id = null, $lang = null, $echo = true){
	return nL::get_permalink($id, $lang, $echo);
}

function nL_lang_links($echo = false, $prefix = '', $sep = ' '){
	return nL::lang_links($echo, $prefix, $sep);
}

function nL_split_langs($text, $lang = null, $sep = null, $force = false){
	return nL::split_langs($text, $lang, $sep, $force);
}