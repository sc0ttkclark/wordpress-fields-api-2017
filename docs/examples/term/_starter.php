<?php
/**
 * Register Fields API configuration
 *
 * @param WP_Fields_API $wp_fields
 */
function example_my_term_starter( $wp_fields ) {

	// Object type: Term
	$object_type = 'term';

	// Object subtype: Category
	$object_subtype = 'category'; // @todo Change to any taxonomy name

	$field_id   = 'my-field';
	$field_args = array(
		'object_subtype' => $object_subtype,
		// @todo Add extra args as necessary
	);

	// Add field
	$wp_fields->add_field( $object_type, $field_id, $field_args );

}

add_action( 'fields_register', 'example_my_term_starter' );