<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Vandergraaf
 */

define( 'PLUGINS_PATH', '/Users/mark/www/wp2017/sct2017/wp-content/plugins/' );


// $_tests_dir = getenv( 'WP_TESTS_DIR' );
// if ( ! $_tests_dir ) {
	$_tests_dir = '/Users/mark/www/tmp/wordpress-tests-lib';
// }

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {

    $plugins_to_active = array(
		'vandergraaf/vandergraaf.php',
        'vandergraaf-page-generator/vandergraaf-page-generator.php'
    );

    update_option( 'active_plugins', $plugins_to_active );

	require PLUGINS_PATH . 'vandergraaf/vandergraaf.php';
	require PLUGINS_PATH . 'vandergraaf-page-generator/vandergraaf-page-generator.php';

}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

//require dirname( dirname( __FILE__ ) ) . '/page-processor.php';

