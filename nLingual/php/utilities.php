<?php
function get_post_id_by_name($name){
	global $wpdb;
	return $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type != 'revision'", $name));
}

function _j($text, $domain = 'default'){
	echo json_encode(__($text, $domain));
}

function _jx($text, $context, $domain = 'default'){
	echo json_encode(_x($text, $context, $domain));
}

function _f($text, $domain = 'default'){
	$args = func_get_args();
	$args = array_slice($args, 2);

	return vsprintf(__($text, $domain), $args);
}

function _ef($text, $domain = 'default'){
	$args = func_get_args();
	$args = array_slice($args, 2);

	vprintf(__($text, $domain), $args);
}

function _a($array, $domain = 'default'){
	$_array = array();
	foreach($array as $text){
		$_array[] = __($text, $domain);
	}

	return $_array;
}

function _ax($array, $context, $domain = 'default'){
	$_array = array();
	foreach($array as $text){
		$_array[] = _x($text, $context, $domain);
	}

	return $_array;
}