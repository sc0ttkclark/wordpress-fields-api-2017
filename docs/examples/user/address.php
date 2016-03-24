<?php
/**
 * Register Fields API configuration
 *
 * @param WP_Fields_API $wp_fields
 */
function example_my_user_address( $wp_fields ) {

	// Object type: User
	$object_type = 'user';

	// Object subtype: n/a
	$object_subtype = null;

	// Address Line 1
	$field_id   = 'address_1';
	$field_args = array();

	// Add field
	$wp_fields->add_field( $object_type, $field_id, $field_args );

	// Address Line 2
	$field_id   = 'address_2';
	$field_args = array();

	// Add field
	$wp_fields->add_field( $object_type, $field_id, $field_args );

	// City
	$field_id   = 'address_city';
	$field_args = array();

	// Add field
	$wp_fields->add_field( $object_type, $field_id, $field_args );

	// State / Region
	$field_id   = 'address_state';
	$field_args = array();

	// Add field
	$wp_fields->add_field( $object_type, $field_id, $field_args );

	// Zip / Postal Code
	$field_id   = 'address_zip';
	$field_args = array();

	// Add field
	$wp_fields->add_field( $object_type, $field_id, $field_args );

	// Country
	$field_id   = 'address_country';
	$field_args = array();

	// Add field
	$wp_fields->add_field( $object_type, $field_id, $field_args );

}

add_action( 'fields_register', 'example_my_user_address' );