/* globals console, alert, prompt, _, ajaxurl, inlineEditPost, inlineEditTax, nlingualL10n, NL_LANGUAGES, NL_DEFAULT_LANGUAGE, NL_PRESETS */
var NL_LOCALIZABLE_FIELDS = {};

// Register a field to be localized
window.nlingualLocalizeField = function( field, id, values, nonce ) {
	NL_LOCALIZABLE_FIELDS[ field ] = {
		id     : id,
		values : values,
		nonce  : nonce
	};
};

// Register multiple fields to be localized
window.nlingualLocalizeFields = function( fields ) {
	for ( var i in fields ) {
		window.nlingualLocalizeField.apply( this, fields[i] );
	}
};

jQuery( function( $ ) {
	// =========================
	// ! Setings Pages
	// =========================

	// Check all fields of a matching name
	$( '.nl-checkall' ).change( function() {
		var name = $( this ).data( 'name' );
		$( 'input[name="' + name + '[]"]' ).attr( 'checked', this.checked );
	} );
	$( '.nl-matchall' ).change( function() {
		var name = $( this ).data( 'name' );
		$( '[name="' + name + '"]' ).val( this.checked ? '*' : '' );
	} );

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

	// Handle rendering of the previews
	$( '.nl-preview' ).on( 'nl:render', function() {
		var language, slug, qvar, skip, format;

		// Get the default language slug, defaulting to "en"
		language = $( '#nlingual_default_language' ).val();
		if ( language && typeof NL_LANGUAGES[ language ] === 'object' ) {
			slug = NL_LANGUAGES[ language ].slug || 'en';
		}

		// Get the query var, defaulting to "nl_language"
		qvar = $( '#nlingual_query_var' ).val() || 'nl_language';

		// Get the skip option, will dictate what format to use
		skip = $( '#nlingual_skip_default_l10n' ).attr( 'checked' );

		// Get the format; url-previews will depend on skip
		if ( $( this ).hasClass( 'nl-url-preview' ) ) {
			format = $( this ).data( skip ? 'excluded' : 'included' );
		} else {
			format = $( this ).data( 'format' );
		}

		// Update the preview
		$( this ).text( format.replace( '%l', slug ).replace( '%v', qvar ) );
	} ).trigger( 'nl:render' );

	// Changing any of these will trigger re-rendering of the previews
	$( '#nlingual_default_language' ).change( function() {
		$( '.nl-preview' ).trigger( 'nl:render' );
	} );
	$( '#nlingual_skip_default_l10n' ).change( function() {
		$( '.nl-preview' ).trigger( 'nl:render' );
	} );
	$( '#nlingual_query_var' ).on( 'keyup change', function() {
		$( '.nl-preview' ).trigger( 'nl:render' );
	} );

	// Changing the method will change which previews are shown
	$( 'input[name="nlingual_redirection_method"]' ).change( function() {
		var method = $( this ).val();

		// Ignore if it's not checked or somehow has no value
		if ( ! this.checked || ! method ) {
			return;
		}

		// Show the associated preview while hiding the others
		$( '.nl-preview' ).hide().filter( '.' + method ).show();
	} ).change();

	// =========================
	// ! Language Manager
	// =========================

	$( '#nlingual_languages' ).each( function() {
		var languageRowTemplate = $( '#nl_language_row' ).text();
		var languageRowIndex = -1;

		// Load preset selector
		var $preset = $( '#nl_language_preset' );
		for ( var preset in NL_PRESETS ) {
			$preset.append( '<option value="' + preset + '">' + NL_PRESETS[ preset ].system_name + '</option>' );
		}

		// Row builder utility
		function buildLangRow( data ) {
			var row = languageRowTemplate, regex;
			// Loop through properties and replace
			for ( var prop in data ) {
				regex = new RegExp( '%' + prop + '%', 'g' );
				row = row.replace( regex, data[ prop ] );
			}

			// Parse the row into a new element
			var $row = $( row );

			// Check active checkbox if true
			$row.find( '.nl-language-active input' ).attr( 'checked', data.active );

			// Add the row to the table
			$( '#nl_language_list' ).append( $row );
		}

		// Load table with current languages
		_.each( NL_LANGUAGES, buildLangRow );

		// Add button functionality
		$( '#nl_language_add' ).click( function() {
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
			data.language_id = languageRowIndex;
			data.slug    = data.iso_code;
			data.active  = true;

			buildLangRow( data );

			languageRowIndex--;
		} );

		// Delete button functionality
		$( this ).on( 'change', '.nl-language-delete input', function() {
			// Get the parent row
			var $row = $( this ).parents( 'tr' ).first();
			// Toggle delete class and inputs
			$row.toggleClass( 'todelete', this.checked );
			$row.find( 'input' ).not( this ).attr( 'disabled', this.checked );
		} );

		// Auto-fill locale_name, iso_code and slug
		$( this ).on( 'change', '.nl-language-system_name input', function() {
			// Get the parent row
			var $row = $( this ).parents( 'tr' ).first();

			// Get the text
			var system_name = $( this ).val();

			// Get the other fields
			var $locale_name = $row.find( '.nl-language-locale_name input' );
			var $iso_code    = $row.find( '.nl-language-iso_code input' );
			var $slug        = $row.find( '.nl-language-slug input' );

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
	// ! Localizeable Strings
	// =========================

	// Setup the base localizer
	var $localizer = $('<span class="nl-localizer"></span>').html(function(){
		var html = '<span class="nl-localizer-toggle" title="' + nlingualL10n.LocalizeThis + '"></span>', language;
		_.each( NL_LANGUAGES, function( language ) {
			html += '<span class="nl-localizer-option" title="' + nlingualL10n.LocalizeFor.replace( '%s', language.system_name ) + '" data-language="' + language.language_id + '"><i class="nl-option-text">' + language.system_name + '</i></span>';
		} );
		return html;
	});

	_.each( NL_LOCALIZABLE_FIELDS, function( data, field ) {
		var id     = data.id,
			values = data.values,
			nonce  = data.nonce;

		var hasLocalized = false;

		// Get the field if it exists and is an input/textarea
		var $field = $( '#' + id );
		if ( $field.length === 0 || ! $field.is('input, textarea') ) {
			return;
		}
		$field.addClass( 'nl-localizable-input' );

		// Wrap the field in a container
		$field.wrap( '<span class="nl-localizable"></span>' );
		var $wrap = $field.parent();

		// Create the control
		var $control = $localizer.clone();

		// Store the field and wrapper references in the control
		$control.data( '$nl_localized_' + NL_DEFAULT_LANGUAGE, $field );
		$control.data( '$wrap', $wrap );

		// Add copies of the field for each language
		_.each( NL_LANGUAGES, function( language ) {
			// Skip the default language
			if ( NL_DEFAULT_LANGUAGE === language.language_id ) {
				return;
			}

			// Get the localized version of the value
			var localized = values[ language.language_id ] || null;

			// Copy, update the id/name, and set the value
			var $localized = $field.clone();
			$localized.attr( {
				id   : 'nl_localized-' + $field.attr( 'id' ) + '-language_' + language.language_id,
				name : 'nlingual_localized[' + $field.attr( 'name' ) + '][' + language.language_id + ']'
			} );
			$localized.val( localized );

			// Store it for later use
			$control.data( '$nl_localized_' + language.language_id, $localized );

			// Add to the container and hide
			$localized.appendTo( $wrap ).hide();

			if ( localized !== '' && localized !== null ) {
				hasLocalized = true;
			}
		} );

		// Add the current class to the default language if localized versions are set
		if ( hasLocalized ) {
			$control.find( '[data-language="' + NL_DEFAULT_LANGUAGE + '"]' ).addClass( 'nl-current' );
		}

		// Add the nonce field
		$wrap.append( '<input type="hidden" name="_nl_l10n_nonce[' + field + ']" value="' + nonce + '" />' );

		// Add the control at the end
		$control.appendTo( $wrap );
	} );

	$( 'body' ).on( 'click', '.nl-localizer-option', function () {
		// Get the localizer and the language
		var $option = $( this );
		var $control = $option.parent();
		var language = $option.data( 'language' );

		// Mark this as the new current one
		$( this ).addClass( 'nl-current' ).siblings().removeClass( 'nl-current' );

		// Default language if nothing selected
		if ( ! language ) {
			language = NL_DEFAULT_LANGUAGE;
		}

		// Show the target version of the field
		$control.data( '$wrap' ).find( '.nl-localizable-input' ).hide();
		$control.data( '$nl_localized_' + language ).show();
	} );

	// =========================
	// ! Meta Box and Quick/Bulk Edit
	// =========================

	// Update visible translation fields based on current language
	$( '#nl_language' ).change( function() {
		var language_id = $( this ).val();

		// Show all translation fields by default
		$( '.nl-translation' ).show();

		// Hide the one for the current language
		$( '#nl_translation_' + language_id ).hide();

	} ).change(); // Update on page load

	// Handle creating new translation
	$( '.nl-translation' ).change( function( e ) {
		var $input = $( this );
		var value = $input.val();
		var post_id = $( '#post_ID' ).val();
		var language_id = $input.parents( '.nl-field' ).data( 'languageid' );

		if ( value === 'new' ) {
			// Ask for a title for the translation
			var title = $( '#title' ).val();
			var placeholder = nlingualL10n.TranslationTitlePlaceholder
				.replace( /%s/, NL_LANGUAGES[ language_id ].system_name )
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
					language_id      : language_id,
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
			var post_language = $postRow.find( '.nl-language' ).val();
			$( '#nl_language' ).val( post_language ).change();

			// Update the translations fields
			$editRow.find( '.nl-translation' ).each( function() {
				var language_id = $( this ).data( 'languageid' );
				var translation = $postRow.find( '.nl-translation-' + language_id ).val();
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