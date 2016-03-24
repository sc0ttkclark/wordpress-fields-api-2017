<?php

/**
 * This is a manager for the Fields API, based on the WP_Customize_Manager.
 *
 * @package    WordPress
 * @subpackage Fields_API
 */
final class WP_Fields_API {

	/**
	 * @var WP_Fields_API
	 */
	private static $instance;

	/**
	 * Registered Fields
	 *
	 * @access protected
	 * @var array
	 */
	protected static $fields = array();

	/**
	 * Field types that may be rendered.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $registered_field_types = array();

	/**
	 * Include the library and bootstrap.
	 *
	 * @constructor
	 * @access public
	 */
	private function __construct() {

		$fields_api_dir = WP_FIELDS_API_DIR . 'implementation/wp-includes/fields-api/';

		// Include API classes
		require_once( $fields_api_dir . 'class-wp-fields-api-field.php' );

		// Register our wp_loaded() first before WP_Customize_Manage::wp_loaded()
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 9 );

	}

	/**
	 * Setup instance for singleton
	 *
	 * @return WP_Fields_API
	 */
	public static function get_instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Trigger the `fields_register` action hook on `wp_loaded`.
	 *
	 * Fields should be registered on this hook.
	 *
	 * @access public
	 */
	public function wp_loaded() {

		/**
		 * Fires when the Fields API is available, and components can be registered.
		 *
		 * @param WP_Fields_API $this The Fields manager object.
		 */
		do_action( 'fields_register', $this );

	}

	/**
	 * Get the registered fields.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Field[]
	 */
	public function get_fields( $object_type = null, $object_subtype = null ) {

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$fields = array();

		if ( null === $object_type ) {
			// Late init
			foreach ( self::$fields as $object_type => $object_subtypes ) {
				foreach ( $object_subtypes as $object_subtype => $fields ) {
					$this->get_fields( $object_type, $object_subtype );
				}
			}

			$fields = self::$fields;
		} elseif ( isset( self::$fields[ $object_type ][ $object_subtype ] ) ) {
			// Late init
			foreach ( self::$fields[ $object_type ][ $object_subtype ] as $id => $field ) {
				// Late init
				self::$fields[ $object_type ][ $object_subtype ][ $id ] = $this->setup_field( $object_type, $id, $object_subtype, $field );
			}

			$fields = self::$fields[ $object_type ][ $object_subtype ];

			// Object subtype inheritance for getting data that covers all Object subtypes
			if ( $primary_object_subtype !== $object_subtype ) {
				$object_fields = $this->get_fields( $object_type, $primary_object_subtype );

				if ( $object_fields ) {
					$fields = array_merge( $fields, $object_fields );
				}
			}
		} elseif ( true === $object_subtype ) {
			// Get all fields

			// Late init
			foreach ( self::$fields[ $object_type ] as $object_subtype => $object_fields ) {
				$object_fields = $this->get_fields( $object_type, $object_subtype );

				if ( $object_fields ) {
					$fields = array_merge( $fields, array_values( $object_fields ) );
				}
			}
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$fields = $this->get_fields( $object_type, $primary_object_subtype );
		}

		return $fields;

	}

	/**
	 * Add a field.
	 *
	 * @access public
	 *
	 * @param string                     $object_type Object type.
	 * @param WP_Fields_API_Field|string $id          Fields API Field object, or ID.
	 * @param string                     $object_subtype Object subtype (for post types and taxonomies).
	 * @param array                      $args        Field arguments; passed to WP_Fields_API_Field
	 *                                                constructor.
	 *
	 * @return bool|WP_Error True on success, or error
	 */
	public function add_field( $object_type, $id, $object_subtype = null, $args = array() ) {

		if ( empty( $id ) && empty( $args ) ) {
			return new WP_Error( '', __( 'ID is required.', 'fields-api' ) );
		}

		if ( is_a( $id, 'WP_Fields_API_Field' ) ) {
			$field = $id;

			$id = $field->id;
		} else {
			// Save for late init
			$field = $args;
		}

		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
		}

		if ( ! isset( self::$fields[ $object_type ] ) ) {
			self::$fields[ $object_type ] = array();
		}

		if ( ! isset( self::$fields[ $object_type ][ $object_subtype ] ) ) {
			self::$fields[ $object_type ][ $object_subtype ] = array();
		}

		if ( isset( self::$fields[ $object_type ][ $object_subtype ][ $id ] ) ) {
			return new WP_Error( '', __( 'Field already exists.', 'fields-api' ) );
		}

		self::$fields[ $object_type ][ $object_subtype ][ $id ] = $field;

		$this->register_meta_integration( $object_type, $id, $field, $object_subtype );

		return true;

	}

	/**
	 * Register meta integration for register_meta and REST API
	 *
	 * @param string                    $object_type Object type
	 * @param string                    $id          Field ID
	 * @param array|WP_Fields_API_Field $field       Field object or options array
	 * @param string|null               $object_subtype Object subtype
	 */
	public function register_meta_integration( $object_type, $id, $field, $object_subtype = null ) {

		// Meta types call register_meta() and register_rest_field() for their fields
		if ( in_array( $object_type, array( 'post', 'term', 'user', 'comment' ) ) && ! $this->get_field_arg( $field, 'internal' ) ) {
			// Set callbacks
			$sanitize_callback = array( $this, 'register_meta_sanitize_callback' );
			$auth_callback = $this->get_field_arg( $field, 'meta_auth_callback' );

			register_meta( $object_type, $id, $sanitize_callback, $auth_callback );

			if ( function_exists( 'register_rest_field' ) && $this->get_field_arg( $field, 'show_in_rest' ) ) {
				$rest_field_args = array(
					'get_callback'    => $this->get_field_arg( $field, 'rest_get_callback' ),
					'update_callback' => $this->get_field_arg( $field, 'rest_update_callback' ),
					'schema'          => $this->get_field_arg( $field, 'rest_schema_callback' ),
					'type'            => $this->get_field_arg( $field, 'rest_field_type' ),
					'description'     => $this->get_field_arg( $field, 'rest_field_description' ),
				);

				register_rest_field( $object_type, $id, $rest_field_args );
			}
		}

	}

	/**
	 * Retrieve a field.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          Field ID.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 *
	 * @return WP_Fields_API_Field|null
	 */
	public function get_field( $object_type, $id, $object_subtype = null ) {

		if ( is_a( $id, 'WP_Fields_API_Field' ) ) {
			return $id;
		}

		$primary_object_subtype = '_' . $object_type;

		// Default to _object_type for internal handling
		if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
			$object_subtype = $primary_object_subtype;
		}

		$field = null;

		if ( isset( self::$fields[ $object_type ][ $object_subtype ][ $id ] ) ) {
			// Late init
			self::$fields[ $object_type ][ $object_subtype ][ $id ] = $this->setup_field( $object_type, $id, $object_subtype, self::$fields[ $object_type ][ $object_subtype ][ $id ] );

			$field = self::$fields[ $object_type ][ $object_subtype ][ $id ];
		} elseif ( $primary_object_subtype !== $object_subtype ) {
			// Object subtype inheritance for getting data that covers all Object subtypes
			$field = $this->get_field( $object_type, $id, $primary_object_subtype );
		}

		return $field;

	}

	/**
	 * Setup the field.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type.
	 * @param string $id          ID of the field.
	 * @param string $object_subtype Object subtype (for post types and taxonomies).
	 * @param array  $args        Field arguments.
	 *
	 * @return WP_Fields_API_Field|null $field The field object.
	 */
	public function setup_field( $object_type, $id, $object_subtype = null, $args = null ) {

		$field = null;

		$field_class = 'WP_Fields_API_Field';

		if ( is_a( $args, $field_class ) ) {
			$field = $args;
		} elseif ( is_array( $args ) ) {
			$args['object_subtype'] = $object_subtype;

			if ( ! empty( $args['type'] ) ) {
				if ( ! empty( self::$registered_field_types[ $args['type'] ] ) ) {
					$field_class = self::$registered_field_types[ $args['type'] ];
				} elseif ( in_array( $args['type'], self::$registered_field_types ) ) {
					$field_class = $args['type'];
				}
			}

			/**
			 * @var $field WP_Fields_API_Field
			 */
			$field = new $field_class( $object_type, $id, $args );
		}

		return $field;

	}

	/**
	 * Remove a field.
	 *
	 * @access public
	 *
	 * @param string $object_type Object type, set true to remove all fields.
	 * @param string $id          Field ID to remove, set true to remove all fields from an object.
	 * @param string $object_subtype Object subtype (for post types and taxonomies), set true to remove to all objects from an object type.
	 */
	public function remove_field( $object_type, $id, $object_subtype = null ) {

		if ( true === $object_type ) {
			// Remove all fields
			self::$fields = array();
		} elseif ( true === $object_subtype ) {
			// Remove all fields for an object type
			if ( isset( self::$fields[ $object_type ] ) ) {
				unset( self::$fields[ $object_type ] );
			}
		} else {
			if ( empty( $object_subtype ) && ! empty( $object_type ) ) {
				$object_subtype = '_' . $object_type; // Default to _object_type for internal handling
			}

			if ( true === $id && null !== $object_subtype ) {
				// Remove all fields for an object type
				if ( isset( self::$fields[ $object_type ][ $object_subtype ] ) ) {
					unset( self::$fields[ $object_type ][ $object_subtype ] );
				}
			} elseif ( isset( self::$fields[ $object_type ][ $object_subtype ][ $id ] ) ) {
				// Remove field from object type and name
				unset( self::$fields[ $object_type ][ $object_subtype ][ $id ] );
			}
		}

	}

	/**
	 * Register a field type.
	 *
	 * @access public
	 *
	 * @see    WP_Fields_API_Field
	 *
	 * @param string $type         Field type ID.
	 * @param string $field_class  Name of a custom field type which is a subclass of WP_Fields_API_Field.
	 */
	public function register_field_type( $type, $field_class = null ) {

		if ( null === $field_class ) {
			$field_class = $type;
		}

		self::$registered_field_types[ $type ] = $field_class;

	}

	/**
	 * Hook into register_meta() sanitize callback and call field
	 *
	 * @param mixed  $meta_value Meta value to sanitize.
	 * @param string $meta_key   Meta key.
	 * @param string $meta_type  Meta type.
	 *
	 * @return mixed
	 */
	public function register_meta_sanitize_callback( $meta_value, $meta_key, $meta_type ) {

		$field = $this->get_field( $meta_type, $meta_key );

		if ( $field ) {
			$meta_value = $field->sanitize( $meta_value );
		}

		return $meta_value;

	}

	/**
	 * Get argument from field array or object
	 *
	 * @param array|object $field
	 * @param string $arg
	 *
	 * @return null|mixed
	 */
	public function get_field_arg( $field, $arg ) {

		$value = null;

		if ( is_array( $field ) && isset( $field[ $arg ] ) ) {
			$value = $field[ $arg ];
		} elseif ( is_object( $field ) && isset( $field->{$arg} ) ) {
			$value = $field->{$arg};
		}

		return $value;

	}

}