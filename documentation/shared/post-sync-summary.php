<?php if ( array_filter( $rules = nLingual\Registry::get_post_sync_rules( $post_type ) ) ) : ?>
	<hr />
	<strong><?php
	/* translators: %s = The plural name of the post type. */
	_ef( 'The following details will be synchronized between sister %s:', 'nlingual', $plural ); ?></strong>
	<ul>
		<?php if ( isset( $rules['post_fields'] ) && $rules['post_fields'] ) : ?>
		<li><strong><?php _e( 'Post Data', 'nlingual' ); ?></strong>: <?php
			$post_fields = array();
			$post_field_names = nLingual\Documenter::post_field_names();
			// Get the names of each field
			foreach ( $rules['post_fields'] as $post_field ) {
				if ( isset( $post_field_names[ $post_field ] ) ) {
					$post_fields[] = $post_field_names[ $post_field ];
				}
			}
			echo implode( ', ', $post_fields );
		?></li>
		<?php endif; ?>

		<?php if ( isset( $rules['post_terms'] ) && $rules['post_terms'] && array_filter( $rules['post_terms'], 'taxonomy_exists' ) ) : ?>
		<li><strong><?php _e( 'Taxonomies', 'nlingual' ); ?></strong>: <?php
			$taxonomies = array();
			// Get the names of each field
			foreach ( $rules['post_terms'] as $taxonomy ) {
				$taxonomies[] = get_taxonomy( $taxonomy )->labels->name;
			}
			echo implode( ', ', $taxonomies );
		?></li>
		<?php endif; ?>

		<?php if ( isset( $rules['post_meta'] ) && $rules['post_meta'] ) : ?>
		<li><strong><?php _e( 'Meta Data', 'nlingual' ); ?></strong>: <?php
			if ( in_array( '*', $rules['post_meta'] ) ) {
				_e( 'All custom fields found.', 'nlingual' );
			} else {
				echo implode( ', ', $rules['post_meta'] );
			}
		?></li>
		<?php endif; ?>

		<?php
		/**
		 * Filters a list of addition items that are synchronized.
		 *
		 * The dynamic portion of the hook name, `$post_type`,
		 * refers to the current screen's post type.
		 *
		 * @since 2.11.0
		 *
		 * @param array $additional A list of items not covered by the sync rules.
		 */
		$additional = apply_filters( "nlingual_{$post_type}_sync_summary", array() );

		if ( $additional ) : ?>
			<li><strong><?php _e( 'Additional Details', 'nlingual' ); ?></strong>: <?php echo implode( ', ', $additional ); ?></li>
		<?php endif; ?>
	</ul>
<?php endif; ?>
