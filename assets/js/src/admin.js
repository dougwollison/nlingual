/* globals jQuery, Backbone, tinymce, ajaxurl, inlineEditPost, inlineEditTax, nlingualL10n */
( () => {
	const nL = window.nLingual = {};

	// =========================
	// ! Backbone Stuff
	// =========================

	const Framework = nL.Framework = {};

	// Language model
	const Language = Framework.Language = Backbone.Model.extend( {
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
	const LanguageSet = Framework.LanguageSet = Backbone.Collection.extend( {
		model: Language,
	} );

	// LocalizableField model
	const LocalizableField = Framework.LocalizableField = Backbone.Model.extend( {
		idAttribute : 'field',
		defaults    : {
			field_id : '',
			values   : '',
			nonce    : '',
		},
	} );

	// LocalizableFieldSet collection
	const LocalizableFieldSet = Framework.LocalizableFieldSet = Backbone.Collection.extend( {
		model: LocalizableField,
	} );

	// =========================
	// ! Setup Main Collections
	// =========================

	const Languages = nL.Languages = new LanguageSet();
	const LocalizableFields = nL.LocalizableFields = new LocalizableFieldSet();

	// =========================
	// ! jQuery Stuff
	// =========================

	jQuery( $ => {
		// =========================
		// ! Setings Pages
		// =========================

		// Check all fields of a matching name
		$( '.nl-checkall' ).change( function() {
			const name = $( this ).data( 'name' );
			$( `input[name="${ name }[]"]` ).attr( 'checked', this.checked );
		} );
		$( '.nl-matchall' ).change( function() {
			const name = $( this ).data( 'name' );
			$( `[name="${ name }"]` ).val( this.checked ? '*' : '' );
		} );

		// Hide all sections by default
		$( '.nl-section-content' ).hide();

		// Add toggle feature for sections
		$( '.nl-section-toggle' ).each( function() {
			$( this ).data( 'text', $( this ).text() );
		} ).click( function() {
			const $toggle  = $( this );
			const $section = $toggle.parent();

			$section.toggleClass( 'open' );
			const open = $section.hasClass( 'open' );

			$section.find( '.nl-section-content' ).animate( { height: 'toggle' } );

			$toggle.text( $toggle.data( open ? 'alt' : 'text' ) );
		} );

		// Handle rendering of the previews
		$( '.nl-preview' ).on( 'nl:render', function() {
			// Get the default language slug, defaulting to "en"
			const language = Languages.get( $( '#nlingual_default_language' ).val() );

			const slug = language && language.get( 'slug' ) || 'en';

			// Get the query var, defaulting to "nl_language"
			const qvar = $( '#nlingual_query_var' ).val() || 'nl_language';

			// Get the skip and override options
			const skip = $( '#nlingual_skip_default_l10n' ).attr( 'checked' );
			const override = $( '#nlingual_post_language_override' ).attr( 'checked' );

			// Get the format; some previews are dependent on options
			let format;
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
			const method = $( this ).val();

			// Ignore if it's not checked or somehow has no value
			if ( ! this.checked || ! method ) {
				return;
			}

			// Show the associated preview while hiding the others
			$( '.nl-preview' ).hide().filter( `.nl-redirect-${ method }` ).show();
		} ).change();

		// =========================
		// ! Language Manager
		// =========================

		$( '#nlingual_languages' ).each( function() {
			// Elements
			const $manager = $( this );
			const $preset  = $( '#nl_language_preset' );
			const $list    = $( '#nl_language_list' );
			const $addBtn  = $( '#nl_language_add' );

			// Setup sortability
			$list.sortable( {
				items       : 'tr',
				containment : 'parent',
				handle      : '.handle',
			} );

			// Load preset selector
			for ( const preset in nL.presets ) {
				$preset.append( `<option value="${ preset }">${ nL.presets[ preset ].system_name }</option>` );
			}

			// Row builder utility
			const languageRowTemplate = $( '#nl_language_row' ).text();
			function buildLangRow( language ) {
				let row = languageRowTemplate.replace( /%id%/g, language.id );

				// Loop through properties and replace
				for ( const prop in language.attributes ) {
					const regex = new RegExp( '%' + prop + '%', 'g' );
					row = row.replace( regex, language.get( prop ) );
				}

				// Parse the row into a new element
				const $row = $( row );

				// Check correct direction checkbox
				$row.find( `.nl-language-direction input[value="${ language.get( 'direction' ) }"]` ).attr( 'checked', true );

				// Check active checkbox if true
				$row.find( '.nl-language-active input' ).attr( 'checked', language.get( 'active' ) );

				// Add the row to the table
				$list.append( $row ).sortable( 'refresh' );
			}

			// Load table with current languages
			Languages.each( buildLangRow );

			// Add button functionality
			let languageRowIndex = -1;
			$addBtn.click( function() {
				let language;

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
				const $row = $( this ).parents( 'tr' ).first(); // Get the parent row

				// Toggle delete class and inputs
				$row.toggleClass( 'todelete', this.checked );
				$row.find( 'input' ).not( this ).attr( 'disabled', this.checked );
			} );

			// Auto-fill locale_name, iso_code and slug
			$manager.on( 'change', '.nl-language-system_name input', function() {
				const $row = $( this ).parents( 'tr' ).first(); // Get the parent row

				// Get the text
				const systemName = $( this ).val();

				// Get the other fields
				const $localeName = $row.find( '.nl-language-locale_name input' );
				const $isoCode    = $row.find( '.nl-language-iso_code input' );
				const $slug        = $row.find( '.nl-language-slug input' );

				// No ISO? Assume first 2 characters of system name
				if ( ! $isoCode.val() ) {
					$isoCode.val( systemName.substr( 0, 2 ).toLowerCase() );
				}

				// No locale? Assume same as ISO code
				if ( ! $localeName.val() ) {
					$localeName.val( $isoCode.val() );
				}

				// No slug? Assume same as ISO code
				if ( ! $slug.val() ) {
					$slug.val( $isoCode.val() );
				}
			} );
		} );

		// =========================
		// ! Input Localizer
		// =========================

		// Setup the base localizer
		const $localizerTemplate = $( '<div class="nl-localizer"></div>' ).html( function() {
			let html = `<div class="nl-localizer-toggle" title="${ nlingualL10n.LocalizeThis }"></div>`;

			Languages.each( language => {
				const title = nlingualL10n.LocalizeFor.replace( '%s', language.get( 'system_name' ) );
				html += `<div class="nl-localizer-option" title="${ title }" data-nl_language="${ language.id }">
					<div class="nl-option-text" data-slug="${ language.get( 'slug' ) }">${ language.get( 'system_name' ) }</div>
				</div>`;
			} );

			return html;
		} );

		LocalizableFields.each( field => {
			const fieldId = field.get( 'field_id' );

			// Get the field if it exists and is an input/textarea
			const $field = $( '#' + fieldId );
			if ( $field.length === 0 || ! $field.is( 'input, textarea' ) ) {
				return;
			}

			$field.addClass( 'nl-localizable-input' );

			// Check if it's a tinymce editor field
			let $wrap;
			if ( $field.hasClass( 'wp-editor-area' ) ) {
				// Use the editor wrapper as the container
				$wrap = $field.parents( '.wp-editor-wrap' );
			} else {
				// Wrap the field in a container
				$field.wrap( '<div class="nl-localizable"></div>' );
				$wrap = $field.parent();
			}

			// Create the control
			const $control = $localizerTemplate.clone();

			// Store the control reference in the field
			$field.data( '$nl_localizer', $control );

			// Store the current language of the control
			$control.data( 'nl_current_language', nL.default_language );

			// Store the field and wrapper reference in the control
			$control.data( '$nl_localizer_field', $field );

			// Create the storage input for the unlocalized field
			const $unlocalized = $( '<input type="hidden" />' );
			$unlocalized.attr( 'name', $field.attr( 'name' ) );
			$unlocalized.val( $field.val() );

			// Add to the wrapper
			$unlocalized.appendTo( $wrap );

			// Store the unlocalized input reference in the control
			$control.data( `$nl_localized_${ nL.default_language }`, $unlocalized );

			let hasLocalized = false;
			const values   = field.get( 'values' );
			const nonce    = field.get( 'nonce' );

			// Add hidden storage inputs
			Languages.each( language => {
				// Skip the default language
				if ( nL.default_language === language.id ) {
					return;
				}

				// Get the localized version of the value
				const localized = values[ language.id ] || null;

				// Create a hidden field for the input
				const $localized = $( '<input type="hidden" />' );
				$localized.attr( 'name', `nlingual_localized[${ $field.attr( 'name' ) }][${ language.id }]` );
				$localized.val( localized );

				// Store it for later use
				$control.data( `$nl_localized_${ language.id }`, $localized );

				// Add to the wrapper
				$localized.appendTo( $wrap );

				if ( '' !== localized && null !== localized ) {
					hasLocalized = true;
				}
			} );

			// Add the current class to the default language if localized versions are set
			if ( hasLocalized ) {
				$control.find( `[data-nl_language="${ nL.default_language }"]` ).addClass( 'nl-current' );
			}

			// Add the nonce field
			$wrap.append( `<input type="hidden" name="_nl_l10n_nonce[${ field.id }]" value="${ nonce }" />` );

			// Add the control at the end
			$control.appendTo( $wrap );
		} );

		$( 'body' ).on( 'click', '.nl-localizer-option', function() {
			// Get the localizer control, and the selected language
			const $option  = $( this );
			const $control = $option.parent();
			const language = $option.data( 'nl_language' ) || nL.default_language;

			// Mark this as the new current one
			$( this ).addClass( 'nl-current' ).siblings().removeClass( 'nl-current' );

			// Get the current field and the localized storage field
			const $field     = $control.data( '$nl_localizer_field' );
			const $localized = $control.data( `$nl_localized_${ language }` );

			// Before we begin changing stuff, trigger an update on the field
			$field.trigger( 'nl:localizer:update' );

			// Update the controls current language
			$control.data( 'nl_current_language', language );

			// Get the value/name of the target localized field
			const value = $localized.val();
			const name  = $localized.attr( 'name' );

			// Swap the field's value/name
			$field.val( value ).attr( 'name', name );

			// Trigger a change event, for potential extensibility
			$field.trigger( 'nl:localizer:change' );
		} );

		$( 'body' ).on( 'input nl:localizer:update nl:localizer:save', '.nl-localizable-input', function() {
			// Get the control reference and it's current language
			const $control = $( this ).data( '$nl_localizer' );
			const language = $control.data( 'nl_current_language' );

			// Get the localized storage field
			const $localized = $control.data( `$nl_localized_${ language }` );

			// Update it with the current value
			$localized.val( this.value );
		} );

		// =========================
		// ! - TinyMCE Extensions
		// =========================

		if ( typeof tinymce === 'object' ) {
			tinymce.on( 'SetupEditor', e => {
				// TinyMCE 4.7 changes callback arg to event CONTAINING editor
				const editor = e.editor || e;

				const $field = $( editor.getElement() ),
					$control = $field.data( '$nl_localizer' );

				if ( ! $control ) {
					return;
				}

				editor.on( 'init', () => {
					$( editor.getContainer() ).parent().after( $control );
				} );

				$field.on( 'nl:localizer:update', () => {
					// Get the content, clean it
					const content = wp.editor.removep( editor.getContent() );

					$field.val( content );
				} );

				$field.on( 'nl:localizer:change', () => {
					// Get the value, process it
					const content = wp.editor.autop( $field.val() );

					editor.setContent( content );
				} );
			} );

			const oldEditorSave = tinymce.Editor.prototype.save;
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
			const id = $( this ).val();
			const $parent = $( this ).parents( '.nl-translations-manager' );

			// Toggle visibility of the translations interface if language isn't set
			$parent.find( '.nl-manage-translations' ).toggleClass( 'hidden', id === '0' );

			// Show all translation fields by default
			$parent.find( '.nl-translation-field' ).show();

			// Hide the one for the current language
			$parent.find( '.nl-translation-' + id ).hide();
		} ).change(); // Update on page load

		// Create a new translation for the assocaited language
		$( '.nl-add-translation' ).click( function() {
			const $field                  = $( this ).parents( '.nl-field' );
			const $input                  = $field.find( '.nl-input' );
			const $select                   = $field.find( '.nl-translation-select' );
			/* eslint-disable camelcase */
			const post_id                 = $( '#post_ID' ).val();
			const post_language_id        = $( '#nl_language' ).val();
			const translation_language_id = $input.parents( '.nl-field' ).data( 'nl_language' );
			/* eslint-enable camelcase */

			const editWindow = window.open( nlingualL10n.admin_post + '?' + $.param( {
				action: 'nl_new_translation',
				post_id,
				post_language_id,
				translation_language_id,
			} ), '_blank' );

			editWindow.onload = function() {
				const [ , id ] = this.location.href.match( /post=(\d+)/ );

				$input.val( id );
				$field.addClass( 'nl-has-translation' );
				$select.attr( 'name', null );
			};
		} );

		// Open a search field to find an existing translation
		$( '.nl-find-translation' ).click( function() {
			const $field  = $( this ).parents( '.nl-field' );
			const $input  = $field.find( '.nl-translation-input' );
			const $select = $field.find( '.nl-translation-select' );

			$select.attr( 'name', $input.attr( 'name' ) );
			$select.show();
		} );

		// Open the editor for the selected translation
		$( '.nl-edit-translation' ).click( function() {
			// Get the parent field
			const $field = $( this ).parents( '.nl-field' );

			// Get the selected value
			const target = $field.find( '.nl-input' ).val();

			// Throw error if target isn't a valid post
			if ( target === 'new' || parseInt( target, 10 ) <= 0 ) {
				alert( nlingualL10n.NoPostSelected );
				return;
			}

			// Build the edit URL and open in a new tab
			const url = $( this ).data( 'url' ).replace( '%d', target );
			window.open( url );
		} );

		// Unlink the target from the current post as a translation
		$( '.nl-drop-translation' ).click( function() {
			if ( ! confirm( nlingualL10n.RemoveTranslationConfirm ) ) {
				return;
			}

			const $field      = $( this ).parents( '.nl-field' );
			const $input      = $field.find( '.nl-translation-input' );
			const $title      = $field.find( '.nl-translation-title' );
			/* eslint-disable camelcase */
			const post_id     = $( '#post_ID' ).val();
			const language_id = $input.parents( '.nl-field' ).data( 'nl_language' );
			/* eslint-enable camelcase */

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
			const wpInlineEditPostEdit = inlineEditPost.edit;

			// Replace with new function
			inlineEditPost.edit = function( post ) {
				// Start by calling the original for default behaviour
				wpInlineEditPostEdit.apply( this, arguments );

				// Get the post ID
				const postId = post && parseInt( this.getId( post ), 10 ) || 0;

				// Get the post and edit rows
				const $postRow = $( `#post-${ postId }` );
				const $editRow = $( `#edit-${ postId }` );

				// Update the nonce field
				const nonce = $postRow.find( '.nl-nonce' ).val();
				$editRow.find( '.nl-nonce' ).val( nonce );

				// Update the language field
				const postLanguage = $postRow.find( '.nl-language' ).val();
				$editRow.find( '.nl-language-input' ).val( postLanguage ).change();

				// Update the translations fields
				$editRow.find( '.nl-translation-field' ).each( function() {
					const id = $( this ).data( 'nl_language' );
					const translation = $postRow.find( `.nl-translation-${id}` ).val();

					$( this ).find( 'select' ).val( translation || 0 );
				} );
			};
		}

		// Extend inlineEditTax if available
		if ( typeof inlineEditTax === 'object' ) {
			const wpInlineEditTaxEdit = inlineEditTax.edit;

			// Replace with new function
			inlineEditTax.edit = function( /* id */ ) {

				// Start by calling the original for default behaviour
				wpInlineEditTaxEdit.apply( this, arguments );
			};
		}
	} );
} )();
