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

	// Handle creating new translation
	$( '.nl-translation' ).change( function( e ) {
		var $input = $( this );
		var value = $input.val();
		var post_id = $( '#post_ID' ).val();
		var lang_id = $input.parents( '.nl-field' ).data( 'langid' );

		if ( value === 'new' ) {
			// Ask for a title for the translation
			var title = $( '#title' ).val();
			var placeholder = nlingualL10n.TranslationTitlePlaceholder
				.replace( /%s/, NL_LANGUAGES[ lang_id ].system_name )
				.replace( /%s/, title );
			var translation_title = prompt( nlingualL10n.TranslationTitle, placeholder );

			// Abort if empty or null
			if ( translation_title === null || translation_title === '' ) {
				e.preventDefault();
				$input.val( -1 );
				return false;
			}

			$.ajax( {
				url: ajaxurl,
				data: {
					action: 'nl_new_translation',
					original: post_id,
					language: lang_id,
					title: translation_title,
					custom_title: translation_title === placeholder
				},
				type: 'post',
				dataType: 'json',
				success: function( data ) {
					// Replace the New option with that of the new post
					$input.find( '.nl-new-translation' ).attr( 'value', data.id ).text( nlingualL10n.NewTranslation + ' ' + data.title );
				},
				error: function() {
					alert( nlingualL10n.NewTranslationError );
					$input.val( -1 );
				}
			} );
		}
	} );

	// Open the editor for the selected translation
	$( '.nl-edit-translation' ).click( function() {
		// Get the parent field
		var $field = $( this ).parents( '.nl-field' );

		// Get the selected value
		var target = $field.find( '.nl-input' ).val();

		// Throw error if target isn't a valid post
		if ( target === 'new' || parseInt( target ) <= 0 ) {
			alert( nlingualL10n.NoPostSelected );
			return;
		}

		// Build the edit URL and open in a new tab
		var url = $( this ).data( 'url' ).replace( '%d', target );
		window.open( url );
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