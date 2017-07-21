<?php
/**
 * Autoloader for De:feed
 */

/**
 * Register the autoloader for the Twitter plugin classes.
 *
 * Based off the official PSR-4 autoloader example found here:
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 *
 * @param string $class The fully-qualified class name
 *
 * @return void
 */
spl_autoload_register( function ( $class ) {
	// project-specific namespace prefix
	$prefix = 'Defeed\\';

	// base directory for the namespace prefix
	$base_dir = __DIR__ . '/core/';

	// does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( 0 !== strncmp( $prefix, $class, $len ) ) {
		// no, move to the next registered autoloader
		return;
	}

	// get the relative class name
	$relative_class = substr( $class, $len );

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	//Adjust filename in order to be compatible with WP standards
	$file_name = basename( $file );
	$wp_file_name = 'class-' . strtolower( str_replace( '_', '-', $file_name ) );
	$file = strtolower( str_replace( $file_name, $wp_file_name, $file ) );

	// if the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
});
