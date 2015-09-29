/* globals console, inlineEditPost, inlineEditTax */

jQuery( function( $ ) {
	// Update visible translation fields based on current language
	$( '#nlingual_language' ).change( function() {
		var lang_id = $( this ).val();

		// Show all translation fields by default
		$( '.nl-translation-field' ).show();

		// Hide the one for the current language
		$( '#nlingual_lang' + lang_id ).hide();

	} ).change(); // Update on page load

	// Open the editor for the selected translation
	$( '.nl-edit-translation' ).click( function() {
		// Get the parent field
		var $field = $( this ).parents( '.nl-field' );

		// Get the selected value
		var target = $field.find( '.nl-input' ).val();
	} );

	// =========================
	// ! WP API Extensions
	// =========================

	// Extend inlineEditPost if available
	if ( typeof inlineEditPost === 'object' ) {
		var wpInlineEditPost_edit = inlineEditPost.edit;
		inlineEditPost.edit = function() {
			// Start by calling the original for default behaviour
			wpInlineEditPost_edit.apply( this, arguments );
		};
	}

	// Extend inlineEditTax if available
	if ( typeof inlineEditTax === 'object' ) {
		var wpInlineEditTax_edit = inlineEditTax.edit;
		inlineEditTax.edit = function() {
			// Start by calling the original for default behaviour
			wpInlineEditTax_edit.apply( this, arguments );
		};
	}
} );