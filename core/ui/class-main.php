<?php
namespace Defeed\Ui;

/**
 * Class Main
 * Organize admin page, element in menu and so
 * @package Defeed
 * @since   0.9.0
 */
class Main {

	/**
	 * The hook suffix assigned by add_utility_page()
	 *
	 * @since 0.9.0
	 *
	 * @type string
	 */
	protected $hook_suffix;

	/**
	 * Class instance.
	 */
	private static $_instance = null;

	/**
	 * Get class instance
	 */
	public static function instance() {
		$class = get_called_class();

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new $class();
		}

		return self::$_instance;
	}

	function __construct() {
		add_action( 'admin_menu', array( $this, 'menu_setup' ) );
		add_filter( 'plugin_action_links', array( $this, 'actions_links' ), 10, 2 );
		add_action( 'wp_ajax_add_feed_item', array( $this, 'add_feed_item') );
	}

	function menu_setup() {
		$hook_suffix = add_utility_page(
			__( 'De:feed settings' ), // page <title>
			'De:feed', // brand name. not translated
			'manage_options', // capability needed
			'defeed', // unique menu slug
			array( $this, 'page_setup' ), // pageload callback
			'dashicons-networking' // to be replaced by dashicon
		);

		// hook_suffix may be false if current viewer does not have the manage_options capability
		if ( ! $hook_suffix ) {
			return false;
		}
		$this->hook_suffix = $hook_suffix;

		// Generate page main content at this load-page.. action in order to have access to the HTTP headers
		add_action(
			'load-' . $hook_suffix,
			array( '\Defeed\Ui\Page_Content', 'generate' ),
			99,
			0
		);

		return $hook_suffix;
	}

	public function page_setup() {
		if ( ! isset( $this->hook_suffix ) ) {
			return;
		}

		//Add needed styles to the page, that for current moment actually a bit adjusted style of admin-nav-menus-php page
		wp_enqueue_style(
			'defeed-style',
			\Defeed\Main::get_url() . 'libs/core-nav-menus/assets/style.css',
			array(),
			filemtime( \Defeed\Main::get_path() . '/libs/core-nav-menus/assets/style.css' )
		);

		//Output previously generated content
		Page_Content::output();
	}

	/**
	 * Add link to the plugin's page from plugins list.
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return mixed
	 */
	public static function actions_links( $links, $file ) {
		if ( $file === 'defeed/defeed.php' ) {
			array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php' ) . '?' . http_build_query( array( 'page' => 'defeed' ) ) ) . '">' . __( 'Settings' ) . '</a>' );
		}

		return $links;
	}

	public function add_feed_item() {
		check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );

		if ( ! current_user_can( 'edit_theme_options' ) )
			wp_die( -1 );

		require_once \Defeed\Main::get_path() . '/libs/core-nav-menus/nav-menu-from-wp-include.php';
		require_once \Defeed\Main::get_path() . '/libs/core-nav-menus/nav-menu.php';

		// For performance reasons, we omit some object properties from the checklist.
		// The following is a hacky way to restore them when adding non-custom items.

		$menu_items_data = array();
		foreach ( (array) $_POST['menu-item'] as $menu_item_data ) {
			if (
				! empty( $menu_item_data['menu-item-type'] ) &&
				'custom' != $menu_item_data['menu-item-type'] &&
				! empty( $menu_item_data['menu-item-object-id'] )
			) {
				switch( $menu_item_data['menu-item-type'] ) {
					case 'post_type' :
						$_object = get_post( $menu_item_data['menu-item-object-id'] );
						break;

					case 'taxonomy' :
						$_object = get_term( $menu_item_data['menu-item-object-id'], $menu_item_data['menu-item-object'] );
						break;
				}

				$_menu_items = array_map( 'wp_setup_nav_menu_item', array( $_object ) );
				$_menu_item = reset( $_menu_items );

				// Restore the missing menu item properties
				$menu_item_data['menu-item-description'] = $_menu_item->description;
			}

			$menu_items_data[] = $menu_item_data;
		}

		$item_ids = wp_save_nav_menu_items( 0, $menu_items_data );
		if ( is_wp_error( $item_ids ) )
			wp_die( 0 );

		$menu_items = array();

		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj = defeed_setup_feed_item( $menu_obj );
				$menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items
				$menu_items[] = $menu_obj;
			}
		}

		/** This filter is documented in wp-admin/includes/nav-menu.php */
		$walker_class_name = apply_filters( 'defeed_edit_feed_walker', 'Walker_Nav_Menu_Edit', $_POST['menu'] );

		if ( ! class_exists( $walker_class_name ) )
			wp_die( 0 );

		if ( ! empty( $menu_items ) ) {
			$args = array(
				'after' => '',
				'before' => '',
				'link_after' => '',
				'link_before' => '',
				'walker' => new $walker_class_name,
			);
			echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
		}
		wp_die();
	}

}