<?php
/**
 * nLingual Compatibilty Tools
 *
 * @package nLingual
 * @subpackage Compatibilty Tools
 * @since 2.0.0
 */

use nLingual\Localizer as Localizer;

// =========================
// ! Localizable Term Conversion
// =========================

/**
 * Print notice offering migration of localizable terms if applicable.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb The database abstraction class instance.
 */
function nl_compatibility_convert_terms_notice() {
	global $wpdb;

	// Get the old separator
	$separator = get_option( 'nlingual-old_separator' );

	// Abort if no separator is present
	if ( ! $separator ) {
		return;
	}

	// Escape % and _ characters in separator
	$separator = str_replace( array( '%', '_' ), array( '\\%', '\\_' ), $separator );

	// Check if ANY term names contain the separator
	$terms = $wpdb->get_results( "SELECT term_id, name FROM $wpdb->terms WHERE name LIKE '%$separator%'" );

	// Abort if no terms are found
	if ( ! $terms ) {
		return;
	}

	// Print the message with the upgrade link
	$message = __( 'Some of the terms you have registered are using the old language splitting method. <a href="%s">Click here</a> to convert them to the new localized format.', NL_TXTDMN );
	$nonce = wp_create_nonce( 'convert-localized-terms' );
	$link = admin_url( 'admin.php?nlingual-action=convert-terms&_nlnonce=' . $nonce );
	$message = sprintf( $message, $link );
	?>
	<div class="notice">
		<p><?php echo $message; ?></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'nl_compatibility_convert_terms_notice' );

/**
 * Proceed to convert applicable terms to the new format.
 *
 * Also enable their respective taxonomies if not already.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb The database abstraction class instance.
 */
function nl_compatibility_convert_terms_process() {
	global $wpdb;

	// Only proceed if action is correct
	if ( ! isset( $_GET['nlingual-action'] ) || $_GET['nlingual-action'] != 'convert-terms' ) {
		return;
	}

	// Fail if nonce does
	if ( ! wp_verify_nonce( $_GET['_nlnonce'], 'convert-localized-terms' ) ) {
		nLingual\cheatin();
	}

	// Get the old separator
	$separator = get_option( 'nlingual-old_separator' );

	// Fail if no separator is present
	if ( ! $separator ) {
		wp_die( _e( 'No language separator found, unable to convert terms.', NL_TXTDMN ) );
	}

	// Escape % and _ characters in separator
	$separator = str_replace( array( '%', '_' ), array( '\\%', '\\_' ), $separator );

	// Get all terms that need to be converted
	$terms = $wpdb->get_results( "
		SELECT t.name, x.description, x.term_taxonomy_id, x.term_id, x.taxonomy
		FROM $wpdb->terms AS t
			LEFT JOIN $wpdb->term_taxonomy AS x ON (t.term_id = x.term_id)
		WHERE t.name LIKE '%$separator%'
			OR x.description LIKE '%$separator%'
	" );

	// Prep the separator for regex use
	$separator = preg_quote( $separator, '/' );

	// Fail if nothing is found
	if ( ! $terms ) {
		wp_die( _e( 'No terms found needing conversion.', NL_TXTDMN ) );
	}

	// Start a list of taxonomies that needed localization
	$taxonomies = array();

	// Get the list of languages, ordered by list order
	$languages = nLingual\Registry::languages();

	// Loop through each term name, convert and store
	foreach ( $terms as $term ) {
		// add taxonomy to list
		$taxonomies[] = $term->taxonomy;

		// Split the name/description
		$name_values = preg_split( "/\s*$separator\s*/", $term->name );
		$description_values = preg_split( "/\s*$separator\s*/", $term->description );

		// Get the first entry as the unlocalized value
		$unlocalized_name = array_shift( $name_values );
		$unlocalized_description = array_shift( $description_values );

		// Update the values in the database
		$wpdb->update( $wpdb->terms, array(
			'name' => $unlocalized_name,
		), array(
			'term_id' => $term->term_id,
		) );
		$wpdb->update( $wpdb->term_taxonomy, array(
			'description' => $unlocalized_description,
		), array(
			'term_taxonomy_id' => $term->term_taxonomy_id,
		) );

		// Store each localized version
		$fields = array( 'name', 'description' );
		foreach ( $fields as $field ) {
			$list = ${ $field . '_values' };

			foreach ( $list as $i => $value ) {
				// Get the corresponding language
				$language = $languages->get( $i + 1, '@' );

				// Break if not found
				if ( ! $language ) {
					break;
				}

				// Save the value
				Localizer::save_string_value( "term_{$term->taxonomy}_{$field}", $language->id, $term->term_id, $value );
			}
		}
	}

	// Now ensure all those taxonomies are registered for localization
	$taxonomies = array_merge( $taxonomies, Registry::get( 'taxonomies' ) );
	$taxonomies = array_unique( $taxonomies );
	update_option( 'nlingual_taxonomies', $taxonomies );

	wp_redirect( 'admin.php?page=nlingual-localizables&notice=nl-terms-converted' );
	exit;
}
add_action( 'admin_init', 'nl_compatibility_convert_terms_process' );

if ( isset( $_GET['notice'] ) && $_GET['notice'] == 'nl-terms-converted' ) :
	/**
	 * Print notice confirming the terms were converted.
	 *
	 * @since 2.0.0
	 */
	function nl_compatibility_convert_terms_success() {
		?>
		<div class="updated">
			<p><?php _e( 'All terms found have been successfully converted, and their taxonomies have been enabled for localization.', NL_TXTDMN ); ?></p>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'nl_compatibility_convert_terms_success' );
endif;