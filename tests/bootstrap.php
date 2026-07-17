<?php
/**
 * Shared test bootstrap: loads the WordPress core test framework inside wp-env, which in turn loads the drop-in.
 *
 * @package WP_Memcached
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test framework not found; run the suite via: npm test\n" );
	exit( 1 );
}

if ( ! extension_loaded( 'memcached' ) ) {
	fwrite( STDERR, "ext-memcached missing in this container; run the suite via: npm test (provisioning installs it)\n" );
	exit( 1 );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

require_once $_tests_dir . '/includes/functions.php';

require $_tests_dir . '/includes/bootstrap.php';
