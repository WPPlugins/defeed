<?php
namespace Defeed;

/**
 * Class Main
 * Plugin's main flow organization
 * @package Defeed
 * @since 0.9.0
 */
class Main {

	/**
	 * Class instance.
	 */
	private static $_instance = null;

	/**
	 * Get class instance
	 */
	public static function instance() {
		$class = get_called_class();

		if (is_null(self::$_instance)) {
			self::$_instance = new $class();
		}

		return self::$_instance;
	}

	/**
	 * Load translated text to display an error message in the site locale
	 *
	 * @since 0.9.0
	 *
	 * @uses  load_plugin_textdomain()
	 * @return void
	 */
	public static function load_translations() {
		load_plugin_textdomain(
			'defeed',
			false, // deprecated parameter as of WP 2.7
			dirname( plugin_basename( __FILE__ ) ) . '/languages' // path to MO files
		);
	}

	function __construct() {
		$this->load_translations();
		new Ui\Main();
		new Entities();
		new Feed_Generator();
	}

	static function get_url() {
		$path = plugin_dir_url( dirname( dirname( __FILE__ ) ) . '/defeed.php' );
		return $path;
	}

	static function get_path() {
		$url = dirname( dirname( __FILE__ ) );
		return $url;
	}

	static function plugin_activation() {
		Feed_Generator::flush_rewrite();
	}

}
