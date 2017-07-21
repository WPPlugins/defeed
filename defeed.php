<?php
/**
 * Plugin Name: de:feed
 * Text Domain: defeed
 * Domain Path: /languages/
 * Version:     0.1.1
 * Description:  Flexible RSS feeds generator.
 * Author: deco.agency
 * Author URI: http://deco.agency
 */

// make sure the plugin does not expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	if ( ! headers_sent() ) {
		if ( function_exists( 'http_response_code' ) ) {
			http_response_code( 403 );
		} else {
			header( 'HTTP/1.1 403 Forbidden', true, 403 );
		}
	}
	exit( 'Hi there! I am a WordPress plugin requiring functions included with WordPress. I am not meant to be addressed directly.' );
}

// Init autoloader
require_once( dirname( __FILE__ ) . '/autoload.php' );

//Check minimum requirements
if ( \Defeed\Utils\Compatibility::check() ) {
	add_action(
		'plugins_loaded',
		array( '\Defeed\Main', 'instance' )
	);
}

register_activation_hook( __FILE__, array( '\Defeed\Main', 'plugin_activation' ) );

