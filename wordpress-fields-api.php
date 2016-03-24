<?php
/**
 * Plugin Name: Fields API
 * Plugin URI: https://github.com/sc0ttkclark/wordpress-fields-api
 * Description: WordPress Fields API prototype and proposal for WordPress core
 * Version: 0.1.0 Beta
 * Author: Scott Kingsley Clark
 * Author URI: http://scottkclark.com/
 * License: GPL2+
 * GitHub Plugin URI: https://github.com/sc0ttkclark/wordpress-fields-api
 * GitHub Branch: develop
 * Requires WP: 4.4
 */

/**
 * @package    WordPress
 * @subpackage Fields_API
 *
 * @codeCoverageIgnore
 */

/**
 * The absolute server path to the fields API directory.
 */
define( 'WP_FIELDS_API_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_FIELDS_API_URL', plugin_dir_url( __FILE__ ) );

/**
 * On `plugins_loaded`, create an instance of the Fields API manager class.
 */
function _wp_fields_api_include() {

	// Bail if we're already in WP core (depending on the name used)
	if ( class_exists( 'WP_Fields_API' ) || class_exists( 'Fields_API' ) ) {
		return;
	}

	require_once( WP_FIELDS_API_DIR . 'implementation/wp-includes/fields-api/class-wp-fields-api.php' );

	// Init Fields API class
	$GLOBALS['wp_fields'] = WP_Fields_API::get_instance();

}

add_action( 'plugins_loaded', '_wp_fields_api_include', 8 );