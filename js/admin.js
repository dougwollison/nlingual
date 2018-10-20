/* globals jQuery, alert, wp, _, Backbone, tinymce, inlineEditPost, inlineEditTax, nlingualL10n */
( function() {
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
			active      : true
		}
	} );

	// Languages collection
	var LanguageSet = Framework.LanguageSet = Backbone.Collection.extend( {
		model: Language
	} );

	// LocalizableField model
	var LocalizableField = Framework.LocalizableField = Backbone.Model.extend( {
		idAttribute: 'field',
		defaults: {
			field_id : '',
			values   : '',
			nonce    : ''
		}
	} );

	// LocalizableFieldSet collection
	var LocalizableFieldSet = Framework.LocalizableFieldSet = Backbone.Collection.extend( {
		model: LocalizableField
	} );

	// =========================
	// ! Setup Main Collections
	// =========================

	var Languages = nL.Languages = new LanguageSet();
	var LocalizableFields = nL.LocalizableFields = new LocalizableFieldSet();

	// =========================
	// ! jQuery Stuff
	// =========================

	jQuery( function( $ ) {
		var $localizerTemplate,
			nlLocalizerSkipUpdate,
			wpInlineEditPost_edit,
			wpInlineEditTax_edit;

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
		$( '.nl-section-toggle' ).each( function() {
			$( this ).data( 'text', $( this ).text() );
		} ).click( function() {
			var $toggle, $section, open;

			$toggle = $( this );
			$section = $toggle.parent();

			$section.toggleClass( 'open' );
			open = $section.hasClass( 'open' );

			$section.find( '.nl-section-content' ).animate( { height: 'toggle' } );

			$toggle.text( $toggle.data( open ? 'alt' : 'text' ) );
		} );

		// Handle rendering of the previews
		$( '.nl-preview' ).on( 'nl:render', function() {
			var language, slug, qvar, skip, override, format;

			// Get the default language slug, defaulting to "en"
			language = $( '#nlingual_default_language' ).val();
			language = Languages.get( language );
			if ( language ) {
				slug = language.get( 'slug' ) || 'en';
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
			$( '.nl-preview' ).hide().filter( '.nl-redirect-' + method ).show();
		} ).change();

		// =========================
		// ! Language Manager
		// =========================

		$( '#nlingual_languages' ).each( function() {
			var $manager, $preset, $list, $addBtn, languageRowTemplate, languageRowIndex, preset;

			// Elements
			$manager = $( this );
			$preset  = $( '#nl_language_preset' );
			$list    = $( '#nl_language_list' );
			$addBtn  = $( '#nl_language_add' );

			languageRowTemplate = $( '#nl_language_row' ).text();
			languageRowIndex = -1;

			// Setup sortability
			$list.sortable( {
				items       : 'tr',
				containment : 'parent',
				handle      : '.handle'
			} );

			// Load preset selector
			for ( preset in nL.presets ) {
				$preset.append( '<option value="' + preset + '">' + nL.presets[ preset ].system_name + '</option>' );
			}

			// Row builder utility
			function buildLangRow( language ) {
				var row, $row, regex, prop;

				row = languageRowTemplate;
				row = row.replace( /%id%/g, language.id );

				// Loop through properties and replace
				for ( prop in language.attributes ) {
					regex = new RegExp( '%' + prop + '%', 'g' );
					row = row.replace( regex, language.get( prop ) );
				}

				// Parse the row into a new element
				$row = $( row );

				// Check correct direction checkbox
				$row.find( '.nl-language-direction input[value="' + language.get( 'direction' ) + '"]' ).attr( 'checked', true );

				// Check active checkbox if true
				$row.find( '.nl-language-active input' ).attr( 'checked', language.get( 'active' ) );

				// Add the row to the table
				$list.append( $row ).sortable( 'refresh' );
			}

			// Load table with current languages
			Languages.each( buildLangRow );

			// Add button functionality
			$addBtn.click( function() {
				var language, preset;

				// Check if preset was selected,
				// otherwise make blank language
				if ( $preset.val() ) {
					preset = $preset.val();
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
				var $row, system_name, $locale_name, $iso_code, $slug;

				$row = $( this ).parents( 'tr' ).first(); // Get the parent row

				// Get the text
				system_name = $( this ).val();

				// Get the other fields
				$locale_name = $row.find( '.nl-language-locale_name input' );
				$iso_code    = $row.find( '.nl-language-iso_code input' );
				$slug        = $row.find( '.nl-language-slug input' );

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
		$localizerTemplate = $( '<div class="nl-localizer"></div>' ).html( function() {
			var html = '<div class="nl-localizer-toggle" title="' + nlingualL10n.LocalizeThis + '"></div>';

			Languages.each( function( language ) {
				html += '<div class="nl-localizer-option" title="' + nlingualL10n.LocalizeFor.replace( '%s', language.get( 'system_name' ) ) + '" data-nl_language="' + language.id + '">' +
					'<div class="nl-option-text" data-slug="' + language.get( 'slug' ) + '">' + language.get( 'system_name' ) + '</div>' +
				'</div>';
			} );

			return html;
		} );

		// Flag for skipping redundant localizer update
		nlLocalizerSkipUpdate = 'nlLocalizerSkipUpdate';

		LocalizableFields.each( function( field ) {
			var field_id, values, nonce, hasLocalized, $field, $wrap, $control, $unlocalized;

			field_id = field.get( 'field_id' );
			values   = field.get( 'values' );
			nonce    = field.get( 'nonce' );

			hasLocalized = false;

			// Get the field if it exists and is an input/textarea
			$field = $( '#' + field_id );
			if ( 0 === $field.length || ! $field.is( 'input, textarea' ) ) {
				return;
			}

			$field.addClass( 'nl-localizable-input' );

			// Check if it's a tinymce editor field
			if ( $field.hasClass( 'wp-editor-area' ) ) {
				// Use the editor wrapper as the container
				$wrap = $field.parents( '.wp-editor-wrap' );
			} else {
				// Wrap the field in a container
				$field.wrap( '<div class="nl-localizable"></div>' );
				$wrap = $field.parent();
			}

			// Create the control
			$control = $localizerTemplate.clone();

			// Store the control reference in the field
			$field.data( '$nl_localizer', $control );

			// Store the current language of the control
			$control.data( 'nl_current_language', nL.default_language );

			// Store the field and wrapper reference in the control
			$control.data( '$nl_localizer_field', $field );

			// Create the storage input for the unlocalized field
			$unlocalized = $( '<input type="hidden" />' );
			$unlocalized.attr( 'name', $field.attr( 'name' ) );
			$unlocalized.val( $field.val() );

			// Add to the wrapper
			$unlocalized.appendTo( $wrap );

			// Store the unlocalized input reference in the control
			$control.data( '$nl_localized_' + nL.default_language, $unlocalized );

			// Add hidden storage inputs
			Languages.each( function( language ) {
				var localized, $localized;

				// Skip the default language
				if ( nL.default_language === language.id ) {
					return;
				}

				// Get the localized version of the value
				localized = values[ language.id ] || null;

				// Create a hidden field for the input
				$localized = $( '<input type="hidden" />' );
				$localized.attr( 'name', 'nlingual_localized[' + $field.attr( 'name' ) + '][' + language.id + ']' );
				$localized.val( localized );

				// Store it for later use
				$control.data( '$nl_localized_' + language.id, $localized );

				// Add to the wrapper
				$localized.appendTo( $wrap );

				if ( '' !== localized && null !== localized ) {
					hasLocalized = true;
				}
			} );

			// Add the current class to the default language if localized versions are set
			if ( hasLocalized ) {
				$control.find( '[data-nl_language="' + nL.default_language + '"]' ).addClass( 'nl-current' );
			}

			// Add the nonce field
			$wrap.append( '<input type="hidden" name="_nl_l10n_nonce[' + field.id + ']" value="' + nonce + '" />' );

			// Add the control at the end
			$control.appendTo( $wrap );
		} );

		$( 'body' ).on( 'click', '.nl-localizer-option', function() {
			var $option, $control, $field, $localized, language, value, name;

			// Get the localizer control, and the selected language
			$option = $( this );
			$control = $option.parent();
			language = $option.data( 'nl_language' );

			// Mark this as the new current one
			$( this ).addClass( 'nl-current' ).siblings().removeClass( 'nl-current' );

			// Default language if nothing selected
			if ( ! language ) {
				language = nL.default_language;
			}

			// Get the current field and the localized storage field
			$field = $control.data( '$nl_localizer_field' );
			$localized = $control.data( '$nl_localized_' + language );

			// Before we begin changing stuff, trigger an update on the field
			$field.trigger( 'nl:localizer:update' );

			// Update the controls current language
			$control.data( 'nl_current_language', language );

			// Get the value/name of the target localized field
			value = $localized.val();
			name = $localized.attr( 'name' );

			// Swap the field's value/name
			$field.val( value ).attr( 'name', name );

			// Trigger a change event, for potential extensibility
			$field.trigger( 'nl:localizer:change' );
		} );

		$( 'body' ).on( 'input nl:localizer:update nl:localizer:save', '.nl-localizable-input', function() {
			var $control, $localized, language;

			// Get the control reference and it's current language
			$control = $( this ).data( '$nl_localizer' );
			language = $control.data( 'nl_current_language' );

			// Get the localized storage field
			$localized = $control.data( '$nl_localized_' + language );

			// Update it with the current value
			$localized.val( this.value );
		} );

		// =========================
		// ! - TinyMCE Extensions
		// =========================

		if ( typeof tinymce === 'object' ) {
			tinymce.on( 'SetupEditor', function( e ) {
				// TinyMCE 4.7 changes callback arg to event CONTAINING editor
				var editor = e.editor || e;

				var $field = $( editor.getElement() ),
					$control = $field.data( '$nl_localizer' );

				if ( ! $control ) {
					return;
				}

				editor.on( 'init', function() {
					$( editor.getContainer() ).parent().after( $control );
				} );

				$field.on( 'nl:localizer:update', function() {
					// Get the content, clean it
					var content = editor.getContent();
						content = wp.editor.removep( content );

					$field.val( content );
				} );

				$field.on( 'nl:localizer:change', function() {
					// Get the value, process it
					var content = $field.val();
						content = wp.editor.autop( content );

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
		// ! - WP Media Extension
		// =========================

		if ( wp && wp.media && wp.media.view ) {
			var oldAttachmentDetailsRender = wp.media.view.Attachment.Details.prototype.render;

			wp.media.view.Attachment.Details.prototype.render = function() {
				var returnVal, localized_values, $selector, $languages, view, $fields;

				returnVal = oldAttachmentDetailsRender.apply( this, arguments );

				localized_values = this.model.get( 'localized_values' );

				if ( ! localized_values ) {
					return returnVal;
				}

				$selector = $( '<label class="setting nl-localizer-languages"></label>' ).html( function() {
					var html = '<span class="name">Translate</span>';

					html += '<span class="value">';
					Languages.each( function( language, index ) {
						if ( index > 0 ) {
							html += ' | ';
						}
						html += '<a href="#" class="nl-localizer-language" data-nl_language="' + language.id + '">' + language.get( 'system_name' ) + '</a>';
					} );
					html += '</span>';

					return html;
				} );

				$languages = $selector.find( '.nl-localizer-language' );

				this.$( '.settings' ).prepend( $selector );

				view = this;
				_.each( [ 'title', 'caption', 'alt', 'description' ], function( field ) {
					var $field, $last_insert;

					$field = $last_insert = view.$( '.setting[data-setting="' + field + '"]' );

					$field.addClass( 'nl-localizer-field' );

					Languages.each( function( language ) {
						var setting, $localized;

						if ( language.id === nL.default_language ) {
							return true;
						}

						setting = 'localized_' + language.id + '_' + field;
						$localized = $field.clone();

						$localized.addClass( 'nl-language-' + language.id );
						$localized.data( 'setting', setting ).attr( 'data-setting', setting );
						$localized.find( 'input,textarea' ).val( localized_values[ field ][ language.id ] );

						$last_insert.after( $localized );
						$last_insert = $localized;
					} );

					$field.addClass( 'nl-language-' + nL.default_language );
				} );

				$fields = this.$( '.nl-localizer-field' );

				$languages.on( 'click', function() {
					var language_id;

					$languages.removeClass( 'nl-current' );
					$( this ).addClass( 'nl-current' );

					language_id = $( this ).data( 'nl_language' );

					$fields.hide().filter( '.nl-language-' + language_id ).show();
				} );

				$languages.filter( '[data-nl_language="' + nL.default_language + '"]' ).trigger( 'click' );

				return returnVal;
			};
		}

		// =========================
		// ! Meta Box and Quick/Bulk Edit
		// =========================

		// Update visible translation fields based on current language
		$( '.nl-language-input' ).change( function() {
			var id, $parent;

			id = $( this ).val();
			$parent = $( this ).parents( '.nl-translation-manager' );

			// Toggle visibility of the translations interface if language isn't set
			$parent.find( '.nl-manage-translations' ).toggleClass( 'hidden', '0' === id );

			// Show all translation fields by default
			$parent.find( '.nl-translation-field' ).show();

			// Hide the one for the current language
			$parent.find( '.nl-translation-' + id ).hide();
		} ).change(); // Update on page load

		// Show/Hide the Add/Edit buttons based on value
		$( '.nl-translation-field' ).each( function() {
			var value = $( this ).find( '.nl-input' ).val();

			var $add = $( this ).find( '.nl-add-translation' ),
				$edit = $( this ).find( '.nl-edit-translation' );

			$edit.hide();
			if ( parseInt( value ) ) {
				$add.hide();
				$edit.show();
			}
		} );

		// Create a new translation for the assocaited language
		$( '.nl-add-translation' ).click( function() {
			var $field, $input, $add, $edit, post_id, post_language_id, translation_language_id;

			$field = $( this ).parents( '.nl-field' );
			$input = $field.find( '.nl-input' );
			$add = $field.find( '.nl-add-translation' );
			$edit = $field.find( '.nl-edit-translation' );
			post_id = $( '#post_ID' ).val();
			post_language_id = $( '#nl_language' ).val();
			translation_language_id = $input.parents( '.nl-field' ).data( 'nl_language' );

			var editWindow = window.open( '/wp-admin/admin-post.php?' + $.param( {
				action                 : 'nl_new_translation',
				post_id                 : post_id,
				post_language_id        : post_language_id,
				translation_language_id : translation_language_id,
			} ), '_blank' );

			editWindow.onload = function() {
				var url = this.location.href.match( /post=(\d+)/ ),
					id = url[1];

				$input.val( id );

				$add.hide();
				$edit.show().attr( 'title' );
			};
		} );

		// Open the editor for the selected translation
		$( '.nl-edit-translation' ).click( function() {
			var $field, target, url;

			// Get the parent field
			$field = $( this ).parents( '.nl-field' );

			// Get the selected value
			target = $field.find( '.nl-input' ).val();

			// Throw error if target isn't a valid post
			if ( 'new' === target || parseInt( target ) <= 0 ) {
				alert( nlingualL10n.NoPostSelected );
				return;
			}

			// Build the edit URL and open in a new tab
			url = $( this ).data( 'url' ).replace( '%d', target );
			window.open( url );
		} );

		// =========================
		// ! WP API Extensions
		// =========================

		// Extend inlineEditPost if available
		if ( 'object' === typeof inlineEditPost ) {
			wpInlineEditPost_edit = inlineEditPost.edit;

			// Replace with new function
			inlineEditPost.edit = function( id ) {
				var post_id, $postRow, $editRow, nonce, post_language;

				// Start by calling the original for default behaviour
				wpInlineEditPost_edit.apply( this, arguments );

				// Get the post ID
				post_id = 0;
				if ( 'object' === typeof id ) {
					post_id = parseInt( this.getId( id ) );
				}

				// Get the post and edit rows
				$postRow = $( '#post-' + post_id );
				$editRow = $( '#edit-' + post_id );

				// Update the nonce field
				nonce = $postRow.find( '.nl-nonce' ).val();
				$editRow.find( '.nl-nonce' ).val( nonce );

				// Update the language field
				post_language = $postRow.find( '.nl-language' ).val();
				$editRow.find( '.nl-language-input' ).val( post_language ).change();

				// Update the translations fields
				$editRow.find( '.nl-translation-field' ).each( function() {
					var id, translation;

					id = $( this ).data( 'nl_language' );
					translation = $postRow.find( '.nl-translation-' + id ).val();

					$( this ).find( 'select' ).val( translation || 0 );
				} );
			};
		}

		// Extend inlineEditTax if available
		if ( 'object' === typeof inlineEditTax ) {
			wpInlineEditTax_edit = inlineEditTax.edit;

			// Replace with new function
			inlineEditTax.edit = function( /* id */ ) {

				// Start by calling the original for default behaviour
				wpInlineEditTax_edit.apply( this, arguments );
			};
		}
	} );
} )();
