<?php if ( array_filter( $rules = nLingual\Registry::get_post_sync_rules( $post_type ) ) ) : ?>
	<hr />
	<strong><?php
	/* translators: %s = The plural name of the post type. */
	_ef( 'The following details will be synchronized between sister %s', 'nlingual', $plural ); ?></strong>
	<ul>
		<?php if ( isset( $rules['post_fields'] ) && $rules['post_fields'] ) : ?>
		<li><strong><?php esc_html_e( 'Post Data', 'nlingual' ); ?></strong>: <?php
			$post_fields = array();
			$post_field_names = nLingual\Documenter::post_field_names();
			// Get the names of each field
			foreach ( $rules['post_fields'] as $post_field ) {
				if ( isset( $post_field_names[ $post_field ] ) ) {
					$post_fields[] = esc_html( $post_field_names[ $post_field ] );
				}
			}
			echo implode( ', ', $post_fields );
		?></li>
		<?php endif; ?>

		<?php if ( isset( $rules['post_terms'] ) && $rules['post_terms'] && array_filter( $rules['post_terms'], 'taxonomy_exists' ) ) : ?>
		<li><strong><?php esc_html_e( 'Taxonomies', 'nlingual' ); ?></strong>: <?php
			$taxonomies = array();
			// Get the names of each field
			foreach ( $rules['post_terms'] as $taxonomy ) {
				$taxonomies[] = esc_html( get_taxonomy( $taxonomy )->labels->name );
			}
			echo implode( ', ', $taxonomies );
		?></li>
		<?php endif; ?>

		<?php if ( isset( $rules['post_meta'] ) && $rules['post_meta'] ) : ?>
		<li><strong><?php esc_html_e( 'Meta Data', 'nlingual' ); ?></strong>: <?php
			if ( in_array( '*', $rules['post_meta'] ) ) {
				_e( 'All custom fields found.', 'nlingual' );
			} else {
				$meta = array();
				foreach ( $rules['post_meta'] as $meta_key ) {
					$meta[] = esc_html( $meta_key );
				}
				echo implode( ', ', $meta );
			}
		?></li>
		<?php endif; ?>
	</ul>
<?php endif; ?>
