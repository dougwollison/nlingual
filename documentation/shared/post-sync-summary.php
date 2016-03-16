<?php if ( array_filter( $rules = nLingual\Registry::get_post_sync_rules( $post_type ) ) ) : ?>
	<hr />
	<strong><?php
	/* Translators: %s = The plural name of the post type. */
	_ef( 'The following details will be synchronized between sister %s', 'nlingual', $plural ); ?></strong>
	<ul>
		<?php if ( $rules['post_fields'] ) : ?>
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

		<?php if ( $rules['post_terms'] ) : ?>
		<li><strong><?php _e( 'Taxonomies', 'nlingual' ); ?></strong>: <?php
			$taxonomies = array();
			// Get the names of each field
			foreach ( $rules['post_terms'] as $taxonomy ) {
				$taxonomies[] = get_taxonomy( $taxonomy )->labels->name;
			}
			echo implode( ', ', $taxonomies );
		?></li>
		<?php endif; ?>

		<?php if ( $rules['post_meta'] ) : ?>
		<li><strong><?php _e( 'Meta Data', 'nlingual' ); ?></strong>: <?php
			if ( in_array( '*', $rules['post_meta'] ) ) {
				_e( 'All custom fields found.', 'nlingual' );
			} else {
				echo implode( ', ', $rules['post_meta'] );
			}
		?></li>
		<?php endif; ?>
	</ul>
<?php endif; ?>
