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
		var language, slug, qvar, skip, override, format;

		// Get the default language slug, defaulting to "en"
		language = $( '#nlingual_default_language' ).val();
		if ( language && typeof NL_LANGUAGES[ language ] === 'object' ) {
			slug = NL_LANGUAGES[ language ].slug || 'en';
		}

		// Get the query var, defaulting to "nl_language"
		qvar = $( '#nlingual_query_var' ).val() || 'nl_language';

		// Get the skip and override options
		skip = $( '#nlingual_skip_default_l10n' ).attr( 'checked' );
		override = $( '#nlingual_post_language_override' ).attr( 'checked' );

		// Get the format; some previews are dependent on options
		if ( $( this ).hasClass( 'nl-url-preview' ) ) {
			format = $( this ).data( skip ? 'excluded' : 'included' );
		} else if ( $( this ).hasClass( 'nl-override-preview' ) ) {
			format = $( this ).data( override ? 'on' : 'off' );
		} else {
			format = $( this ).data( 'format' );
		}

		// Update the preview
		$( this ).text(
			format
			.replace( /%l/g, slug )
			.replace( /%v/g, qvar )
		);
	} ).trigger( 'nl:render' );

	// Changing any of these will trigger re-rendering of the previews
	$( '#nlingual_default_language' ).change( function() {
		$( '.nl-preview' ).trigger( 'nl:render' );
	} );
	$( '#nlingual_skip_default_l10n' ).change( function() {
		$( '.nl-preview' ).trigger( 'nl:render' );
	} );
	$( '#nlingual_post_language_override' ).change(function() {
		$( '.nl-preview' ).trigger( 'nl:render' );
	} );
	$( '#nlingual_query_var' ).on( 'keyup change', function() {
		$( '.nl-preview' ).trigger( 'nl:render' );
	} );

	// Default to GET version of previews
	$( '.nl-preview' ).hide().filter( '.nl-redirect-get' ).show();

	// Changing the method will change which previews are shown
	$( 'input[name="nlingual_url_rewrite_method"]' ).change( function() {
		var method = $( this ).val();

		// Ignore if it's not checked or somehow has no value
		if ( ! this.checked || ! method ) {
			return;
		}

		// Show the associated preview while hiding the others
		$( '.nl-preview' ).hide().filter( '.nl-redirect-' + method ).show();
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

			// Check correct direction checkbox
			$row.find( '.nl-language-direction input[value="' + data.direction + '"]' ).attr( 'checked', true );

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
					direction   : '',
				};
			}

			// Default values
			data.id     = languageRowIndex;
			data.slug   = data.iso_code;
			data.active = true;

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
	// ! Input Localizer
	// =========================

	// Flag for skipping redundant localizer update
	var nlLocalizerSkipUpdate = 'nlLocalizerSkipUpdate';

	// Setup the base localizer
	var $localizer = $('<span class="nl-localizer"></span>').html(function(){
		var html = '<span class="nl-localizer-toggle" title="' + nlingualL10n.LocalizeThis + '"></span>', language;
		_.each( NL_LANGUAGES, function( language ) {
			html += '<span class="nl-localizer-option" title="' + nlingualL10n.LocalizeFor.replace( '%s', language.system_name ) + '" data-nl_language="' + language.id + '"><i class="nl-option-text">' + language.system_name + '</i></span>';
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

		// Store the control reference in the field
		$field.data( '$nl_localizer', $control );

		// Store the current language of the control
		$control.data( 'nl_current_language', NL_DEFAULT_LANGUAGE );

		// Store the field and wrapper reference in the control
		$control.data( '$nl_localizer_field', $field );

		// Create the storage input for the unlocalized field
		var $unlocalized = $( '<input type="hidden" />' );
			$unlocalized.attr( 'name', $field.attr( 'name' ) );
			$unlocalized.val( $field.val() );

		// Add to the wrapper
		$unlocalized.appendTo( $wrap );

		// Store the unlocalized input reference in the control
		$control.data( '$nl_localized_' + NL_DEFAULT_LANGUAGE, $unlocalized );

		// Add hidden storage inputs
		_.each( NL_LANGUAGES, function( language ) {
			// Skip the default language
			if ( NL_DEFAULT_LANGUAGE === language.id ) {
				return;
			}

			// Get the localized version of the value
			var localized = values[ language.id ] || null;

			// Create a hidden field for the input
			var $localized = $( '<input type="hidden" />' );
				$localized.attr( 'name', 'nlingual_localized[' + $field.attr( 'name' ) + '][' + language.id + ']' );
				$localized.val( localized );

			// Store it for later use
			$control.data( '$nl_localized_' + language.id, $localized );

			// Add to the wrapper
			$localized.appendTo( $wrap );

			if ( localized !== '' && localized !== null ) {
				hasLocalized = true;
			}
		} );

		// Add the current class to the default language if localized versions are set
		if ( hasLocalized ) {
			$control.find( '[data-nl_language="' + NL_DEFAULT_LANGUAGE + '"]' ).addClass( 'nl-current' );
		}

		// Add the nonce field
		$wrap.append( '<input type="hidden" name="_nl_l10n_nonce[' + field + ']" value="' + nonce + '" />' );

		// Add the control at the end
		$control.appendTo( $wrap );
	} );

	$( 'body' ).on( 'click', '.nl-localizer-option', function () {
		// Get the localizer control, and the selected language
		var $option = $( this ),
			$control = $option.parent(),
			language = $option.data( 'nl_language' );

		// Mark this as the new current one
		$( this ).addClass( 'nl-current' ).siblings().removeClass( 'nl-current' );

		// Default language if nothing selected
		if ( ! language ) {
			language = NL_DEFAULT_LANGUAGE;
		}

		// Get the current field and the localized storage field
		var $field = $control.data( '$nl_localizer_field' ),
			$localized = $control.data( '$nl_localized_' + language );

		// Before we begin changing stuff, trigger an update on the field
		$field.trigger( 'nl:localizer:update' );

		// Update the controls current language
		$control.data( 'nl_current_language', language );

		// Get the value/name of the target localized field
		var value = $localized.val(),
			name = $localized.attr( 'name' );

		// Swap the field's value/name
		$field.val( value ).attr( 'name', name );

		// Trigger a change event, for potential extensibility
		$field.trigger( 'input', nlLocalizerSkipUpdate );
	} );

	$( 'body' ).on( 'input nl:localizer:update', '.nl-localizable-input', function ( event, extra ) {
		// Skip if this was a change event triggered by the update above
		console.log(event.type);
		if ( event.type === 'input' && extra === nlLocalizerSkipUpdate ) {
			return;
		}

		// Get the control reference and it's current language
		var $control = $( this ).data( '$nl_localizer' );
		var language = $control.data( 'nl_current_language' );

		// Get the localized storage field
		var $localized = $control.data( '$nl_localized_' + language );

		// Update it with the current value
		$localized.val( this.value );
	} );

	// =========================
	// ! Meta Box and Quick/Bulk Edit
	// =========================

	// Update visible translation fields based on current language
	$( '.nl-language-input' ).change( function() {
		var id = $( this ).val();
		var $parent = $( this ).parents( '.nl-translations-manager' );

		// Toggle visibility of the translations interface if language isn't set
		$parent.find( '.nl-set-translations' ).toggleClass( 'hidden', id === '0' );

		// Show all translation fields by default
		$parent.find( '.nl-translation-field' ).show();

		// Hide the one for the current language
		$parent.find( '.nl-translation-' + id ).hide();
	} ).change(); // Update on page load

	// Handle creating new translation
	$( '.nl-translation-input' ).change( function( e ) {
		var $input = $( this );
		var value = $input.val();
		var post_id = $( '#post_ID' ).val();
		var language_id = $input.parents( '.nl-field' ).data( 'nl_language' );

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
					language_id  : language_id,
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
			$editRow.find( '.nl-language-input' ).val( post_language ).change();

			// Update the translations fields
			$editRow.find( '.nl-translation' ).each( function() {
				var id = $( this ).data( 'nl_language' );
				var translation = $postRow.find( '.nl-translation-' + id ).val();
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
