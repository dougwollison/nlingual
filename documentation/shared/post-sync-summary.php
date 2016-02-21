<?php namespace nLingual; ?>
<?php if ( $rules = Registry::get_rules( 'sync', 'post_type', $post_type ) ) : ?>
	<hr />
	<strong><?php _ef( 'The following details will be synchronized between sister %s', $plural ); ?></strong>
	<ul>
		<?php if ( $rules['post_fields'] ) : ?>
		<li><strong><?php _e( 'Post Data' ); ?></strong> - <?php
			$post_fields = array();
			// Get the names of each field
			foreach ( $rules['post_fields'] as $post_field ) {
				$post_fields[] = _x( Documenter::$post_field_names[ $post_field ], 'post field' );
			}
			echo implode( ', ', $post_fields );
		?></li>
		<?php endif; ?>

		<?php if ( $rules['post_terms'] ) : ?>
		<li><strong><?php _e( 'Taxonomies' ); ?></strong> - <?php
			$taxonomies = array();
			// Get the names of each field
			foreach ( $rules['post_terms'] as $taxonomy ) {
				$taxonomies[] = get_taxonomy( $taxonomy )->labels->name;
			}
			echo implode( ', ', $taxonomies );
		?></li>
		<?php endif; ?>

		<?php if ( $rules['post_meta'] ) : ?>
		<li><strong><?php _e( 'Meta Data' ); ?></strong> - <?php
			if ( in_array( '*', $rules['post_meta'] ) ) {
				echo _( 'All custom fields found.' );
			} else {
				echo implode( ', ', $rules['post_meta'] );
			}
		?></li>
		<?php endif; ?>
	</ul>
<?php endif; ?>
