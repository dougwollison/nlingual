/* globals jQuery, alert, confirm, wp, Backbone, tinymce, ajaxurl, inlineEditPost, inlineEditTax, nlingualL10n */
( () => {
	var nL = window.nLingual = {};

	// =========================
	// ! Backbone Stuff
	// =========================

	var Framework = nL.Framework = {};

	// Language model
	var Language = Framework.Language = Backbone.Model.extend( {
		defaults: {
			system_name : '',
			native_name : '',
			short_name  : '',
			iso_code    : '',
			slug        : '',
			locale_name : '',
			direction   : '',
			active      : true,
		},
	} );

	// Languages collection
	var LanguageSet = Framework.LanguageSet = Backbone.Collection.extend( {
		model: Language,
	} );

	// LocalizableField model
	var LocalizableField = Framework.LocalizableField = Backbone.Model.extend( {
		idAttribute : 'field',
		defaults    : {
			field_id : '',
			values   : '',
			nonce    : '',
		},
	} );

	// LocalizableFieldSet collection
	var LocalizableFieldSet = Framework.LocalizableFieldSet = Backbone.Collection.extend( {
		model: LocalizableField,
	} );

	// =========================
	// ! Setup Main Collections
	// =========================

	var Languages = nL.Languages = new LanguageSet();
	var LocalizableFields = nL.LocalizableFields = new LocalizableFieldSet();

	// =========================
	// ! jQuery Stuff
	// =========================

	jQuery( $ => {
		// =========================
		// ! Setings Pages
		// =========================

		// Check all fields of a matching name
		$( '.nl-checkall' ).change( function() {
			var name = $( this ).data( 'name' );
			$( `input[name="${name}[]"]` ).attr( 'checked', this.checked );
		} );
		$( '.nl-matchall' ).change( function() {
			var name = $( this ).data( 'name' );
			$( `[name="${name}"]` ).val( this.checked ? '*' : '' );
		} );

		// Hide all sections by default
		$( '.nl-section-content' ).hide();

		// Add toggle feature for sections
		$( '.nl-section-toggle' ).each( function() {
			$( this ).data( 'text', $( this ).text() );
		} ).click( function() {
			var $toggle  = $( this );
			var $section = $toggle.parent();

			$section.toggleClass( 'open' );
			var open = $section.hasClass( 'open' );

			$section.find( '.nl-section-content' ).animate( { height: 'toggle' } );

			$toggle.text( $toggle.data( open ? 'alt' : 'text' ) );
		} );

		// Handle rendering of the previews
		$( '.nl-preview' ).on( 'nl:render', function() {
			// Get the default language slug, defaulting to "en"
			var language = Languages.get( $( '#nlingual_default_language' ).val() );

			var slug = language && language.get( 'slug' ) || 'en';

			// Get the query var, defaulting to "nl_language"
			var qvar = $( '#nlingual_query_var' ).val() || 'nl_language';

			// Get the skip and override options
			var skip = $( '#nlingual_skip_default_l10n' ).attr( 'checked' );
			var override = $( '#nlingual_post_language_override' ).attr( 'checked' );

			// Get the format; some previews are dependent on options
			var format;
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
		$( '#nlingual_post_language_override' ).change( function() {
			$( '.nl-preview' ).trigger( 'nl:render' );
		} );
		$( '#nlingual_query_var' ).on( 'keyup change', function() {
			$( '.nl-preview' ).trigger( 'nl:render' );
		} );

		// Default to GET version of previews
		$( '.nl-preview' ).hide().filter( '.nl-redirect-get' ).show();

		// Changing the method will change which previews are shown
		$( 'input[name="nlingual_options[url_rewrite_method]"]' ).change( function() {
			var method = $( this ).val();

			// Ignore if it's not checked or somehow has no value
			if ( ! this.checked || ! method ) {
				return;
			}

			// Show the associated preview while hiding the others
			$( '.nl-preview' ).hide().filter( `.nl-redirect-${method}` ).show();
		} ).change();

		// =========================
		// ! Language Manager
		// =========================

		$( '#nlingual_languages' ).each( function() {
			// Elements
			var $manager = $( this );
			var $preset  = $( '#nl_language_preset' );
			var $list    = $( '#nl_language_list' );
			var $addBtn  = $( '#nl_language_add' );

			var languageRowTemplate = $( '#nl_language_row' ).text();
			var languageRowIndex = -1;

			// Setup sortability
			$list.sortable( {
				items       : 'tr',
				containment : 'parent',
				handle      : '.handle',
			} );

			// Load preset selector
			for ( const preset in nL.presets ) {
				$preset.append( `<option value="${preset}">${nL.presets[ preset ].system_name}</option>` );
			}

			// Row builder utility
			function buildLangRow( language ) {
				var row = languageRowTemplate.replace( /%id%/g, language.id );

				// Loop through properties and replace
				for ( const prop in language.attributes ) {
					const regex = new RegExp( '%' + prop + '%', 'g' );
					row = row.replace( regex, language.get( prop ) );
				}

				// Parse the row into a new element
				var $row = $( row );

				// Check correct direction checkbox
				$row.find( `.nl-language-direction input[value="${language.get( 'direction' )}"]` ).attr( 'checked', true );

				// Check active checkbox if true
				$row.find( '.nl-language-active input' ).attr( 'checked', language.get( 'active' ) );

				// Add the row to the table
				$list.append( $row ).sortable( 'refresh' );
			}

			// Load table with current languages
			Languages.each( buildLangRow );

			// Add button functionality
			$addBtn.click( function() {
				var language;

				// Check if preset was selected,
				// otherwise make blank language
				if ( $preset.val() ) {
					const preset = $preset.val();
					language = new Language( nL.presets[ preset ] );
					language.set( 'iso_code', preset );

					// Reset preset selector
					$preset.val( null );
				} else {
					language = new Language();
				}

				// Default values
				language.id = languageRowIndex;
				language.set( 'slug', language.get( 'iso_code' ) );
				language.set( 'active', true );

				buildLangRow( language );

				languageRowIndex--;
			} );

			// Delete button functionality
			$manager.on( 'change', '.nl-language-delete input', function() {
				var $row = $( this ).parents( 'tr' ).first(); // Get the parent row

				// Toggle delete class and inputs
				$row.toggleClass( 'todelete', this.checked );
				$row.find( 'input' ).not( this ).attr( 'disabled', this.checked );
			} );

			// Auto-fill locale_name, iso_code and slug
			$manager.on( 'change', '.nl-language-system_name input', function() {
				var $row = $( this ).parents( 'tr' ).first(); // Get the parent row

				// Get the text
				var system_name = $( this ).val();

				// Get the other fields
				var $locale_name = $row.find( '.nl-language-locale_name input' );
				var $iso_code    = $row.find( '.nl-language-iso_code input' );
				var $slug        = $row.find( '.nl-language-slug input' );

				// No ISO? Assume first 2 characters of system name
				if ( ! $iso_code.val() ) {
					$iso_code.val( system_name.substr( 0, 2 ).toLowerCase() );
				}

				// No locale? Assume same as ISO code
				if ( ! $locale_name.val() ) {
					$locale_name.val( $iso_code.val() );
				}

				// No slug? Assume same as ISO code
				if ( ! $slug.val() ) {
					$slug.val( $iso_code.val() );
				}
			} );
		} );

		// =========================
		// ! Input Localizer
		// =========================

		// Setup the base localizer
		var $localizerTemplate = $( '<div class="nl-localizer"></div>' ).html( function() {
			var html = `<div class="nl-localizer-toggle" title="${nlingualL10n.LocalizeThis}"></div>`;

			Languages.each( language => {
				var title = nlingualL10n.LocalizeFor.replace( '%s', language.get( 'system_name' ) );
				html += `<div class="nl-localizer-option" title="${title}" data-nl_language="${language.id}">
					<div class="nl-option-text" data-slug="${language.get( 'slug' )}">${language.get( 'system_name' )}</div>
				</div>`;
			} );

			return html;
		} );

		LocalizableFields.each( field => {
			var field_id = field.get( 'field_id' );
			var values   = field.get( 'values' );
			var nonce    = field.get( 'nonce' );

			var hasLocalized = false;

			// Get the field if it exists and is an input/textarea
			var $field = $( '#' + field_id );
			if ( $field.length === 0 || ! $field.is( 'input, textarea' ) ) {
				return;
			}

			$field.addClass( 'nl-localizable-input' );

			// Check if it's a tinymce editor field
			var $wrap;
			if ( $field.hasClass( 'wp-editor-area' ) ) {
				// Use the editor wrapper as the container
				$wrap = $field.parents( '.wp-editor-wrap' );
			} else {
				// Wrap the field in a container
				$field.wrap( '<div class="nl-localizable"></div>' );
				$wrap = $field.parent();
			}

			// Create the control
			var $control = $localizerTemplate.clone();

			// Store the control reference in the field
			$field.data( '$nl_localizer', $control );

			// Store the current language of the control
			$control.data( 'nl_current_language', nL.default_language );

			// Store the field and wrapper reference in the control
			$control.data( '$nl_localizer_field', $field );

			// Create the storage input for the unlocalized field
			var $unlocalized = $( '<input type="hidden" />' );
			$unlocalized.attr( 'name', $field.attr( 'name' ) );
			$unlocalized.val( $field.val() );

			// Add to the wrapper
			$unlocalized.appendTo( $wrap );

			// Store the unlocalized input reference in the control
			$control.data( `$nl_localized_${nL.default_language}`, $unlocalized );

			// Add hidden storage inputs
			var hasLocalized;
			Languages.each( language => {
				// Skip the default language
				if ( nL.default_language === language.id ) {
					return;
				}

				// Get the localized version of the value
				var localized = values[ language.id ] || null;

				// Create a hidden field for the input
				var $localized = $( '<input type="hidden" />' );
				$localized.attr( 'name', `nlingual_localized[${$field.attr( 'name' )}][${language.id}]` );
				$localized.val( localized );

				// Store it for later use
				$control.data( `$nl_localized_${language.id}`, $localized );

				// Add to the wrapper
				$localized.appendTo( $wrap );

				if ( '' !== localized && null !== localized ) {
					hasLocalized = true;
				}
			} );

			// Add the current class to the default language if localized versions are set
			if ( hasLocalized ) {
				$control.find( `[data-nl_language="${nL.default_language}"]` ).addClass( 'nl-current' );
			}

			// Add the nonce field
			$wrap.append( `<input type="hidden" name="_nl_l10n_nonce[${field.id}]" value="${nonce}" />` );

			// Add the control at the end
			$control.appendTo( $wrap );
		} );

		$( 'body' ).on( 'click', '.nl-localizer-option', function() {
			// Get the localizer control, and the selected language
			var $option  = $( this );
			var $control = $option.parent();
			var language = $option.data( 'nl_language' ) || nL.default_language;

			// Mark this as the new current one
			$( this ).addClass( 'nl-current' ).siblings().removeClass( 'nl-current' );

			// Get the current field and the localized storage field
			var $field     = $control.data( '$nl_localizer_field' );
			var $localized = $control.data( `$nl_localized_${language}` );

			// Before we begin changing stuff, trigger an update on the field
			$field.trigger( 'nl:localizer:update' );

			// Update the controls current language
			$control.data( 'nl_current_language', language );

			// Get the value/name of the target localized field
			var value = $localized.val();
			var name  = $localized.attr( 'name' );

			// Swap the field's value/name
			$field.val( value ).attr( 'name', name );

			// Trigger a change event, for potential extensibility
			$field.trigger( 'nl:localizer:change' );
		} );

		$( 'body' ).on( 'input nl:localizer:update nl:localizer:save', '.nl-localizable-input', function() {
			// Get the control reference and it's current language
			var $control = $( this ).data( '$nl_localizer' );
			var language = $control.data( 'nl_current_language' );

			// Get the localized storage field
			var $localized = $control.data( `$nl_localized_${language}` );

			// Update it with the current value
			$localized.val( this.value );
		} );

		// =========================
		// ! - TinyMCE Extensions
		// =========================

		if ( typeof tinymce === 'object' ) {
			tinymce.on( 'SetupEditor', e => {
				// TinyMCE 4.7 changes callback arg to event CONTAINING editor
				var editor = e.editor || e;

				var $field = $( editor.getElement() ),
					$control = $field.data( '$nl_localizer' );

				if ( ! $control ) {
					return;
				}

				editor.on( 'init', () => {
					$( editor.getContainer() ).parent().after( $control );
				} );

				$field.on( 'nl:localizer:update', () => {
					// Get the content, clean it
					var content = wp.editor.removep( editor.getContent() );

					$field.val( content );
				} );

				$field.on( 'nl:localizer:change', () => {
					// Get the value, process it
					var content = wp.editor.autop( $field.val() );

					editor.setContent( content );
				} );
			} );

			var oldEditorSave = tinymce.Editor.prototype.save;
			tinymce.Editor.prototype.save = function() {
				oldEditorSave.apply( this, arguments );

				this.fire( 'SavedContent' );
				$( this.getElement() ).trigger( 'nl:localizer:save' );
			};
		}

		// =========================
		// ! Meta Box and Quick/Bulk Edit
		// =========================

		// Update visible translation fields based on current language
		$( '.nl-language-input' ).change( function() {
			var id = $( this ).val();
			var $parent = $( this ).parents( '.nl-translation-manager' );

			// Toggle visibility of the translations interface if language isn't set
			$parent.find( '.nl-manage-translations' ).toggleClass( 'hidden', id === '0' );

			// Show all translation fields by default
			$parent.find( '.nl-translation-field' ).show();

			// Hide the one for the current language
			$parent.find( '.nl-translation-' + id ).hide();
		} ).change(); // Update on page load

		// Create a new translation for the assocaited language
		$( '.nl-add-translation' ).click( function() {
			var $field                  = $( this ).parents( '.nl-field' );
			var $input                  = $field.find( '.nl-input' );
			var $select                   = $field.find( '.nl-translation-select' );
			var post_id                 = $( '#post_ID' ).val();
			var post_language_id        = $( '#nl_language' ).val();
			var translation_language_id = $input.parents( '.nl-field' ).data( 'nl_language' );

			var editWindow = window.open( nlingualL10n.admin_post + '?' + $.param( {
				action: 'nl_new_translation',
				post_id,
				post_language_id,
				translation_language_id,
			} ), '_blank' );

			editWindow.onload = function() {
				var [ , id ] = this.location.href.match( /post=(\d+)/ );

				$input.val( id );
				$field.addClass( 'nl-has-translation' );
				$select.attr( 'name', null );
			};
		} );

		// Open a search field to find an existing translation
		$( '.nl-find-translation' ).click( function() {
			var $field  = $( this ).parents( '.nl-field' );
			var $input  = $field.find( '.nl-translation-input' );
			var $select = $field.find( '.nl-translation-select' );

			$select.attr( 'name', $input.attr( 'name' ) );
			$select.show();
		} );

		// Open the editor for the selected translation
		$( '.nl-edit-translation' ).click( function() {
			// Get the parent field
			var $field = $( this ).parents( '.nl-field' );

			// Get the selected value
			var target = $field.find( '.nl-input' ).val();

			// Throw error if target isn't a valid post
			if ( target === 'new' || parseInt( target, 10 ) <= 0 ) {
				alert( nlingualL10n.NoPostSelected );
				return;
			}

			// Build the edit URL and open in a new tab
			var url = $( this ).data( 'url' ).replace( '%d', target );
			window.open( url );
		} );

		// Unlink the target from the current post as a translation
		$( '.nl-drop-translation' ).click( function() {
			if ( ! confirm( nlingualL10n.RemoveTranslationConfirm ) ) {
				return;
			}

			var $field      = $( this ).parents( '.nl-field' );
			var $input      = $field.find( '.nl-translation-input' );
			var $title      = $field.find( '.nl-translation-title' );
			var post_id     = $( '#post_ID' ).val();
			var language_id = $input.parents( '.nl-field' ).data( 'nl_language' );

			$.ajax( {
				url  : ajaxurl,
				type : 'POST',
				data : {
					action: 'nl_drop_translation',
					post_id,
					language_id,
				},
				success() {
					$input.val( null );
					$title.text( nlingualL10n.NoTranslation );
					$field.removeClass( 'nl-has-translation' );
				},
				error() {
					alert( nlingualL10n.RemoveTranslationError );
				},
			} );
		} );

		// =========================
		// ! WP API Extensions
		// =========================

		// Extend inlineEditPost if available
		if ( typeof inlineEditPost === 'object' ) {
			const wpInlineEditPost_edit = inlineEditPost.edit;

			// Replace with new function
			inlineEditPost.edit = function( post ) {
				// Start by calling the original for default behaviour
				wpInlineEditPost_edit.apply( this, arguments );

				// Get the post ID
				var post_id = post && parseInt( this.getId( post ), 10 ) || 0;

				// Get the post and edit rows
				var $postRow = $( `#post-${post_id}` );
				var $editRow = $( `#edit-${post_id}` );

				// Update the nonce field
				var nonce = $postRow.find( '.nl-nonce' ).val();
				$editRow.find( '.nl-nonce' ).val( nonce );

				// Update the language field
				var post_language = $postRow.find( '.nl-language' ).val();
				$editRow.find( '.nl-language-input' ).val( post_language ).change();

				// Update the translations fields
				$editRow.find( '.nl-translation-field' ).each( function() {
					var id = $( this ).data( 'nl_language' );
					var translation = $postRow.find( `.nl-translation-${id}` ).val();

					$( this ).find( 'select' ).val( translation || 0 );
				} );
			};
		}

		// Extend inlineEditTax if available
		if ( typeof inlineEditTax === 'object' ) {
			const wpInlineEditTax_edit = inlineEditTax.edit;

			// Replace with new function
			inlineEditTax.edit = function( /* id */ ) {

				// Start by calling the original for default behaviour
				wpInlineEditTax_edit.apply( this, arguments );
			};
		}
	} );
} )();
