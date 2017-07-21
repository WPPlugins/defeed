<?php
namespace Defeed\Utils;

/**
 * Communicate a lack of compatibility between the De:feed and the current site's environment
 *
 * @since 0.9.0
 */
class Compatibility {
	/**
	 * Minimum version of PHP required to run the plugin
	 *
	 * Format: major.minor(.release)
	 *
	 * @since 0.9.0
	 *
	 * @type string
	 */
	const MIN_PHP_VERSION = '5.3.0';

	/**
	 * Check that plugin can work in current environment
	 * @since 0.9.0
	 * @return bool
	 */
	public static function check() {
		if ( version_compare( PHP_VERSION, static::MIN_PHP_VERSION, '<' ) ) {
			// possibly display a notice, trigger error
			add_action( 'admin_init', array( '\Defeed\Utils\Compatibility', 'admin_init' ) );

			return false;
		} else {
			return true;
		}
	}

	/**
	 * Admin init handler
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	public static function admin_init() {
		// no action taken for ajax request
		// extra non-formatted output could break a response format such as XML or JSON
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// only show notice to a user of proper capability
		if ( ! Compatibility::current_user_can_manage_plugins() ) {
			return;
		}

		// display error messages in the site locale
		Compatibility::load_translations();

		// trigger an E_USER_NOTICE for the built-in error handler
		trigger_error( sprintf( __( 'De:feed requires PHP version %s or greater.', 'defeed' ), Compatibility::MIN_PHP_VERSION ) );

		// deactivate the plugin
		Compatibility::deactivate_plugin();

		// display an admin notice
		add_action( 'admin_notices', array( '\Defeed\Utils\Compatibility', 'admin_notice' ) );
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

	/**
	 * Get the plugin path relative to the plugins directory
	 *
	 * Used to identify the plugin in a list of installed and activated plugins
	 *
	 * @since 0.9.0
	 *
	 * @return string Plugin path. e.g. defeed/defeed.php
	 */
	public static function get_plugin_path() {
		return dirname( plugin_basename( __FILE__ ) ) . '/defeed.php';
	}

	/**
	 * Does the current user have the capability to possibly fix the problem?
	 *
	 * @since 0.9.0
	 *
	 * @return bool True if the current user might be able to fix, else false
	 */
	public static function current_user_can_manage_plugins() {
		return current_user_can( is_plugin_active_for_network( Compatibility::get_plugin_path() ) ? 'manage_network_plugins' : 'activate_plugins' );
	}

	/**
	 * Deactivate the plugin due to incompatibility
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	public static function deactivate_plugin() {
		// test for plugin management capability
		if ( ! Compatibility::current_user_can_manage_plugins() ) {
			return;
		}

		// deactivate with deactivation actions (non-silent)
		deactivate_plugins( array( Compatibility::get_plugin_path() ) );

		// remove activate state to prevent a "Plugin activated" notice
		// notice located in wp-admin/plugins.php
		unset( $_GET['activate'] );
	}

	/**
	 * Display an admin notice communicating an incompatibility
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	public static function admin_notice() {
		echo '<div class="notice error is-dismissible">';
		echo '<p>' . esc_html( sprintf( __( 'The De:feed requires PHP version %s or greater.', 'defeed' ), Compatibility::MIN_PHP_VERSION ) ) . '</p>';

		if ( is_plugin_inactive( Compatibility::get_plugin_path() ) ) {
			echo '<p>' . __( 'Plugin <strong>deactivated</strong>.' ) . '</p>';
		}

		echo '</div>';
	}

}