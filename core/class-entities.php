<?php
namespace Defeed;

/**
 * Class Entities
 * Register needed for plugins work entities
 * @package Defeed
 * @since 0.9.0
 */
class Entities {

	function __construct() {
		add_action( 'init', array( $this, 'register_feed_item' ) );
		add_action( 'init', array( $this, 'register_feed' ) );
	}

	/**
	 * Registration of feed items, that will handle feed's content
	 */
	function register_feed_item() {
		register_post_type( 'defeed_item', array(
			'labels' => array(
				'name' => __( 'Feed Items', 'defeed' ),
				'singular_name' => __( 'Feed Item', 'defeed' ),
			),
			'public' => false,
			'hierarchical' => false,
			'rewrite' => false,
			'delete_with_user' => false,
			'query_var' => false,
		) );
	}

	/**
	 * Registration of feed entity, that actually is taxonomy
	 */
	function register_feed() {
		register_taxonomy( 'defeed', 'defeed_item', array(
			'public' => false,
			'hierarchical' => false,
			'labels' => array(
				'name' => __( 'Feeds', 'defeed' ),
				'singular_name' => __( 'Feed', 'defeed' ),
			),
			'query_var' => false,
			'rewrite' => false,
			'show_ui' => false,
			'show_in_nav_menus' => false,
		) );
	}

}