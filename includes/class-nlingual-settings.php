<?php
/**
 * nLingual Settings Helper
 *
 * @package nLingual
 * @subpackage Helpers
 *
 * @since 2.0.0
 */

namespace nLingual;

/**
 * The Settings Kit
 *
 * Internal-use utility kit for printing out
 * the option fields for the Manager.
 *
 * @internal Used by the Manager.
 *
 * @since 2.0.0
 */
final class Settings {
	/**
	 * Add the desired settings field.
	 *
	 * Prefixes option name with "nlingual_"
	 * and the page name with "nlingual-".
	 *
	 * @since 2.0.0
	 *
	 * @uses Settings::build_field() as the callback for the field.
	 *
	 * @param string $field   The name of the field.
	 * @param array  $options The options for the field.
	 * 		@option string "title" The title of the field.
	 * 		@option string "label" Additional label for the input.
	 *		@option string "help"  The help text for the field.
	 *		@option string "type"  The type of field to print out.
	 *		@option string "args"  Special arguments for the field callback.
	 * @param string $page    The name of the page to display on.
	 * @param string $section Optional. The name of the section to display in.
	 */
	public static function add_field( $field, $options, $page, $section = 'default' ) {
		// Parse the options
		$options = wp_parse_args( $options, array(
			'title' => '',
			'label' => '',
			'help'  => '',
			'type'  => '',
			'data'  => array()
		) );

		// Handle prefixing the name with nlingual_options
		if ( preg_match( '/([^\[]+)(\[.+\])/', $field, $matches ) ) {
			$id = "nlingual_" . trim( preg_replace( '/[\[\]]+/', '_', $field ), '_' );
			$name = "nlingual_options[{$matches[1]}]{$matches[2]}";
		} else {
			$id = "nlingual_{$field}";
			$name = "nlingual_options[{$field}]";
		}

		// Build the callback arguments
		$class = sanitize_key( $field );
		$args = array(
			'class'     => "nl-settings-field nl-settings-{$page}-field nlingual_{$class}-field",
			'option'    => $field,
			'id'        => $id,
			'name'      => $name,
			'label'     => $options['label'],
			'help'      => $options['help'],
			'type'      => $options['type'],
			'data'      => $options['data'],
		);

		// Add label_for arg if appropriate
		if ( ! in_array( $options['type'], array( 'radiolist', 'checklist', 'checkbox', 'sync_settings' ) ) ) {
			$args['label_for'] = $args['id'];
		}

		// Add the settings field
		add_settings_field(
			"nlingual_{$field}", // id
			$options['title'], // title
			array( get_called_class(), 'build_field' ), // callback
			"nlingual-{$page}", // page
			$section, // section
			$args // arguments
		);
	}

	/**
	 * Add multiple settings fields.
	 *
	 * @since 2.0.0
	 *
	 * @see Settings::build_field() for how the fields are built.
	 *
	 * @param array  $fields  The fields to add.
	 * @param string $page    The name of the page to display on.
	 * @param string $section Optional. The name of the section to display in.
	 */
	public static function add_fields( $fields, $page, $section = 'default' ) {
		foreach ( $fields as $field => $options ) {
			self::add_field( $field, $options, $page, $section );
		}
	}

	/**
	 * Given an array, extract the disired value defined like so: myvar[mykey][0].
	 *
	 * @since 2.0.0
	 *
	 * @uses Settings::extract_value() to handle any array map stuff.
	 *
	 * @param array        $array The array to extract from.
	 * @param array|string $map   The map to follow, in myvar[mykey] or [myvar, mykey] form.
	 *
	 * @return mixed The extracted value.
	 */
	private static function extract_value( array $array, $map ) {
		// Abort if not an array
		if ( ! is_array( $array ) ) return $array;

		// If $map is a string, turn it into an array
		if ( ! is_array( $map ) ) {
			$map = trim( $map, ']' ); // Get rid of last ] so we don't have an empty value at the end
			$map = preg_split( '/[\[\]]+/', $map );
		}

		// Extract the first key to look for
		$key = array_shift( $map );

		// See if it exists
		if ( isset( $array[ $key ] ) ) {
			// See if we need to go deeper
			if ( $map ) {
				return self::extract_value( $array[ $key ], $map );
			}

			return $array[ $key ];
		}

		// Nothing found.
		return null;
	}

	/**
	 * Retrieve the settings value.
	 *
	 * Handles names like option[suboption][] appropraitely.
	 *
	 * @since 2.0.0
	 *
	 * @uses Settings::extract_value() to get the value out of the array based on the map.
	 *
	 * @param string $name The name of the setting to retrieve.
	 */
	private static function get_value( $name ) {
		if ( preg_match( '/([\w-]+)\[([\w-]+)\](.*)/', $name, $matches ) ) {
			// Field is an array map, get the actual key...
			$name = $matches[1];
			// ... and the map to use.
			$map = $matches[2] . $matches[3];
		}

		// Get the value
		$value = Registry::get( $name );

		// Process the value via the map if necessary
		if ( ! empty( $map ) ) {
			$value = self::extract_value( $value, $map );
		}

		return $value;
	}

	/**
	 * Build a field.
	 *
	 * Calls appropriate build_%type%_field method,
	 * along with printing out help text.
	 *
	 * @since 2.0.0
	 *
	 * @uses Settings::get_value() to retrieve a value for the field.
	 *
	 * @param array $args The arguments for the field.
	 *		@option string "name" The field name/ID.
	 *		@option string "type" The field type.
	 *		@option mixed  "data" Optional. data for the field.
	 * 		@option string "help" Optional. Help text.
	 * @param mixed $value Optional. A specifi value to use
	 *                     instead of dynamically retrieving it.
	 */
	public static function build_field( $args, $value = null ) {
		// Get the value for the field if not provided
		if ( is_null( $value ) ) {
			$value = self::get_value( $args['option'] );
		}

		switch ( $args['type'] ) {
			// Not actually fields
			case 'notice':
				$method = "print_notice";
				$cb_args = array( $args['data'] );
				break;

			// Special fields
			case 'select':
			case 'radiolist':
			case 'checklist':
			case 'sync_settings':
				$method = "build_{$args['type']}_field";
				if ( $args['type'] == 'select' ) {
					$cb_args = array( $args['name'], $args['id'], $value, $args['data'] );
				} else {
					$cb_args = array( $args['name'], $value, $args['data'] );
				}
				break;

			// Regular fields
			default:
				$method = "build_input_field";
				$cb_args = array( $args['name'], $args['id'], $value, $args['type'], $args['data'] );
		}

		$html = call_user_func_array( array( get_called_class(), $method ), $cb_args );

		if ( $args['help'] ) {
			// Wrap $html in lable with help text if checkbox or radio
			if ( $args['type'] == 'checkbox' || $args['type'] == 'radio' ) {
				$html = sprintf( '<label>%s %s</label>', $html, $args['help'] );
			} else {
				// Append as description paragraph otherwise
				$html .= sprintf( '<p class="description">%s</p>', $args['help'] );
			}
		}

		// Print the output
		echo $html;
	}

	/**
	 * Build a basic <input> field.
	 *
	 * Also handles <textarea> fields.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name       The name of the field.
	 * @param string $id         The ID of the field.
	 * @param mixed  $value      The value of the field.
	 * @param string $label      Optional. The label for the input.
	 * @param array  $attributes Optional. Custom attributes for the field.
	 *
	 * @return string The HTML of the field.
	 */
	private static function build_input_field( $name, $id, $value, $type, $attributes = array() ) {
		$html = '';

		// Ensure $attributes is an array
		$attributes = (array) $attributes;

		// If a checkbox, include a default=0 dummy field
		if ( $type == 'checkbox' ) {
			$html .= sprintf( '<input type="hidden" name="%s" value="0" />', $name );

			// Also add the checked attribute if $value is true-ish
			if ( $value ) {
				$attributes['checked'] = true;
			}

			// Replace $value with the TRUE (1)
			$value = 1;
		}

		// Build the $attributes list if needed
		$atts = '';
		foreach ( $attributes as $key => $val ) {
			if ( is_bool( $val ) ) {
				$atts .= $val ? " {$key}" : '';
			} else {
				$atts .= " {$key}=\"{$val}\"";
			}
		}

		// Build the input
		if ( $type == 'textarea' ) {
			// or <textarea> as the case may be.
			$html .= sprintf( '<textarea name="%s" id="%s"%s>%s</textarea>', $name, $id, $atts, $value );
		} else {
			$value = esc_attr( $value );
			$html .= sprintf( '<input type="%s" name="%s" id="%s" value="%s"%s />', $type, $name, $id, $value, $atts );
		}

		return $html;
	}

	/**
	 * Build a <select> field.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name       The name of the field.
	 * @param string $id         The ID of the field.
	 * @param mixed  $value   The value of the field.
	 * @param array  $options The options for the field.
	 */
	private static function build_select_field( $name, $id, $value, $options ) {
		$html = '';

		$html .= sprintf( '<select name="%s" id="%s">', $name, $id );
		foreach ( $options as $val => $label ) {
			$selected = $val == $value ? ' selected' : '';
			$html .= sprintf( '<option value="%s"%s>%s</option>', $val, $selected, $label );
		}
		$html .= '</select>';

		return $html;
	}

	/**
	 * Build a list of inputs.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type    The input type.
	 * @param string $name    The name of the field.
	 * @param mixed  $value   The value of the field.
	 * @param array  $options The options for the field.
	 */
	private static function build_inputlist_field( $type, $name, $value, $options ) {
		// Ensure $value is an array
		$value = (array) $value;

		// Checkbox field support array value
		$field_name = $name;
		if ( $type == 'checkbox' ) {
			$field_name .= '[]';
		}

		$inputs = array();
		foreach ( $options as $val => $label ) {
			$checked = in_array( $val, $value ) ? ' checked' : '';
			$inputs[] = sprintf( '<label><input type="%s" name="%s" value="%s"%s /> %s</label>', $type, $field_name, $val, $checked, $label );
		}

		// Build the list, including a fallback "none" input
		$html = '<fieldset class="nl-inputlist">' .
			sprintf( '<input type="hidden" name="%s" value="" />', $name ) .
			implode( '<br /> ', $inputs ) .
		'</fieldset>';

		return $html;
	}

	/**
	 * Build a list of radio inputs.
	 *
	 * @see Settings::build_inputlist_field() for what it all does.
	 */
	private static function build_radiolist_field( $name, $value, $options ) {
		return self::build_inputlist_field( 'radio', $name, $value, $options );
	}

	/**
	 * Build a list of checkbox inputs.
	 *
	 * @see Settings::build_input_list() for what it all does.
	 */
	private static function build_checklist_field( $name, $value, $options ) {
		return self::build_inputlist_field( 'checkbox', $name, $value, $options );
	}

	/**
	 * Build a sync settings interface
	 *
	 * @since 2.0.0
	 *
	 * @uses Documenter::post_field_names() to get the post field names.
	 * @uses Settings::build_checklist_field() to build checklists of fields and terms to enable.
	 *
	 * @param string $name      The name of the field.
	 * @param mixed  $value     The value of the field.
	 * @param string $post_type The post type in question.
	 */
	private static function build_sync_settings_field( $name, $value, $post_type ) {
		// Post Data values
		$post_fields = Documenter::post_field_names();
		$post_fields['post_date']      .= '<sup>1</sup>'; // flag date field for note about modified and gmt versions
		$post_fields['post_status']    .= '<sup>2</sup>'; // flag status field for note about trashing
		$post_fields['post_parent']    .= '<sup>3</sup>'; // flag parent field for note about counterpart translation
		$post_fields['comment_status'] .= '<sup>4</sup>'; // flag comment status field for note about pingback status

		// Taxonomies values
		$post_taxs = get_object_taxonomies( $post_type, 'objects' );
		foreach ( $post_taxs as &$tax ) {
			$tax = $tax->labels->name;
		}

		// Convert TRUE values
		if ( $value['post_fields'] === true ) {
			$value['post_fields'] = array_keys( $post_fields );
		}
		if ( $value['post_terms'] === true ) {
			$value['post_terms'] = array_keys( $post_taxs );
		}
		if ( $value['post_meta'] === true ) {
			$value['post_meta'] = '*';
		}
		?>
		<div class="nl-field-section">
			<button type="button" class="button nl-section-toggle hide-if-no-js" data-alt="<?php _e( 'Close Settings', 'nlingual' ); ?>"><?php _e( 'Open Settings', 'nlingual' ); ?></button>
			<div class="nl-section-content">
				<h4><label  title="<?php _e( 'Check All/None', 'nlingual' ); ?>">
					<?php _e( 'Post Data', 'nlingual' ); ?>
					<input type="checkbox" class="nl-checkall" data-name="<?php echo "{$name}[post_fields]"; ?>" />
				</label></h4>
				<?php echo self::build_checklist_field( "{$name}[post_fields]", $value['post_fields'], $post_fields ); ?>
				<p class="description"><?php _e( 'What post information should be copied?', 'nlingual' ); ?></p>

				<?php if ( $post_taxs ) : ?>
					<h4><label title="<?php _e( 'Check All/None', 'nlingual' ); ?>">
						<?php _e( 'Taxonomies', 'nlingual' ); ?>
						<input type="checkbox" class="nl-checkall" data-name="<?php echo "{$name}[post_terms]"; ?>" />
					</label></h4>
					<?php echo self::build_checklist_field( "{$name}[post_terms]", $value['post_terms'], $post_taxs ); ?>
					<p class="description"><?php _e( 'What terms should be copied?', 'nlingual' ); ?></p>
				<?php endif; ?>

				<h4><label  title="<?php _e( 'Match All/None', 'nlingual' ); ?>">
					<?php _e( 'Meta Data', 'nlingual' ); ?>
					<input type="checkbox" class="nl-matchall" data-name="<?php echo "{$name}[post_meta]"; ?>" />
				</label></h4>
				<?php echo self::build_input_field( "{$name}[post_meta]", "{$name}_post_meta", implode( "\n", (array) $value['post_meta'] ), 'textarea', array(
					'class' => 'widefat',
					'rows' => 5,
				) ); ?>
				<p class="description"><?php _e( 'Which custom fields should be copied?', 'nlingual' ); ?> <br />
					<small><?php _e( 'One per line. Enter an asterisk (*) to match all fields.', 'nlingual' ); ?></small></p>
			</div>
		</div>
		<?php
	}

	/**
	 *
	 *
	 * @since 2.0.0
	 *
	 * @param string $name  The name of the field.
	 * @param mixed  $value The value of the field.
	 * @param string $text  The notice text.
	 */
	private static function print_notice( $text ) {
		printf( '<p><span class="nl-settings-notice">%s</span></p>', $text );
	}
}
