/* globals console, alert, prompt, ajaxurl, inlineEditPost, inlineEditTax, nlingualL10n, NL_LANGUAGES, NL_PRESETS */

jQuery( function( $ ) {
	// =========================
	// ! Setings Pages
	// =========================

	// Check all fields of a matching name
	$('.nl-checkall').change(function(){
		var name = $(this).data('name');
		$('input[name="'+name+'[]"]').attr('checked', this.checked);
	});
	$('.nl-matchall').change(function(){
		var name = $(this).data('name');
		$('[name="'+name+'"]').val(this.checked ? '*' : '');
	});

	// Hide all sections by default
	$( '.nl-section-content' ).hide();

	// Add toggle feature for sections
	$('.nl-section-toggle' ).each( function() {
		$( this ).data( 'text', $( this ).text() );
	} ).click( function() {
		var $toggle = $( this );
		var $section = $toggle.parent();

		$section.toggleClass( 'open' );
		var open = $section.hasClass( 'open' );

		$section.find( '.nl-section-content' ).animate( { height: 'toggle' } );

		$toggle.text( $toggle.data( open ? 'alt' : 'text' ) );
	} );

	// =========================
	// ! Language Manager
	// =========================

	$( '#nlingual_languages' ).each( function() {
		var langRowTemplate = $( '#nl_lang_row' ).text();
		var langRowIndex = -1;

		// Load preset selector
		var $preset = $( '#nl_lang_preset' );
		for ( var preset in NL_PRESETS ) {
			$preset.append( '<option value="' + preset + '">' + NL_PRESETS[ preset ].system_name + '</option>' );
		}

		// Row builder utility
		function buildLangRow( data ) {
			var row = langRowTemplate, regex;
			// Loop through properties and replace
			for ( var prop in data ) {
				regex = new RegExp( '%' + prop + '%', 'g' );
				row = row.replace( regex, data[ prop ] );
			}

			// Parse the row into a new element
			var $row = $( row );

			// Check active checkbox if true
			$row.find( '.nl-lang-active input' ).attr( 'checked', data.active );

			// Add the row to the table
			$( '#nl_lang_list' ).append( $row );
		}

		// Load table with current languages
		for ( var lang in NL_LANGUAGES ) {
			buildLangRow( NL_LANGUAGES[ lang ] );
		}

		// Add button functionality
		$( '#nl_lang_add' ).click( function() {
			var data, preset;

			// Check if preset was selected
			if ( $preset.val() ) {
				preset = $preset.val();
				data = NL_PRESETS[ preset ];
				data.iso_code = preset;

				// Reset preset selector
				$preset.val( null );
			} else {
				// Blank
				data = {
					system_name : '',
					native_name : '',
					short_name  : '',
					iso_code    : '',
					locale_name : '',
				};
			}

			// Default values
			data.lang_id = langRowIndex;
			data.slug    = data.iso_code;
			data.active  = true;

			buildLangRow( data );

			langRowIndex--;
		} );

		// Delete button functionality
		$( this ).on( 'change', '.nl-lang-delete input', function() {
			// Get the parent row
			var $row = $( this ).parents( 'tr' ).first();
			// Toggle delete class and inputs
			$row.toggleClass( 'todelete', this.checked );
			$row.find( 'input' ).not( this ).attr( 'disabled', this.checked );
		} );

		// Auto-fill locale_name, iso_code and slug
		$( this ).on( 'change', '.nl-lang-system_name input', function() {
			// Get the parent row
			var $row = $( this ).parents( 'tr' ).first();

			// Get the text
			var system_name = $( this ).val();

			// Get the other fields
			var $locale_name = $row.find( '.nl-lang-locale_name input' );
			var $iso_code    = $row.find( '.nl-lang-iso_code input' );
			var $slug        = $row.find( '.nl-lang-slug input' );

			// Guess values accordingly if not set
			if ( ! $iso_code.val() ) {
				// Assume first 2 characters of system name
				$iso_code.val( system_name.substr( 0, 2 ).toLowerCase() );
			}
			if ( ! $locale_name.val() ) {
				// Assume same as ISO code
				$locale_name.val( $iso_code.val() );
			}
			if ( ! $slug.val() ) {
				// Assume same as ISO code
				$slug.val( $iso_code.val() );
			}
		} );
	} );

	// =========================
	// ! Meta Box and Quick/Bulk Edit
	// =========================

	// Update visible translation fields based on current language
	$( '#nl_language' ).change( function() {
		var lang_id = $( this ).val();

		// Show all translation fields by default
		$( '.nl-translation' ).show();

		// Hide the one for the current language
		$( '#nl_translation_' + lang_id ).hide();

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
					action       : 'nl_new_translation',
					post_id      : post_id,
					lang_id      : lang_id,
					title        : translation_title,
					custom_title : translation_title === placeholder
				},
				type: 'post',
				dataType: 'json',
				success: function( data ) {
					if ( data === 0 ) {
						return this.error();
					}

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
		inlineEditPost.edit = function( id ) {
			// Start by calling the original for default behaviour
			wpInlineEditPost_edit.apply( this, arguments );

			// get the post ID
			var post_id = 0;
			if( typeof id === 'object' ) {
				post_id = parseInt( this.getId( id ) );
			}

			// Get the post and edit rows
			var $postRow = $( '#post-' + post_id ),
				$editRow = $( '#edit-' + post_id );

			// Update the language field
			var post_lang = $postRow.find( '.nl-language' ).val();
			$( '#nl_language' ).val( post_lang ).change();

			// Update the translations fields
			$editRow.find( '.nl-translation' ).each( function() {
				var lang_id = $( this ).data( 'langid' );
				var translation = $postRow.find( '.nl-translation-' + lang_id ).val();
				$( this ).find( 'select' ).val( translation || -1 );
			} );
		};
	}

	// Extend inlineEditTax if available
	if ( typeof inlineEditTax === 'object' ) {
		var wpInlineEditTax_edit = inlineEditTax.edit;
		inlineEditTax.edit = function( id ) {
			// Start by calling the original for default behaviour
			wpInlineEditTax_edit.apply( this, arguments );
		};
	}
} );