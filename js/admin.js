/* globals console, alert, prompt, _, ajaxurl, inlineEditPost, inlineEditTax, nlingualL10n, NL_LANGUAGES, NL_DEFAULT_LANG, NL_PRESETS */
var NL_LOCALIZABLE_FIELDS = {};

// Register a field to be localized
window.nlingualLocalizeField = function( field, values, nonce ) {
	NL_LOCALIZABLE_FIELDS[ field ] = {
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
		var lang, slug, qvar, skip, format;

		// Get the default language slug, defaulting to "en"
		lang = $( '#nlingual_default_language' ).val();
		if ( lang && typeof NL_LANGUAGES[ lang ] === 'object' ) {
			slug = NL_LANGUAGES[ lang ].slug || 'en';
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
		_.each( NL_LANGUAGES, buildLangRow );

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
	// ! Localizeable Strings
	// =========================

	// Setup the base localizer
	var $localizer = $('<span class="nl-localizer"></span>').html(function(){
		var html = '<span class="nl-localizer-toggle" title="' + nlingualL10n.LocalizeThis + '"></span>', lang;
		_.each( NL_LANGUAGES, function( lang ) {
			html += '<span class="nl-localizer-option" title="' + nlingualL10n.LocalizeFor.replace( '%s', lang.system_name ) + '" data-lang="' + lang.lang_id + '"><i class="nl-option-text">' + lang.system_name + '</i></span>';
		} );
		return html;
	});

	_.each( NL_LOCALIZABLE_FIELDS, function( data, field ) {
		var values = data.values,
			nonce  = data.nonce;

		var hasLocalized = false;

		// Get the field if it exists
		var $field = $( '#' + field );
		if ( $field.length === 0 ) {
			return;
		}
		$field.addClass( 'nl-localizable-input' );

		// Wrap the field in a container
		$field.wrap( '<span class="nl-localizable"></span>' );
		var $wrap = $field.parent();

		// Create the control
		var $control = $localizer.clone();

		// Store the field and wrapper references in the control
		$control.data( '$nl_localized_' + NL_DEFAULT_LANG, $field );
		$control.data( '$wrap', $wrap );

		// Add copies of the field for each language
		_.each( NL_LANGUAGES, function( lang ) {
			// Skip the default language
			if ( NL_DEFAULT_LANG === lang.lang_id ) {
				return;
			}

			// Get the localized version of the value
			var localized = values[ lang.lang_id ] || null;

			// Copy, update the id/name, and set the value
			var $localized = $field.clone();
			$localized.attr( {
				id   : 'nl_localized-' + $field.attr( 'id' ) + '-lang_' + lang.lang_id,
				name : 'nlingual_localized[' + $field.attr( 'name' ) + '][' + lang.lang_id + ']'
			} );
			$localized.val( localized );

			// Store it for later use
			$control.data( '$nl_localized_' + lang.lang_id, $localized );

			// Add to the container and hide
			$localized.appendTo( $wrap ).hide();

			if ( localized !== '' && localized !== null ) {
				hasLocalized = true;
			}
		} );

		// Add the current class to the default language if localized versions are set
		if ( hasLocalized ) {
			$control.find( '[data-lang="' + NL_DEFAULT_LANG + '"]' ).addClass( 'nl-current' );
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
		var lang = $option.data( 'lang' );

		// Mark this as the new current one
		$( this ).addClass( 'nl-current' ).siblings().removeClass( 'nl-current' );

		// Default lang if nothing selected
		if ( ! lang ) {
			lang = NL_DEFAULT_LANG;
		}

		// Show the target version of the field
		$control.data( '$wrap' ).find( '.nl-localizable-input' ).hide();
		$control.data( '$nl_localized_' + lang ).show();
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