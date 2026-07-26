<?php
/**
 * PHPUnit bootstrap: loads the plugin into the WordPress test suite.
 *
 * @package WP-CommentNavi
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	// Where wp-env mounts the WordPress test library.
	$_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test library at {$_tests_dir}." . PHP_EOL;
	echo 'Run the suite through bin/test.sh, or set WP_TESTS_DIR.' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin, and its admin half, which the plugin itself only loads on
 * admin requests.
 *
 * The admin file is required conditionally because this bootstrap has to load
 * both the procedural plugin as it stood before 2.0.0 and the restructured one,
 * so that the same suite can be run against either.
 *
 * @return void
 */
function _commentnavi_manually_load_plugin() {
	require dirname( __DIR__ ) . '/wp-commentnavi.php';

	$admin = dirname( __DIR__ ) . '/includes/class-commentnavi-admin.php';
	if ( file_exists( $admin ) ) {
		require_once $admin;
	}
}
tests_add_filter( 'muplugins_loaded', '_commentnavi_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

// Extends WP_UnitTestCase, so it can only be loaded once the suite has booted.
// phpunit.xml.dist only collects files named test-*.php, so this is not picked
// up as a test case itself.
require_once __DIR__ . '/class-commentnavi-testcase.php';
