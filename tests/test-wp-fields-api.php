<?php

/**
 * Class WP_Test_Fields_API_Testcase
 *
 * @uses PHPUnit_Framework_TestCase
 */
class WP_Test_Fields_API_Testcase extends WP_UnitTestCase {

	public $object_type = 'post';
	public $object_subtype = 'my_custom_post_type';

	public function tearDown() {

		// Do main teardown
		parent::tearDown();

		/**
		 * @var $wp_fields WP_Fields_API
		 */
		global $wp_fields;

		// Reset WP Fields instance for testing purposes
		$wp_fields->remove_field( true, true );

	}

	/**
	 * Test Fields API is setup
	 *
	 * @covers WP_Fields_API::__construct
	 */
	public function test_api() {

		/**
		 * @var $wp_fields WP_Fields_API
		 */
		global $wp_fields;

		$this->assertTrue( is_a( $wp_fields, 'WP_Fields_API' ) );

	}

	/**
	 * Test WP_Fields_API::add_field()
	 *
	 * @param string $object_type
	 * @param string $object_subtype
	 */
	public function test_add_field( $object_type = 'post', $object_subtype = null ) {

		/**
		 * @var $wp_fields WP_Fields_API
		 */
		global $wp_fields;

		// @todo Fill out example
		$wp_fields->add_field( $object_type, 'my_test_field', $object_subtype, array(
			'type' => '',
			'format' => '',
			'etc' => '',
		) );

	}

	/**
	 * Test WP_Fields_API::get_fields()
	 */
	public function test_get_fields() {

		/**
		 * @var $wp_fields WP_Fields_API
		 */
		global $wp_fields;

		// Add a field
		$this->test_add_field( $this->object_type, $this->object_subtype );

		// Get fields for object type / name
		$fields = $wp_fields->get_fields( $this->object_type, $this->object_subtype );

		$this->assertEquals( 1, count( $fields ) );

		$this->assertArrayHasKey( 'my_test_field', $fields );

		// Get a field that doesn't exist
		$fields = $wp_fields->get_fields( $this->object_type, 'some_other_post_type' );

		$this->assertEquals( 0, count( $fields ) );

	}

	/**
	 * Test WP_Fields_API::get_field()
	 */
	public function test_get_field() {

		/**
		 * @var $wp_fields WP_Fields_API
		 */
		global $wp_fields;

		// Add a field
		$this->test_add_field( $this->object_type, $this->object_subtype );

		// Field exists for this object type / name
		$field = $wp_fields->get_field( $this->object_type, 'my_test_field', $this->object_subtype );

		$this->assertNotEmpty( $field );

		$this->assertEquals( 'my_test_field', $field->id );

		// Field doesn't exist for this object type / name
		$field = $wp_fields->get_field( $this->object_type, 'my_test_field', 'some_other_post_type' );

		$this->assertEmpty( $field );

		// Field doesn't exist for this object type / name
		$field = $wp_fields->get_field( $this->object_type, 'my_test_field2', $this->object_subtype );

		$this->assertEmpty( $field );

	}

	/**
	 * Test WP_Fields_API::remove_field()
	 */
	public function test_remove_field() {

		/**
		 * @var $wp_fields WP_Fields_API
		 */
		global $wp_fields;

		// Add a field
		$this->test_add_field( $this->object_type, $this->object_subtype );

		// Field exists for this object type / name
		$field = $wp_fields->get_field( $this->object_type, 'my_test_field', $this->object_subtype );

		$this->assertNotEmpty( $field );

		$this->assertEquals( 'my_test_field', $field->id );

		// Remove field
		$wp_fields->remove_field( $this->object_type, 'my_test_field', $this->object_subtype );

		// Field no longer exists for this object type / name
		$field = $wp_fields->get_field( $this->object_type, 'my_test_field', $this->object_subtype );

		$this->assertEmpty( $field );

	}

}